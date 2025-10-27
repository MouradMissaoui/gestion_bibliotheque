<!-- pages/membres.php -->
<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Membre.php';

$database = new Database();
$db = $database->getConnection();
$membre = new Membre($db);

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'ajouter':
            $membre->nom = trim($_POST['nom']);
            $membre->email = trim($_POST['email']);
            
            if ($membre->creer()) {
                $message = "Membre ajouté avec succès! ID: " . $membre->id;
                $messageType = "success";
            } else {
                $message = "Erreur lors de l'ajout du membre. Vérifiez que l'email n'existe pas déjà.";
                $messageType = "danger";
            }
            break;
            
        case 'modifier_limite':
            $query = "UPDATE Membre SET maxEmprunts = :max WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":max", $_POST['maxEmprunts'], PDO::PARAM_INT);
            $stmt->bindParam(":id", $_POST['membreId']);
            
            if ($stmt->execute()) {
                $message = "Limite d'emprunts modifiée avec succès!";
                $messageType = "success";
            } else {
                $message = "Erreur lors de la modification.";
                $messageType = "danger";
            }
            break;
            
        case 'supprimer':
            // Vérifier qu'il n'a pas d'emprunts en cours
            $queryCheck = "SELECT COUNT(*) as nb FROM Emprunt WHERE membreId = :id AND statut = 'EN_COURS'";
            $stmtCheck = $db->prepare($queryCheck);
            $stmtCheck->bindParam(":id", $_POST['membreId']);
            $stmtCheck->execute();
            $empruntsActifs = $stmtCheck->fetch(PDO::FETCH_ASSOC)['nb'];
            
            if ($empruntsActifs > 0) {
                $message = "Impossible de supprimer ce membre : il a encore $empruntsActifs emprunt(s) en cours.";
                $messageType = "warning";
            } else {
                $query = "DELETE FROM Membre WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $_POST['membreId']);
                
                if ($stmt->execute()) {
                    $message = "Membre supprimé avec succès!";
                    $messageType = "success";
                } else {
                    $message = "Erreur lors de la suppression.";
                    $messageType = "danger";
                }
            }
            break;
    }
}

// Récupérer tous les membres avec statistiques
$query = "SELECT M.*, 
          COUNT(DISTINCT E.id) as total_emprunts_historique,
          COUNT(DISTINCT CASE WHEN E.statut = 'EN_COURS' THEN E.id END) as emprunts_actuels,
          SUM(CASE WHEN A.actif = TRUE THEN A.montant ELSE 0 END) as amendes_dues
          FROM Membre M
          LEFT JOIN Emprunt E ON M.id = E.membreId
          LEFT JOIN Amende A ON E.id = A.empruntId
          GROUP BY M.id
          ORDER BY M.nom";
$resultats = $db->query($query);

// Statistiques globales
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN nbEmprunts >= maxEmprunts THEN 1 ELSE 0 END) as limites_atteintes,
    SUM(nbEmprunts) as total_emprunts_cours
    FROM Membre";
$stats = $db->query($statsQuery)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Membres - BiblioGest</title>
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .member-card {
            transition: transform 0.2s;
        }
        .member-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .badge-limit {
            font-size: 0.9rem;
        }
        .stat-box {
            border-left: 4px solid;
            padding-left: 15px;
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
                    <li class="nav-item"><a class="nav-link active" href="membres.php">Membres</a></li>
                    <li class="nav-item"><a class="nav-link" href="emprunts.php">Emprunts</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservations.php">Réservations</a></li>
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
            <h2><i class="fas fa-users text-success"></i> Gestion des Membres</h2>
            <?php if (isset($_SESSION['bibliothecaire'])): ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAjout">
                    <i class="fas fa-user-plus"></i> Nouveau membre
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

        <!-- Statistiques globales -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-box border-success">
                    <div class="card-body">
                        <h3 class="text-success"><?= $stats['total'] ?></h3>
                        <p class="text-muted mb-0">Membres inscrits</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-box border-primary">
                    <div class="card-body">
                        <h3 class="text-primary"><?= $stats['total_emprunts_cours'] ?></h3>
                        <p class="text-muted mb-0">Emprunts en cours</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-box border-warning">
                    <div class="card-body">
                        <h3 class="text-warning"><?= $stats['limites_atteintes'] ?></h3>
                        <p class="text-muted mb-0">Limites atteintes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre de recherche -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un membre par nom ou email...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des membres -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-list"></i> Liste des membres (<?= $stats['total'] ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="membresTable">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">ID</th>
                                <th width="20%">Nom</th>
                                <th width="20%">Email</th>
                                <th width="15%">Emprunts</th>
                                <th width="10%">Limite</th>
                                <th width="15%">Inscription</th>
                                <?php if (isset($_SESSION['bibliothecaire'])): ?>
                                <th width="10%">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $resultats->fetch(PDO::FETCH_ASSOC)): ?>
                                <?php 
                                $ratio = $row['emprunts_actuels'] / $row['maxEmprunts'];
                                $badgeClass = $ratio >= 1 ? 'danger' : ($ratio >= 0.7 ? 'warning' : 'success');
                                $badgeIcon = $ratio >= 1 ? 'fas fa-ban' : 'fas fa-check';
                                ?>
                                <tr class="membre-row">
                                    <td><code><?= htmlspecialchars($row['id']) ?></code></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['nom']) ?></strong>
                                        <?php if ($row['amendes_dues'] > 0): ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                <?= number_format($row['amendes_dues'], 2) ?> € d'amendes
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($row['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $badgeClass ?> badge-limit">
                                            <i class="<?= $badgeIcon ?>"></i>
                                            <?= $row['emprunts_actuels'] ?> en cours
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            Total: <?= $row['total_emprunts_historique'] ?> emprunts
                                        </small>
                                    </td>
                                    <td>
                                        <?php if (isset($_SESSION['bibliothecaire'])): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="modifier_limite">
                                                <input type="hidden" name="membreId" value="<?= $row['id'] ?>">
                                                <select name="maxEmprunts" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="1" <?= $row['maxEmprunts'] == 1 ? 'selected' : '' ?>>1</option>
                                                    <option value="2" <?= $row['maxEmprunts'] == 2 ? 'selected' : '' ?>>2</option>
                                                    <option value="3" <?= $row['maxEmprunts'] == 3 ? 'selected' : '' ?>>3</option>
                                                    <option value="5" <?= $row['maxEmprunts'] == 5 ? 'selected' : '' ?>>5</option>
                                                    <option value="10" <?= $row['maxEmprunts'] == 10 ? 'selected' : '' ?>>10</option>
                                                </select>
                                            </form>
                                        <?php else: ?>
                                            <?= $row['maxEmprunts'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($row['created_at'])) ?></small>
                                    </td>
                                    <?php if (isset($_SESSION['bibliothecaire'])): ?>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="detail_membre.php?id=<?= $row['id'] ?>" 
                                               class="btn btn-info" 
                                               title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-danger" 
                                                    onclick="confirmerSuppression('<?= $row['id'] ?>', '<?= htmlspecialchars($row['nom'], ENT_QUOTES) ?>')"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Membre -->
    <?php if (isset($_SESSION['bibliothecaire'])): ?>
    <div class="modal fade" id="modalAjout" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Inscrire un nouveau membre</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom complet *</label>
                            <input type="text" name="nom" class="form-control" placeholder="Ex: Jean Dupont" required>
                            <small class="text-muted">Prénom et nom de famille</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" placeholder="jean.dupont@email.fr" required>
                            <small class="text-muted">L'email doit être unique</small>
                        </div>

                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> <strong>Information :</strong>
                            <ul class="mb-0">
                                <li>Un ID unique sera généré automatiquement</li>
                                <li>Limite par défaut : 3 emprunts simultanés</li>
                                <li>Le membre pourra emprunter immédiatement</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Inscrire le membre
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Formulaire caché pour suppression -->
    <form id="formSuppression" method="POST" style="display:none;">
        <input type="hidden" name="action" value="supprimer">
        <input type="hidden" name="membreId" id="membreIdSuppr">
    </form>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Recherche en temps réel
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.membre-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Confirmation de suppression
        function confirmerSuppression(id, nom) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer le membre "${nom}" ?\n\nCette action est irréversible !`)) {
                document.getElementById('membreIdSuppr').value = id;
                document.getElementById('formSuppression').submit();
            }
        }

        // Auto-hide des alertes
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>