<?php

error_reporting(0);
@ini_set('display_errors', 0);
ob_start();
require_once 'config.php';

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'ajouter') {
    $plat_id  = (int)($_POST['plat_id'] ?? 0);
    $quantite = (int)($_POST['quantite'] ?? 1);

    if ($plat_id <= 0 || $quantite <= 0) {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'Données invalides']);
        exit;
    }
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM plats WHERE id = ? AND disponible = 1");
    $stmt->execute([$plat_id]);
    $plat = $stmt->fetch();
    // Ensure image column exists (graceful fallback if migration pending)
    if ($plat && !isset($plat['image'])) $plat['image'] = '';

    if (!$plat) {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'Plat introuvable']);
        exit;
    }

    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    $ingredients   = trim($_POST['ingredients']   ?? '');
    $instructions  = trim($_POST['instructions']  ?? '');

    if (isset($_SESSION['panier'][$plat_id])) {
        $_SESSION['panier'][$plat_id]['quantite'] += $quantite;
        if ($ingredients)  $_SESSION['panier'][$plat_id]['ingredients']  = $ingredients;
        if ($instructions) $_SESSION['panier'][$plat_id]['instructions'] = $instructions;
    } else {
        $_SESSION['panier'][$plat_id] = [
            'id'           => $plat['id'],
            'nom'          => $plat['nom'],
            'prix'         => $plat['prix'],
            'quantite'     => $quantite,
            'image'        => SITE_URL . '/' . getImagePath($plat['nom'], 'default.jpg', $plat['image'] ?? ''),
            'ingredients'  => $ingredients,
            'instructions' => $instructions,
        ];
    }

    ob_clean();


    echo json_encode([
        'succes'       => true,
        'message'      => 'Ajouté au panier !',
        'nb_articles'  => compterArticles()
    ]);
    exit;
}

if ($action === 'modifier') {
    $plat_id  = (int)($_POST['plat_id'] ?? 0);
    $quantite = (int)($_POST['quantite'] ?? 0);

    if ($quantite <= 0) {
        unset($_SESSION['panier'][$plat_id]);
    } else {
        $_SESSION['panier'][$plat_id]['quantite'] = $quantite;
    }

    ob_clean();


    echo json_encode(['succes' => true, 'total' => calculerTotal(), 'nb_articles' => compterArticles()]);
    exit;
}

if ($action === 'supprimer') {
    $plat_id = (int)($_POST['plat_id'] ?? 0);
    unset($_SESSION['panier'][$plat_id]);
    ob_clean();

    echo json_encode(['succes' => true, 'total' => calculerTotal(), 'nb_articles' => compterArticles()]);
    exit;
}

if ($action === 'vider') {
    $_SESSION['panier'] = [];
    ob_clean();

    echo json_encode(['succes' => true]);
    exit;
}

if ($action === 'commander') {
    if (!estConnecte()) {
        ob_clean();

        echo json_encode(['succes' => false, 'redirect' => 'connexion.php']);
        exit;
    }

    if (empty($_SESSION['panier'])) {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'Votre panier est vide.']);
        exit;
    }

    $adresse   = trim($_POST['adresse'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    if (!$adresse || !$telephone) {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'Adresse et téléphone requis.']);
        exit;
    }

    $db    = getDB();
    $total = calculerTotal();

    $stmt = $db->prepare("INSERT INTO commandes (utilisateur_id, total, adresse_livraison, telephone, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['utilisateur_id'], $total, $adresse, $telephone, $notes]);
    $commande_id = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO commande_details (commande_id, plat_id, quantite, prix_unitaire, ingredients, instructions) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($_SESSION['panier'] as $item) {
        $stmt->execute([
            $commande_id,
            $item['id'],
            $item['quantite'],
            $item['prix'],
            $item['ingredients']  ?? '',
            $item['instructions'] ?? '',
        ]);
    }

    $_SESSION['panier'] = [];

    ob_clean();


    echo json_encode([
        'succes'      => true,
        'commande_id' => $commande_id,
        'message'     => 'Commande passée avec succès !'
    ]);
    exit;
}

if ($action === 'statut') {
    $id   = (int)($_GET['id'] ?? 0);
    $db   = getDB();
    $stmt = $db->prepare("SELECT statut, created_at, updated_at FROM commandes WHERE id = ?");
    $stmt->execute([$id]);
    $cmd  = $stmt->fetch();
    ob_clean();

    echo json_encode($cmd ?: ['erreur' => 'Commande introuvable']);
    exit;
}

if ($action === 'lire' || $action === 'compter') {
    $items = [];
    if (!empty($_SESSION['panier'])) {
        foreach ($_SESSION['panier'] as $item) {
            $items[] = $item;
        }
    }
    ob_clean();

    echo json_encode([
        'succes'      => true,
        'items'       => $items,
        'nb_articles' => compterArticles(),
        'total'       => calculerTotal(),
    ]);
    exit;
}


function compterArticles() {
    if (empty($_SESSION['panier'])) return 0;
    return array_sum(array_column($_SESSION['panier'], 'quantite'));
}

function calculerTotal() {
    if (empty($_SESSION['panier'])) return 0;
    $total = 0;
    foreach ($_SESSION['panier'] as $item) {
        $total += $item['prix'] * $item['quantite'];
    }
    return round($total, 2);
}
?>
