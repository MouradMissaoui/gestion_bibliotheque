<?php
// api/controllers/EmpruntsController.php 
require_once '../classes/Emprunt.php';

class EmpruntsController {
    private $db;
    private $emprunt;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->emprunt = new Emprunt($this->db);
    }
    
    // GET /api/emprunts
    public function getAll() {
        $params = $_GET;
        
        $query = "SELECT E.*, L.titre, L.auteur, M.nom as membre_nom 
                  FROM Emprunt E
                  JOIN Livre L ON E.ISBN = L.ISBN
                  JOIN Membre M ON E.membreId = M.id";
        
        // Filtrer par statut
        if (isset($params['statut'])) {
            $query .= " WHERE E.statut = :statut";
        }
        
        $query .= " ORDER BY E.dateEmprunt DESC";
        
        $stmt = $this->db->prepare($query);
        if (isset($params['statut'])) {
            $stmt->bindParam(':statut', $params['statut']);
        }
        $stmt->execute();
        
        $emprunts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $emprunts[] = [
                'id' => $row['id'],
                'ISBN' => $row['ISBN'],
                'livre' => $row['titre'],
                'auteur' => $row['auteur'],
                'membreId' => $row['membreId'],
                'membre' => $row['membre_nom'],
                'dateEmprunt' => $row['dateEmprunt'],
                'dateRetourPrevue' => $row['dateRetourPrevue'],
                'dateRetour' => $row['dateRetour'],
                'statut' => $row['statut']
            ];
        }
        
        return [
            'success' => true,
            'count' => count($emprunts),
            'data' => $emprunts,
            'status' => 200
        ];
    }
    
    // GET /api/emprunts/{id}
    public function getOne($id) {
        $query = "SELECT E.*, L.titre, L.auteur, M.nom as membre_nom, M.email
                  FROM Emprunt E
                  JOIN Livre L ON E.ISBN = L.ISBN
                  JOIN Membre M ON E.membreId = M.id
                  WHERE E.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $emprunt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($emprunt) {
            return [
                'success' => true,
                'data' => $emprunt,
                'status' => 200
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Emprunt non trouvé',
                'status' => 404
            ];
        }
    }
    
    // POST /api/emprunts
    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['membreId']) || !isset($data['ISBN'])) {
            return [
                'success' => false,
                'error' => 'Données manquantes (membreId, ISBN requis)',
                'status' => 400
            ];
        }
        
        // Vérifier la limite du membre
        $query = "SELECT nbEmprunts, maxEmprunts FROM Membre WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $data['membreId']);
        $stmt->execute();
        $membre = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($membre['nbEmprunts'] >= $membre['maxEmprunts']) {
            return [
                'success' => false,
                'error' => 'Limite d\'emprunts atteinte',
                'status' => 400
            ];
        }
        
        if ($this->emprunt->creer($data['membreId'], $data['ISBN'])) {
            return [
                'success' => true,
                'message' => 'Emprunt créé avec succès',
                'data' => [
                    'id' => $this->emprunt->id
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
    
    // PUT /api/emprunts/{id}
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['action'])) {
            return [
                'success' => false,
                'error' => 'Action manquante (retour ou prolonger)',
                'status' => 400
            ];
        }
        
        $this->emprunt->id = $id;
        
        if ($data['action'] === 'retour') {
            if ($this->emprunt->terminer()) {
                return [
                    'success' => true,
                    'message' => 'Retour enregistré avec succès',
                    'status' => 200
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Erreur lors du retour',
                    'status' => 500
                ];
            }
        } elseif ($data['action'] === 'prolonger') {
            $jours = $data['jours'] ?? 7;
            if ($this->emprunt->prolongerJours($jours)) {
                return [
                    'success' => true,
                    'message' => "Emprunt prolongé de $jours jours",
                    'status' => 200
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Erreur lors de la prolongation',
                    'status' => 500
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'Action invalide',
                'status' => 400
            ];
        }
    }
    
    // DELETE /api/emprunts/{id}
    public function delete($id) {
        return [
            'success' => false,
            'error' => 'Suppression d\'emprunts non autorisée. Utilisez le retour.',
            'status' => 405
        ];
    }
}
?>