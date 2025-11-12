<?php
// api/controllers/ReservationsController.php
class ReservationsController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // GET /api/reservations
    public function getAll() {
        $params = $_GET;
        
        $query = "SELECT R.*, L.titre, L.auteur, M.nom as membre_nom, M.email
                  FROM Reservation R
                  JOIN Livre L ON R.ISBN = L.ISBN
                  JOIN Membre M ON R.membreId = M.id";
        
        if (isset($params['actif'])) {
            $query .= " WHERE R.actif = :actif";
        }
        
        $query .= " ORDER BY R.dateReservation DESC";
        
        $stmt = $this->db->prepare($query);
        if (isset($params['actif'])) {
            $actif = $params['actif'] === 'true' ? 1 : 0;
            $stmt->bindParam(':actif', $actif);
        }
        $stmt->execute();
        
        $reservations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservations[] = [
                'id' => $row['id'],
                'ISBN' => $row['ISBN'],
                'livre' => $row['titre'],
                'auteur' => $row['auteur'],
                'membreId' => $row['membreId'],
                'membre' => $row['membre_nom'],
                'email' => $row['email'],
                'dateReservation' => $row['dateReservation'],
                'statut' => $row['statut'],
                'actif' => (bool)$row['actif']
            ];
        }
        
        return [
            'success' => true,
            'count' => count($reservations),
            'data' => $reservations,
            'status' => 200
        ];
    }
    
    // GET /api/reservations/{id}
    public function getOne($id) {
        $query = "SELECT R.*, L.titre, L.auteur, M.nom as membre_nom, M.email
                  FROM Reservation R
                  JOIN Livre L ON R.ISBN = L.ISBN
                  JOIN Membre M ON R.membreId = M.id
                  WHERE R.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation) {
            return [
                'success' => true,
                'data' => [
                    'id' => $reservation['id'],
                    'ISBN' => $reservation['ISBN'],
                    'livre' => $reservation['titre'],
                    'membreId' => $reservation['membreId'],
                    'membre' => $reservation['membre_nom'],
                    'dateReservation' => $reservation['dateReservation'],
                    'statut' => $reservation['statut'],
                    'actif' => (bool)$reservation['actif']
                ],
                'status' => 200
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Réservation non trouvée',
                'status' => 404
            ];
        }
    }
    
    // POST /api/reservations
    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['membreId']) || !isset($data['ISBN'])) {
            return [
                'success' => false,
                'error' => 'Données manquantes (membreId, ISBN requis)',
                'status' => 400
            ];
        }
        
        // Vérifier que le livre est emprunté
        $queryCheck = "SELECT disponible FROM Livre WHERE ISBN = :isbn";
        $stmtCheck = $this->db->prepare($queryCheck);
        $stmtCheck->bindParam(':isbn', $data['ISBN']);
        $stmtCheck->execute();
        $livre = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($livre['disponible']) {
            return [
                'success' => false,
                'error' => 'Le livre est disponible, aucune réservation nécessaire',
                'status' => 400
            ];
        }
        
        // Créer la réservation
        $query = "INSERT INTO Reservation (id, ISBN, membreId, dateReservation, statut, actif)
                  VALUES (:id, :isbn, :membreId, CURDATE(), 'EN_ATTENTE', TRUE)";
        $stmt = $this->db->prepare($query);
        
        $id = 'R' . uniqid();
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':isbn', $data['ISBN']);
        $stmt->bindParam(':membreId', $data['membreId']);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'data' => ['id' => $id],
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
    
    // PUT /api/reservations/{id}
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['action'])) {
            return [
                'success' => false,
                'error' => 'Action manquante (confirmer, annuler, terminer)',
                'status' => 400
            ];
        }
        
        switch ($data['action']) {
            case 'confirmer':
                $query = "UPDATE Reservation SET statut = 'CONFIRMEE' WHERE id = :id";
                break;
            case 'annuler':
                $query = "UPDATE Reservation SET statut = 'ANNULEE', actif = FALSE WHERE id = :id";
                break;
            case 'terminer':
                $query = "UPDATE Reservation SET statut = 'TERMINEE', actif = FALSE WHERE id = :id";
                break;
            default:
                return [
                    'success' => false,
                    'error' => 'Action invalide',
                    'status' => 400
                ];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Réservation mise à jour avec succès',
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
    
    // DELETE /api/reservations/{id}
    public function delete($id) {
        $query = "DELETE FROM Reservation WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Réservation supprimée avec succès',
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