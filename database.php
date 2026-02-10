<?php
class Database {
    private $host = "localhost";
    private $db_name = "reservation_salles";
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
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Erreur de connexion à la base de données");
        }

        return $this->conn;
    }

    public static function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    // ... autres méthodes existantes ...
    
    /**
     * Vérifie si une salle est disponible pour un créneau donné
     * 
     * @param int $salle_id ID de la salle
     * @param string $date Date au format YYYY-MM-DD
     * @param string $heure_debut Heure de début au format HH:MM:SS
     * @param string $heure_fin Heure de fin au format HH:MM:SS
     * @param int $exclude_reservation_id ID de réservation à exclure (pour les modifications)
     * @return bool True si disponible, false sinon
     */
    public function checkDisponibilite($salle_id, $date, $heure_debut, $heure_fin, $exclude_reservation_id = 0) {
        $conn = $this->getConnection();
        
        $query = "SELECT COUNT(*) as count FROM reservations 
                 WHERE salle_id = :salle_id 
                 AND date_reservation = :date 
                 AND statut IN ('confirmee', 'en_attente')
                 AND id != :exclude_id
                 AND NOT (
                     heure_fin <= :heure_debut OR 
                     heure_debut >= :heure_fin
                 )";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':salle_id', $salle_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':heure_debut', $heure_debut);
        $stmt->bindParam(':heure_fin', $heure_fin);
        $stmt->bindParam(':exclude_id', $exclude_reservation_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] == 0;
    }
    
    /**
     * Récupère les créneaux disponibles pour une salle à une date donnée
     * 
     * @param int $salle_id ID de la salle
     * @param string $date Date au format YYYY-MM-DD
     * @return array Tableau des créneaux disponibles
     */
    public function getCreneauxDisponibles($salle_id, $date) {
        $conn = $this->getConnection();
        
        // Heures d'ouverture (8h-20h)
        $creneaux = [];
        $heure_ouverture = '08:00:00';
        $heure_fermeture = '20:00:00';
        
        // Récupérer les réservations existantes
        $query = "SELECT heure_debut, heure_fin FROM reservations 
                 WHERE salle_id = :salle_id 
                 AND date_reservation = :date 
                 AND statut IN ('confirmee', 'en_attente')
                 ORDER BY heure_debut";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':salle_id', $salle_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Générer les créneaux disponibles
        $heure_courante = $heure_ouverture;
        
        foreach($reservations as $reservation) {
            if($heure_courante < $reservation['heure_debut']) {
                $creneaux[] = [
                    'debut' => $heure_courante,
                    'fin' => $reservation['heure_debut']
                ];
            }
            $heure_courante = max($heure_courante, $reservation['heure_fin']);
        }
        
        // Dernier créneau de la journée
        if($heure_courante < $heure_fermeture) {
            $creneaux[] = [
                'debut' => $heure_courante,
                'fin' => $heure_fermeture
            ];
        }
        
        return $creneaux;
    }
}

