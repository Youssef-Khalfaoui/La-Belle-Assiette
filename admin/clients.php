<?php
require_once '../php/config.php';
if (!estAdmin()) rediriger('../connexion.php');

$db = getDB();

$clients = $db->query("
    SELECT u.*,
           COUNT(c.id)             AS nb_commandes,
           COALESCE(SUM(c.total),0) AS total_depense
    FROM utilisateurs u
    LEFT JOIN commandes c ON c.utilisateur_id = u.id
    WHERE u.role = 'client'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

$pageTitle = 'Clients';
require '_head.php';
?>
<body>
<div class="admin-layout">
  <?php $activeNav = 'clients'; require '_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-page-title">
      <div class="title-icon"><i class="fa-solid fa-users"></i></div>
      <h2>Clients <span style="color:var(--gris);font-size:1rem;font-family:'DM Sans',sans-serif;font-weight:400">(<?= count($clients) ?>)</span></h2>
    </div>
    <p class="admin-page-subtitle">Liste des clients inscrits et leur historique de commandes.</p>

    <div class="admin-card">
      <?php if (empty($clients)): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><i class="fa-solid fa-users"></i></div>
          <h3>Aucun client inscrit</h3>
        </div>
      <?php else: ?>
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Client</th>
              <th>Contact</th>
              <th style="text-align:center">Commandes</th>
              <th>Total dépensé</th>
              <th>Inscrit le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clients as $c): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:0.75rem">
                  <div style="width:38px;height:38px;background:var(--rouge-pale);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--rouge);flex-shrink:0">
                    <?= mb_strtoupper(mb_substr($c['prenom'],0,1)) ?>
                  </div>
                  <div>
                    <strong><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></strong>
                  </div>
                </div>
              </td>
              <td>
                <span><?= htmlspecialchars($c['email']) ?></span><br>
                <?php if ($c['telephone']): ?>
                  <span style="color:var(--gris);font-size:0.82rem"><i class="fa-solid fa-phone" style="margin-right:0.3rem"></i><?= htmlspecialchars($c['telephone']) ?></span>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <span style="background:var(--rouge-pale);color:var(--rouge);padding:0.3rem 0.8rem;border-radius:50px;font-weight:700;font-size:0.9rem">
                  <?= $c['nb_commandes'] ?>
                </span>
              </td>
              <td style="font-weight:700;color:var(--rouge)"><?= number_format($c['total_depense'],2) ?> €</td>
              <td style="color:var(--gris)"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
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
