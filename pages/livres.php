<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Livre.php';

$database = new Database();
$db = $database->getConnection();
$livre = new Livre($db);

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter':
                $livre->ISBN = $_POST['ISBN'];
                $livre->titre = $_POST['titre'];
                $livre->auteur = $_POST['auteur'];
                $livre->annee = $_POST['annee'];
                $livre->disponible = true;
                
                if ($livre->creer()) {
                    $message = "Livre ajouté avec succès!";
                    $messageType = "success";
                } else {
                    $message = "Erreur lors de l'ajout du livre.";
                    $messageType = "danger";
                }
                break;
            
            case 'supprimer':
                $livre->ISBN = $_POST['ISBN'];
                if ($livre->supprimer()) {
                    $message = "Livre supprimé avec succès!";
                    $messageType = "success";
                } else {
                    $message = "Impossible de supprimer ce livre (emprunts en cours).";
                    $messageType = "danger";
                }
                break;
        }
    }
}

// Recherche
$resultats = isset($_GET['recherche']) ? $livre->rechercher($_GET['recherche']) : $livre->lireTous();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Livres</title>
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
        <div class="row">
            <div class="col-md-12">
                <h2><i class="fas fa-book"></i> Gestion des Livres</h2>
                <hr>
                
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulaire d'ajout -->
                <?php if (isset($_SESSION['bibliothecaire'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-plus"></i> Ajouter un nouveau livre</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="ajouter">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">ISBN *</label>
                                    <input type="text" name="ISBN" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Titre *</label>
                                    <input type="text" name="titre" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Auteur *</label>
                                    <input type="text" name="auteur" class="form-control" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Année *</label>
                                    <input type="number" name="annee" class="form-control" min="1000" max="<?= date('Y') ?>" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ajouter</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recherche -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <input type="text" name="recherche" class="form-control" placeholder="Rechercher par titre, auteur ou ISBN..." value="<?= $_GET['recherche'] ?? '' ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Rechercher</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des livres -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5><i class="fas fa-list"></i> Catalogue des livres</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ISBN</th>
                                        <th>Titre</th>
                                        <th>Auteur</th>
                                        <th>Année</th>
                                        <th>Statut</th>
                                        <?php if (isset($_SESSION['bibliothecaire'])): ?>
                                        <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $resultats->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['ISBN']) ?></td>
                                        <td><?= htmlspecialchars($row['titre']) ?></td>
                                        <td><?= htmlspecialchars($row['auteur']) ?></td>
                                        <td><?= htmlspecialchars($row['annee']) ?></td>
                                        <td>
                                            <?php if ($row['disponible']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Disponible</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-times"></i> Emprunté</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (isset($_SESSION['bibliothecaire'])): ?>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ?');">
                                                <input type="hidden" name="action" value="supprimer">
                                                <input type="hidden" name="ISBN" value="<?= $row['ISBN'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>