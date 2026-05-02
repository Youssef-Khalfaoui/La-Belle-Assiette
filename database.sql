
CREATE DATABASE IF NOT EXISTS restaurant_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE restaurant_db;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  icone VARCHAR(10) DEFAULT '🍽️',
  ordre INT DEFAULT 0
);

CREATE TABLE plats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categorie_id INT NOT NULL,
  nom VARCHAR(150) NOT NULL,
  description TEXT,
  prix DECIMAL(8,2) NOT NULL,
  image VARCHAR(255) DEFAULT 'default.jpg',
  disponible TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE utilisateurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,
  telephone VARCHAR(20),
  adresse TEXT,
  role ENUM('client','admin') DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE commandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id INT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  statut ENUM('en_attente','confirmee','en_preparation','prete','livree','annulee') DEFAULT 'en_attente',
  adresse_livraison TEXT NOT NULL,
  telephone VARCHAR(20) NOT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE commande_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  commande_id INT NOT NULL,
  plat_id INT NOT NULL,
  quantite INT NOT NULL DEFAULT 1,
  prix_unitaire DECIMAL(8,2) NOT NULL,
  ingredients TEXT DEFAULT NULL,
  instructions TEXT DEFAULT NULL,
  FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  FOREIGN KEY (plat_id) REFERENCES plats(id)
);


INSERT INTO categories (nom, icone, ordre) VALUES
('Entrées',    '🥗', 1),
('Pizzas',     '🍕', 2),
('Pâtes',      '🍝', 3),
('Grillades',  '🥩', 4),
('Desserts',   '🍰', 5),
('Boissons',   '🥤', 6);

INSERT INTO plats (categorie_id, nom, description, prix, disponible) VALUES
(1, 'Salade Niçoise',       'Tomates, thon, olives, œufs, anchois, vinaigrette maison',  8.50,  1),
(1, 'Soupe à l\'oignon',    'Soupe traditionnelle gratinée au fromage fondu',              7.00,  1),
(1, 'Bruschetta Classique', 'Pain grillé, tomates fraîches, basilic, huile d\'olive',     6.50,  1),
(2, 'Margherita',           'Sauce tomate, mozzarella di bufala, basilic frais',          12.00, 1),
(2, 'Quatre Saisons',       'Jambon, champignons, artichauts, olives, mozzarella',        14.50, 1),
(2, 'Pizza Végétarienne',   'Poivrons, courgettes, aubergines, tomates cerises',          13.00, 1),
(2, 'Pizza Reine',          'Jambon, champignons, mozzarella fondante',                   13.50, 1),
(3, 'Carbonara',            'Spaghetti, pancetta croustillante, œuf, parmesan, poivre',   13.00, 1),
(3, 'Bolognaise',           'Tagliatelles, viande de bœuf, sauce tomate mijotée',         12.50, 1),
(3, 'Pesto Genovese',       'Trofie, pesto basilic maison, pignons de pin, parmesan',     12.00, 1),
(4, 'Entrecôte 300g',       'Entrecôte de bœuf grillée, sauce au poivre, frites maison', 24.00, 1),
(4, 'Poulet Rôti',          'Demi-poulet rôti aux herbes de Provence, légumes du jour',   16.50, 1),
(4, 'Brochette d\'agneau',  'Brochettes marinées aux épices, riz pilaf, salade',          19.00, 1),
(5, 'Tiramisu',             'Recette traditionnelle italienne, café fort, mascarpone',     7.00,  1),
(5, 'Crème Brûlée',         'Crème à la vanille, caramel craquant maison',                6.50,  1),
(5, 'Mousse au Chocolat',   'Chocolat noir 70%, légère et aérienne',                      6.00,  1),
(6, 'Eau Minérale 50cl',    'Evian ou Badoit',                                             2.50,  1),
(6, 'Coca-Cola 33cl',       'Coca-Cola, Coca Zero ou Fanta',                               3.00,  1),
(6, 'Jus d\'orange frais',  'Pressé à la commande, 100% naturel',                         4.50,  1),
(6, 'Café Expresso',        'Café arabica torréfié, servi avec petit biscuit',             2.50,  1);

INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'Restaurant', 'admin@resto.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

