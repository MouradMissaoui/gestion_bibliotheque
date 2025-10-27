-- Création de la base de données
CREATE DATABASE IF NOT EXISTS gestion_bibliotheque CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_bibliotheque;

-- Table Livre
CREATE TABLE Livre (
    ISBN VARCHAR(20) PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    auteur VARCHAR(255) NOT NULL,
    annee INT NOT NULL,
    disponible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table Membre
CREATE TABLE Membre (
    id VARCHAR(50) PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    nbEmprunts INT DEFAULT 0,
    maxEmprunts INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table Bibliothecaire
CREATE TABLE Bibliothecaire (
    matricule VARCHAR(50) PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    droitsAdmin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table Emprunt
CREATE TABLE Emprunt (
    id VARCHAR(50) PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    membreId VARCHAR(50) NOT NULL,
    dateEmprunt DATE NOT NULL,
    dateRetourPrevue DATE NOT NULL,
    dateRetour DATE NULL,
    statut VARCHAR(50) DEFAULT 'EN_COURS',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ISBN) REFERENCES Livre(ISBN) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (membreId) REFERENCES Membre(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_dates CHECK (dateRetourPrevue >= dateEmprunt),
    CONSTRAINT chk_dateRetour CHECK (dateRetour IS NULL OR dateRetour >= dateEmprunt)
) ENGINE=InnoDB;

-- Table Amende
CREATE TABLE Amende (
    id VARCHAR(50) PRIMARY KEY,
    empruntId VARCHAR(50) NOT NULL UNIQUE,
    montant DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    dateCreation DATE NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empruntId) REFERENCES Emprunt(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_montant CHECK (montant >= 0)
) ENGINE=InnoDB;

-- Table Reservation
CREATE TABLE Reservation (
    id VARCHAR(50) PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    membreId VARCHAR(50) NOT NULL,
    dateReservation DATE NOT NULL,
    statut VARCHAR(50) DEFAULT 'EN_ATTENTE',
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ISBN) REFERENCES Livre(ISBN) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (membreId) REFERENCES Membre(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Index pour optimiser les recherches
CREATE INDEX idx_emprunt_membre ON Emprunt(membreId);
CREATE INDEX idx_emprunt_livre ON Emprunt(ISBN);
CREATE INDEX idx_emprunt_statut ON Emprunt(statut);
CREATE INDEX idx_reservation_membre ON Reservation(membreId);
CREATE INDEX idx_reservation_livre ON Reservation(ISBN);
CREATE INDEX idx_amende_actif ON Amende(actif);

-- Trigger pour mettre à jour nbEmprunts automatiquement
DELIMITER //

CREATE TRIGGER after_emprunt_insert
AFTER INSERT ON Emprunt
FOR EACH ROW
BEGIN
    UPDATE Membre 
    SET nbEmprunts = nbEmprunts + 1 
    WHERE id = NEW.membreId;
    
    UPDATE Livre 
    SET disponible = FALSE 
    WHERE ISBN = NEW.ISBN;
END//

CREATE TRIGGER after_emprunt_return
AFTER UPDATE ON Emprunt
FOR EACH ROW
BEGIN
    IF NEW.dateRetour IS NOT NULL AND OLD.dateRetour IS NULL THEN
        UPDATE Membre 
        SET nbEmprunts = nbEmprunts - 1 
        WHERE id = NEW.membreId;
        
        UPDATE Livre 
        SET disponible = TRUE 
        WHERE ISBN = NEW.ISBN;
    END IF;
END//

DELIMITER ;

-- Données de test
INSERT INTO Bibliothecaire (matricule, nom, email, droitsAdmin) VALUES
('B001', 'Admin Bibliothèque', 'admin@biblio.fr', TRUE),
('B002', 'Jean Dupont', 'jean.dupont@biblio.fr', FALSE);

INSERT INTO Livre (ISBN, titre, auteur, annee, disponible) VALUES
('978-2-1234-5680-3', 'Le Petit Prince', 'Antoine de Saint-Exupéry', 1943, TRUE),
('978-2-0705-6789-1', 'Les Misérables', 'Victor Hugo', 1862, TRUE),
('978-2-2253-0648-7', 'Harry Potter à l''école des sorciers', 'J.K. Rowling', 1997, TRUE);

INSERT INTO Membre (id, nom, email, nbEmprunts, maxEmprunts) VALUES
('M001', 'Marie Martin', 'marie.martin@email.fr', 0, 3),
('M002', 'Pierre Dubois', 'pierre.dubois@email.fr', 0, 3);