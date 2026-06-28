# Momo Travel

Momo Travel est une plateforme web de réservation de voyages. Elle permet aux utilisateurs de trouver des logements, des visites et de découvrir de nouvelles destinations pour leurs prochains séjours. 

## 🚀 Fonctionnalités principales

* **Recherche & Réservation :** Recherche de destinations, forfaits, et processus complet de réservation de voyage.
* **Multilingue :** Support de l'anglais et du français (changement de langue dynamique).
* **Système d'utilisateurs :** 
  * Inscription et connexion (y compris via Google OAuth).
  * Profils utilisateurs et espace personnel (mes billets, factures PDF).
* **Communauté :**
  * **Blog :** Articles de présentation de destinations et d'astuces de voyage.
  * **Forum :** Espace d'échange entre les voyageurs.
  * **Messagerie :** Système de messages privés entre les utilisateurs.
  * **Avis :** Laisser et consulter des avis sur les séjours.
* **Gestion et Administration :**
  * **Espace Agence :** Panel dédié pour les agences de voyages partenaires.
  * **Espace Administrateur :** Gestion complète du site (utilisateurs, FAQ, pages légales, thèmes, messagerie, forum).

## 🛠 Technologies utilisées

* **Back-end :** PHP (procédural)
* **Base de données :** MySQL (Connexion via PDO)
* **Front-end :** HTML5, CSS3, JavaScript (Vanilla)
* **Design :** CSS sur-mesure (sans framework UI externe) avec des polices Google Fonts (Playfair Display, Roboto).

## 📁 Structure du projet

Voici un aperçu des principaux fichiers et dossiers du projet :

* `index.php` : Page d'accueil du site.
* `header.php` / `footer.php` : Éléments communs de l'interface (navigation, bas de page).
* `auth.php`, `logout.php`, `google_callback.php` : Gestion de l'authentification.
* `book.php`, `reservation.php`, `process_booking.php`, `payment.php` : Processus de réservation et de paiement.
* `user_page.php`, `my_tickets.php`, `invoice_pdf.php` : Espace personnel du client.
* `agency.php` : Interface pour les agences.
* `admin.php` et fichiers préfixés `admin_*` : Interface du panneau d'administration.
* `blog.php`, `forum.php`, `messages.php` : Fonctionnalités communautaires.
* `style.css` : Feuille de style globale du projet.

## ⚙️ Installation et Configuration

1. **Prérequis :** Un serveur web local (comme XAMPP, MAMP, WAMP ou Laragon) avec PHP et MySQL.
2. **Cloner le projet :**
   ```bash
   git clone <url-du-depot>
   ```
3. **Configuration de la Base de Données :**
   * Créez une base de données MySQL.
   * Importez le script SQL du projet (s'il est fourni).
   * Assurez-vous que le fichier de configuration de base de données (généralement `../config/db_connect.php`) contient les bonnes informations d'identification (hôte, nom de la base de données, utilisateur, mot de passe).
4. **Lancement :**
   * Placez les fichiers dans le dossier racine de votre serveur local (par ex. `htdocs` pour XAMPP ou `www` pour WAMP).
   * Accédez au projet depuis votre navigateur via `http://localhost/mon-dossier-projet`.

## 👥 Rôles Utilisateurs

Le système gère différents niveaux d'accès :
* **Visiteur :** Peut parcourir les destinations, le blog et lire les avis.
* **Utilisateur (User) :** Peut réserver des voyages, publier sur le forum, envoyer des messages et laisser des avis.
* **Agence (Agency) :** Peut proposer des voyages et gérer ses offres.
* **Administrateur (Admin) :** A un contrôle total sur la plateforme, les utilisateurs et le contenu.

---
*Ce README a été généré pour aider à la compréhension de l'architecture et des fonctionnalités du projet Momo Travel.*
