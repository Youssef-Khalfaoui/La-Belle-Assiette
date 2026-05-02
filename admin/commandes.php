<?php
require_once '../php/config.php';
if (!estAdmin()) rediriger('../connexion.php');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd_id'], $_POST['statut']) && !isset($_POST['action'])) {
    $stmt = $db->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
    $stmt->execute([$_POST['statut'], (int)$_POST['cmd_id']]);
    $redir = 'commandes.php' . (isset($_GET['statut']) ? '?statut=' . urlencode($_GET['statut']) : '');
    rediriger($redir);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['cmd_id']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['cmd_id'];
    $db->prepare("DELETE FROM commande_details WHERE commande_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM commandes WHERE id = ?")->execute([$id]);
    $redir = 'commandes.php' . (isset($_GET['statut']) ? '?statut=' . urlencode($_GET['statut']) : '');
    rediriger($redir . (strpos($redir,'?') !== false ? '&' : '?') . 'deleted=1');
}

$filtre = $_GET['statut'] ?? 'tous';
$where  = $filtre !== 'tous' ? "WHERE c.statut = " . $db->quote($filtre) : "";

$commandes = $db->query("
    SELECT c.*, u.prenom, u.nom, u.email, u.telephone AS tel_client,
           COUNT(cd.id) AS nb_plats
    FROM commandes c
    JOIN utilisateurs u ON u.id = c.utilisateur_id
    LEFT JOIN commande_details cd ON cd.commande_id = c.id
    $where
    GROUP BY c.id
    ORDER BY c.created_at DESC
")->fetchAll();

$statuts_options = ['en_attente','confirmee','en_preparation','prete','livree'];
$statuts_labels  = [
  'en_attente'     => ['label' => 'En attente',    'icon' => 'fa-hourglass-half', 'css' => 'en_attente'],
  'confirmee'      => ['label' => 'Confirmée',     'icon' => 'fa-circle-check',   'css' => 'confirmee'],
  'en_preparation' => ['label' => 'En préparation','icon' => 'fa-utensils',        'css' => 'en_preparation'],
  'prete'          => ['label' => 'Prête',         'icon' => 'fa-bell',            'css' => 'prete'],
  'livree'         => ['label' => 'Livrée',        'icon' => 'fa-house',           'css' => 'livree'],
];

$pageTitle = 'Commandes';
require '_head.php';
?>
<style>
  .filtres-statut { display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1.5rem; }
  .filtre-s { padding:0.42rem 1rem;border-radius:50px;font-size:0.82rem;font-weight:600;text-decoration:none;border:2px solid var(--bordure);color:var(--gris-fonce);background:var(--blanc);transition:var(--transition); }
  .filtre-s:hover { border-color:var(--rouge);color:var(--rouge); }
  .filtre-s.actif { background:var(--rouge);border-color:var(--rouge);color:var(--blanc); }

  .cmd-card { background:var(--blanc);border-radius:16px;border:1px solid var(--bordure);overflow:hidden;margin-bottom:1rem;transition:box-shadow 0.2s; }
  .cmd-card:hover { box-shadow:var(--ombre-hover); }
  .cmd-header { display:grid;grid-template-columns:60px 1fr 1fr 120px 140px 120px 160px 44px 44px;align-items:center;gap:0.75rem;padding:1rem 1.25rem;border-bottom:1px solid transparent;cursor:pointer;transition:background 0.15s;user-select:none; }
  .cmd-header:hover { background:var(--gris-clair); }
  .cmd-header.expanded { border-bottom-color:var(--bordure);background:var(--gris-clair); }
  .cmd-id { font-weight:700;color:var(--gris);font-size:0.9rem; }
  .cmd-client strong { display:block;font-size:0.9rem; }
  .cmd-client span { font-size:0.78rem;color:var(--gris); }
  .cmd-addr { font-size:0.82rem;color:var(--gris-fonce);overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
  .cmd-total { font-weight:800;color:var(--rouge);font-size:1rem; }
  .cmd-date { font-size:0.8rem;color:var(--gris); }
  .cmd-toggle-btn { width:32px;height:32px;border-radius:50%;background:var(--gris-clair);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--gris-fonce);font-size:0.85rem;transition:all 0.2s; }
  .cmd-toggle-btn.open { background:var(--rouge);color:#fff;transform:rotate(180deg); }

  .cmd-del-btn { width:32px;height:32px;border-radius:50%;background:#fff0ee;border:1.5px solid #fcd0cb;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#d63c2c;font-size:0.82rem;transition:all 0.2s;flex-shrink:0; }
  .cmd-del-btn:hover { background:#d63c2c;color:#fff;border-color:#d63c2c; }

  .cmd-detail { display:none;padding:1.25rem 1.5rem;background:var(--blanc); }
  .cmd-detail.open { display:block;animation:slideDown 0.25s cubic-bezier(0.4,0,0.2,1); }
  @keyframes slideDown { from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)} }
  .cmd-detail-grid { display:grid;grid-template-columns:1fr 320px;gap:1.5rem; }
  .detail-section-title { font-size:0.72rem;text-transform:uppercase;letter-spacing:0.07em;color:var(--gris);font-weight:700;margin-bottom:0.75rem;display:flex;align-items:center;gap:0.4rem; }
  .dish-row { background:var(--gris-clair);border-radius:12px;padding:0.85rem 1rem;margin-bottom:0.6rem;border:1px solid var(--bordure); }
  .dish-row-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:0; }
  .dish-row-header.has-details { margin-bottom:0.65rem; }
  .dish-name { font-weight:600;font-size:0.92rem;display:flex;align-items:center;gap:0.5rem; }
  .dish-qty-badge { background:var(--rouge);color:#fff;border-radius:50px;padding:0.12rem 0.55rem;font-size:0.75rem;font-weight:700; }
  .dish-price { font-weight:700;color:var(--rouge);font-size:0.92rem; }
  .ingr-tags { display:flex;flex-wrap:wrap;gap:0.3rem;margin-bottom:0.5rem; }
  .ingr-tag { background:#fff;border:1px solid var(--bordure);border-radius:50px;padding:0.15rem 0.6rem;font-size:0.75rem;color:var(--gris-fonce); }
  .ingr-tag i { color:var(--rouge);font-size:0.6rem;margin-right:0.25rem; }
  .special-note { background:#fff8f5;border-left:3px solid var(--rouge);border-radius:0 8px 8px 0;padding:0.45rem 0.75rem;font-size:0.82rem;color:var(--gris-fonce);display:flex;align-items:flex-start;gap:0.4rem; }
  .special-note i { color:var(--rouge);margin-top:0.1rem;flex-shrink:0; }
  .cmd-info-panel { display:flex;flex-direction:column;gap:1rem; }
  .info-box { background:var(--gris-clair);border-radius:12px;padding:1rem;border:1px solid var(--bordure); }
  .info-row { display:flex;align-items:flex-start;gap:0.6rem;font-size:0.85rem;color:var(--gris-fonce);margin-bottom:0.5rem; }
  .info-row:last-child { margin-bottom:0; }
  .info-row i { color:var(--rouge);width:14px;flex-shrink:0;margin-top:2px; }
  .status-form { display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap; }
  .status-select { flex:1;padding:0.55rem 0.75rem;border:1.5px solid var(--bordure);border-radius:10px;font-size:0.85rem;font-family:'DM Sans',sans-serif;background:#fff;color:var(--noir);cursor:pointer;transition:border-color 0.2s; }
  .status-select:focus { outline:none;border-color:var(--rouge); }
  .inline-status-form { display:flex;align-items:center; }
  .inline-select-wrap { display:flex;align-items:center;border:1.5px solid var(--bordure);border-radius:12px;overflow:hidden;background:#fff;transition:border-color 0.2s,box-shadow 0.2s; }
  .inline-select-wrap:hover,.inline-select-wrap:focus-within { border-color:var(--rouge);box-shadow:0 0 0 3px rgba(232,68,42,0.1); }
  .inline-select { border:none;outline:none;padding:0.48rem 0.6rem 0.48rem 0.75rem;font-size:0.82rem;font-family:'DM Sans',sans-serif;font-weight:500;color:var(--noir);background:transparent;cursor:pointer;min-width:120px;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%238A7F75'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center;padding-right:24px; }
  .inline-submit { width:34px;height:34px;background:var(--rouge);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:0.82rem;transition:background 0.2s;flex-shrink:0; }
  .inline-submit:hover { background:#c73520; }

  @media (max-width:1200px) {
    .cmd-header { grid-template-columns:50px 1fr 100px 120px 44px 44px; }
    .cmd-header > :nth-child(3),.cmd-header > :nth-child(6) { display:none; }
  }
  @media (max-width:860px) {
    .cmd-detail-grid { grid-template-columns:1fr; }
    .cmd-header { grid-template-columns:50px 1fr 90px 44px 44px; }
    .cmd-header > :nth-child(3),.cmd-header > :nth-child(5),.cmd-header > :nth-child(6) { display:none; }
  }
</style>
<body>
<div class="admin-layout">
  <?php $activeNav = 'commandes'; require '_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-page-title">
      <div class="title-icon"><i class="fa-solid fa-box-open"></i></div>
      <h2>Gestion des Commandes <span style="color:var(--gris);font-size:1rem;font-family:'DM Sans',sans-serif;font-weight:400">(<?= count($commandes) ?>)</span></h2>
    </div>
    <p class="admin-page-subtitle">Cliquez sur une ligne pour voir les détails, ingrédients et instructions.</p>

    <?php if (isset($_GET['deleted'])): ?>
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:0.9rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.6rem;color:#166534;font-size:0.88rem;font-weight:600">
      <i class="fa-solid fa-circle-check"></i> Commande supprimée avec succès.
    </div>
    <?php endif; ?>

    <div class="filtres-statut">
      <a href="commandes.php" class="filtre-s <?= $filtre==='tous'?'actif':'' ?>"><i class="fa-solid fa-utensils"></i> Toutes</a>
      <?php foreach ($statuts_options as $s): ?>
        <a href="commandes.php?statut=<?= $s ?>" class="filtre-s <?= $filtre===$s?'actif':'' ?>">
          <i class="fa-solid <?= $statuts_labels[$s]['icon'] ?>"></i> <?= $statuts_labels[$s]['label'] ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:60px 1fr 1fr 120px 140px 120px 160px 44px 44px;gap:0.75rem;padding:0.5rem 1.25rem;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--gris);font-weight:700">
      <span>#</span><span>Client</span><span>Adresse</span><span>Total</span><span>Statut</span><span>Date</span><span>Changer statut</span><span></span><span></span>
    </div>

    <?php if (empty($commandes)): ?>
      <div class="admin-card">
        <div class="empty-state">
          <div class="empty-state-icon"><i class="fa-solid fa-box-open"></i></div>
          <h3>Aucune commande trouvée</h3>
        </div>
      </div>
    <?php endif; ?>

    <?php foreach ($commandes as $cmd):
      $sl = $statuts_labels[$cmd['statut']];

      $details = $db->prepare("
        SELECT cd.quantite, cd.prix_unitaire, cd.ingredients, cd.instructions, p.nom, p.id AS plat_id
        FROM commande_details cd
        JOIN plats p ON p.id = cd.plat_id
        WHERE cd.commande_id = ?
        ORDER BY cd.id
      ");
      $details->execute([$cmd['id']]);
      $details = $details->fetchAll();

      $hasIngredients = false;
      foreach ($details as $d) {
        if (!empty(trim($d['ingredients'] ?? '')) || !empty(trim($d['instructions'] ?? ''))) {
          $hasIngredients = true; break;
        }
      }
    ?>
    <div class="cmd-card" id="cmd-card-<?= $cmd['id'] ?>">
      <div class="cmd-header" onclick="toggleCmd(<?= $cmd['id'] ?>)" id="cmd-hdr-<?= $cmd['id'] ?>">
        <div class="cmd-id">#<?= $cmd['id'] ?></div>

        <div class="cmd-client">
          <strong><?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?></strong>
          <span><?= htmlspecialchars($cmd['email']) ?></span>
          <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($cmd['telephone']) ?></span>
        </div>

        <div class="cmd-addr" title="<?= htmlspecialchars($cmd['adresse_livraison']) ?>">
          <?= htmlspecialchars(mb_substr($cmd['adresse_livraison'], 0, 50)) ?>
        </div>

        <div class="cmd-total"><?= number_format($cmd['total'], 2) ?> €</div>

        <div>
          <span class="statut statut-<?= $cmd['statut'] ?>"><i class="fa-solid <?= $sl['icon'] ?>"></i> <?= $sl['label'] ?></span>
          <?php if ($hasIngredients): ?>
            <br><span style="font-size:0.7rem;color:var(--rouge);font-weight:600;margin-top:2px;display:inline-block"><i class="fa-solid fa-seedling"></i> Personnalisé</span>
          <?php endif; ?>
        </div>

        <div class="cmd-date"><?= date('d/m/Y', strtotime($cmd['created_at'])) ?><br><?= date('H:i', strtotime($cmd['created_at'])) ?></div>

        <form method="POST" onclick="event.stopPropagation()" class="inline-status-form">
          <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
          <div class="inline-select-wrap">
            <select name="statut" class="inline-select">
              <?php foreach ($statuts_options as $opt): ?>
                <option value="<?= $opt ?>" <?= $cmd['statut']===$opt?'selected':'' ?>><?= $statuts_labels[$opt]['label'] ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="inline-submit" title="Mettre à jour le statut"><i class="fa-solid fa-check"></i></button>
          </div>
        </form>

        <form method="POST" onclick="event.stopPropagation()" onsubmit="return confirm('Supprimer la commande #<?= $cmd['id'] ?> ?\nCette action est irréversible.')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
          <?php if ($filtre !== 'tous'): ?><input type="hidden" name="_statut_filter" value="<?= htmlspecialchars($filtre) ?>"><?php endif; ?>
          <button type="submit" class="cmd-del-btn" title="Supprimer la commande"><i class="fa-solid fa-trash-can"></i></button>
        </form>

        <button class="cmd-toggle-btn" id="cmd-toggle-<?= $cmd['id'] ?>" title="Voir les détails">
          <i class="fa-solid fa-chevron-down"></i>
        </button>
      </div>

      <div class="cmd-detail" id="cmd-detail-<?= $cmd['id'] ?>">
        <div class="cmd-detail-grid">
          <div>
            <p class="detail-section-title"><i class="fa-solid fa-utensils"></i> Plats commandés</p>
            <?php foreach ($details as $d):
              $ingrs = array_filter(array_map('trim', explode(',', $d['ingredients'] ?? '')));
              $hasDetail = !empty($ingrs) || !empty(trim($d['instructions'] ?? ''));
            ?>
              <div class="dish-row">
                <div class="dish-row-header <?= $hasDetail ? 'has-details' : '' ?>">
                  <div class="dish-name">
                    <span class="dish-qty-badge"><?= $d['quantite'] ?>×</span>
                    <?= htmlspecialchars($d['nom']) ?>
                  </div>
                  <div class="dish-price"><?= number_format($d['prix_unitaire'] * $d['quantite'], 2) ?> €</div>
                </div>
                <?php if (!empty($ingrs)): ?>
                  <div style="margin-bottom:<?= !empty(trim($d['instructions'] ?? '')) ? '0.5rem' : '0' ?>">
                    <span class="detail-section-title" style="margin-bottom:0.35rem"><i class="fa-solid fa-seedling"></i> Ingrédients sélectionnés</span>
                    <div class="ingr-tags">
                      <?php foreach ($ingrs as $ing): ?>
                        <span class="ingr-tag"><i class="fa-solid fa-check"></i><?= htmlspecialchars($ing) ?></span>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>
                <?php if (!empty(trim($d['instructions'] ?? ''))): ?>
                  <div class="special-note">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span><?= htmlspecialchars($d['instructions']) ?></span>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="cmd-info-panel">
            <div>
              <p class="detail-section-title"><i class="fa-solid fa-user"></i> Informations client</p>
              <div class="info-box">
                <div class="info-row"><i class="fa-solid fa-user"></i> <span><?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?></span></div>
                <div class="info-row"><i class="fa-solid fa-envelope"></i> <span><?= htmlspecialchars($cmd['email']) ?></span></div>
                <div class="info-row"><i class="fa-solid fa-phone"></i> <span><?= htmlspecialchars($cmd['telephone']) ?></span></div>
                <div class="info-row"><i class="fa-solid fa-location-dot"></i> <span><?= htmlspecialchars($cmd['adresse_livraison']) ?></span></div>
                <?php if ($cmd['notes']): ?>
                  <div class="info-row" style="margin-top:0.5rem;padding-top:0.5rem;border-top:1px solid var(--bordure)">
                    <i class="fa-solid fa-note-sticky"></i>
                    <span style="font-style:italic"><?= htmlspecialchars($cmd['notes']) ?></span>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div>
              <p class="detail-section-title"><i class="fa-solid fa-arrows-rotate"></i> Changer le statut</p>
              <div class="info-box">
                <form method="POST" class="status-form">
                  <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
                  <select name="statut" class="status-select">
                    <?php foreach ($statuts_options as $opt): ?>
                      <option value="<?= $opt ?>" <?= $cmd['statut']===$opt?'selected':'' ?>><?= $statuts_labels[$opt]['label'] ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn-action btn-primary"><i class="fa-solid fa-check"></i> Valider</button>
                </form>
              </div>
            </div>

            <div>
              <p class="detail-section-title"><i class="fa-solid fa-circle-info"></i> Résumé commande</p>
              <div class="info-box">
                <div class="info-row"><i class="fa-solid fa-hashtag"></i> <span>Commande <strong>#<?= $cmd['id'] ?></strong></span></div>
                <div class="info-row"><i class="fa-solid fa-calendar"></i> <span><?= date('d/m/Y à H:i', strtotime($cmd['created_at'])) ?></span></div>
                <div class="info-row"><i class="fa-solid fa-basket-shopping"></i> <span><?= $cmd['nb_plats'] ?> article(s)</span></div>
                <div class="info-row"><i class="fa-solid fa-money-bill"></i> <span><strong style="color:var(--rouge)"><?= number_format($cmd['total'], 2) ?> €</strong></span></div>
              </div>
            </div>

            <form method="POST" onsubmit="return confirm('Supprimer la commande #<?= $cmd['id'] ?> ?\nCette action est irréversible.')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
              <button type="submit" class="btn-action btn-delete" style="width:100%;justify-content:center">
                <i class="fa-solid fa-trash-can"></i> Supprimer cette commande
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </main>
</div>

<script>
function toggleCmd(id) {
  const detail = document.getElementById('cmd-detail-' + id);
  const header = document.getElementById('cmd-hdr-' + id);
  const toggleBtn = document.getElementById('cmd-toggle-' + id);
  const isOpen = detail.classList.contains('open');
  document.querySelectorAll('.cmd-detail.open').forEach(el => {
    el.classList.remove('open');
    const hdrId = el.id.replace('cmd-detail-', '');
    document.getElementById('cmd-hdr-' + hdrId)?.classList.remove('expanded');
    document.getElementById('cmd-toggle-' + hdrId)?.classList.remove('open');
  });
  if (!isOpen) {
    detail.classList.add('open');
    header.classList.add('expanded');
    toggleBtn.classList.add('open');
  }
}
</script>
</body>
</html>
