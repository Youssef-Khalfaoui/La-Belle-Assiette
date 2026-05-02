<?php

error_reporting(0);
@ini_set('display_errors', 0);
ob_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!estConnecte()) {
    ob_clean();

    echo json_encode(['succes' => false, 'message' => 'Non connecté']);
    exit;
}

$uid    = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db     = getDB();

if ($action === 'modifier_detail') {
    $commande_id = (int)($_POST['commande_id'] ?? 0);
    $detail_id   = (int)($_POST['detail_id']   ?? 0);
    $ingredients = trim($_POST['ingredients']   ?? '');
    $instructions= trim($_POST['instructions']  ?? '');

    if (!$commande_id || !$detail_id) {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'Données invalides']);
        exit;
    }

    $stmt = $db->prepare("SELECT statut FROM commandes WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$commande_id, $uid]);
    $cmd = $stmt->fetch();

    if (!$cmd) {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'Commande introuvable']);
        exit;
    }

    if ($cmd['statut'] !== 'en_attente') {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'La commande a déjà été prise en charge, vous ne pouvez plus la modifier.']);
        exit;
    }

    $stmt2 = $db->prepare("SELECT id FROM commande_details WHERE id = ? AND commande_id = ?");
    $stmt2->execute([$detail_id, $commande_id]);
    if (!$stmt2->fetch()) {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'Détail introuvable']);
        exit;
    }

    $stmt3 = $db->prepare("UPDATE commande_details SET ingredients = ?, instructions = ? WHERE id = ?");
    $stmt3->execute([$ingredients, $instructions, $detail_id]);

    ob_clean();


    echo json_encode(['succes' => true, 'message' => 'Commande mise à jour avec succès !']);
    exit;
}

if ($action === 'annuler') {
    $commande_id = (int)($_POST['commande_id'] ?? 0);

    if (!$commande_id) {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'Données invalides']);
        exit;
    }

    $stmt = $db->prepare("SELECT statut FROM commandes WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$commande_id, $uid]);
    $cmd = $stmt->fetch();

    if (!$cmd) {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'Commande introuvable']);
        exit;
    }

    if ($cmd['statut'] !== 'en_attente') {
        ob_clean();

        echo json_encode(['succes' => false, 'message' => 'La commande a déjà été prise en charge, vous ne pouvez plus l\'annuler.']);
        exit;
    }

    $db->prepare("DELETE FROM commande_details WHERE commande_id = ?")->execute([$commande_id]);
    $db->prepare("DELETE FROM commandes WHERE id = ?")->execute([$commande_id]);

    ob_clean();
    echo json_encode(['succes' => true, 'message' => 'Commande supprimée avec succès.']);
    exit;
}

ob_clean();


echo json_encode(['succes' => false, 'message' => 'Action inconnue']);
