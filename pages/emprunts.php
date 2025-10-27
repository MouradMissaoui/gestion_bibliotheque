<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Emprunt.php';
require_once '../classes/Livre.php';
require_once '../classes/Membre.php';

$database = new Database();
$db = $database->getConnection();
$emprunt = new Emprunt($db);

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'creer':
            // Vérifier d'abord si le membre peut emprunter
            $queryCheck = "SELECT nbEmprunts, maxEmprunts FROM Membre WHERE id = :id";
            $stmtCheck = $db->prepare($queryCheck);
            $stmtCheck->bindParam(":id", $_POST['membreId']);
            $stmtCheck->execute();
            $membreInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($membreInfo['nbEmprunts'] >= $membreInfo['maxEmprunts']) {
                $message = "Ce membre a atteint sa limite d'emprunts (" . $membreInfo['maxEmprunts'] . "/" . $membreInfo['maxEmprunts'] . "). Impossible de créer un nouvel emprunt.";
                $messageType = "danger";
            } else {
                // Créer l'emprunt
                if ($emprunt->creer($_POST['membreId'], $_POST['ISBN'])) {
                    $message = "Emprunt créé avec succès!";
                    $messageType = "success";
                } else {
                    $message = "Erreur lors de la création de l'emprunt.";
                    $messageType = "danger";
                }
            }
            break;
        case 'retourner':
            $emprunt->id = $_POST['empruntId'];
            if ($emprunt->terminer()) {
                $message = "Retour enregistré avec succès!";
                $messageType = "success";
            } else {
                $message = "Erreur lors du retour.";
                $messageType = "danger";
            }
            break;
        
        case 'prolonger':
            $emprunt->id = $_POST['empruntId'];
            if ($emprunt->prolongerJours(7)) {
                $message = "Emprunt prolongé de 7 jours!";
                $messageType = "success";
            } else {
                $message = "Erreur lors de la prolongation.";
                $messageType = "danger";
            }
            break;

    }
}

$resultats = $emprunt->lireTous();
$livre = new Livre($db);
$livresDisponibles = $livre->lireTous();
$membre = new Membre($db);
$membres = $membre->lireTous();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Emprunts</title>
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        <h2><i class="fas fa-exchange-alt"></i> Gestion des Emprunts</h2>
        <hr>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['bibliothecaire'])): ?>
        <!-- Formulaire de création d'emprunt -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-plus"></i> Nouvel Emprunt</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="creer">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Membre *</label>
                            <select name="membreId" class="form-select" required>
                                <option value="">Sélectionner un membre...</option>
                                <?php while ($m = $membres->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?> (<?= $m['nbEmprunts'] ?>/<?= $m['maxEmprunts'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Livre *</label>
                            <select name="ISBN" class="form-select" required>
                                <option value="">Sélectionner un livre...</option>
                                <?php while ($l = $livresDisponibles->fetch(PDO::FETCH_ASSOC)): ?>
                                    <?php if ($l['disponible']): ?>
                                        <option value="<?= $l['ISBN'] ?>"><?= htmlspecialchars($l['titre']) ?> - <?= htmlspecialchars($l['auteur']) ?></option>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer l'emprunt</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Liste des emprunts -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-list"></i> Liste des emprunts</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Membre</th>
                                <th>Livre</th>
                                <th>Date d'emprunt</th>
                                <th>Date retour prévue</th>
                                <th>Statut</th>
                                <?php if (isset($_SESSION['bibliothecaire'])): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $resultats->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['membre_nom']) ?></td>
                                <td><?= htmlspecialchars($row['titre']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['dateEmprunt'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['dateRetourPrevue'])) ?></td>
                                <td>
                                    <?php if ($row['statut'] == 'EN_COURS'): ?>
                                        <?php if (strtotime($row['dateRetourPrevue']) < time()): ?>
                                            <span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> En retard</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fas fa-clock"></i> En cours</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-check"></i> Terminé</span>
                                    <?php endif; ?>
                                </td>
                                <?php if (isset($_SESSION['bibliothecaire']) && $row['statut'] == 'EN_COURS'): ?>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="retourner">
                                        <input type="hidden" name="empruntId" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-undo"></i> Retour</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="prolonger">
                                        <input type="hidden" name="empruntId" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-clock"></i> +7j</button>
                                    </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>