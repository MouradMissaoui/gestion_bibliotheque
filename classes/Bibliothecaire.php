
<!-- classes/Bibliothecaire.php -->
<?php
class Bibliothecaire {
    private $conn;
    
    public $matricule;
    public $nom;
    public $email;
    public $droitsAdmin;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Authentification
    public function seConnecter($email) {
        $query = "SELECT * FROM Bibliothecaire WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Inscrire un membre
    public function inscrireMembre($nom, $email) {
        $membre = new Membre($this->conn);
        $membre->nom = $nom;
        $membre->email = $email;
        return $membre->creer();
    }

    // Ajouter un livre
    public function ajouterLivre($ISBN, $titre, $auteur, $annee) {
        $livre = new Livre($this->conn);
        $livre->ISBN = $ISBN;
        $livre->titre = $titre;
        $livre->auteur = $auteur;
        $livre->annee = $annee;
        $livre->disponible = true;
        return $livre->creer();
    }

    // Supprimer un livre
    public function supprimerLivre($ISBN) {
        $livre = new Livre($this->conn);
        $livre->ISBN = $ISBN;
        return $livre->supprimer();
    }

    // Générer un rapport
    public function genererRapport() {
        $rapport = [];
        
        $query = "SELECT COUNT(*) as total FROM Livre";
        $stmt = $this->conn->query($query);
        $rapport['total_livres'] = $stmt->fetch()['total'];
        
        $query = "SELECT COUNT(*) as total FROM Membre";
        $stmt = $this->conn->query($query);
        $rapport['total_membres'] = $stmt->fetch()['total'];
        
        $query = "SELECT COUNT(*) as total FROM Emprunt WHERE statut = 'EN_COURS'";
        $stmt = $this->conn->query($query);
        $rapport['emprunts_actifs'] = $stmt->fetch()['total'];
        
        $query = "SELECT SUM(montant) as total FROM Amende WHERE actif = TRUE";
        $stmt = $this->conn->query($query);
        $rapport['amendes_totales'] = $stmt->fetch()['total'] ?? 0;
        
        return $rapport;
    }
}
?>