<!-- INDEX.PHP - Page d'accueil -->
<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Livre.php';
require_once 'classes/Membre.php';
require_once 'classes/Emprunt.php';
require_once 'classes/Bibliothecaire.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Bibliothèque</title>
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 0; }
        .card { transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book"></i> BiblioGest
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="pages/livres.php">Livres</a></li>
                    <li class="nav-item"><a class="nav-link" href="pages/membres.php">Membres</a></li>
                    <li class="nav-item"><a class="nav-link" href="pages/emprunts.php">Emprunts</a></li>
                    <li class="nav-item"><a class="nav-link" href="pages/reservations.php">Réservations</a></li>
                    <?php if(isset($_SESSION['bibliothecaire'])): ?>
                        <li class="nav-item"><a class="nav-link btn btn-danger text-white ms-2" href="pages/logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2" href="pages/login.php">Connexion</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero">
        <div class="container text-center">
            <h1 class="display-4"><i class="fas fa-book-reader"></i> Système de Gestion de Bibliothèque</h1>
            <p class="lead">Gérez efficacement vos livres, emprunts et membres</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-book fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Catalogue de Livres</h5>
                        <p class="card-text">Consultez notre collection complète de livres disponibles</p>
                        <a href="pages/livres.php" class="btn btn-primary">Voir les livres</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Gestion des Membres</h5>
                        <p class="card-text">Gérez les membres de la bibliothèque</p>
                        <a href="pages/membres.php" class="btn btn-success">Voir les membres</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-exchange-alt fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Emprunts</h5>
                        <p class="card-text">Suivez les emprunts et les retours de livres</p>
                        <a href="pages/emprunts.php" class="btn btn-warning">Gérer les emprunts</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <?php
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            $statsLivres = $conn->query("SELECT COUNT(*) as total, SUM(disponible) as disponibles FROM Livre")->fetch();
            $statsMembres = $conn->query("SELECT COUNT(*) as total FROM Membre")->fetch();
            $statsEmprunts = $conn->query("SELECT COUNT(*) as total FROM Emprunt WHERE statut = 'EN_COURS'")->fetch();
        ?>
        <div class="row mt-5">
            <div class="col-md-12">
                <h3 class="text-center mb-4">Statistiques de la Bibliothèque</h3>
            </div>
            <div class="col-md-4 text-center">
                <div class="alert alert-info">
                    <h4><?= $statsLivres['total'] ?></h4>
                    <p>Livres au total<br><small><?= $statsLivres['disponibles'] ?> disponibles</small></p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="alert alert-success">
                    <h4><?= $statsMembres['total'] ?></h4>
                    <p>Membres inscrits</p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="alert alert-warning">
                    <h4><?= $statsEmprunts['total'] ?></h4>
                    <p>Emprunts en cours</p>
                </div>
            </div>
        </div>
        <?php } catch(Exception $e) { ?>
            <div class="alert alert-danger">Erreur de connexion à la base de données</div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>