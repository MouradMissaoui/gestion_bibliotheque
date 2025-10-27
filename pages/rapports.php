<!-- pages/rapports.php -->
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['bibliothecaire'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Statistiques générales
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM Livre) as total_livres,
        (SELECT COUNT(*) FROM Livre WHERE disponible = TRUE) as livres_disponibles,
        (SELECT COUNT(*) FROM Membre) as total_membres,
        (SELECT COUNT(*) FROM Emprunt WHERE statut = 'EN_COURS') as emprunts_cours,
        (SELECT COUNT(*) FROM Emprunt WHERE statut = 'EN_COURS' AND dateRetourPrevue < CURDATE()) as emprunts_retard,
        (SELECT COUNT(*) FROM Reservation WHERE actif = TRUE) as reservations_actives,
        (SELECT COALESCE(SUM(montant), 0) FROM Amende WHERE actif = TRUE) as amendes_totales,
        (SELECT COUNT(*) FROM Emprunt WHERE MONTH(dateEmprunt) = MONTH(CURDATE()) AND YEAR(dateEmprunt) = YEAR(CURDATE())) as emprunts_mois
";
$stats = $db->query($statsQuery)->fetch(PDO::FETCH_ASSOC);

// Top 5 livres les plus empruntés
$topLivresQuery = "
    SELECT L.titre, L.auteur, L.ISBN, COUNT(E.id) as nb_emprunts
    FROM Livre L
    LEFT JOIN Emprunt E ON L.ISBN = E.ISBN
    GROUP BY L.ISBN, L.titre, L.auteur
    HAVING COUNT(E.id) > 0
    ORDER BY nb_emprunts DESC
    LIMIT 5
";
$topLivres = $db->query($topLivresQuery)->fetchAll(PDO::FETCH_ASSOC);

// Top 5 membres les plus actifs
$topMembresQuery = "
    SELECT M.nom, M.email, COUNT(E.id) as nb_emprunts
    FROM Membre M
    LEFT JOIN Emprunt E ON M.id = E.membreId
    GROUP BY M.id, M.nom, M.email
    HAVING COUNT(E.id) > 0
    ORDER BY nb_emprunts DESC
    LIMIT 5
";
$topMembres = $db->query($topMembresQuery)->fetchAll(PDO::FETCH_ASSOC);

// Emprunts par mois (6 derniers mois)
$empruntsParMoisQuery = "
    SELECT 
        DATE_FORMAT(dateEmprunt, '%Y-%m') as mois,
        DATE_FORMAT(dateEmprunt, '%M %Y') as mois_libelle,
        COUNT(*) as nb_emprunts
    FROM Emprunt
    WHERE dateEmprunt >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(dateEmprunt, '%Y-%m')
    ORDER BY mois ASC
";
$empruntsParMois = $db->query($empruntsParMoisQuery)->fetchAll(PDO::FETCH_ASSOC);

// Emprunts en retard avec détails
$retardsQuery = "
    SELECT E.*, L.titre, L.auteur, M.nom as membre_nom, M.email,
           DATEDIFF(CURDATE(), E.dateRetourPrevue) as jours_retard
    FROM Emprunt E
    JOIN Livre L ON E.ISBN = L.ISBN
    JOIN Membre M ON E.membreId = M.id
    WHERE E.statut = 'EN_COURS' AND E.dateRetourPrevue < CURDATE()
    ORDER BY jours_retard DESC
    LIMIT 10
";
$retards = $db->query($retardsQuery)->fetchAll(PDO::FETCH_ASSOC);

// Taux d'utilisation par livre
$tauxUtilisationQuery = "
    SELECT 
        COUNT(*) as total_livres,
        SUM(CASE WHEN disponible = FALSE THEN 1 ELSE 0 END) as livres_empruntes
    FROM Livre
";
$tauxUtilisation = $db->query($tauxUtilisationQuery)->fetch(PDO::FETCH_ASSOC);
$pourcentageUtilisation = $tauxUtilisation['total_livres'] > 0 
    ? round(($tauxUtilisation['livres_empruntes'] / $tauxUtilisation['total_livres']) * 100, 1) 
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports et Statistiques - BiblioGest</title>
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .rank-badge {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #8B4513); }
        .rank-other { background: linear-gradient(135deg, #667eea, #764ba2); }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-book"></i> BiblioGest
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="livres.php">Livres</a></li>
                    <li class="nav-item"><a class="nav-link" href="membres.php">Membres</a></li>
                    <li class="nav-item"><a class="nav-link" href="emprunts.php">Emprunts</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservations.php">Réservations</a></li>
                    <li class="nav-item"><a class="nav-link" href="amendes.php">Amendes</a></li>
                    <li class="nav-item"><a class="nav-link active" href="rapports.php">Rapports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-bar text-primary"></i> Rapports et Statistiques</h2>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>

        <hr>

        <!-- Statistiques principales -->
        <h4 class="mb-3"><i class="fas fa-tachometer-alt"></i> Vue d'ensemble</h4>
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="fas fa-book fa-3x text-white mb-2"></i>
                    <h3 class="text-white"><?= $stats['total_livres'] ?></h3>
                    <p class="text-white mb-0">Livres au catalogue</p>
                    <small class="text-white opacity-75"><?= $stats['livres_disponibles'] ?> disponibles</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #56ab2f, #a8e063);">
                    <i class="fas fa-users fa-3x text-white mb-2"></i>
                    <h3 class="text-white"><?= $stats['total_membres'] ?></h3>
                    <p class="text-white mb-0">Membres inscrits</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #f2994a, #f2c94c);">
                    <i class="fas fa-exchange-alt fa-3x text-white mb-2"></i>
                    <h3 class="text-white"><?= $stats['emprunts_cours'] ?></h3>
                    <p class="text-white mb-0">Emprunts en cours</p>
                    <?php if ($stats['emprunts_retard'] > 0): ?>
                        <small class="text-white"><?= $stats['emprunts_retard'] ?> en retard</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #eb3349, #f45c43);">
                    <i class="fas fa-dollar-sign fa-3x text-white mb-2"></i>
                    <h3 class="text-white"><?= number_format($stats['amendes_totales'], 2) ?> $</h3>
                    <p class="text-white mb-0">Amendes à percevoir</p>
                </div>
            </div>
        </div>

        <!-- Indicateurs de performance -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Taux d'utilisation</h5>
                        <div style="font-size: 3rem; color: #667eea;">
                            <?= $pourcentageUtilisation ?>%
                        </div>
                        <p class="text-muted mb-0">des livres empruntés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Emprunts ce mois</h5>
                        <div style="font-size: 3rem; color: #56ab2f;">
                            <?= $stats['emprunts_mois'] ?>
                        </div>
                        <p class="text-muted mb-0">nouveaux emprunts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Réservations actives</h5>
                        <div style="font-size: 3rem; color: #f2994a;">
                            <?= $stats['reservations_actives'] ?>
                        </div>
                        <p class="text-muted mb-0">en attente</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row mb-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-chart-line"></i> Évolution des emprunts (6 derniers mois)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartEmprunts"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-chart-pie"></i> Répartition</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartRepartition"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 5 -->
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5><i class="fas fa-trophy"></i> Top 5 - Livres les plus empruntés</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($topLivres) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($topLivres as $index => $livre): ?>
                                    <div class="list-group-item d-flex align-items-center">
                                        <span class="rank-badge rank-<?= $index < 3 ? ($index + 1) : 'other' ?> me-3">
                                            <?= $index + 1 ?>
                                        </span>
                                        <div class="flex-grow-1">
                                            <strong><?= htmlspecialchars($livre['titre']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($livre['auteur']) ?></small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?= $livre['nb_emprunts'] ?> emprunts
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">Aucun emprunt enregistré</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-star"></i> Top 5 - Membres les plus actifs</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($topMembres) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($topMembres as $index => $membre): ?>
                                    <div class="list-group-item d-flex align-items-center">
                                        <span class="rank-badge rank-<?= $index < 3 ? ($index + 1) : 'other' ?> me-3">
                                            <?= $index + 1 ?>
                                        </span>
                                        <div class="flex-grow-1">
                                            <strong><?= htmlspecialchars($membre['nom']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($membre['email']) ?></small>
                                        </div>
                                        <span class="badge bg-success rounded-pill">
                                            <?= $membre['nb_emprunts'] ?> emprunts
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">Aucun emprunt enregistré</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emprunts en retard -->
        <?php if (count($retards) > 0): ?>
        <div class="card mb-5">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-exclamation-triangle"></i> Emprunts en retard (<?= count($retards) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Membre</th>
                                <th>Livre</th>
                                <th>Date retour prévue</th>
                                <th>Jours de retard</th>
                                <th>Amende</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($retards as $retard): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($retard['membre_nom']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($retard['email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($retard['titre']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($retard['auteur']) ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($retard['dateRetourPrevue'])) ?></td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?= $retard['jours_retard'] ?> jour(s)
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-danger">
                                            <?= number_format($retard['jours_retard'] * 1.0, 2) ?> €
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique des emprunts mensuels
        const ctxEmprunts = document.getElementById('chartEmprunts').getContext('2d');
        new Chart(ctxEmprunts, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($empruntsParMois, 'mois_libelle')) ?>,
                datasets: [{
                    label: "Nombre d'emprunts",
                    data: <?= json_encode(array_column($empruntsParMois, 'nb_emprunts')) ?>,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Graphique de répartition
        const ctxRepartition = document.getElementById('chartRepartition').getContext('2d');
        new Chart(ctxRepartition, {
            type: 'doughnut',
            data: {
                labels: ['Disponibles', 'Empruntés'],
                datasets: [{
                    data: [
                        <?= $stats['livres_disponibles'] ?>,
                        <?= $stats['total_livres'] - $stats['livres_disponibles'] ?>
                    ],
                    backgroundColor: [
                        'rgba(86, 171, 47, 0.8)',
                        'rgba(242, 153, 74, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>