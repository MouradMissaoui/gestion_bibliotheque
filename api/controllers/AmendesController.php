<?php
//api/controllers/AmendesController.php
class AmendesController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // GET /api/amendes
    public function getAll() {
        $params = $_GET;
        
        $query = "SELECT A.*, M.nom as membre_nom, M.email, L.titre,
                  E.dateEmprunt, E.dateRetour, E.dateRetourPrevue
                  FROM Amende A
                  JOIN Emprunt E ON A.empruntId = E.id
                  JOIN Membre M ON E.membreId = M.id
                  JOIN Livre L ON E.ISBN = L.ISBN";
        
        if (isset($params['actif'])) {
            $query .= " WHERE A.actif = :actif";
        }
        
        $query .= " ORDER BY A.dateCreation DESC";
        
        $stmt = $this->db->prepare($query);
        if (isset($params['actif'])) {
            $actif = $params['actif'] === 'true' ? 1 : 0;
            $stmt->bindParam(':actif', $actif);
        }
        $stmt->execute();
        
        $amendes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $amendes[] = [
                'id' => $row['id'],
                'empruntId' => $row['empruntId'],
                'membre' => $row['membre_nom'],
                'email' => $row['email'],
                'livre' => $row['titre'],
                'montant' => (float)$row['montant'],
                'dateCreation' => $row['dateCreation'],
                'actif' => (bool)$row['actif'],
                'dateEmprunt' => $row['dateEmprunt'],
                'dateRetour' => $row['dateRetour'],
                'dateRetourPrevue' => $row['dateRetourPrevue']
            ];
        }
        
        return [
            'success' => true,
            'count' => count($amendes),
            'total_montant' => array_sum(array_column($amendes, 'montant')),
            'data' => $amendes,
            'status' => 200
        ];
    }
    
    // GET /api/amendes/{id}
    public function getOne($id) {
        $query = "SELECT A.*, M.nom as membre_nom, M.email, L.titre
                  FROM Amende A
                  JOIN Emprunt E ON A.empruntId = E.id
                  JOIN Membre M ON E.membreId = M.id
                  JOIN Livre L ON E.ISBN = L.ISBN
                  WHERE A.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $amende = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($amende) {
            return [
                'success' => true,
                'data' => [
                    'id' => $amende['id'],
                    'empruntId' => $amende['empruntId'],
                    'membre' => $amende['membre_nom'],
                    'livre' => $amende['titre'],
                    'montant' => (float)$amende['montant'],
                    'dateCreation' => $amende['dateCreation'],
                    'actif' => (bool)$amende['actif']
                ],
                'status' => 200
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Amende non trouvée',
                'status' => 404
            ];
        }
    }
    
    // POST /api/amendes
    public function create() {
        return [
            'success' => false,
            'error' => 'Les amendes sont créées automatiquement lors des retours en retard',
            'status' => 405
        ];
    }
    
    // PUT /api/amendes/{id}
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['action']) && $data['action'] === 'payer') {
            $query = "UPDATE Amende SET actif = FALSE WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Amende marquée comme payée',
                    'status' => 200
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Erreur lors de la mise à jour',
                    'status' => 500
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'Action invalide (utilisez "payer")',
                'status' => 400
            ];
        }
    }
    
    // DELETE /api/amendes/{id}
    public function delete($id) {
        return [
            'success' => false,
            'error' => 'Suppression d\'amendes non autorisée. Utilisez PUT pour marquer comme payée.',
            'status' => 405
        ];
    }
}
