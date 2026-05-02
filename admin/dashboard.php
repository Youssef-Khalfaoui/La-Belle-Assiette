<?php
require_once '../php/config.php';
if (!estAdmin()) rediriger('../connexion.php');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['cmd_id']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['cmd_id'];
    $db->prepare("DELETE FROM commande_details WHERE commande_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM commandes WHERE id = ?")->execute([$id]);
    rediriger('dashboard.php?deleted=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd_id'], $_POST['statut']) && !isset($_POST['action'])) {
    $stmt = $db->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
    $stmt->execute([$_POST['statut'], (int)$_POST['cmd_id']]);
    rediriger('dashboard.php');
}

$stats = [
  'total_commandes'   => $db->query("SELECT COUNT(*) FROM commandes")->fetchColumn(),
  'commandes_jour'    => $db->query("SELECT COUNT(*) FROM commandes WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
  'revenus_total'     => $db->query("SELECT COALESCE(SUM(total),0) FROM commandes")->fetchColumn(),
  'revenus_jour'      => $db->query("SELECT COALESCE(SUM(total),0) FROM commandes WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
  'total_clients'     => $db->query("SELECT COUNT(*) FROM utilisateurs WHERE role='client'")->fetchColumn(),
  'total_plats'       => $db->query("SELECT COUNT(*) FROM plats WHERE disponible=1")->fetchColumn(),
  'en_attente'        => $db->query("SELECT COUNT(*) FROM commandes WHERE statut='en_attente'")->fetchColumn(),
  'en_preparation'    => $db->query("SELECT COUNT(*) FROM commandes WHERE statut='en_preparation'")->fetchColumn(),
];

$dernieres = $db->query("
    SELECT c.*, u.prenom, u.nom, u.email
    FROM commandes c
    JOIN utilisateurs u ON u.id = c.utilisateur_id
    ORDER BY c.created_at DESC LIMIT 10
")->fetchAll();

$statuts_labels = [
  'en_attente'     => ['label' => 'En attente',    'icon' => 'fa-clock'],
  'confirmee'      => ['label' => 'Confirmée',     'icon' => 'fa-check-double'],
  'en_preparation' => ['label' => 'En préparation','icon' => 'fa-utensils'],
  'prete'          => ['label' => 'Prête',         'icon' => 'fa-box'],
  'livree'         => ['label' => 'Livrée',        'icon' => 'fa-truck-fast'],
];

$pageTitle = 'Tableau de bord';
require '_head.php';
?>
<body>
<div class="admin-layout">
  <?php $activeNav = 'dashboard'; require '_sidebar.php'; ?>

  <main class="admin-main">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1.75rem">
      <div>
        <div class="admin-page-title">
          <div class="title-icon"><i class="fa-solid fa-gauge-high"></i></div>
          <h2>Tableau de bord</h2>
        </div>
        <p class="admin-page-subtitle">Bonjour <strong><?= htmlspecialchars($_SESSION['nom']) ?></strong> — <?= date('d/m/Y') ?></p>
      </div>
      <div style="display:flex;gap:0.6rem;flex-wrap:wrap">
        <?php if ($stats['en_attente'] > 0): ?>
          <a href="commandes.php?statut=en_attente" class="statut statut-en_attente" style="text-decoration:none"><i class="fa-solid fa-clock"></i> <?= $stats['en_attente'] ?> en attente</a>
        <?php endif; ?>
        <?php if ($stats['en_preparation'] > 0): ?>
          <a href="commandes.php?statut=en_preparation" class="statut statut-en_preparation" style="text-decoration:none"><i class="fa-solid fa-fire-burner"></i> <?= $stats['en_preparation'] ?> en préparation</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:0.9rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.6rem;color:#166534;font-size:0.88rem;font-weight:600">
      <i class="fa-solid fa-circle-check"></i> Commande supprimée avec succès.
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:1rem;margin-bottom:2rem">
      <?php
      $cards = [
        ['fa-receipt',        $stats['total_commandes'],                   'Commandes totales'],
        ['fa-calendar-check', $stats['commandes_jour'],                    'Commandes auj.'],
        ['fa-sack-dollar',    number_format($stats['revenus_total'],0).'€','Revenus totaux'],
        ['fa-arrow-trend-up', number_format($stats['revenus_jour'],0).'€', 'Revenus auj.'],
        ['fa-user-group',     $stats['total_clients'],                     'Clients inscrits'],
        ['fa-utensils',       $stats['total_plats'],                       'Plats disponibles'],
      ];
      foreach ($cards as [$icon, $val, $label]): ?>
      <div style="background:var(--blanc);border-radius:var(--rayon);padding:1.4rem;border:1px solid var(--bordure);transition:var(--transition)" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--ombre)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="width:42px;height:42px;background:var(--rouge-pale);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--rouge);font-size:1.1rem;margin-bottom:1rem">
          <i class="fa-solid <?= $icon ?>"></i>
        </div>
        <div style="font-family:'Playfair Display',serif;font-size:1.9rem;font-weight:700;color:var(--rouge);line-height:1"><?= $val ?></div>
        <div style="color:var(--gris);font-size:0.82rem;margin-top:0.35rem"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <h3><i class="fa-solid fa-clipboard-list"></i> Dernières commandes</h3>
        <a href="commandes.php" class="btn-action btn-secondary" style="font-size:0.82rem">Voir toutes <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <?php if (empty($dernieres)): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><i class="fa-solid fa-box-open"></i></div>
          <h3>Aucune commande</h3>
        </div>
      <?php else: ?>
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Client</th>
              <th>Total</th>
              <th>Statut</th>
              <th>Date</th>
              <th style="text-align:center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dernieres as $cmd):
              $s = $statuts_labels[$cmd['statut']] ?? ['label' => $cmd['statut'], 'icon' => 'fa-circle-question'];
              $clientName = htmlspecialchars($cmd['prenom'].' '.$cmd['nom']);
            ?>
            <tr>
              <td style="color:var(--gris);font-weight:600">#<?= $cmd['id'] ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:0.6rem">
                  <div style="width:34px;height:34px;background:var(--rouge-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--rouge);font-size:0.85rem;flex-shrink:0"><?= mb_strtoupper(mb_substr($cmd['prenom'],0,1)) ?></div>
                  <div>
                    <strong><?= $clientName ?></strong><br>
                    <span style="color:var(--gris);font-size:0.8rem"><?= htmlspecialchars($cmd['email']) ?></span>
                  </div>
                </div>
              </td>
              <td style="font-weight:700;color:var(--rouge)"><?= number_format($cmd['total'], 2) ?> €</td>
              <td><span class="statut statut-<?= $cmd['statut'] ?>"><i class="fa-solid <?= $s['icon'] ?>"></i> <?= $s['label'] ?></span></td>
              <td style="color:var(--gris)"><?= date('d/m H:i', strtotime($cmd['created_at'])) ?></td>
              <td style="text-align:center">
                <form method="POST" onsubmit="return confirm('Supprimer la commande #<?= $cmd['id'] ?> ?\nCette action est irréversible.')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="cmd_id" value="<?= $cmd['id'] ?>">
                  <button type="submit" class="btn-action btn-delete">
                    <i class="fa-solid fa-trash-can"></i> Supprimer
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
