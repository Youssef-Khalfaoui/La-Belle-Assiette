<?php
require_once '../php/config.php';
if (!estAdmin()) rediriger('../connexion.php');

$db      = getDB();
$message = '';
$erreur  = '';

try {
    $cols = $db->query("SHOW COLUMNS FROM plats")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('image', $cols)) {
        $db->exec("ALTER TABLE plats ADD COLUMN image VARCHAR(255) DEFAULT 'default.jpg' AFTER prix");
    }
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_form  = $_POST['action_form'] ?? '';
    $nom          = trim($_POST['nom'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $prix         = (float)($_POST['prix'] ?? 0);
    $cat_id       = (int)($_POST['categorie_id'] ?? 0);
    $disponible   = isset($_POST['disponible']) ? 1 : 0;

    if ($action_form === 'supprimer' || $action_form === 'toggle') {
        $id = (int)$_POST['plat_id'];
        if ($action_form === 'supprimer') {
            // Delete custom uploaded image if any
            $row = $db->prepare("SELECT image FROM plats WHERE id=?");
            $row->execute([$id]);
            $imgData = $row->fetchColumn();
            if ($imgData && str_starts_with($imgData, 'images/uploads/')) {
                @unlink(__DIR__ . '/../' . $imgData);
            }
            $db->prepare("DELETE FROM plats WHERE id=?")->execute([$id]);
            $message = "Plat supprimé.";
        } else {
            $db->prepare("UPDATE plats SET disponible = NOT disponible WHERE id=?")->execute([$id]);
            $message = "Disponibilité mise à jour.";
        }
    } elseif (!$nom || $prix <= 0 || !$cat_id) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif ($action_form === 'ajouter') {
        $stmt = $db->prepare("INSERT INTO plats (categorie_id, nom, description, prix, disponible) VALUES (?,?,?,?,?)");
        $stmt->execute([$cat_id, $nom, $description, $prix, $disponible]);
        $newId = $db->lastInsertId();

        if (!empty($_FILES['image']['name'])) {
            $imgPath = uploadDishImage($_FILES['image'], $newId);
            if ($imgPath) {
                $db->prepare("UPDATE plats SET image=? WHERE id=?")->execute([$imgPath, $newId]);
            }
        }
        $message = "Plat \"$nom\" ajouté avec succès.";

    } elseif ($action_form === 'modifier') {
        $id = (int)$_POST['plat_id'];

        $imgPath = '';
        if (!empty($_FILES['image']['name'])) {
            $imgPath = uploadDishImage($_FILES['image'], $id);
        }

        if ($imgPath) {
            $stmt = $db->prepare("UPDATE plats SET categorie_id=?,nom=?,description=?,prix=?,disponible=?,image=? WHERE id=?");
            $stmt->execute([$cat_id, $nom, $description, $prix, $disponible, $imgPath, $id]);
        } else {
            $stmt = $db->prepare("UPDATE plats SET categorie_id=?,nom=?,description=?,prix=?,disponible=? WHERE id=?");
            $stmt->execute([$cat_id, $nom, $description, $prix, $disponible, $id]);
        }
        $message = "Plat modifié avec succès.";
    }
}

$plats      = $db->query("SELECT p.*, c.nom AS cat_nom FROM plats p JOIN categories c ON c.id=p.categorie_id ORDER BY c.ordre, p.nom")->fetchAll();
$categories = $db->query("SELECT * FROM categories ORDER BY ordre")->fetchAll();

$fa_cat = [
  'Entrées'     => 'fa-leaf',
  'Pizzas'      => 'fa-pizza-slice',
  'Pâtes'       => 'fa-wheat-awn',
  'Grillades'   => 'fa-fire-flame-curved',
  'Burgers'     => 'fa-burger',
  'Charcuterie' => 'fa-drumstick-bite',
  'Protéines'   => 'fa-dumbbell',
  'Desserts'    => 'fa-cake-candles',
  'Boissons'    => 'fa-mug-hot',
];

$pageTitle = 'Gestion des Plats';
require '_head.php';
?>
<style>
  .grille-admin { display:grid;grid-template-columns:1fr 360px;gap:2rem;align-items:start; }
  .groupe-champ { margin-bottom:1rem; }
  .groupe-champ label { display:block;font-size:0.82rem;font-weight:600;color:var(--gris-fonce);margin-bottom:0.4rem; }
  .groupe-champ .champ { width:100%;padding:0.6rem 0.9rem;border:1.5px solid var(--bordure);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:0.88rem;color:var(--noir);background:#fff;transition:border-color 0.2s; }
  .groupe-champ .champ:focus { outline:none;border-color:var(--rouge); }
  .groupe-champ textarea.champ { resize:vertical;min-height:80px; }
  @media (max-width:900px) { .grille-admin { grid-template-columns:1fr; } }

  .img-upload-zone {
    border: 2px dashed var(--bordure);
    border-radius: 12px;
    cursor: pointer;
    overflow: hidden;
    transition: border-color 0.25s, background 0.25s;
    min-height: 130px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gris-clair);
    position: relative;
  }
  .img-upload-zone:hover { border-color: var(--rouge); background: var(--rouge-pale); }
  .img-upload-zone.drag-over { border-color: var(--rouge); background: var(--rouge-pale); transform: scale(1.01); }

  .img-upload-placeholder {
    display: flex; flex-direction: column; align-items: center; gap: 0.35rem;
    padding: 1.5rem; text-align: center; pointer-events: none;
  }
  .img-upload-placeholder i { font-size: 2rem; color: var(--gris); }
  .img-upload-placeholder span { font-size: 0.85rem; color: var(--gris-fonce); font-weight: 500; }
  .img-upload-placeholder small { font-size: 0.75rem; color: var(--gris); }

  .img-upload-preview { width: 100%; position: relative; }
  .img-upload-preview img { width: 100%; height: 160px; object-fit: cover; display: block; }
  .img-remove-btn {
    position: absolute; top: 8px; right: 8px;
    background: rgba(0,0,0,0.55); color: #fff; border: none;
    border-radius: 50%; width: 28px; height: 28px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 0.8rem; transition: background 0.2s;
  }
  .img-remove-btn:hover { background: var(--rouge); }

  .champ-erreur { border-color: #d63c2c !important; background: #fff8f7 !important; }
  .champ-msg-erreur {
    display: block; color: #d63c2c; font-size: 0.76rem;
    font-weight: 600; margin-top: 0.3rem;
    animation: fadeSlideIn 0.2s ease;
  }
  @keyframes fadeSlideIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
</style>
<body>
<div class="admin-layout">
  <?php $activeNav = 'plats'; require '_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-page-title">
      <div class="title-icon"><i class="fa-solid fa-pizza-slice"></i></div>
      <h2>Gestion des Plats</h2>
    </div>
    <p class="admin-page-subtitle">Ajoutez, modifiez ou supprimez les plats du menu.</p>

    <?php if ($message): ?>
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:0.9rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.6rem;color:#166534;font-size:0.88rem;font-weight:600">
      <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    <?php if ($erreur): ?>
    <div style="background:#fff0ee;border:1.5px solid #fcd0cb;border-radius:10px;padding:0.9rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.6rem;color:#d63c2c;font-size:0.88rem;font-weight:600">
      <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erreur) ?>
    </div>
    <?php endif; ?>

    <div class="grille-admin">
      <div class="admin-card">
        <div class="admin-card-header">
          <h3><i class="fa-solid fa-list"></i> <?= count($plats) ?> plats au menu</h3>
        </div>
        <div style="overflow-x:auto">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Plat</th>
                <th>Image</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th style="text-align:center">Dispo</th>
                <th style="text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($plats as $p): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($p['nom']) ?></strong><br>
                  <span style="color:var(--gris);font-size:0.78rem"><?= mb_substr(htmlspecialchars($p['description']),0,50) ?>…</span>
                </td>
                <td>
                  <?php $imgPath = getImagePath($p['nom'], 'default.jpg', $p['image'] ?? ''); ?>
                  <img src="../<?= htmlspecialchars($imgPath) ?>"
                       alt="<?= htmlspecialchars($p['nom']) ?>"
                       style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid var(--bordure)"
                       onerror="this.src='../images/default.jpg'">
                </td>
                <td>
                  <i class="fa-solid <?= $fa_cat[$p['cat_nom']] ?? 'fa-utensils' ?>" style="color:var(--rouge);margin-right:0.35rem"></i>
                  <?= htmlspecialchars($p['cat_nom']) ?>
                </td>
                <td style="font-weight:700;color:var(--rouge)"><?= number_format($p['prix'],2) ?> €</td>
                <td style="text-align:center">
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action_form" value="toggle">
                    <input type="hidden" name="plat_id" value="<?= $p['id'] ?>">
                    <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.3rem;color:<?= $p['disponible'] ? '#2E7D32' : '#C62828' ?>" title="Basculer disponibilité">
                      <i class="fa-solid <?= $p['disponible'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                    </button>
                  </form>
                </td>
                <td style="text-align:center">
                  <div style="display:flex;gap:0.4rem;justify-content:center">
                    <button onclick="remplirFormulaire(<?= htmlspecialchars(json_encode($p)) ?>)"
                            class="btn-action btn-secondary" title="Modifier">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <form method="POST" onsubmit="return confirm('Supprimer ce plat ?')">
                      <input type="hidden" name="action_form" value="supprimer">
                      <input type="hidden" name="plat_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="btn-action btn-delete" title="Supprimer">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="admin-card" style="padding:1.5rem">
        <h3 id="form-titre" style="margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem;font-size:1rem">
          <i id="form-titre-icon" class="fa-solid fa-plus" style="color:var(--rouge)"></i>
          <span id="form-titre-text">Ajouter un plat</span>
        </h3>
        <form method="POST" id="form-plat" enctype="multipart/form-data">
          <input type="hidden" name="action_form" id="action_form" value="ajouter">
          <input type="hidden" name="plat_id" id="plat_id_input" value="">

          <div class="groupe-champ">
            <label>Image du plat</label>
            <div class="img-upload-zone" id="img-upload-zone" onclick="document.getElementById('input-image').click()">
              <div class="img-upload-preview" id="img-preview-wrap" style="display:none">
                <img id="img-preview" src="" alt="Apercu">
                <button type="button" class="img-remove-btn" id="img-remove-btn" title="Supprimer l'image">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
              <div class="img-upload-placeholder" id="img-placeholder">
                <i class="fa-solid fa-camera"></i>
                <span>Cliquer pour choisir une image</span>
                <small>JPG, PNG, WEBP · max 2 Mo</small>
              </div>
            </div>
            <input type="file" name="image" id="input-image" accept="image/jpeg,image/png,image/webp" style="display:none">
          </div>

          <div class="groupe-champ">
            <label>Nom du plat *</label>
            <input type="text" name="nom" id="input-nom" class="champ" required placeholder="Ex: Pizza Margherita">
          </div>
          <div class="groupe-champ">
            <label>Description</label>
            <textarea name="description" id="input-description" class="champ" placeholder="Ingrédients, préparation..."></textarea>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="groupe-champ">
              <label>Prix (€) *</label>
              <input type="number" name="prix" id="input-prix" class="champ" step="0.01" min="0.5" required placeholder="12.50">
            </div>
            <div class="groupe-champ">
              <label>Catégorie *</label>
              <select name="categorie_id" id="input-cat" class="champ">
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="groupe-champ" style="display:flex;align-items:center;gap:0.6rem">
            <input type="checkbox" name="disponible" id="input-dispo" checked style="width:18px;height:18px;cursor:pointer;accent-color:var(--rouge)">
            <label for="input-dispo" style="margin:0;cursor:pointer">Plat disponible</label>
          </div>
          <button type="submit" class="btn-action btn-primary" id="btn-submit" style="width:100%;justify-content:center;padding:0.7rem;font-size:0.9rem;border-radius:10px">
            <i class="fa-solid fa-plus"></i> Ajouter le plat
          </button>
          <button type="button" onclick="resetFormulaire()" class="btn-action btn-secondary" id="btn-annuler" style="width:100%;justify-content:center;padding:0.7rem;font-size:0.9rem;border-radius:10px;margin-top:0.5rem;display:none">
            <i class="fa-solid fa-xmark"></i> Annuler
          </button>
        </form>
      </div>
    </div>
  </main>
</div>

<script>
function remplirFormulaire(plat) {
  document.getElementById('form-titre-icon').className = 'fa-solid fa-pen-to-square';
  document.getElementById('form-titre-text').textContent = 'Modifier : ' + plat.nom;
  document.getElementById('action_form').value       = 'modifier';
  document.getElementById('plat_id_input').value     = plat.id;
  document.getElementById('input-nom').value         = plat.nom;
  document.getElementById('input-description').value = plat.description || '';
  document.getElementById('input-prix').value        = plat.prix;
  document.getElementById('input-cat').value         = plat.categorie_id;
  document.getElementById('input-dispo').checked     = plat.disponible == 1;
  document.getElementById('btn-submit').innerHTML    = '<i class="fa-solid fa-check"></i> Enregistrer les modifications';
  document.getElementById('btn-annuler').style.display = 'flex';

  // Show existing image in upload zone
  const previewWrap = document.getElementById('img-preview-wrap');
  const previewImg  = document.getElementById('img-preview');
  const placeholder = document.getElementById('img-placeholder');
  const imgSrc = plat.image && plat.image !== 'default.jpg'
    ? '../' + plat.image
    : getImgByName(plat.nom);
  if (imgSrc) {
    previewImg.src = imgSrc;
    previewImg.onerror = () => { previewWrap.style.display='none'; placeholder.style.display='flex'; };
    previewWrap.style.display = 'block';
    placeholder.style.display = 'none';
  } else {
    previewWrap.style.display = 'none';
    placeholder.style.display = 'flex';
  }

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function getImgByName(nom) {
  const n = nom.toLowerCase();
  const map = {
    'salade ni': 'plats/salade-nicoise.jpg',
    "soupe": 'plats/soupe-oignon.jpg',
    'bruschetta': 'plats/bruschetta.jpg',
    'quatre saisons': 'plats/pizza-quatre-saisons.jpg',
    'végétarienne': 'plats/pizza-vegetarienne.jpg',
    'reine': 'plats/pizza-reine.jpg',
    'margherita': 'plats/pizza-margherita.jpg',
    'carbonara': 'plats/pasta-carbonara.jpg',
    'bolognaise': 'plats/pasta-bolognaise.jpg',
    'pesto': 'plats/pasta-pesto.jpg',
    'brochette': 'plats/brochette-agneau.jpg',
    'entrecôte': 'plats/entrecote.jpg',
    'entrecote': 'plats/entrecote.jpg',
    'poulet': 'plats/poulet-roti.jpg',
    'classic burger': 'plats/burger-classic.jpg',
    'bacon': 'plats/burger-bacon.jpg',
    'chicken burger': 'plats/burger-chicken.jpg',
    'veggie': 'plats/burger-veggie.jpg',
    'smash': 'plats/burger-smash.jpg',
    'plateau': 'plats/plateau-charcuterie.jpg',
    'assiette mixte': 'plats/assiette-mixte.jpg',
    'chorizo': 'plats/chorizo-poele.jpg',
    'merguez': 'plats/merguez.jpg',
    'toulouse': 'plats/saucisse-toulouse.jpg',
    'fitness': 'plats/assiette-fitness.jpg',
    'bowl': 'plats/bowl-proteine.jpg',
    'bénédicte': 'plats/oeufs-benedicte.jpg',
    'steak': 'plats/steak-hache.jpg',
    'saumon': 'plats/saumon-grille.jpg',
    'wrap': 'plats/wrap-thon-avocat.jpg',
    'tiramisu': 'plats/tiramisu.jpg',
    'brülée': 'plats/creme-brulee.jpg',
    'brulee': 'plats/creme-brulee.jpg',
    'mousse': 'plats/mousse-chocolat.jpg',
    'coca': 'plats/coca-cola.jpg',
    'jus': 'plats/jus-orange.jpg',
    'expresso': 'plats/cafe-expresso.jpg',
    'eau': 'plats/eau-minerale.jpg',
  };
  for (const [key, path] of Object.entries(map)) {
    if (n.includes(key)) return '../images/' + path;
  }
  return '../images/default.jpg';
}
function resetFormulaire() {
  document.getElementById('form-titre-icon').className   = 'fa-solid fa-plus';
  document.getElementById('form-titre-text').textContent = 'Ajouter un plat';
  document.getElementById('action_form').value            = 'ajouter';
  document.getElementById('plat_id_input').value          = '';
  document.getElementById('form-plat').reset();
  document.getElementById('btn-submit').innerHTML         = '<i class="fa-solid fa-plus"></i> Ajouter le plat';
  document.getElementById('btn-annuler').style.display    = 'none';
  resetImageUpload();
}

(function() {
  const zone        = document.getElementById('img-upload-zone');
  const input       = document.getElementById('input-image');
  const previewWrap = document.getElementById('img-preview-wrap');
  const previewImg  = document.getElementById('img-preview');
  const placeholder = document.getElementById('img-placeholder');
  const removeBtn   = document.getElementById('img-remove-btn');

  function showPreview(file) {
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
      alert('Image trop lourde. Taille maximale : 2 Mo.');
      input.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = e => {
      previewImg.src = e.target.result;
      previewWrap.style.display = 'block';
      placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }

  window.resetImageUpload = function() {
    input.value = '';
    previewImg.src = '';
    previewWrap.style.display = 'none';
    placeholder.style.display = 'flex';
  };

  input.addEventListener('change', () => { if (input.files[0]) showPreview(input.files[0]); });

  removeBtn?.addEventListener('click', e => { e.stopPropagation(); window.resetImageUpload(); });

  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
      input.files = e.dataTransfer.files;
      showPreview(file);
    }
  });
})();
</script>
  <script src="../js/validation.js" defer></script>
</body>
</html>
