<?php
require_once 'php/config.php';

$db = getDB();

$categories = $db->query("SELECT * FROM categories ORDER BY ordre")->fetchAll();

$plats = $db->query("SELECT p.*, c.nom AS categorie_nom, c.icone AS categorie_icone
                     FROM plats p
                     JOIN categories c ON p.categorie_id = c.id
                     WHERE p.disponible = 1
                     ORDER BY c.ordre, p.nom")->fetchAll();

$nb_panier = 0;
if (!empty($_SESSION['panier'])) {
  $nb_panier = array_sum(array_column($_SESSION['panier'], 'quantite'));
}

$message_succes = $_SESSION['succes'] ?? '';
$message_erreur = $_SESSION['erreur'] ?? '';
unset($_SESSION['succes'], $_SESSION['erreur']);

$fa_cat = [
  'Entrées' => 'fa-leaf',
  'Pizzas' => 'fa-pizza-slice',
  'Pâtes' => 'fa-wheat-awn',
  'Grillades' => 'fa-fire-flame-curved',
  'Burgers' => 'fa-burger',
  'Charcuterie' => 'fa-drumstick-bite',
  'Protéines' => 'fa-dumbbell',
  'Desserts' => 'fa-cake-candles',
  'Boissons' => 'fa-mug-hot',
];
$fa_cat_chip = $fa_cat;


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

<body id="top">

  <nav class="navbar">
    <a href="#top" class="navbar-logo" id="logo-top">
      <i class="fa-solid fa-utensils"></i>
      <span>La Belle Assiette</span>
    </a>
    <ul class="navbar-links">
      <li><a href="index.php" class="actif"><i class="fa-solid fa-house"></i> Menu</a></li>

      <?php if (estConnecte()): ?>
        <?php if (estAdmin()): ?>
          <li><a href="admin/dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
        <?php else: ?>
          <li><a href="mes-commandes.php"><i class="fa-solid fa-box-open"></i> Mes commandes</a></li>
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

  <section class="hero has-bg-img">
    <div class="hero-badge">
      <i class="fa-solid fa-star"></i> Commande en ligne
      <i class="fa-solid fa-truck"></i> Livraison 25–40 min
    </div>

    <h1>Bienvenue chez<br><em>La Belle Assiette</em></h1>
    <p>Des saveurs authentiques, cuisinées avec passion, livrées directement chez vous.</p>

    <div class="hero-actions">
      <a href="#menu" class="btn btn-primaire">
        <i class="fa-solid fa-utensils"></i> Voir le menu
      </a>
      <?php if (!estConnecte()): ?>
        <a href="inscription.php" class="btn btn-secondaire">
          <i class="fa-solid fa-user-plus"></i> Créer un compte
        </a>
      <?php else: ?>
        <a href="mes-commandes.php" class="btn btn-secondaire">
          <i class="fa-solid fa-box-open"></i> Mes commandes
        </a>
      <?php endif; ?>
    </div>

    <div class="hero-features">
      <div class="hero-feature"><i class="fa-solid fa-clock"></i> Livraison 25–40 min</div>
      <div class="hero-feature"><i class="fa-solid fa-star"></i> Note 4.8/5</div>
      <div class="hero-feature"><i class="fa-solid fa-shield-halved"></i> Paiement sécurisé</div>
      <div class="hero-feature"><i class="fa-solid fa-leaf"></i> Ingrédients frais</div>
    </div>
  </section>

  <?php if ($message_succes || $message_erreur): ?>
    <div class="container">
      <?php if ($message_succes): ?>
        <div class="alerte alerte-succes" style="margin-top:1.5rem"><?= htmlspecialchars($message_succes) ?></div>
      <?php endif; ?>
      <?php if ($message_erreur): ?>
        <div class="alerte alerte-erreur" style="margin-top:1.5rem"><?= htmlspecialchars($message_erreur) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="container" id="menu">
    <div class="section-titre">
      <div>
        <h2><i class="fa-solid fa-book-open"></i> Notre Menu</h2>
        <p><?= count($plats) ?> plats disponibles aujourd'hui</p>
      </div>
    </div>

    <div class="filtres-menu">
      <button class="filtre-btn actif" data-cat="tous">
        <i class="fa-solid fa-border-all"></i> Tous
      </button>
      <?php foreach ($categories as $cat):
        $icon = $fa_cat[$cat['nom']] ?? 'fa-utensils';
        ?>
        <button class="filtre-btn" data-cat="<?= $cat['id'] ?>">
          <i class="fa-solid <?= $icon ?>"></i>
          <?= htmlspecialchars($cat['nom']) ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="grille-plats">
      <?php foreach ($plats as $i => $plat):
        $chip_icon = $fa_cat_chip[$plat['categorie_nom']] ?? 'fa-utensils';
        ?>
        <div class="carte-plat" data-cat="<?= $plat['categorie_id'] ?>" style="animation-delay:<?= $i * 0.05 ?>s">
          <div class="carte-plat-image">
            <img src="<?= getImagePath($plat['nom'], 'default.jpg', $plat['image'] ?? '') ?>" alt="<?= htmlspecialchars($plat['nom']) ?>"
              class="carte-plat-img" loading="lazy"
              onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <span class="emoji-fallback" style="display:none"><i class="fa-solid <?= $chip_icon ?>"></i></span>

            <div class="carte-plat-categorie">
              <i class="fa-solid <?= $chip_icon ?>"></i>
              <?= htmlspecialchars($plat['categorie_nom']) ?>
            </div>
          </div>
          <div class="carte-plat-body">
            <h3><?= htmlspecialchars($plat['nom']) ?></h3>
            <p><?= htmlspecialchars($plat['description']) ?></p>
            <div class="carte-plat-footer">
              <span class="prix"><?= number_format($plat['prix'], 2) ?> €</span>
              <button class="btn-ajouter"
                data-id="<?= $plat['id'] ?>"
                data-nom="<?= htmlspecialchars($plat['nom'], ENT_QUOTES) ?>"
                data-prix="<?= $plat['prix'] ?>"
                data-cat="<?= htmlspecialchars($plat['categorie_nom'], ENT_QUOTES) ?>"
                data-desc="<?= htmlspecialchars($plat['description'], ENT_QUOTES) ?>"
                data-image="<?= htmlspecialchars(getImagePath($plat['nom'], 'default.jpg', $plat['image'] ?? ''), ENT_QUOTES) ?>"
                title="Personnaliser et ajouter au panier">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span class="sr-only">Ajouter</span>
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

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

  <div class="ingr-modal-backdrop" id="ingr-modal-backdrop">
    <div class="ingr-modal">

      <!-- Header -->
      <div class="ingr-modal-header">
        <div class="ingr-modal-img-wrap">
          <img id="ingr-modal-img" src="" alt="" style="display:none">
          <div class="ingr-img-fallback" id="ingr-img-fallback">
            <i class="fa-solid fa-utensils"></i>
          </div>
        </div>
        <div class="ingr-modal-meta">
          <span class="ingr-modal-tag" id="ingr-modal-cat"></span>
          <h2 id="ingr-modal-nom"></h2>
          <p class="ingr-modal-prix" id="ingr-modal-prix"></p>
        </div>
        <button class="ingr-modal-close" id="ingr-modal-close" aria-label="Fermer">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="ingr-modal-body">

        <div id="ingr-section">
          <p class="ingr-modal-subtitle">
            <i class="fa-solid fa-seedling"></i> Ingrédients
            <span style="font-weight:400;text-transform:none;font-size:0.75rem;color:var(--gris);letter-spacing:0"> &mdash; décochez pour exclure</span>
          </p>
          <div class="ingr-columns" id="ingr-liste"></div>
        </div>

        <div class="ingr-desc-box" id="ingr-desc-box">
          <p class="ingr-desc-label">
            <i class="fa-solid fa-pen-to-square"></i> Notes &amp; instructions spéciales
          </p>
          <textarea class="ingr-desc-textarea" id="ingr-notes" rows="3"
            placeholder="Ex : sans oignons, cuisson bien cuite, sauce à part&hellip;"></textarea>
        </div>

      </div>

      <div class="ingr-modal-footer">
        <span class="ingr-selected-count" id="ingr-count"></span>
        <div class="ingr-modal-actions">
          <button class="ingr-btn-cancel" id="ingr-btn-cancel">
            <i class="fa-solid fa-xmark"></i> Annuler
          </button>
          <button class="ingr-btn-confirm" id="ingr-btn-confirm">
            <i class="fa-solid fa-cart-plus"></i> Ajouter au panier
          </button>
        </div>
      </div>

    </div>
  </div>

  <footer>
    <p>&copy; <?= date('Y') ?> <strong>La Belle Assiette</strong> — Tous droits réservés</p>
    <div class="footer-meta">
      <span><i class="fa-solid fa-location-dot"></i> Tunis, Tunisie</span>
      <span><i class="fa-solid fa-phone"></i> +216 71 000 000</span>
      <span><i class="fa-solid fa-clock"></i> 11h – 23h tous les jours</span>
    </div>
  </footer>

  <script src="js/scroll-top.js" defer></script>
  <script src="js/app.js"></script>
  <script src="js/validation.js" defer></script>
</body>

</html>
