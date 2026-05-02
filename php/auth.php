<?php

require_once 'config.php';

$action = $_POST['action'] ?? '';

if ($action === 'inscription') {
    $nom      = trim($_POST['nom'] ?? '');
    $prenom   = trim($_POST['prenom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mdp      = $_POST['mot_de_passe'] ?? '';
    $tel      = trim($_POST['telephone'] ?? '');
    $adresse  = trim($_POST['adresse'] ?? '');

    if (!$nom || !$prenom || !$email || !$mdp) {
        $_SESSION['erreur'] = "Tous les champs obligatoires doivent être remplis.";
        rediriger('../inscription.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['erreur'] = "Adresse e-mail invalide.";
        rediriger('../inscription.php');
    }

    if (strlen($mdp) < 6) {
        $_SESSION['erreur'] = "Le mot de passe doit contenir au moins 6 caractères.";
        rediriger('../inscription.php');
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['erreur'] = "Cet e-mail est déjà utilisé.";
        rediriger('../inscription.php');
    }

    $hash = password_hash($mdp, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, adresse) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $email, $hash, $tel, $adresse]);

    $id = $db->lastInsertId();
    $_SESSION['utilisateur_id'] = $id;
    $_SESSION['nom']            = $prenom . ' ' . $nom;
    $_SESSION['role']           = 'client';
    $_SESSION['succes']         = "Bienvenue, $prenom ! Votre compte a été créé.";
    rediriger('../index.php');
}

if ($action === 'connexion') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (!$email || !$mdp) {
        $_SESSION['erreur'] = "Veuillez remplir tous les champs.";
        rediriger('../connexion.php');
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
        $_SESSION['erreur'] = "E-mail ou mot de passe incorrect.";
        rediriger('../connexion.php');
    }

    $_SESSION['utilisateur_id'] = $user['id'];
    $_SESSION['nom']            = $user['prenom'] . ' ' . $user['nom'];
    $_SESSION['role']           = $user['role'];

    if ($user['role'] === 'admin') {
        rediriger('../admin/dashboard.php');
    } else {
        rediriger('../index.php');
    }
}

if ($action === 'deconnexion' || isset($_GET['logout'])) {
    session_destroy();
    rediriger('../index.php');
}
?>
