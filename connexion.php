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
    <div class="carte-form">
      <h2>Connexion</h2>
      <p class="sous-titre">Accédez à votre espace personnel</p>

      <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
      <?php endif; ?>

      <form method="POST" action="php/auth.php">
        <input type="hidden" name="action" value="connexion">

        <div class="groupe-champ">
          <label><i class="fa-solid fa-envelope"></i> Email</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-envelope input-icon"></i>
            <input type="email" id="email" name="email" class="champ" placeholder="votre@email.com" required autofocus>
          </div>
        </div>

        <div class="groupe-champ">
          <label><i class="fa-solid fa-lock"></i> Mot de passe</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-lock input-icon"></i>
            <input type="password" id="mot_de_passe" name="mot_de_passe" class="champ" placeholder="••••••••" required>
          </div>
        </div>

        <br>
        <button type="submit" name="connexion" class="btn btn-primaire btn-plein-largeur">
          <i class="fa-solid fa-right-to-bracket"></i> Se connecter
        </button>
      </form>

      <p style="text-align:center;margin-top:1.5rem;color:var(--gris);font-size:0.9rem">
        Pas encore de compte ?
        <a href="inscription.php" style="color:var(--rouge);font-weight:600">Créer un compte</a>
      </p>

      <div
        style="margin-top:1.5rem;padding:1rem;background:var(--gris-clair);border-radius:10px;font-size:0.82rem;color:var(--gris)">
        <strong>Compte admin de test :</strong><br>
        Email : admin@resto.fr<br>
        Mot de passe : password
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
  <script src="js/validation.js" defer></script>
</body>

</html>
