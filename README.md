# gestion_bibliotheque
# 📚 BiblioGest - Système de Gestion de Bibliothèque

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-Educational-green)

## 📖 Description

**BiblioGest** est un système complet de gestion de bibliothèque développé en PHP/MySQL. Il permet de gérer efficacement le catalogue de livres, les membres inscrits, les emprunts, les réservations et les amendes pour retards.

### ✨ Fonctionnalités principales

- 📚 **Gestion du catalogue** : Ajouter, rechercher et supprimer des livres
- 👥 **Gestion des membres** : Inscription, suivi des emprunts, limites personnalisables
- 📖 **Système d'emprunts** : Création, retour, prolongation (14 jours par défaut)
- 🔖 **Réservations** : Réserver des livres actuellement empruntés
- 💵 **Amendes automatiques** : Calcul automatique (1$/jour de retard)
- 📊 **Statistiques et rapports** : Graphiques, top 5, indicateurs de performance
- 🔐 **Authentification** : Système de connexion pour bibliothécaires
- 🤖 **Automatisation** : Triggers MySQL pour maintenir l'intégrité des données

---

## 🚀 Installation

### Prérequis

Avant de commencer, assurez-vous d'avoir installé :

- **PHP 7.4+** : [Télécharger PHP](https://www.php.net/downloads)
- **MySQL 5.7+** : [Télécharger MySQL](https://dev.mysql.com/downloads/)
- **Apache** : Via [XAMPP](https://www.apachefriends.org/), [WAMP](https://www.wampserver.com/), [MAMP](https://www.mamp.info/) ou [Laragon](https://laragon.org/)
- **Git** : [Télécharger Git](https://git-scm.com/downloads)
- Un navigateur web moderne (Chrome, Firefox, Edge)

### Étapes d'installation

#### 1. Cloner le repository

```bash
cd C:/xampp/htdocs/  # Ou le chemin de votre serveur web
git clone [URL_DU_REPOSITORY] gestion_bibliotheque
cd gestion_bibliotheque
```

#### 2. Créer la base de données

**Option A : Via phpMyAdmin**
1. Démarrez Apache et MySQL depuis XAMPP/WAMP
2. Ouvrez phpMyAdmin : `http://localhost/phpmyadmin`
3. Cliquez sur "Nouveau" → Créez une base nommée `gestion_bibliotheque`
4. Sélectionnez la base → Onglet "Importer"
5. Choisissez le fichier `database.sql` → Cliquez sur "Exécuter"

**Option B : Via ligne de commande**
```bash
mysql -u root -p
CREATE DATABASE gestion_bibliotheque CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_bibliotheque;
SOURCE database.sql;
EXIT;
```

#### 3. Configurer la connexion

Ouvrez le fichier `config/database.php` et ajustez selon votre environnement :

```php
private $host = "localhost";           // Votre hôte MySQL
private $db_name = "gestion_bibliotheque";
private $username = "root";            // Votre utilisateur MySQL
private $password = "";                // Votre mot de passe MySQL (vide pour XAMPP)
```

#### 4. Vérifier les permissions

Assurez-vous que le serveur web a les droits de lecture sur tous les fichiers :

```bash
# Linux/Mac
chmod -R 755 gestion_bibliotheque/

# Windows : Les permissions par défaut sont généralement suffisantes
```

#### 5. Démarrer l'application

1. Démarrez **Apache** et **MySQL** depuis votre panneau de contrôle
2. Accédez à l'application : `http://localhost/gestion_bibliotheque/`
3. Connectez-vous avec un compte de test (voir section ci-dessous)

### ✅ Vérification de l'installation

Pour vérifier que tout fonctionne :

1. La page d'accueil affiche les statistiques
2. Vous pouvez vous connecter avec `admin@biblio.fr`
3. Toutes les pages du menu sont accessibles
4. Les emprunts peuvent être créés et retournés

### ⚠️ Résolution des problèmes courants

**"Could not connect to database"**
- Vérifiez que MySQL est démarré
- Vérifiez les identifiants dans `config/database.php`
- Testez la connexion : `mysql -u root -p`

**"Table doesn't exist"**
- Vérifiez que `database.sql` a bien été importé
- Reconnectez-vous à MySQL et réexécutez l'import

**Page blanche**
- Activez l'affichage des erreurs dans `config/database.php` :
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Consultez les logs Apache : `xampp/apache/logs/error.log`

---

## 🔑 Comptes de test

Deux comptes sont créés automatiquement :

| Email | Rôle | Droits |
|-------|------|--------|
| `admin@biblio.fr` | Administrateur | Tous droits |
| `jean.dupont@biblio.fr` | Bibliothécaire | Standard |

> **Note :** Pas de mot de passe requis (authentification par email uniquement)

---

## 📁 Structure du projet

```
gestion_bibliotheque/
│
├── index.php                    # Page d'accueil
├── install.php                  # Script d'installation
├── database.sql                 # Schéma de base de données
│
├── config/
│   └── database.php            # Configuration connexion MySQL
│
├── classes/
│   ├── Livre.php               # Gestion des livres
│   ├── Membre.php              # Gestion des membres
│   ├── Emprunt.php             # Gestion des emprunts
│   ├── Amende.php              # Gestion des amendes
│   └── Bibliothecaire.php      # Gestion bibliothécaires
│
├── pages/
│   ├── login.php               # Authentification
│   ├── logout.php              # Déconnexion
│   ├── livres.php              # Catalogue de livres
│   ├── membres.php             # Liste des membres
│   ├── emprunts.php            # Gestion des emprunts
│   ├── reservations.php        # Système de réservations
│   ├── amendes.php             # Suivi des amendes
│   ├── rapports.php            # Statistiques et graphiques
│   └── detail_membre.php       # Fiche détaillée membre
│
└── assets/
    ├── css/
    │   └── custom.css          # Styles personnalisés
    └── js/
        └── custom.js           # Scripts JavaScript
```

---

## 💡 Utilisation

### Pour les bibliothécaires

#### 1. Connexion
- Accédez à la page de connexion
- Entrez votre email de bibliothécaire
- Vous serez redirigé vers le tableau de bord

#### 2. Ajouter un livre
```
Livres → Bouton "Nouveau livre" → Remplir le formulaire → Ajouter
```

#### 3. Inscrire un membre
```
Membres → Bouton "Nouveau membre" → Nom + Email → Inscrire
```

#### 4. Créer un emprunt
```
Emprunts → Sélectionner membre et livre → Créer l'emprunt
```
- Durée : **14 jours**
- Livre devient **indisponible**
- Compteur du membre **incrémenté automatiquement**

#### 5. Enregistrer un retour
```
Emprunts → Bouton "Retour" sur l'emprunt concerné
```
- Livre redevient **disponible**
- Si retard : **Amende créée automatiquement** (1$/jour)

#### 6. Prolonger un emprunt
```
Emprunts → Bouton "+7j" → Date prolongée de 7 jours
```

#### 7. Consulter les statistiques
```
Rapports → Vue d'ensemble, graphiques, top 5
```

---

## ⚙️ Règles de gestion

### Limites d'emprunts
- **Maximum par membre :** 3 livres simultanément (modifiable)
- **Vérification automatique** avant chaque nouvel emprunt
- Message d'erreur si limite atteinte

### Durée d'emprunt
- **Durée standard :** 14 jours
- **Prolongation possible :** +7 jours
- **Modifications :** Dans `classes/Emprunt.php` ligne `INTERVAL 14 DAY`

### Amendes
- **Tarif :** 1$ par jour de retard
- **Calcul automatique** lors du retour
- **Modification du tarif :** Dans `calculerAmende()` changer `* 1.0`

### Réservations
- Seulement pour les **livres empruntés**
- Statuts : EN_ATTENTE → CONFIRMÉE → TERMINÉE
- Notification manuelle (automatisation à développer)

---

## 🗄️ Base de données

### Tables principales

| Table | Description | Colonnes principales |
|-------|-------------|---------------------|
| **Livre** | Catalogue de livres | ISBN (PK), titre, auteur, année, disponible |
| **Membre** | Membres inscrits | id (PK), nom, email, nbEmprunts, maxEmprunts |
| **Emprunt** | Historique emprunts | id (PK), ISBN (FK), membreId (FK), dates, statut |
| **Amende** | Amendes pour retards | id (PK), empruntId (FK), montant, actif |
| **Reservation** | Réservations livres | id (PK), ISBN (FK), membreId (FK), statut |
| **Bibliothecaire** | Comptes staff | matricule (PK), nom, email, droitsAdmin |

### Triggers automatiques

#### `after_emprunt_insert`
Déclenché après la création d'un emprunt :
- Incrémente `nbEmprunts` du membre
- Met le livre en `disponible = FALSE`

#### `after_emprunt_return`
Déclenché après un retour (dateRetour renseignée) :
- Décrémente `nbEmprunts` du membre
- Remet le livre en `disponible = TRUE`

---

## 🔒 Sécurité

### Mesures implémentées

✅ **Requêtes préparées (PDO)** - Protection contre injections SQL  
✅ **htmlspecialchars()** - Protection contre failles XSS  
✅ **Sessions PHP sécurisées** - Authentification  
✅ **Validation des données** - Côté serveur  
✅ **Contraintes d'intégrité** - Base de données  
✅ **Vérification des permissions** - Actions réservées aux bibliothécaires

### Recommandations pour la production

- ⚠️ Implémenter des **mots de passe hashés** (password_hash)
- ⚠️ Activer **HTTPS obligatoire**
- ⚠️ Limiter les **tentatives de connexion**
- ⚠️ Configurer des **sauvegardes automatiques**
- ⚠️ Désactiver `display_errors` en production
- ⚠️ Supprimer `install.php` et `database.sql` après installation

---

## 🎨 Technologies utilisées

### Backend
- **PHP 7.4+** - Langage serveur
- **MySQL 5.7+** - Base de données relationnelle
- **PDO** - Accès sécurisé à la base de données

### Frontend
- **HTML5 / CSS3** - Structure et styles
- **Bootstrap 5.3** - Framework CSS responsive
- **JavaScript ES6** - Interactivité
- **Chart.js** - Graphiques statistiques
- **Font Awesome 6** - Icônes

---

## 📊 Fonctionnalités avancées

### Recherche en temps réel
Disponible sur les pages Livres et Membres - filtrage instantané

### Statistiques dynamiques
- Graphiques d'évolution (6 derniers mois)
- Top 5 livres et membres
- Taux d'utilisation
- Indicateurs de performance

### Fiche détaillée membre
- Timeline des emprunts
- Taux de ponctualité
- Historique complet
- Amendes actives

---

## 🛠️ Personnalisation

### Modifier la durée d'emprunt

Dans `classes/Emprunt.php`, méthode `creer()` :
```php
DATE_ADD(CURDATE(), INTERVAL 14 DAY)  // Changez 14
```

### Modifier le tarif des amendes

Dans `classes/Emprunt.php`, méthode `calculerAmende()` :
```php
return $row['joursRetard'] * 1.0;  // Changez 1.0
```

### Modifier la limite d'emprunts

Dans la base de données, table `Membre`, colonne `maxEmprunts`  
Ou via l'interface : Membres → Menu déroulant de limite

---

## 🐛 Dépannage

### Erreur "Connection failed"
**Solution :** Vérifiez que MySQL est démarré et que les identifiants dans `config/database.php` sont corrects

### Page blanche
**Solution :** Activez l'affichage des erreurs :
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Emprunts ne se créent pas
**Solution :** Vérifiez que :
- Le livre est disponible
- Le membre n'a pas atteint sa limite (3 emprunts)
- Les triggers MySQL sont actifs

### Amendes non calculées
**Solution :** Vérifiez que la méthode `terminer()` de la classe `Emprunt` est appelée lors du retour

---

## 📈 Améliorations futures possibles

- [ ] Système de notifications par email
- [ ] Scan de codes-barres / ISBN
- [ ] Export PDF des rapports
- [ ] Application mobile
- [ ] API REST complète
- [ ] Gestion des catégories de livres
- [ ] Système de recommandations
- [ ] Multi-bibliothèques
- [ ] Chat support en ligne
- [ ] Historique des modifications

---

## 📝 Licence

Ce projet est fourni à des fins **éducatives**. Libre d'utilisation, modification et distribution.

---

## 👨‍💻 Support

Pour toute question ou problème :
- Consultez la documentation technique
- Vérifiez les logs Apache/MySQL
- Testez dans un environnement propre

---

## 📞 Contact

Projet développé pour l'enseignement collégial - Gestion de bibliothèques scolaires et municipales

---

**Version :** 1.0.0  
**Dernière mise à jour :** 2025  
**Statut :** ✅ Production Ready

---

## 🎓 Crédits

Développé dans le cadre d'un projet pédagogique de gestion de bibliothèque.

**Technologies open source utilisées :**
- Bootstrap (MIT License)
- Font Awesome (Free License)
- Chart.js (MIT License)
- PHP / MySQL (Open Source)

---

**🌟 N'oubliez pas de laisser une étoile si ce projet vous aide ! ⭐**
