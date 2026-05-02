<?php

$activeNav = $activeNav ?? '';
?>
<aside class="admin-sidebar">
<div class="admin-sidebar-inner">
  <div class="admin-logo">
    <div class="admin-logo-icon"><i class="fa-solid fa-utensils"></i></div>
    <div>
      <div class="admin-logo-name">La Belle Assiette</div>
      <div class="admin-logo-sub">Administration</div>
    </div>
  </div>
  <nav class="admin-nav">
    <a href="dashboard.php" class="<?= $activeNav==='dashboard' ? 'actif' : '' ?>">
      <span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span>
      <span class="nav-label">Tableau de bord</span>
    </a>
    <a href="commandes.php" class="<?= $activeNav==='commandes' ? 'actif' : '' ?>">
      <span class="nav-icon"><i class="fa-solid fa-box-open"></i></span>
      <span class="nav-label">Commandes</span>
    </a>
    <a href="plats.php" class="<?= $activeNav==='plats' ? 'actif' : '' ?>">
      <span class="nav-icon"><i class="fa-solid fa-pizza-slice"></i></span>
      <span class="nav-label">Gestion des plats</span>
    </a>
    <a href="clients.php" class="<?= $activeNav==='clients' ? 'actif' : '' ?>">
      <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
      <span class="nav-label">Clients</span>
    </a>
    <div class="nav-divider"></div>
    <a href="../index.php" class="nav-secondary">
      <span class="nav-icon"><i class="fa-solid fa-globe"></i></span>
      <span class="nav-label">Voir le site</span>
    </a>
    <a href="../php/auth.php?logout=1" class="nav-logout">
      <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
      <span class="nav-label">Déconnexion</span>
    </a>
  </nav>
</div>
</aside>
