
<!-- classes/Livre.php -->
<?php
class Livre {
    private $conn;
    
    public $ISBN;
    public $titre;
    public $auteur;
    public $annee;
    public $disponible;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer tous les livres
    public function lireTous() {
        $query = "SELECT * FROM Livre ORDER BY titre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Rechercher des livres
    public function rechercher($terme) {
        $query = "SELECT * FROM Livre WHERE titre LIKE :terme OR auteur LIKE :terme OR ISBN LIKE :terme";
        $stmt = $this->conn->prepare($query);
        $terme = "%{$terme}%";
        $stmt->bindParam(":terme", $terme);
        $stmt->execute();
        return $stmt;
    }

    // Ajouter un livre
    public function creer() {
        $query = "INSERT INTO Livre (ISBN, titre, auteur, annee, disponible) VALUES (:ISBN, :titre, :auteur, :annee, :disponible)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":ISBN", $this->ISBN);
        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":auteur", $this->auteur);
        $stmt->bindParam(":annee", $this->annee);
        $stmt->bindParam(":disponible", $this->disponible);
        
        return $stmt->execute();
    }

    // Vérifier si le livre est disponible
    public function estDisponible() {
        $query = "SELECT disponible FROM Livre WHERE ISBN = :ISBN";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ISBN", $this->ISBN);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['disponible'] == 1;
    }

    // Réserver un livre
    public function reserver() {
        $query = "UPDATE Livre SET disponible = FALSE WHERE ISBN = :ISBN";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ISBN", $this->ISBN);
        return $stmt->execute();
    }

    // Retourner un livre
    public function retourner() {
        $query = "UPDATE Livre SET disponible = TRUE WHERE ISBN = :ISBN";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ISBN", $this->ISBN);
        return $stmt->execute();
    }

    // Supprimer un livre
    public function supprimer() {
        $query = "DELETE FROM Livre WHERE ISBN = :ISBN";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ISBN", $this->ISBN);
        return $stmt->execute();
    }
}


?>