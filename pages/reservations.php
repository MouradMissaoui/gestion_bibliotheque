<!-- pages/reservations.php -->
<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'creer':
            try {
                // Vérifier que le livre est bien emprunté
                $queryCheck = "SELECT disponible FROM Livre WHERE ISBN = :isbn";
                $stmtCheck = $db->prepare($queryCheck);
                $stmtCheck->bindParam(":isbn", $_POST['ISBN']);
                $stmtCheck->execute();
                $livre = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                if ($livre && $livre['disponible'] == 0) {
                    $query = "INSERT INTO Reservation (id, ISBN, membreId, dateReservation, statut, actif) 
                              VALUES (:id, :ISBN, :membreId, CURDATE(), 'EN_ATTENTE', TRUE)";
                    $stmt = $db->prepare($query);
                    $id = 'R' . uniqid();
                    $stmt->bindParam(":id", $id);
                    $stmt->bindParam(":ISBN", $_POST['ISBN']);
                    $stmt->bindParam(":membreId", $_POST['membreId']);
                    
                    if ($stmt->execute()) {
                        $message = "Réservation créée avec succès! Le membre sera notifié quand le livre sera disponible.";
                        $messageType = "success";
                    } else {
                        $message = "Erreur lors de la création de la réservation.";
                        $messageType = "danger";
                    }
                } else {
                    $message = "Ce livre est actuellement disponible. Aucune réservation nécessaire!";
                    $messageType = "warning";
                }
            } catch (PDOException $e) {
                $message = "Erreur: " . $e->getMessage();
                $messageType = "danger";
            }
            break;
        
        case 'annuler':
            $query = "UPDATE Reservation SET actif = FALSE, statut = 'ANNULEE' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $_POST['reservationId']);
            
            if ($stmt->execute()) {
                $message = "Réservation annulée avec succès!";
                $messageType = "info";
            } else {
                $message = "Erreur lors de l'annulation.";
                $messageType = "danger";
            }
            break;
        
        case 'confirmer':
            $query = "UPDATE Reservation SET statut = 'CONFIRMEE' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $_POST['reservationId']);
            
            if ($stmt->execute()) {
                $message = "Réservation confirmée! Le membre peut venir récupérer le livre.";
                $messageType = "success";
            } else {
                $message = "Erreur lors de la confirmation.";
                $messageType = "danger";
            }
            break;
            
        case 'terminer':
            $query = "UPDATE Reservation SET statut = 'TERMINEE', actif = FALSE WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $_POST['reservationId']);
            
            if ($stmt->execute()) {
                $message = "Réservation terminée avec succès!";
                $messageType = "success";
            }
            break;
    }
}

// Récupérer les réservations avec détails
$query = "SELECT R.*, L.titre, L.auteur, L.disponible, M.nom as membre_nom, M.email,
          DATEDIFF(CURDATE(), R.dateReservation) as jours_attente
          FROM Reservation R
          JOIN Livre L ON R.ISBN = L.ISBN
          JOIN Membre M ON R.membreId = M.id
          ORDER BY R.actif DESC, R.dateReservation DESC";
$resultats = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Pour le formulaire - Livres empruntés
$queryLivres = "SELECT DISTINCT L.ISBN, L.titre, L.auteur 
                FROM Livre L
                WHERE L.disponible = FALSE
                ORDER BY L.titre";
$livresEmpruntes = $db->query($queryLivres)->fetchAll(PDO::FETCH_ASSOC);

// Pour le formulaire - Membres
$queryMembres = "SELECT id, nom, email FROM Membre ORDER BY nom";
$membres = $db->query($queryMembres)->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN actif = TRUE AND statut = 'EN_ATTENTE' THEN 1 ELSE 0 END) as en_attente,
    SUM(CASE WHEN statut = 'CONFIRMEE' THEN 1 ELSE 0 END) as confirmees,
    SUM(CASE WHEN statut = 'TERMINEE' THEN 1 ELSE 0 END) as terminees
    FROM Reservation";
$stats = $db->query($statsQuery)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réservations - BiblioGest</title>
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .reservation-card {
            border-left: 4px solid;
            transition: all 0.3s;
        }
        .reservation-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .reservation-card.en-attente { border-color: #ffc107; }
        .reservation-card.confirmee { border-color: #28a745; }
        .reservation-card.terminee { border-color: #6c757d; }
        .reservation-card.annulee { border-color: #dc3545; }
        .stat-box {
            border-radius: 10px;
            padding: 20px;
            text-align: center;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="livres.php">Livres</a></li>
                    <li class="nav-item"><a class="nav-link" href="membres.php">Membres</a></li>
                    <li class="nav-item"><a class="nav-link" href="emprunts.php">Emprunts</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reservations.php">Réservations</a></li>
                    <?php if (isset($_SESSION['bibliothecaire'])): ?>
                        <li class="nav-item"><a class="nav-link" href="amendes.php">Amendes</a></li>
                        <li class="nav-item"><a class="nav-link" href="rapports.php">Rapports</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-bookmark text-info"></i> Gestion des Réservations</h2>
            <?php if (isset($_SESSION['bibliothecaire'])): ?>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalCreer">
                    <i class="fas fa-plus"></i> Nouvelle réservation
                </button>
            <?php endif; ?>
        </div>

        <hr>

        <!-- Messages -->
        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-box bg-light">
                    <h3><?= $stats['total'] ?></h3>
                    <p class="text-muted mb-0">Total réservations</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box" style="background: #fff3cd;">
                    <h3 class="text-warning"><?= $stats['en_attente'] ?></h3>
                    <p class="text-muted mb-0">En attente</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box" style="background: #d1e7dd;">
                    <h3 class="text-success"><?= $stats['confirmees'] ?></h3>
                    <p class="text-muted mb-0">Confirmées</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box bg-light">
                    <h3 class="text-secondary"><?= $stats['terminees'] ?></h3>
                    <p class="text-muted mb-0">Terminées</p>
                </div>
            </div>
        </div>

        <!-- Explication du système -->
        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Comment fonctionnent les réservations ?</h5>
            <ol class="mb-0">
                <li><strong>EN_ATTENTE</strong> : Le livre est emprunté, le membre attend qu'il soit disponible</li>
                <li><strong>CONFIRMÉE</strong> : Le livre est disponible, le membre peut venir le chercher</li>
                <li><strong>TERMINÉE</strong> : Le membre a récupéré et emprunté le livre</li>
                <li><strong>ANNULÉE</strong> : La réservation a été annulée</li>
            </ol>
        </div>

        <!-- Réservations actives -->
        <?php 
        $reservationsActives = array_filter($resultats, fn($r) => $r['actif']);
        if (count($reservationsActives) > 0): 
        ?>
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-clock"></i> Réservations actives (<?= count($reservationsActives) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($reservationsActives as $r): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card reservation-card <?= strtolower(str_replace('_', '-', $r['statut'])) ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="fas fa-book text-primary"></i>
                                                <strong><?= htmlspecialchars($r['titre']) ?></strong>
                                            </h6>
                                            <small class="text-muted"><?= htmlspecialchars($r['auteur']) ?></small>
                                        </div>
                                        <?php if ($r['statut'] == 'EN_ATTENTE'): ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-hourglass-half"></i> En attente
                                            </span>
                                        <?php elseif ($r['statut'] == 'CONFIRMEE'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Confirmée
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <hr>

                                    <p class="mb-2">
                                        <i class="fas fa-user text-info"></i>
                                        <strong><?= htmlspecialchars($r['membre_nom']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($r['email']) ?></small>
                                    </p>

                                    <p class="mb-2">
                                        <i class="fas fa-calendar text-secondary"></i>
                                        Réservé le <?= date('d/m/Y', strtotime($r['dateReservation'])) ?>
                                        <span class="badge bg-secondary"><?= $r['jours_attente'] ?> jour(s)</span>
                                    </p>

                                    <?php if ($r['disponible']): ?>
                                        <div class="alert alert-success mb-2 py-2">
                                            <i class="fas fa-check-circle"></i> 
                                            <strong>Le livre est maintenant disponible!</strong>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($_SESSION['bibliothecaire'])): ?>
                                    <div class="btn-group btn-group-sm w-100 mt-2">
                                        <?php if ($r['statut'] == 'EN_ATTENTE' && $r['disponible']): ?>
                                            <form method="POST" style="flex: 1;">
                                                <input type="hidden" name="action" value="confirmer">
                                                <input type="hidden" name="reservationId" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-success w-100">
                                                    <i class="fas fa-check"></i> Confirmer
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($r['statut'] == 'CONFIRMEE'): ?>
                                            <form method="POST" style="flex: 1;">
                                                <input type="hidden" name="action" value="terminer">
                                                <input type="hidden" name="reservationId" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-check-double"></i> Livre récupéré
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" style="flex: 1;" onsubmit="return confirm('Annuler cette réservation ?');">
                                            <input type="hidden" name="action" value="annuler">
                                            <input type="hidden" name="reservationId" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fas fa-times"></i> Annuler
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle fa-3x mb-3"></i>
            <h5>Aucune réservation active pour le moment</h5>
            <p class="mb-0">Les réservations apparaîtront ici lorsque des membres réserveront des livres empruntés.</p>
        </div>
        <?php endif; ?>

        <!-- Historique -->
        <?php 
        $reservationsInactives = array_filter($resultats, fn($r) => !$r['actif']);
        if (count($reservationsInactives) > 0): 
        ?>
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-history"></i> Historique des réservations</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Membre</th>
                                <th>Livre</th>
                                <th>Durée</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservationsInactives as $r): ?>
                            <tr>
                                <td><small><?= date('d/m/Y', strtotime($r['dateReservation'])) ?></small></td>
                                <td><small><?= htmlspecialchars($r['membre_nom']) ?></small></td>
                                <td><small><?= htmlspecialchars($r['titre']) ?></small></td>
                                <td><small><?= $r['jours_attente'] ?> jour(s)</small></td>
                                <td>
                                    <?php if ($r['statut'] == 'TERMINEE'): ?>
                                        <span class="badge bg-success">Terminée</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Annulée</span>
                                    <?php endif; ?>
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

    <!-- Modal Créer réservation -->
    <?php if (isset($_SESSION['bibliothecaire'])): ?>
    <div class="modal fade" id="modalCreer" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-bookmark"></i> Créer une nouvelle réservation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="creer">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Important :</strong>
                            Les réservations ne sont possibles que pour les livres actuellement empruntés.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Membre *</label>
                            <select name="membreId" class="form-select" required>
                                <option value="">-- Sélectionner un membre --</option>
                                <?php foreach ($membres as $m): ?>
                                    <option value="<?= $m['id'] ?>">
                                        <?= htmlspecialchars($m['nom']) ?> (<?= htmlspecialchars($m['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Livre emprunté *</label>
                            <select name="ISBN" class="form-select" required>
                                <option value="">-- Sélectionner un livre --</option>
                                <?php if (count($livresEmpruntes) > 0): ?>
                                    <?php foreach ($livresEmpruntes as $l): ?>
                                        <option value="<?= $l['ISBN'] ?>">
                                            <?= htmlspecialchars($l['titre']) ?> - <?= htmlspecialchars($l['auteur']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Aucun livre emprunté actuellement</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">
                                Seuls les livres actuellement empruntés sont listés
                            </small>
                        </div>

                        <?php if (count($livresEmpruntes) == 0): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle"></i>
                                Tous les livres sont disponibles. Aucune réservation n'est nécessaire pour le moment.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-info" <?= count($livresEmpruntes) == 0 ? 'disabled' : '' ?>>
                            <i class="fas fa-bookmark"></i> Créer la réservation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide des alertes après 5 secondes
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Animation des cartes au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.reservation-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.transition = 'all 0.5s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>