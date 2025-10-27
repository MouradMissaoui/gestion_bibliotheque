<!-- pages/detail_membre.php -->
<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['bibliothecaire']) || !isset($_GET['id'])) {
    header('Location: membres.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$membreId = $_GET['id'];

// Récupérer les informations du membre
$queryMembre = "SELECT * FROM Membre WHERE id = :id";
$stmtMembre = $db->prepare($queryMembre);
$stmtMembre->bindParam(":id", $membreId);
$stmtMembre->execute();
$membre = $stmtMembre->fetch(PDO::FETCH_ASSOC);

if (!$membre) {
    header('Location: membres.php');
    exit;
}

// Historique complet des emprunts
$queryEmprunts = "SELECT E.*, L.titre, L.auteur, L.ISBN,
                  CASE 
                    WHEN E.statut = 'EN_COURS' AND E.dateRetourPrevue < CURDATE() THEN 'RETARD'
                    ELSE E.statut 
                  END as statut_reel,
                  DATEDIFF(COALESCE(E.dateRetour, CURDATE()), E.dateEmprunt) as duree_jours,
                  CASE 
                    WHEN E.dateRetour IS NOT NULL AND E.dateRetour > E.dateRetourPrevue 
                    THEN DATEDIFF(E.dateRetour, E.dateRetourPrevue)
                    WHEN E.statut = 'EN_COURS' AND CURDATE() > E.dateRetourPrevue
                    THEN DATEDIFF(CURDATE(), E.dateRetourPrevue)
                    ELSE 0
                  END as jours_retard
                  FROM Emprunt E
                  JOIN Livre L ON E.ISBN = L.ISBN
                  WHERE E.membreId = :membreId
                  ORDER BY E.dateEmprunt DESC";
$stmtEmprunts = $db->prepare($queryEmprunts);
$stmtEmprunts->bindParam(":membreId", $membreId);
$stmtEmprunts->execute();
$emprunts = $stmtEmprunts->fetchAll(PDO::FETCH_ASSOC);

// Réservations actives
$queryReservations = "SELECT R.*, L.titre, L.auteur 
                      FROM Reservation R
                      JOIN Livre L ON R.ISBN = L.ISBN
                      WHERE R.membreId = :membreId AND R.actif = TRUE
                      ORDER BY R.dateReservation DESC";
$stmtReservations = $db->prepare($queryReservations);
$stmtReservations->bindParam(":membreId", $membreId);
$stmtReservations->execute();
$reservations = $stmtReservations->fetchAll(PDO::FETCH_ASSOC);

// Amendes
$queryAmendes = "SELECT A.*, L.titre, E.dateEmprunt, E.dateRetour
                 FROM Amende A
                 JOIN Emprunt E ON A.empruntId = E.id
                 JOIN Livre L ON E.ISBN = L.ISBN
                 WHERE E.membreId = :membreId
                 ORDER BY A.dateCreation DESC";
$stmtAmendes = $db->prepare($queryAmendes);
$stmtAmendes->bindParam(":membreId", $membreId);
$stmtAmendes->execute();
$amendes = $stmtAmendes->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques
$totalEmprunts = count($emprunts);
$empruntsEnCours = count(array_filter($emprunts, fn($e) => $e['statut'] == 'EN_COURS'));
$empruntsTermines = count(array_filter($emprunts, fn($e) => $e['statut'] == 'TERMINE'));
$empruntsEnRetard = count(array_filter($emprunts, fn($e) => $e['statut_reel'] == 'RETARD'));
$totalAmendes = array_sum(array_map(fn($a) => $a['actif'] ? $a['montant'] : 0, $amendes));
$retoursEnRetard = count(array_filter($emprunts, fn($e) => $e['jours_retard'] > 0 && $e['statut'] == 'TERMINE'));
$tauxPonctualite = $empruntsTermines > 0 ? round((($empruntsTermines - $retoursEnRetard) / $empruntsTermines) * 100, 1) : 100;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche Membre - <?= htmlspecialchars($membre['nom']) ?></title>
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-box {
            border-left: 5px solid;
            padding: 15px;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .stat-box.primary { border-color: #0d6efd; }
        .stat-box.success { border-color: #198754; }
        .stat-box.warning { border-color: #ffc107; }
        .stat-box.danger { border-color: #dc3545; }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #0d6efd;
        }
        .timeline-item.success::before { background: #198754; box-shadow: 0 0 0 2px #198754; }
        .timeline-item.danger::before { background: #dc3545; box-shadow: 0 0 0 2px #dc3545; }
        .progress-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-book"></i> BiblioGest
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="membres.php">
                    <i class="fas fa-arrow-left"></i> Retour aux membres
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- En-tête membre -->
        <div class="card mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <i class="fas fa-user-circle fa-5x"></i>
                    </div>
                    <div class="col-md-7">
                        <h2 class="mb-2"><?= htmlspecialchars($membre['nom']) ?></h2>
                        <p class="mb-1"><i class="fas fa-envelope"></i> <?= htmlspecialchars($membre['email']) ?></p>
                        <p class="mb-1"><i class="fas fa-id-card"></i> ID: <?= htmlspecialchars($membre['id']) ?></p>
                        <p class="mb-0"><i class="fas fa-calendar"></i> Membre depuis le <?= date('d/m/Y', strtotime($membre['created_at'])) ?></p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4>Emprunts actuels</h4>
                        <h1><?= $membre['nbEmprunts'] ?> / <?= $membre['maxEmprunts'] ?></h1>
                        <?php if ($membre['nbEmprunts'] >= $membre['maxEmprunts']): ?>
                            <span class="badge bg-danger">Limite atteinte</span>
                        <?php else: ?>
                            <span class="badge bg-success">Actif</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <h4 class="mb-3"><i class="fas fa-chart-bar"></i> Statistiques</h4>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-box primary">
                    <h3><?= $totalEmprunts ?></h3>
                    <p class="text-muted mb-0">Total emprunts</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box success">
                    <h3><?= $empruntsTermines ?></h3>
                    <p class="text-muted mb-0">Terminés</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box warning">
                    <h3><?= $empruntsEnCours ?></h3>
                    <p class="text-muted mb-0">En cours</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box danger">
                    <h3><?= number_format($totalAmendes, 2) ?> €</h3>
                    <p class="text-muted mb-0">Amendes dues</p>
                </div>
            </div>
        </div>

        <!-- Indicateurs de performance -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5>Taux de ponctualité</h5>
                        <div class="progress-circle" style="background: conic-gradient(#28a745 0% <?= $tauxPonctualite ?>%, #e9ecef <?= $tauxPonctualite ?>% 100%);">
                            <?= $tauxPonctualite ?>%
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <?= $empruntsTermines - $retoursEnRetard ?> / <?= $empruntsTermines ?> retours à temps
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5>Retards</h5>
                        <div style="font-size: 3rem; color: <?= $empruntsEnRetard > 0 ? '#dc3545' : '#28a745' ?>;">
                            <?= $empruntsEnRetard ?>
                        </div>
                        <small class="text-muted">emprunt(s) en retard actuellement</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5>Réservations</h5>
                        <div style="font-size: 3rem; color: #0d6efd;">
                            <?= count($reservations) ?>
                        </div>
                        <small class="text-muted">réservation(s) active(s)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emprunts en cours -->
        <?php if ($empruntsEnCours > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5><i class="fas fa-clock"></i> Emprunts en cours (<?= $empruntsEnCours ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Livre</th>
                                <th>Emprunté le</th>
                                <th>Retour prévu</th>
                                <th>Jours restants</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($emprunts as $e): ?>
                                <?php if ($e['statut'] == 'EN_COURS'): ?>
                                    <?php 
                                    $joursRestants = (strtotime($e['dateRetourPrevue']) - time()) / 86400;
                                    $enRetard = $joursRestants < 0;
                                    ?>
                                    <tr class="<?= $enRetard ? 'table-danger' : '' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($e['titre']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($e['auteur']) ?></small>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($e['dateEmprunt'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($e['dateRetourPrevue'])) ?></td>
                                        <td>
                                            <?php if ($enRetard): ?>
                                                <span class="text-danger fw-bold">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    <?= abs(floor($joursRestants)) ?> jours de retard
                                                </span>
                                            <?php else: ?>
                                                <?= ceil($joursRestants) ?> jour(s)
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($enRetard): ?>
                                                <span class="badge bg-danger">En retard</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">À jour</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historique des emprunts (Timeline) -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-history"></i> Historique complet des emprunts (<?= $totalEmprunts ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($emprunts) > 0): ?>
                    <div class="timeline">
                        <?php foreach ($emprunts as $e): ?>
                            <div class="timeline-item <?= $e['statut'] == 'TERMINE' ? 'success' : ($e['statut_reel'] == 'RETARD' ? 'danger' : '') ?>">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="mb-1">
                                                    <i class="fas fa-book text-primary"></i>
                                                    <strong><?= htmlspecialchars($e['titre']) ?></strong>
                                                </h6>
                                                <small class="text-muted"><?= htmlspecialchars($e['auteur']) ?></small><br>
                                                <small class="text-muted">ISBN: <?= htmlspecialchars($e['ISBN']) ?></small>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted">Emprunté le</small><br>
                                                <strong><?= date('d/m/Y', strtotime($e['dateEmprunt'])) ?></strong><br>
                                                <small class="text-muted">Retour prévu</small><br>
                                                <strong><?= date('d/m/Y', strtotime($e['dateRetourPrevue'])) ?></strong>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <?php if ($e['statut'] == 'TERMINE'): ?>
                                                    <span class="badge bg-success mb-2">Terminé</span><br>
                                                    <small class="text-muted">Retourné le</small><br>
                                                    <strong><?= date('d/m/Y', strtotime($e['dateRetour'])) ?></strong><br>
                                                    <small class="text-muted">Durée: <?= $e['duree_jours'] ?> jour(s)</small>
                                                    <?php if ($e['jours_retard'] > 0): ?>
                                                        <br><span class="badge bg-warning"><?= $e['jours_retard'] ?> jour(s) de retard</span>
                                                    <?php endif; ?>
                                                <?php elseif ($e['statut_reel'] == 'RETARD'): ?>
                                                    <span class="badge bg-danger mb-2">En retard</span><br>
                                                    <small class="text-danger fw-bold"><?= $e['jours_retard'] ?> jour(s) de retard</small>
                                                <?php else: ?>
                                                    <span class="badge bg-warning mb-2">En cours</span><br>
                                                    <small class="text-muted">Durée actuelle: <?= $e['duree_jours'] ?> jour(s)</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Aucun emprunt enregistré pour ce membre</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Réservations -->
        <?php if (count($reservations) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5><i class="fas fa-bookmark"></i> Réservations actives (<?= count($reservations) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($reservations as $r): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6><strong><?= htmlspecialchars($r['titre']) ?></strong></h6>
                                    <p class="mb-2"><small class="text-muted"><?= htmlspecialchars($r['auteur']) ?></small></p>
                                    <p class="mb-2">
                                        <i class="fas fa-calendar"></i>
                                        Réservé le <?= date('d/m/Y', strtotime($r['dateReservation'])) ?>
                                    </p>
                                    <?php if ($r['statut'] == 'EN_ATTENTE'): ?>
                                        <span class="badge bg-warning">En attente</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Confirmée</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Amendes -->
        <?php if (count($amendes) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-euro-sign"></i> Amendes (<?= count($amendes) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Livre</th>
                                <th>Date création</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($amendes as $a): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($a['titre']) ?></strong><br>
                                        <small class="text-muted">
                                            Emprunt: <?= date('d/m/Y', strtotime($a['dateEmprunt'])) ?>
                                            - Retour: <?= date('d/m/Y', strtotime($a['dateRetour'])) ?>
                                        </small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($a['dateCreation'])) ?></td>
                                    <td><strong class="text-danger"><?= number_format($a['montant'], 2) ?> €</strong></td>
                                    <td>
                                        <?php if ($a['actif']): ?>
                                            <span class="badge bg-danger">Non payée</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Payée</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end"><strong>Total à payer:</strong></td>
                                <td colspan="2"><strong class="text-danger fs-5"><?= number_format($totalAmendes, 2) ?> €</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions rapides -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5><i class="fas fa-tools"></i> Actions rapides</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex">
                    <a href="emprunts.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Créer un nouvel emprunt
                    </a>
                    <a href="reservations.php" class="btn btn-info">
                        <i class="fas fa-bookmark"></i> Créer une réservation
                    </a>
                    <?php if ($totalAmendes > 0): ?>
                        <a href="amendes.php" class="btn btn-warning">
                            <i class="fas fa-euro-sign"></i> Gérer les amendes
                        </a>
                    <?php endif; ?>
                    <a href="membres.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        item.style.transition = 'all 0.5s ease';
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0)';
                    }, 50);
                }, index * 50);
            });
        });
    </script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>