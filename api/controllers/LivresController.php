<?php
// api/controllers/LivresController.php 
require_once '../classes/Livre.php';

class LivresController {
    private $db;
    private $livre;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->livre = new Livre($this->db);
    }
    
    // GET /api/livres
    public function getAll() {
        $params = $_GET;
        
        // Recherche
        if (isset($params['search'])) {
            $stmt = $this->livre->rechercher($params['search']);
        } else {
            $stmt = $this->livre->lireTous();
        }
        
        $livres = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $livres[] = [
                'ISBN' => $row['ISBN'],
                'titre' => $row['titre'],
                'auteur' => $row['auteur'],
                'annee' => (int)$row['annee'],
                'disponible' => (bool)$row['disponible'],
                'created_at' => $row['created_at']
            ];
        }
        
        return [
            'success' => true,
            'count' => count($livres),
            'data' => $livres,
            'status' => 200
        ];
    }
    
    // GET /api/livres/{ISBN}
    public function getOne($isbn) {
        $query = "SELECT * FROM Livre WHERE ISBN = :isbn";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':isbn', $isbn);
        $stmt->execute();
        
        $livre = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($livre) {
            return [
                'success' => true,
                'data' => [
                    'ISBN' => $livre['ISBN'],
                    'titre' => $livre['titre'],
                    'auteur' => $livre['auteur'],
                    'annee' => (int)$livre['annee'],
                    'disponible' => (bool)$livre['disponible'],
                    'created_at' => $livre['created_at']
                ],
                'status' => 200
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Livre non trouvé',
                'status' => 404
            ];
        }
    }
    
    // POST /api/livres
    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validation
        if (!isset($data['ISBN']) || !isset($data['titre']) || !isset($data['auteur']) || !isset($data['annee'])) {
            return [
                'success' => false,
                'error' => 'Données manquantes (ISBN, titre, auteur, annee requis)',
                'status' => 400
            ];
        }
        
        $this->livre->ISBN = $data['ISBN'];
        $this->livre->titre = $data['titre'];
        $this->livre->auteur = $data['auteur'];
        $this->livre->annee = $data['annee'];
        $this->livre->disponible = true;
        
        if ($this->livre->creer()) {
            return [
                'success' => true,
                'message' => 'Livre créé avec succès',
                'data' => [
                    'ISBN' => $data['ISBN']
                ],
                'status' => 201
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Erreur lors de la création',
                'status' => 500
            ];
        }
    }
    
    // PUT /api/livres/{ISBN}
    public function update($isbn) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['titre']) || !isset($data['auteur']) || !isset($data['annee'])) {
            return [
                'success' => false,
                'error' => 'Données manquantes (titre, auteur, annee requis)',
                'status' => 400
            ];
        }
        
        $query = "UPDATE Livre SET titre = :titre, auteur = :auteur, annee = :annee WHERE ISBN = :isbn";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':titre', $data['titre']);
        $stmt->bindParam(':auteur', $data['auteur']);
        $stmt->bindParam(':annee', $data['annee']);
        $stmt->bindParam(':isbn', $isbn);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Livre mis à jour avec succès',
                'status' => 200
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Erreur lors de la mise à jour',
                'status' => 500
            ];
        }
    }
    
    // DELETE /api/livres/{ISBN}
    public function delete($isbn) {
        $this->livre->ISBN = $isbn;
        
        if ($this->livre->supprimer()) {
            return [
                'success' => true,
                'message' => 'Livre supprimé avec succès',
                'status' => 200
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Erreur lors de la suppression',
                'status' => 500
            ];
        }
    }
}
?>