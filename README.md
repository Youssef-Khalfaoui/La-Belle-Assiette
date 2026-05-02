# 🍕 La Belle Assiette — Restaurant + Système de Commande

Application web complète de restaurant avec commande en ligne.
**Stack:** HTML · CSS · JavaScript · PHP · MySQL

---

## 📁 Structure du Projet

```
restaurant/
├── index.php               ← Page d'accueil (menu + panier)
├── connexion.php           ← Page de connexion
├── inscription.php         ← Page d'inscription
├── mes-commandes.php       ← Historique des commandes (client)
│
├── php/
│   ├── config.php          ← Configuration DB + helpers
│   ├── auth.php            ← Inscription / Connexion / Déconnexion
│   └── panier.php          ← API panier + commandes (JSON)
│
├── css/
│   └── style.css           ← Feuille de style principale
│
├── js/
│   └── app.js              ← JavaScript (panier, filtres, toasts)
│
├── admin/
│   ├── dashboard.php       ← Tableau de bord admin
│   ├── commandes.php       ← Gestion des commandes
│   ├── plats.php           ← Ajouter / modifier / supprimer des plats
│   └── clients.php         ← Liste des clients inscrits
│
└── database.sql            ← Script SQL (création + données initiales)
```

---

## ⚙️ Installation

### 1. Prérequis
- **XAMPP** (ou WAMP / LAMP) avec Apache + PHP 8.0+ + MySQL
- Navigateur web moderne

### 2. Copier les fichiers
Copiez le dossier `restaurant/` dans :
```
C:\xampp\htdocs\restaurant\        (Windows)
/opt/lampp/htdocs/restaurant/      (Linux)
/Applications/XAMPP/htdocs/restaurant/  (Mac)
```

### 3. Créer la base de données
1. Démarrez **XAMPP** (Apache + MySQL)
2. Ouvrez **phpMyAdmin** → http://localhost/phpmyadmin
3. Cliquez sur **"Importer"**
4. Choisissez le fichier `database.sql`
5. Cliquez **"Exécuter"**

### 4. Configurer la connexion
Ouvrez `php/config.php` et adaptez si nécessaire :
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Votre utilisateur MySQL
define('DB_PASS', '');          // Votre mot de passe MySQL
define('DB_NAME', 'restaurant_db');
```

### 5. Lancer l'application
Ouvrez votre navigateur → http://localhost/restaurant

---

## 🔑 Comptes de test

| Rôle  | Email             | Mot de passe |
|-------|-------------------|--------------|
| Admin | admin@resto.fr    | password     |

Créez un compte client directement via la page d'inscription.

---

## 🚀 Fonctionnalités

### 👥 Côté Client
- ✅ Inscription et connexion sécurisée (mot de passe hashé bcrypt)
- ✅ Parcourir le menu avec filtres par catégorie
- ✅ Panier latéral dynamique (sans rechargement de page)
- ✅ Passer une commande avec adresse de livraison
- ✅ Voir l'historique et le suivi des commandes
- ✅ Barre de progression du statut de commande

### 🔧 Côté Admin
- ✅ Tableau de bord avec statistiques en temps réel
- ✅ Gérer les commandes et changer leur statut
- ✅ Ajouter / modifier / supprimer des plats
- ✅ Activer / désactiver un plat du menu
- ✅ Liste des clients inscrits avec statistiques

---

## 🗄️ Base de Données

| Table               | Description                          |
|---------------------|--------------------------------------|
| `utilisateurs`      | Clients et administrateurs           |
| `categories`        | Catégories du menu (Pizzas, etc.)    |
| `plats`             | Plats avec prix et description       |
| `commandes`         | Commandes passées                    |
| `commande_details`  | Détail des plats par commande        |

---

## 🔒 Sécurité Implémentée
- Mots de passe hashés avec `password_hash()` (bcrypt)
- Requêtes préparées PDO (protection injection SQL)
- Vérification de rôle sur toutes les pages admin
- `htmlspecialchars()` sur toutes les sorties HTML (protection XSS)

---

*Projet réalisé dans le cadre du cours de développement web — Chapitre 4*
