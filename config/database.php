<!-- config/database.php -->
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
class Database {
    private $host = "localhost";
    private $db_name = "gestion_bibliotheque";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $e) {
            echo "Erreur de connexion: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?>