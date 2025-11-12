<?php
// api/controllers/StatsController.php
class StatsController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // GET /api/stats
    public function getAll() {
        $stats = [
            'total_livres' => 0,
            'livres_disponibles' => 0,
            'total_membres' => 0,
            'emprunts_en_cours' => 0,
            'emprunts_en_retard' => 0,
            'reservations_actives' => 0,
            'amendes_totales' => 0,
            'emprunts_ce_mois' => 0
        ];
        
        // Total livres
        $query = "SELECT COUNT(*) as total, SUM(disponible) as disponibles FROM Livre";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
        $stats['total_livres'] = (int)$result['total'];
        $stats['livres_disponibles'] = (int)$result['disponibles'];
        
        // Total membres
        $query = "SELECT COUNT(*) as total FROM Membre";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
        $stats['total_membres'] = (int)$result['total'];
        
        // Emprunts en cours
        $query = "SELECT COUNT(*) as total FROM Emprunt WHERE statut = 'EN_COURS'";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
        $stats['emprunts_en_cours'] = (int)$result['total'];
        
        // Emprunts en retard
        $query = "SELECT COUNT(*) as total FROM Emprunt 
                  WHERE statut = 'EN_COURS' AND dateRetourPrevue < CURDATE()";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
        $stats['emprunts_en_retard'] = (int)$result['total'];
        
        // Réservations actives
        $query = "SELECT COUNT(*) as total FROM Reservation WHERE actif = TRUE";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
        $stats['reservations_actives'] = (int)$result['total'];
        
        // Amendes totales
        $query = "SELECT COALESCE(SUM(montant), 0) as total FROM Amende WHERE actif = TRUE";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
        $stats['amendes_totales'] = (float)$result['total'];
        
        // Emprunts ce mois
        $query = "SELECT COUNT(*) as total FROM Emprunt 
                  WHERE MONTH(dateEmprunt) = MONTH(CURDATE()) 
                  AND YEAR(dateEmprunt) = YEAR(CURDATE())";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
        $stats['emprunts_ce_mois'] = (int)$result['total'];
        
        return [
            'success' => true,
            'data' => $stats,
            'status' => 200
        ];
    }
    
    // GET /api/stats/top-livres ou /api/stats/top-membres
    public function getOne($type) {
        if ($type === 'top-livres') {
            $query = "SELECT L.ISBN, L.titre, L.auteur, COUNT(E.id) as nb_emprunts
                      FROM Livre L
                      LEFT JOIN Emprunt E ON L.ISBN = E.ISBN
                      GROUP BY L.ISBN, L.titre, L.auteur
                      HAVING COUNT(E.id) > 0
                      ORDER BY nb_emprunts DESC
                      LIMIT 5";
            $stmt = $this->db->query($query);
            
            $livres = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $livres[] = [
                    'ISBN' => $row['ISBN'],
                    'titre' => $row['titre'],
                    'auteur' => $row['auteur'],
                    'nb_emprunts' => (int)$row['nb_emprunts']
                ];
            }
            
            return [
                'success' => true,
                'data' => $livres,
                'status' => 200
            ];
            
        } elseif ($type === 'top-membres') {
            $query = "SELECT M.id, M.nom, M.email, COUNT(E.id) as nb_emprunts
                      FROM Membre M
                      LEFT JOIN Emprunt E ON M.id = E.membreId
                      GROUP BY M.id, M.nom, M.email
                      HAVING COUNT(E.id) > 0
                      ORDER BY nb_emprunts DESC
                      LIMIT 5";
            $stmt = $this->db->query($query);
            
            $membres = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $membres[] = [
                    'id' => $row['id'],
                    'nom' => $row['nom'],
                    'email' => $row['email'],
                    'nb_emprunts' => (int)$row['nb_emprunts']
                ];
            }
            
            return [
                'success' => true,
                'data' => $membres,
                'status' => 200
            ];
            
        } else {
            return [
                'success' => false,
                'error' => 'Type de statistique non trouvé',
                'status' => 404
            ];
        }
    }
    
    public function create() {
        return [
            'success' => false,
            'error' => 'Méthode non autorisée',
            'status' => 405
        ];
    }
    
    public function update($id) {
        return [
            'success' => false,
            'error' => 'Méthode non autorisée',
            'status' => 405
        ];
    }
    
    public function delete($id) {
        return [
            'success' => false,
            'error' => 'Méthode non autorisée',
            'status' => 405
        ];
    }
}
?>