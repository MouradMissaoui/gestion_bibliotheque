<!-- pages/logout.php -->
<?php
/**
 * Page de déconnexion
 * Détruit la session et redirige vers l'accueil
 */

// Démarrer la session
session_start();

// Enregistrer un message de déconnexion dans une variable de session temporaire
$_SESSION['logout_message'] = true;

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session si il existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Détruire la session
session_destroy();

// Redémarrer une nouvelle session pour le message
session_start();
$_SESSION['message'] = "Vous avez été déconnecté avec succès.";
$_SESSION['message_type'] = "info";

// Redirection vers la page d'accueil
header('Location: ../index.php');
exit;
?>