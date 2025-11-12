<?php
// api/index.php - Point d'entrée de l'API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

// Fonction helper pour envoyer une réponse JSON
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Router simple
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Extraire le chemin de l'API
$path = parse_url($request_uri, PHP_URL_PATH);
$base_path = '/gestion_bibliotheque/api/';
if (strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
}
$segments = explode('/', trim($path, '/'));

$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

// Router vers les contrôleurs
switch ($resource) {
    case 'livres':
        require_once 'controllers/LivresController.php';
        $controller = new LivresController();
        break;
    
    case 'membres':
        require_once 'controllers/MembresController.php';
        $controller = new MembresController();
        break;
    
    case 'emprunts':
        require_once 'controllers/EmpruntsController.php';
        $controller = new EmpruntsController();
        break;
    
    case 'reservations':
        require_once 'controllers/ReservationsController.php';
        $controller = new ReservationsController();
        break;
    
    case 'amendes':
        require_once 'controllers/AmendesController.php';
        $controller = new AmendesController();
        break;
    
    case 'stats':
        require_once 'controllers/StatsController.php';
        $controller = new StatsController();
        break;
    
    default:
        sendResponse(['error' => 'Ressource non trouvée'], 404);
}

// Exécuter l'action selon la méthode HTTP
try {
    switch ($request_method) {
        case 'GET':
            $result = $id ? $controller->getOne($id) : $controller->getAll();
            break;
        
        case 'POST':
            $result = $controller->create();
            break;
        
        case 'PUT':
            $result = $controller->update($id);
            break;
        
        case 'DELETE':
            $result = $controller->delete($id);
            break;
        
        default:
            sendResponse(['error' => 'Méthode non autorisée'], 405);
    }
    
    // Envoyer la réponse retournée par le contrôleur
    $status = $result['status'] ?? 200;
    unset($result['status']);
    sendResponse($result, $status);
    
} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}
?>