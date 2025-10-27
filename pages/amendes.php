<!-- pages/amendes.php -->
<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['bibliothecaire'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Gestion du paiement d'amende
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'payer') {
        $query = "UPDATE Amende SET actif = FALSE WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $_POST['amendeId']);
        
        if ($stmt->execute()) {
            $message = "Amende marquée comme payée!";
            $messageType = "success";
        } else {
            $message = "Erreur lors du paiement.";
            $messageType = "danger";
        }
    }
}

// Récupérer toutes les amendes
$query = "SELECT A.*, M.nom as membre_nom, M.email, L.titre, E.dateEmprunt, E.dateRetourPrevue, E.dateRetour
          FROM Amende A
          JOIN Emprunt E ON A.empruntId = E.id
          JOIN Membre M ON E.membreId = M.id
          JOIN Livre L ON E.ISBN = L.ISBN
          ORDER BY A.actif DESC, A.dateCreation DESC";
$amendes = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Calculer les totaux
$totalActif = array_sum(array_map(fn($a) => $a['actif'] ? $a['montant'] : 0, $amendes));
$totalPaye = array_sum(array_map(fn($a) => !$a['actif'] ? $a['montant'] : 0, $amendes));
$nombreActif = count(array_filter($amendes, fn($a) => $a['actif']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Amendes</title>
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-book"></i> BiblioGest</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Retour à l'accueil</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div>
            <i class="fas fa-dollar-sign fa-3x text-danger"></i>
            <h2 class="d-inline-block ms-2">Gestion des Amendes</h2>
        </div>
        <hr>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h4 class="text-danger"><?= number_format($totalActif, 2) ?> $</h4>
                        <p class="text-muted mb-0">Amendes à payer</p>
                        <small><?= $nombreActif ?> amende(s)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h4 class="text-success"><?= number_format($totalPaye, 2) ?> $</h4>
                        <p class="text-muted mb-0">Amendes payées</p>
                        <small><?= count($amendes) - $nombreActif ?> amende(s)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-calculator fa-2x text-primary mb-2"></i>
                        <h4 class="text-primary"><?= number_format($totalActif + $totalPaye, 2) ?> $</h4>
                        <p class="text-muted mb-0">Total historique</p>
                        <small><?= count($amendes) ?> amende(s)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Amendes actives -->
        <?php if ($nombreActif > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-exclamation-circle"></i> Amendes à payer (<?= $nombreActif ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Membre</th>
                                <th>Livre</th>
                                <th>Dates emprunt</th>
                                <th>Jours de retard</th>
                                <th>Montant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($amendes as $a): ?>
                                <?php if ($a['actif']): ?>
                                    <?php $joursRetard = (strtotime($a['dateRetour']) - strtotime($a['dateRetourPrevue'])) / 86400; ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($a['membre_nom']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($a['email']) ?></small>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($a['titre']) ?></strong>
                                        </td>
                                        <td>
                                            <small>
                                                Emprunté: <?= date('d/m/Y', strtotime($a['dateEmprunt'])) ?><br>
                                                Retour prévu: <?= date('d/m/Y', strtotime($a['dateRetourPrevue'])) ?><br>
                                                Retourné: <?= date('d/m/Y', strtotime($a['dateRetour'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning"><?= floor($joursRetard) ?> jours</span>
                                        </td>
                                        <td>
                                            <strong class="text-danger"><?= number_format($a['montant'], 2) ?> €</strong>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="payer">
                                                <input type="hidden" name="amendeId" value="<?= $a['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Marquer cette amende comme payée ?');">
                                                    <i class="fas fa-check"></i> Marquer payée
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end"><strong>Total à percevoir :</strong></td>
                                <td><strong class="text-danger fs-5"><?= number_format($totalActif, 2) ?> €</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historique des amendes payées -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-history"></i> Historique des amendes payées</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Membre</th>
                                <th>Livre</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $hasPayees = false;
                            foreach ($amendes as $a): 
                                if (!$a['actif']): 
                                    $hasPayees = true;
                            ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($a['dateCreation'])) ?></td>
                                    <td><?= htmlspecialchars($a['membre_nom']) ?></td>
                                    <td><?= htmlspecialchars($a['titre']) ?></td>
                                    <td><?= number_format($a['montant'], 2) ?> €</td>
                                    <td><span class="badge bg-success">Payée</span></td>
                                </tr>
                            <?php 
                                endif;
                            endforeach; 
                            
                            if (!$hasPayees):
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        Aucune amende payée pour le moment
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>

