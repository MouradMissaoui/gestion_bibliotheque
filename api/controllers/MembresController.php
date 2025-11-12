<?php
// api/controllers/MembresController.php
require_once '../classes/Membre.php';

class MembresController {
    private $db;
    private $membre;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->membre = new Membre($this->db);
    }
    
    // GET /api/membres
    public function getAll() {
        $stmt = $this->membre->lireTous();
        
        $membres = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $membres[] = [
                'id' => $row['id'],
                'nom' => $row['nom'],
                'email' => $row['email'],
                'nbEmprunts' => (int)$row['nbEmprunts'],
                'maxEmprunts' => (int)$row['maxEmprunts'],
                'created_at' => $row['created_at']
            ];
        }
        
        return [
            'success' => true,
            'count' => count($membres),
            'data' => $membres,
            'status' => 200
        ];
    }
    
    // GET /api/membres/{id}
    public function getOne($id) {
        $query = "SELECT * FROM Membre WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $membre = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($membre) {
            // Récupérer aussi les emprunts en cours
            $queryEmprunts = "SELECT E.*, L.titre FROM Emprunt E 
                             JOIN Livre L ON E.ISBN = L.ISBN 
                             WHERE E.membreId = :id AND E.statut = 'EN_COURS'";
            $stmtEmprunts = $this->db->prepare($queryEmprunts);
            $stmtEmprunts->bindParam(':id', $id);
            $stmtEmprunts->execute();
            $emprunts = $stmtEmprunts->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'id' => $membre['id'],
                    'nom' => $membre['nom'],
                    'email' => $membre['email'],
                    'nbEmprunts' => (int)$membre['nbEmprunts'],
                    'maxEmprunts' => (int)$membre['maxEmprunts'],
                    'created_at' => $membre['created_at'],
                    'emprunts_en_cours' => $emprunts
                ],
                'status' => 200
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Membre non trouvé',
                'status' => 404
            ];
        }
    }
    
    // POST /api/membres
    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['nom']) || !isset($data['email'])) {
            return [
                'success' => false,
                'error' => 'Données manquantes (nom, email requis)',
                'status' => 400
            ];
        }
        
        $this->membre->nom = $data['nom'];
        $this->membre->email = $data['email'];
        
        if ($this->membre->creer()) {
            return [
                'success' => true,
                'message' => 'Membre créé avec succès',
                'data' => [
                    'id' => $this->membre->id
                ],
                'status' => 201
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Erreur lors de la création (email déjà existant ?)',
                'status' => 500
            ];
        }
    }
    
    // PUT /api/membres/{id}
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['nom']) || !isset($data['email']) || !isset($data['maxEmprunts'])) {
            return [
                'success' => false,
                'error' => 'Données manquantes (nom, email, maxEmprunts requis)',
                'status' => 400
            ];
        }
        
        $query = "UPDATE Membre SET nom = :nom, email = :email, maxEmprunts = :max WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':nom', $data['nom']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':max', $data['maxEmprunts']);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Membre mis à jour avec succès',
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
    
    // DELETE /api/membres/{id}
    public function delete($id) {
        // Vérifier qu'il n'a pas d'emprunts en cours
        $query = "SELECT COUNT(*) as nb FROM Emprunt WHERE membreId = :id AND statut = 'EN_COURS'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['nb'] > 0) {
            return [
                'success' => false,
                'error' => 'Impossible de supprimer : membre a des emprunts en cours',
                'status' => 400
            ];
        }
        
        $query = "DELETE FROM Membre WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Membre supprimé avec succès',
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