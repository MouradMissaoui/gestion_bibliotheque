<!-- classes/Emprunt.php -->
<?php
class Emprunt {
    private $conn;
    
    public $id;
    public $ISBN;
    public $membreId;
    public $dateEmprunt;
    public $dateRetourPrevue;
    public $dateRetour;
    public $statut;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouvel emprunt
    public function creer($membreId, $ISBN) {
        $query = "INSERT INTO Emprunt (id, ISBN, membreId, dateEmprunt, dateRetourPrevue, statut) 
                  VALUES (:id, :ISBN, :membreId, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'EN_COURS')";
        $stmt = $this->conn->prepare($query);
        
        $this->id = 'E' . uniqid();
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":ISBN", $ISBN);
        $stmt->bindParam(":membreId", $membreId);
        
        return $stmt->execute();
    }

    // Récupérer tous les emprunts
    public function lireTous() {
        $query = "SELECT E.*, L.titre, L.auteur, M.nom as membre_nom 
                  FROM Emprunt E 
                  JOIN Livre L ON E.ISBN = L.ISBN 
                  JOIN Membre M ON E.membreId = M.id 
                  ORDER BY E.dateEmprunt DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Vérifier si l'emprunt est en retard
// Vérifier si l'emprunt est en retard
    public function estEnRetard() {
        $query = "SELECT DATEDIFF(CURDATE(), dateRetourPrevue) as joursRetard 
                FROM Emprunt WHERE id = :id AND statut = 'EN_COURS'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérifier que le résultat existe
        if (!$row) {
            return false;
        }
        
        return $row['joursRetard'] > 0;
    }

    // Calculer l'amende (1€ par jour de retard)
// Calculer l'amende (1€ par jour de retard)
    public function calculerAmende() {
        $query = "SELECT GREATEST(0, DATEDIFF(CURDATE(), dateRetourPrevue)) as joursRetard 
                FROM Emprunt WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérifier que le résultat existe
        if (!$row) {
            return 0;
        }
        
        return $row['joursRetard'] * 1.0; // 1€ par jour
    }

    // Prolonger l'emprunt
    public function prolongerJours($jours) {
        $query = "UPDATE Emprunt SET dateRetourPrevue = DATE_ADD(dateRetourPrevue, INTERVAL :jours DAY) 
                  WHERE id = :id AND statut = 'EN_COURS'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":jours", $jours);
        return $stmt->execute();
    }

    // Terminer l'emprunt (retour)
    public function terminer() {
        $query = "UPDATE Emprunt SET dateRetour = CURDATE(), statut = 'TERMINE' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        if ($stmt->execute()) {
            // Créer une amende si nécessaire
            if ($this->estEnRetard()) {
                $amende = new Amende($this->conn);
                $montant = $this->calculerAmende();
                $amende->creer($this->id, $montant);
            }
            return true;
        }
        return false;
    }
}
?>