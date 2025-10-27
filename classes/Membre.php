<!-- classes/Membre.php -->
<?php
class Membre {
    private $conn;
    
    public $id;
    public $nom;
    public $email;
    public $nbEmprunts;
    public $maxEmprunts;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer tous les membres
    public function lireTous() {
        $query = "SELECT * FROM Membre ORDER BY nom";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Créer un nouveau membre
    public function creer() {
        $query = "INSERT INTO Membre (id, nom, email, nbEmprunts, maxEmprunts) VALUES (:id, :nom, :email, 0, 3)";
        $stmt = $this->conn->prepare($query);
        
        $this->id = 'M' . uniqid();
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":email", $this->email);
        
        return $stmt->execute();
    }

    // Vérifier si le membre peut emprunter
    public function peutEmprunter() {
        $query = "SELECT nbEmprunts, maxEmprunts FROM Membre WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['nbEmprunts'] < $row['maxEmprunts'];
    }

    // Emprunter un livre
    public function emprunterLivre($ISBN) {
        if (!$this->peutEmprunter()) {
            return false;
        }
        
        $emprunt = new Emprunt($this->conn);
        return $emprunt->creer($this->id, $ISBN);
    }

    // Obtenir les emprunts actifs d'un membre
    public function getEmpruntsActifs() {
        $query = "SELECT E.*, L.titre, L.auteur 
                  FROM Emprunt E 
                  JOIN Livre L ON E.ISBN = L.ISBN 
                  WHERE E.membreId = :membreId AND E.statut = 'EN_COURS'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":membreId", $this->id);
        $stmt->execute();
        return $stmt;
    }
}
?>