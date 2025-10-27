<!-- pages/login.php -->
<?php
session_start();

// Si déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['bibliothecaire'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';
require_once '../classes/Bibliothecaire.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Veuillez entrer votre email.";
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $bibliothecaire = new Bibliothecaire($db);
            
            $user = $bibliothecaire->seConnecter($email);
            
            if ($user) {
                // Connexion réussie
                $_SESSION['bibliothecaire'] = [
                    'matricule' => $user['matricule'],
                    'nom' => $user['nom'],
                    'email' => $user['email'],
                    'droitsAdmin' => $user['droitsAdmin']
                ];
                
                // Régénérer l'ID de session pour sécurité
                session_regenerate_id(true);
                
                $success = "Connexion réussie ! Redirection...";
                
                // Redirection après 1 seconde
                header("refresh:1;url=../index.php");
            } else {
                $error = "Email non reconnu. Veuillez vérifier votre saisie.";
            }
        } catch (Exception $e) {
            $error = "Erreur de connexion. Veuillez réessayer plus tard.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - BiblioGest</title>
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            max-width: 500px;
            margin: 0 auto;
        }
        .login-card {
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            border-radius: 20px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .login-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .login-body {
            padding: 40px;
            background: white;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .test-accounts {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            overflow: hidden;
        }
        .shape {
            position: absolute;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 20s infinite;
        }
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            left: 80%;
            animation-delay: 4s;
        }
        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            top: 80%;
            left: 20%;
            animation-delay: 8s;
        }
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            25% {
                transform: translateY(-20px) rotate(90deg);
            }
            50% {
                transform: translateY(-40px) rotate(180deg);
            }
            75% {
                transform: translateY(-20px) rotate(270deg);
            }
        }
    </style>
</head>
<body>
    <!-- Formes flottantes décoratives -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <div class="login-container">
            <div class="card login-card">
                <div class="login-header">
                    <i class="fas fa-book-reader login-icon"></i>
                    <h2 class="mb-0">BiblioGest</h2>
                    <p class="mb-0 opacity-75">Système de Gestion de Bibliothèque</p>
                </div>

                <div class="login-body">
                    <h4 class="text-center mb-4">Connexion Bibliothécaire</h4>

                    <!-- Messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire de connexion -->
                    <form method="POST" id="loginForm">
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-envelope text-primary"></i> Adresse email
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-control form-control-lg" 
                                placeholder="votre.email@biblio.fr" 
                                required
                                autofocus
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            <small class="text-muted">Entrez votre email de bibliothécaire</small>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg btn-login">
                                <i class="fas fa-sign-in-alt"></i> Se connecter
                            </button>
                        </div>
                    </form>

                    <!-- Comptes de test -->
                    <div class="test-accounts">
                        <h6 class="mb-2">
                            <i class="fas fa-info-circle text-primary"></i> Comptes de démonstration
                        </h6>
                        <small>
                            <strong>Administrateur :</strong><br>
                            <code style="cursor: pointer;" onclick="fillEmail('admin@biblio.fr')">admin@biblio.fr</code>
                            <span class="badge bg-danger ms-2">Tous droits</span>
                            <br><br>
                            <strong>Bibliothécaire :</strong><br>
                            <code style="cursor: pointer;" onclick="fillEmail('jean.dupont@biblio.fr')">jean.dupont@biblio.fr</code>
                            <span class="badge bg-info ms-2">Standard</span>
                        </small>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-lightbulb"></i> Cliquez sur un email pour le remplir automatiquement
                            </small>
                        </div>
                    </div>

                    <!-- Retour à l'accueil -->
                    <div class="text-center mt-4">
                        <a href="../index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left"></i> Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>

            <!-- Information de sécurité -->
            <div class="text-center mt-4 text-white">
                <small>
                    <i class="fas fa-shield-alt"></i> 
                    Connexion sécurisée via session PHP
                    <br>
                    <i class="fas fa-lock"></i> 
                    Utilisez uniquement sur des réseaux de confiance
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour remplir automatiquement l'email
        function fillEmail(email) {
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('input[name="email"]').focus();
        }

        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.login-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(function() {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });

        // Empêcher la soumission multiple du formulaire
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...';
        });
    </script>
    <script src="../assets/js/custom.js"></script>
</body>
</html>