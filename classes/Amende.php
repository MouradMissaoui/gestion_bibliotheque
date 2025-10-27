<!-- classes/Amende.php -->
<?php
class Amende {
    private $conn;
    
    public $id;
    public $empruntId;
    public $montant;
    public $dateCreation;
    public $actif;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer une amende
    public function creer($empruntId, $montant) {
        $query = "INSERT INTO Amende (id, empruntId, montant, dateCreation, actif) 
                  VALUES (:id, :empruntId, :montant, CURDATE(), TRUE)";
        $stmt = $this->conn->prepare($query);
        
        $this->id = 'A' . uniqid();
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":empruntId", $empruntId);
        $stmt->bindParam(":montant", $montant);
        
        return $stmt->execute();
    }

    // Marquer une amende comme payée
    public function marquerPayee() {
        $query = "UPDATE Amende SET actif = FALSE WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    // Récupérer toutes les amendes actives
    public function lireToutesActives() {
        $query = "SELECT A.*, M.nom as membre_nom, M.email 
                  FROM Amende A 
                  JOIN Emprunt E ON A.empruntId = E.id 
                  JOIN Membre M ON E.membreId = M.id 
                  WHERE A.actif = TRUE 
                  ORDER BY A.dateCreation DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>