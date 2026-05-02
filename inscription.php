<?php
require_once 'php/config.php';
if (estConnecte())
  rediriger('index.php');

$erreur = $_SESSION['erreur'] ?? '';
unset($_SESSION['erreur']);

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

<body>

  <nav class="navbar">
    <a href="index.php" class="navbar-logo">
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

  <div class="container">
    <div class="carte-form" style="max-width:520px">
      <h2>Créer un compte</h2>
      <p class="sous-titre">Rejoignez La Belle Assiette</p>

      <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
      <?php endif; ?>

      <form method="POST" action="php/auth.php">
        <input type="hidden" name="action" value="inscription">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div class="groupe-champ">
            <label><i class="fa-solid fa-user"></i> Prénom *</label>
            <div class="input-wrapper">
              <i class="fa-solid fa-user input-icon"></i>
              <input type="text" name="prenom" class="champ" placeholder="Prénom" required>
            </div>
          </div>
          <div class="groupe-champ">
            <label><i class="fa-solid fa-user"></i> Nom *</label>
            <div class="input-wrapper">
              <i class="fa-solid fa-user input-icon"></i>
              <input type="text" name="nom" class="champ" placeholder="Nom" required>
            </div>
          </div>
        </div>

        <div class="groupe-champ">
          <label><i class="fa-solid fa-envelope"></i> Adresse e-mail *</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-envelope input-icon"></i>
            <input type="email" name="email" class="champ" placeholder="votre@email.com" required>
          </div>
        </div>

        <div class="groupe-champ">
          <label><i class="fa-solid fa-lock"></i> Mot de passe * <small style="font-weight:400">(min. 6
              caractères)</small></label>
          <div class="input-wrapper">
            <i class="fa-solid fa-lock input-icon"></i>
            <input type="password" name="mot_de_passe" class="champ" placeholder="••••••••" required minlength="6">
          </div>
        </div>

        <div class="groupe-champ">
          <label><i class="fa-solid fa-phone"></i> Téléphone</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-phone input-icon"></i>
            <input type="tel" name="telephone" class="champ" placeholder="+216 XX XXX XXX">
          </div>
        </div>

        <div class="groupe-champ">
          <label><i class="fa-solid fa-location-dot"></i> Adresse de livraison</label>
          <textarea name="adresse" class="champ" placeholder="Votre adresse complète..."></textarea>
        </div>

        <button type="submit" class="btn btn-primaire btn-plein-largeur">
          <i class="fa-solid fa-user-plus"></i> Créer mon compte
        </button>
      </form>

      <p style="text-align:center;margin-top:1.5rem;color:var(--gris);font-size:0.9rem">
        Déjà un compte ?
        <a href="connexion.php" style="color:var(--rouge);font-weight:600">Se connecter</a>
      </p>
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
  <script src="js/validation.js" defer></script>
</body>

</html>
