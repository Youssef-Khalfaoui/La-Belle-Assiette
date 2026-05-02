<?php

define('DB_HOST',     'localhost');
define('DB_USER',     'root');       
define('DB_PASS',     '');           
define('DB_NAME',     'restaurant_db');
define('SITE_NAME',   'La Belle Assiette');
define('SITE_URL',    'http://localhost/restaurant');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            _runMigrations($pdo);
        } catch (PDOException $e) {
            die(json_encode(['erreur' => 'Connexion impossible: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function _runMigrations(PDO $pdo): void {
    $cols = $pdo->query("SHOW COLUMNS FROM commande_details")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('ingredients', $cols)) {
        $pdo->exec("ALTER TABLE commande_details ADD COLUMN ingredients TEXT DEFAULT NULL");
    }
    if (!in_array('instructions', $cols)) {
        $pdo->exec("ALTER TABLE commande_details ADD COLUMN instructions TEXT DEFAULT NULL");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function estConnecte() {
    return isset($_SESSION['utilisateur_id']);
}

function estAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function rediriger($url) {
    header("Location: $url");
    exit;
}


function getImagePath(string $nom, string $default = 'default.jpg', string $dbImage = ''): string
{
    $map = [
        'salade niçoise'      => 'plats/salade-nicoise.jpg',
        'salade nicoise'      => 'plats/salade-nicoise.jpg',
        "soupe à l'oignon"    => 'plats/soupe-oignon.jpg',
        "soupe a l'oignon"    => 'plats/soupe-oignon.jpg',
        'bruschetta'          => 'plats/bruschetta.jpg',

        'quatre saisons'      => 'plats/pizza-quatre-saisons.jpg',
        'pizza végétarienne'  => 'plats/pizza-vegetarienne.jpg',
        'pizza vegetarienne'  => 'plats/pizza-vegetarienne.jpg',
        'pizza reine'         => 'plats/pizza-reine.jpg',
        'margherita'          => 'plats/pizza-margherita.jpg',

        'carbonara'           => 'plats/pasta-carbonara.jpg',
        'bolognaise'          => 'plats/pasta-bolognaise.jpg',
        'pesto genovese'      => 'plats/pasta-pesto.jpg',
        'pesto'               => 'plats/pasta-pesto.jpg',

        'brochette d\'agneau' => 'plats/brochette-agneau.jpg',
        'brochette'           => 'plats/brochette-agneau.jpg',
        'entrecôte'           => 'plats/entrecote.jpg',
        'entrecote'           => 'plats/entrecote.jpg',
        'poulet rôti'         => 'plats/poulet-roti.jpg',
        'poulet roti'         => 'plats/poulet-roti.jpg',
        'poulet'              => 'plats/poulet-roti.jpg',

        'classic burger'      => 'plats/burger-classic.jpg',
        'bacon crispy'        => 'plats/burger-bacon.jpg',
        'chicken burger'      => 'plats/burger-chicken.jpg',
        'veggie burger'       => 'plats/burger-veggie.jpg',
        'smash burger'        => 'plats/burger-smash.jpg',

        'plateau charcuterie' => 'plats/plateau-charcuterie.jpg',
        'assiette mixte'      => 'plats/assiette-mixte.jpg',
        'chorizo'             => 'plats/chorizo-poele.jpg',
        'merguez'             => 'plats/merguez.jpg',
        'saucisse toulouse'   => 'plats/saucisse-toulouse.jpg',
        'toulouse'            => 'plats/saucisse-toulouse.jpg',

        'assiette fitness'    => 'plats/assiette-fitness.jpg',
        'bowl protéiné'       => 'plats/bowl-proteine.jpg',
        'bowl proteine'       => 'plats/bowl-proteine.jpg',
        'oeufs bénédicte'     => 'plats/oeufs-benedicte.jpg',
        'oeufs benedicte'     => 'plats/oeufs-benedicte.jpg',
        'bénédicte'           => 'plats/oeufs-benedicte.jpg',
        'benedicte'           => 'plats/oeufs-benedicte.jpg',
        'steak haché'         => 'plats/steak-hache.jpg',
        'steak hache'         => 'plats/steak-hache.jpg',
        'saumon grillé'       => 'plats/saumon-grille.jpg',
        'saumon grille'       => 'plats/saumon-grille.jpg',
        'saumon'              => 'plats/saumon-grille.jpg',
        'wrap thon'           => 'plats/wrap-thon-avocat.jpg',

        'tiramisu'            => 'plats/tiramisu.jpg',
        'crème brûlée'        => 'plats/creme-brulee.jpg',
        'creme brulee'        => 'plats/creme-brulee.jpg',
        'mousse au chocolat'  => 'plats/mousse-chocolat.jpg',
        'mousse chocolat'     => 'plats/mousse-chocolat.jpg',
        'mousse'              => 'plats/mousse-chocolat.jpg',

        'coca-cola'           => 'plats/coca-cola.jpg',
        'coca cola'           => 'plats/coca-cola.jpg',
        'coca'                => 'plats/coca-cola.jpg',
        'jus d\'orange'       => 'plats/jus-orange.jpg',
        'jus orange'          => 'plats/jus-orange.jpg',
        'café expresso'       => 'plats/cafe-expresso.jpg',
        'cafe expresso'       => 'plats/cafe-expresso.jpg',
        'expresso'            => 'plats/cafe-expresso.jpg',
        'eau minérale'        => 'plats/eau-minerale.jpg',
        'eau minerale'        => 'plats/eau-minerale.jpg',
        'eau'                 => 'plats/eau-minerale.jpg',
    ];

    if ($dbImage && $dbImage !== 'default.jpg') {
        $fullPath = __DIR__ . '/../' . $dbImage;
        if (file_exists($fullPath)) {
            return $dbImage;
        }
    }

    $n = mb_strtolower(trim($nom));
    foreach ($map as $key => $path) {
        if (str_contains($n, $key)) {
            return 'images/' . $path;
        }
    }
    return 'images/' . $default;
}

function uploadDishImage(array $file, string $platId): string {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) return '';
    if ($file['error'] !== UPLOAD_ERR_OK) return '';

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime    = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) return '';
    if ($file['size'] > 3 * 1024 * 1024) return '';

    $ext      = $allowed[$mime];
    $filename = 'plat_' . $platId . '_' . time() . '.' . $ext;
    $dir      = __DIR__ . '/../images/uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    foreach (glob($dir . 'plat_' . $platId . '_*') as $old) {
        @unlink($old);
    }

    $dest = $dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return '';

    return 'images/uploads/' . $filename;
}
?>
