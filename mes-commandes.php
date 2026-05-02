<?php
require_once 'php/config.php';
if (!estConnecte())
  rediriger('connexion.php');

$db = getDB();
$uid = $_SESSION['utilisateur_id'];

$commandes = $db->prepare("
    SELECT c.*, COUNT(cd.id) AS nb_plats
    FROM commandes c
    LEFT JOIN commande_details cd ON cd.commande_id = c.id
    WHERE c.utilisateur_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$commandes->execute([$uid]);
$commandes = $commandes->fetchAll();

$statut_fa = [
  'en_attente' => 'fa-hourglass-half',
  'confirmee' => 'fa-circle-check',
  'en_preparation' => 'fa-fire-flame-curved',
  'prete' => 'fa-star',
  'livree' => 'fa-house-circle-check',
  'annulee' => 'fa-circle-xmark',
];
$statut_labels = [
  'en_attente' => 'En attente',
  'confirmee' => 'Confirmée',
  'en_preparation' => 'En préparation',
  'prete' => 'Prête',
  'livree' => 'Livrée',
  'annulee' => 'Annulée',
];

$nb_panier = 0;
if (!empty($_SESSION['panier'])) {
  $nb_panier = array_sum(array_column($_SESSION['panier'], 'quantite'));
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>La Belle Assiette</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="images/logo/favicon.svg">
<link rel="stylesheet" href="css/style.css">
</head>

<body style="display:flex;flex-direction:column;min-height:100vh;">

  <nav class="navbar">
    <a href="index.php" class="navbar-logo">
      <i class="fa-solid fa-utensils"></i>
      <span>La Belle Assiette</span>
    </a>
    <ul class="navbar-links">
      <li><a href="index.php"><i class="fa-solid fa-house"></i> Menu</a></li>

      <?php if (estConnecte()): ?>
        <?php if (estAdmin()): ?>
          <li><a href="admin/dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
        <?php else: ?>
          <li><a href="mes-commandes.php" class="actif"><i class="fa-solid fa-box-open"></i> Mes commandes</a></li>
        <?php endif; ?>
        <li><a href="php/auth.php?logout=1"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a></li>
      <?php else: ?>
        <li><a href="connexion.php"><i class="fa-solid fa-right-to-bracket"></i> Connexion</a></li>
        <li><a href="inscription.php"><i class="fa-solid fa-user-plus"></i> Inscription</a></li>
      <?php endif; ?>

      <li>
        <a href="#" id="btn-panier" class="btn-panier">
          <i class="fa-solid fa-cart-shopping"></i> Panier
          <span class="badge-panier" id="badge-panier" style="display:<?= $nb_panier > 0 ? 'flex' : 'none' ?>">
            <?= $nb_panier ?>
          </span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="container" style="flex:1; max-width:1400px;">
    <div class="section-titre">
      <div>
        <h2><i class="fa-solid fa-box-open"></i> Mes Commandes</h2>
        <p>Bonjour, <strong><?= htmlspecialchars($_SESSION['nom']) ?></strong> — <?= count($commandes) ?> commande(s)
        </p>
      </div>
      <a href="index.php" class="btn btn-primaire">
        <i class="fa-solid fa-plus"></i> Nouvelle commande
      </a>
    </div>

    <?php if (empty($commandes)): ?>
      <div class="cmd-card" style="text-align:center;padding:3rem 2rem;">
        <div style="font-size:3rem;margin-bottom:1rem;color:var(--gris)">
          <i class="fa-solid fa-cart-shopping"></i>
        </div>
        <h3>Aucune commande pour l'instant</h3>
        <p style="color:var(--gris);margin:0.5rem 0 1.5rem">Parcourez notre menu et passez votre première commande !</p>
        <a href="index.php" class="btn btn-primaire">
          <i class="fa-solid fa-utensils"></i> Voir le menu
        </a>
      </div>

    <?php else: ?>
      <div class="cmd-list">
        <?php foreach ($commandes as $cmd):
          $s = $cmd['statut'];
          $label = $statut_labels[$s] ?? $s;
          $icon = $statut_fa[$s] ?? 'fa-circle-question';

          $details = $db->prepare("
            SELECT cd.id, cd.quantite, cd.prix_unitaire, cd.ingredients, cd.instructions, p.nom, p.description
            FROM commande_details cd
            JOIN plats p ON p.id = cd.plat_id
            WHERE cd.commande_id = ?
        ");
          $details->execute([$cmd['id']]);
          $details = $details->fetchAll();
          ?>
          <div class="cmd-card">

            <div class="cmd-header">
              <div class="cmd-header-left">
                <span class="cmd-number">Commande #<?= $cmd['id'] ?></span>
                <span class="cmd-date"><?= date('d/m/Y à H:i', strtotime($cmd['created_at'])) ?></span>
              </div>
              <div class="cmd-header-right">
                <span class="statut statut-<?= $s ?>">
                  <i class="fa-solid <?= $icon ?>"></i> <?= $label ?>
                </span>
                <span class="cmd-total"><?= number_format($cmd['total'], 2) ?> €</span>
              </div>
            </div>

            <div class="cmd-body">

              <div class="cmd-items">
                <?php foreach ($details as $d):
                    // Prefer stored custom ingredients; fall back to the dish description
                    $stored = trim($d['ingredients'] ?? '');
                    if ($stored !== '') {
                        $ingrs = array_filter(array_map('trim', explode(',', $stored)));
                    } else {
                        $parts = array_filter(array_map('trim', explode(',', $d['description'] ?? '')));
                        $ingrs = count($parts) >= 2 ? array_filter($parts, fn($p) => strlen($p) <= 60) : [];
                    }
                ?>
                  <div class="cmd-item">
                    <div class="cmd-item-row">
                      <span class="cmd-item-name">
                        <span class="cmd-qty"><?= $d['quantite'] ?>×</span>
                        <?= htmlspecialchars($d['nom']) ?>
                      </span>
                      <span class="cmd-item-price"><?= number_format($d['prix_unitaire'] * $d['quantite'], 2) ?> €</span>
                    </div>
                    <?php if (!empty($ingrs)): ?>
                      <div class="cmd-ingr">
                        <p class="cmd-ingr-label"><i class="fa-solid fa-seedling"></i> Ingrédients</p>
                        <div class="cmd-ingr-tags">
                          <?php foreach ($ingrs as $ing): ?>
                            <span class="cmd-ingr-tag"><i class="fa-solid fa-check"></i> <?= htmlspecialchars($ing) ?></span>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if ($d['instructions']): ?>
                      <div class="cmd-note">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <?= htmlspecialchars($d['instructions']) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="cmd-meta">
                <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($cmd['adresse_livraison']) ?></span>
                <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($cmd['telephone']) ?></span>
                <?php if ($cmd['notes']): ?>
                  <span><i class="fa-solid fa-note-sticky"></i> <?= htmlspecialchars($cmd['notes']) ?></span>
                <?php endif; ?>
              </div>

              <?php if ($s === 'en_attente'): ?>
                <div class="cmd-actions">
                  <button class="btn-cmd-annuler btn-annuler-cmd"
                    data-cmd-id="<?= $cmd['id'] ?>">
                    <i class="fa-solid fa-xmark"></i> Annuler la commande
                  </button>
                </div>
              <?php endif; ?>

            </div>

            <?php
            $etapes = ['en_attente', 'confirmee', 'en_preparation', 'prete', 'livree'];
            $idx_actuel = array_search($s, $etapes);
            if ($idx_actuel !== false && $s !== 'annulee'):
              $etape_icons = ['fa-hourglass-half', 'fa-circle-check', 'fa-fire-flame-curved', 'fa-star', 'fa-house-circle-check'];
              $prog_labels = ['Reçue', 'Confirmée', 'En cuisine', 'Prête', 'Livrée'];
              ?>
              <div class="cmd-progress">
                <div class="cmd-steps">
                  <?php foreach ($etapes as $i => $e):
                    $fait   = $i < $idx_actuel;
                    $actuel = $i === $idx_actuel;
                    $dot_class  = $actuel ? 'current' : ($fait ? 'done' : 'pending');
                    $lbl_class  = $actuel ? 'current' : ($fait ? 'done' : 'pending');
                    $line_class = ($i < $idx_actuel) ? 'done' : 'pending';
                    ?>
                    <div class="cmd-step">
                      <div class="cmd-step-dot <?= $dot_class ?>">
                        <?php if ($fait || $actuel): ?>
                          <i class="fa-solid <?= $etape_icons[$i] ?>"></i>
                        <?php else: ?>
                          <?= $i + 1 ?>
                        <?php endif; ?>
                      </div>
                      <div class="cmd-step-lbl <?= $lbl_class ?>"><?= $prog_labels[$i] ?></div>
                    </div>
                    <?php if ($i < count($etapes) - 1): ?>
                      <div class="cmd-step-line <?= $line_class ?>"></div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <footer>
    <p>&copy; <?= date('Y') ?> <strong>La Belle Assiette</strong> — Tous droits réservés</p>
    <div class="footer-meta">
      <span><i class="fa-solid fa-location-dot"></i> Tunis, Tunisie</span>
      <span><i class="fa-solid fa-phone"></i> +216 71 000 000</span>
      <span><i class="fa-solid fa-clock"></i> 11h – 23h tous les jours</span>
    </div>
  </footer>

  <div class="overlay" id="overlay"></div>

  <aside class="panier-sidebar" id="panier-sidebar">
    <div class="panier-header">
      <h2><i class="fa-solid fa-cart-shopping"></i> Mon Panier</h2>
      <button class="btn-fermer" id="btn-fermer-panier" aria-label="Fermer"></button>
    </div>
    <div class="panier-items" id="panier-items">
      <div class="panier-vide">
        <div class="panier-vide-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <p>Votre panier est vide</p>
        <small style="color:var(--gris)">Ajoutez des plats pour commencer</small>
      </div>
    </div>
    <div class="panier-footer" id="panier-footer" style="display:none">
      <div class="total-ligne">
        <span><i class="fa-solid fa-tag"></i> Sous-total</span>
        <span id="total-ttc">0.00 €</span>
      </div>
      <div class="total-ligne">
        <span><i class="fa-solid fa-truck"></i> Livraison</span>
        <span id="total-livraison">1.89 €</span>
      </div>
      <div class="total-ligne principal">
        <span>Total</span>
        <span id="total-final">0.00 €</span>
      </div>
      <br>
      <button class="btn btn-primaire btn-plein-largeur" id="btn-commander">
        <i class="fa-solid fa-check-circle"></i> Commander maintenant
      </button>
    </div>
  </aside>

  <div class="modal-overlay" id="modal-overlay">
    <div class="modal">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
        <h2><i class="fa-solid fa-bag-shopping" style="color:var(--rouge);margin-right:.5rem"></i>Finaliser</h2>
        <button class="btn-fermer" id="modal-fermer" aria-label="Fermer"></button>
      </div>
      <?php if (!estConnecte()): ?>
        <div class="alerte alerte-erreur">Vous devez être connecté pour commander.</div>
        <a href="connexion.php" class="btn btn-primaire btn-plein-largeur">
          <i class="fa-solid fa-right-to-bracket"></i> Se connecter
        </a>
      <?php else: ?>
        <form id="form-commande">
          <div class="groupe-champ">
            <label><i class="fa-solid fa-location-dot"></i> Adresse de livraison *</label>
            <div class="input-wrapper">
              <i class="fa-solid fa-location-dot input-icon"></i>
              <input type="text" name="adresse" class="champ" placeholder="Ex : 12 rue de la Paix, Tunis" required>
            </div>
          </div>
          <div class="groupe-champ">
            <label><i class="fa-solid fa-phone"></i> Téléphone *</label>
            <div class="input-wrapper">
              <i class="fa-solid fa-phone input-icon"></i>
              <input type="tel" name="telephone" class="champ" placeholder="Ex : 0612345678" required>
            </div>
          </div>
          <div class="groupe-champ">
            <label><i class="fa-solid fa-note-sticky"></i> Notes pour le livreur</label>
            <textarea name="notes" class="champ" placeholder="Code d'entrée, étage, instructions..."></textarea>
          </div>
          <button type="submit" class="btn btn-primaire btn-plein-largeur">
            <i class="fa-solid fa-check-circle"></i> Confirmer la commande
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div id="modal-annuler-backdrop" class="client-modal-backdrop">
    <div class="client-modal-box-sm">
      <div style="font-size:3rem;color:var(--rouge);margin-bottom:1rem"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h3 style="margin:0 0 0.5rem">Annuler cette commande ?</h3>
      <p style="color:var(--gris);font-size:0.93rem;margin-bottom:1.5rem">Cette action est irréversible. Votre commande sera annulée définitivement.</p>
      <div style="display:flex;gap:0.75rem;justify-content:center">
        <button id="modal-annuler-non" style="background:#fff;color:var(--gris-fonce);border:1px solid var(--bordure);padding:0.6rem 1.4rem;border-radius:50px;cursor:pointer;font-weight:600">Non, garder</button>
        <button id="modal-annuler-oui" style="background:var(--rouge);color:#fff;border:none;padding:0.6rem 1.4rem;border-radius:50px;cursor:pointer;font-weight:600">Oui, annuler</button>
      </div>
    </div>
  </div>

  <div id="toast-mc" style="position:fixed;bottom:2rem;left:50%;transform:translateX(-50%) translateY(100px);background:#333;color:#fff;padding:0.75rem 1.5rem;border-radius:50px;font-size:0.9rem;font-weight:600;z-index:9999;transition:transform 0.3s ease;pointer-events:none;white-space:nowrap"></div>

  <script src="js/scroll-top.js" defer></script>
  <script src="js/app.js"></script>
  <script>
  (function () {
    const BASE = 'php/commande_client.php';

    function toast(msg, type) {
      const el = document.getElementById('toast-mc');
      el.textContent = msg;
      el.style.background = type === 'erreur' ? '#c0392b' : '#27ae60';
      el.style.transform = 'translateX(-50%) translateY(0)';
      clearTimeout(el._t);
      el._t = setTimeout(() => { el.style.transform = 'translateX(-50%) translateY(100px)'; }, 3500);
    }

    const backdropAnnuler = document.getElementById('modal-annuler-backdrop');
    let   pendingAnnulCmdId = null;

    function openAnnuler(cmdId) {
      pendingAnnulCmdId = cmdId;
      window.scrollTo({ top: 0, behavior: 'instant' });
      backdropAnnuler.classList.add('actif');
      document.body.style.overflow = 'hidden';
    }

    function closeAnnuler() {
      backdropAnnuler.classList.remove('actif');
      document.body.style.overflow = '';
      pendingAnnulCmdId = null;
    }

    document.getElementById('modal-annuler-non').addEventListener('click', closeAnnuler);
    backdropAnnuler.addEventListener('click', e => { if (e.target === backdropAnnuler) closeAnnuler(); });

    document.getElementById('modal-annuler-oui').addEventListener('click', async () => {
      const ouiBtn = document.getElementById('modal-annuler-oui');
      ouiBtn.disabled = true;
      ouiBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> En cours…';

      const params = new URLSearchParams({ action: 'annuler', commande_id: pendingAnnulCmdId });
      try {
        const res  = await fetch(BASE, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params.toString()
        });
        const rawText = await res.text();
        const j = rawText.indexOf('{');
        const data = JSON.parse(j >= 0 ? rawText.slice(j) : rawText);
        closeAnnuler();
        if (data.succes) {
          toast(data.message, 'succes');
          setTimeout(() => location.reload(), 1300);
        } else {
          toast(data.message || 'Erreur', 'erreur');
        }
      } catch {
        toast('Erreur réseau', 'erreur');
      } finally {
        ouiBtn.disabled = false;
        ouiBtn.innerHTML = 'Oui, annuler';
      }
    });

    document.querySelectorAll('.btn-annuler-cmd').forEach(btn => {
      btn.addEventListener('click', () => openAnnuler(btn.dataset.cmdId));
    });
  })();
  </script>
</body>

</html>
