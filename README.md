# Wonderwise - Plateforme Éducative

## Aperçu
Ce projet a été développé dans le cadre du cours PIDEV à l'École d'Ingénieurs ESPRIT. Il s'agit d'une plateforme éducative complète, construite avec Symfony et MySQL sur un environnement XAMPP, conçue pour offrir une expérience d'apprentissage interactive avec des fonctionnalités modernes et une gestion sécurisée des utilisateurs via une interface web. Le projet intègre également des éléments culturels et touristiques liés à des pays spécifiques pour enrichir l'expérience éducative.

## Fonctionnalités

### Système de Réservation et Paiement Intégré
- **Gestion des offres touristiques**
  - Création et modification des offres
  - Système de prix dynamique
  - Gestion des places disponibles
  - Filtrage et tri des offres
- **Système de réservation avancé avec paiement intégré**
  - Réservation en ligne avec confirmation par email
  - Gestion des régimes alimentaires
  - Choix du mode de paiement via une intégration Stripe pour paiements sécurisés
  - Support de multiples modes de paiement (carte bancaire, espèces, virement, chèque)
  - Conversion automatique des devises
  - Suivi du statut des réservations et des transactions
  - Interface de réservation intuitive

### Gestion des Pays
- Informations culturelles et touristiques par pays
- Profils détaillés des pays, incluant :
  - **Art**
  - **Monuments**
  - **Plats Traditionnels**
  - **Célébrités**

#### Exemple : Tunisie
- **Art** : Calligraphie arabe, tapis traditionnels de Kairouan
- **Monuments** : Amphithéâtre d'El Jem, Médina de Tunis
- **Plats Traditionnels** : Couscous, brik, shakshuka
- **Célébrités** : Ons Jabeur (joueuse de tennis), Tarak Ben Ammar (producteur de cinéma)

### Gestion des Guides et Événements
- **Système de gestion des guides touristiques**
  - Profils détaillés des guides
  - Disponibilité et planning
  - Spécialités et langues parlées
- **Gestion des événements**
  - Création et modification d'événements
  - Calendrier interactif
  - Système de notification

### Système d'Avis et Réclamations
- **Gestion des avis clients**
  - Système de notation
  - Commentaires et retours d'expérience
  - Modération des avis
- **Système de réclamations**
  - Suivi des réclamations
  - Traitement et résolution
  - Historique des interactions

### Gestion des Utilisateurs
- Interface d'administration complète
- Gestion des profils utilisateurs
- Système de rôles et permissions
- Tableau de bord personnalisé

## Stack Technique

### Frontend
- Twig (moteur de templates Symfony)
- Bootstrap 5 pour les styles
- FullCalendar pour la fonctionnalité calendrier
- Leaflet.js pour l'intégration des cartes

### Backend
- Symfony 6.4
- PHP 8.1
- MySQL via XAMPP pour la base de données
- Doctrine ORM pour la gestion des données

### Autres Outils
- Composer pour la gestion des dépendances
- PHPUnit pour les tests
- API Stripe pour les paiements

## Structure du Projet
```
wonderwise/
├── config/
├── public/
│   ├── assets/
│   └── index.php
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Repository/
│   └── Service/
├── templates/
├── tests/
├── var/
├── vendor/
├── composer.json
└── README.md
```

## Détails de Configuration de la Base de Données
- Utiliser MySQL via XAMPP.
- Créer une base de données nommée `wonderwise_db`.
- Mettre à jour le fichier `.env` avec :
  ```
  DATABASE_URL="mysql://root:@127.0.0.1:3306/wonderwise_db"
  ```
- Exécuter les migrations : `php bin/console doctrine:migrations:migrate`

## Variables d'Environnement Requises
- `STRIPE_API_KEY`: Clé API Stripe pour les paiements.
- `DATABASE_URL`: URL de connexion à la base de données (voir ci-dessus).
- Définir ces variables dans le fichier `.env`.

## Étapes Détaillées d'Installation pour l'Environnement de Développement
1. Installer XAMPP (Apache et MySQL activés).
2. Cloner le dépôt : `git clone https://github.com/AOUINIRAYEN/wonderwise.git`
3. Installer PHP 8.1 et Composer.
4. Configurer la base de données MySQL (voir section "Détails de Configuration de la Base de Données").
5. Naviguer dans le répertoire : `cd wonderwise`
6. Installer les dépendances : `composer install`
7. Lancer le serveur : `symfony server:start` ou via XAMPP (placer le projet dans `htdocs`).

## Documentation API
- **Endpoint de Réservation** : `POST /api/reservations`
  - Requiert un JSON avec `{ "userId": 1, "offerId": 1, "paymentMethod": "card" }`
  - Retourne un statut HTTP 201 avec l'ID de la réservation.
- **Endpoint de Paiement** : `POST /api/payments`
  - Requiert un JSON avec `{ "reservationId": 1, "amount": 100.00 }`
  - Retourne un statut HTTP 200 avec le statut de paiement.
- Consultez `src/Controller/` pour plus de détails.

## Procédures de Test
- Exécuter les tests unitaires : `php bin/phpunit`
- Vérifier les fonctionnalités :
  - Créer un utilisateur via l'interface web.
  - Réserver une offre et simuler un paiement avec Stripe.
  - Laisser un avis et vérifier sa modération.
- Ajouter des tests dans le dossier `tests/`.

## Pour Commencer
1. Cloner le dépôt : `git clone https://github.com/AOUINIRAYEN/wonderwise.git`
2. Installer XAMPP et démarrer Apache et MySQL.
3. Configurer la base de données MySQL (voir section "Détails de Configuration de la Base de Données").
4. Naviguer dans le répertoire : `cd wonderwise`
5. Installer les dépendances : `composer install`
6. Configurer les clés API (Stripe, etc.) dans le fichier `.env`.
7. Lancer le serveur : `symfony server:start` ou via XAMPP (placer le projet dans `htdocs`).

## Configuration Requise
- XAMPP (Apache et MySQL)
- PHP 8.1 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Compte Stripe pour les paiements
- Minimum 4GB de RAM
- Espace disque : 500MB minimum

## Remerciements
Ce projet a été réalisé sous la direction des professeurs de l'École d'Ingénieurs ESPRIT.

## Mots-clés
- symfony
- php
- mysql
- xampp
- plateforme-educative
- gestion-reservations
- systeme-paiement
- gestion-pays
- culture-touristique
- gestion-evenements
- gestion-guides
- systeme-avis
- gestion-reclamations

---
**Note :** Certains fichiers de configuration et variables d'environnement doivent être configurés avant de lancer l'application. Veuillez contacter l'équipe de développement pour des instructions détaillées d'installation.
