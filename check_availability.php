<?php
// Démarrer la session
session_start();

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Définir le type de contenu
header('Content-Type: application/json');

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'available' => false,
        'error' => 'Méthode non autorisée. Utilisez POST.'
    ]);
    exit();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'available' => false,
        'error' => 'Vous devez être connecté pour vérifier la disponibilité.',
        'redirect' => 'login.php'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Récupérer et valider les données
    $salle_id = isset($_POST['salle_id']) ? intval($_POST['salle_id']) : 0;
    $date_reservation = isset($_POST['date_reservation']) ? Database::sanitize($_POST['date_reservation']) : '';
    $heure_debut = isset($_POST['heure_debut']) ? Database::sanitize($_POST['heure_debut']) : '';
    $heure_fin = isset($_POST['heure_fin']) ? Database::sanitize($_POST['heure_fin']) : '';

    // Validation des données
    if ($salle_id <= 0 || empty($date_reservation) || empty($heure_debut) || empty($heure_fin)) {
        echo json_encode([
            'available' => false,
            'error' => 'Données manquantes ou invalides.'
        ]);
        exit();
    }

    // Vérifier si l'heure de fin est après l'heure de début
    if ($heure_debut >= $heure_fin) {
        echo json_encode([
            'available' => false,
            'error' => 'L\'heure de fin doit être après l\'heure de début.'
        ]);
        exit();
    }

    // Vérifier la durée minimale (30 minutes)
    $debut_timestamp = strtotime($heure_debut);
    $fin_timestamp = strtotime($heure_fin);
    $duree_minutes = ($fin_timestamp - $debut_timestamp) / 60;
    
    if ($duree_minutes < 30) {
        echo json_encode([
            'available' => false,
            'error' => 'La réservation doit durer au moins 30 minutes.'
        ]);
        exit();
    }

    // VÉRIFICATION DES CONFLITS - LOGIQUE CORRECTE
    // On cherche les réservations qui se chevauchent avec le créneau demandé
    $query = "SELECT 
                r.id,
                r.date_reservation,
                r.heure_debut,
                r.heure_fin,
                r.statut,
                s.nom as salle_nom,
                u.prenom,
                u.nom
              FROM reservations r
              JOIN salles s ON r.salle_id = s.id
              JOIN utilisateurs u ON r.utilisateur_id = u.id
              WHERE r.salle_id = :salle_id 
              AND r.date_reservation = :date_reservation 
              AND r.statut IN ('confirmee', 'en_attente')
              AND (
                  (r.heure_debut < :heure_fin AND r.heure_fin > :heure_debut)
              )";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':salle_id', $salle_id, PDO::PARAM_INT);
    $stmt->bindParam(':date_reservation', $date_reservation);
    $stmt->bindParam(':heure_debut', $heure_debut);
    $stmt->bindParam(':heure_fin', $heure_fin);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de l\'exécution de la requête.');
    }
    
    $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($conflicts) > 0) {
        echo json_encode([
            'available' => false,
            'conflicts' => $conflicts,
            'message' => 'La salle est déjà réservée pour ce créneau horaire.',
            'conflict_count' => count($conflicts)
        ]);
    } else {
        echo json_encode([
            'available' => true,
            'message' => 'Créneau disponible pour la réservation.',
            'salle_id' => $salle_id,
            'date' => $date_reservation,
            'debut' => $heure_debut,
            'fin' => $heure_fin
        ]);
    }

} catch (Exception $e) {
    // Journaliser l'erreur
    error_log('Erreur check_availability.php: ' . $e->getMessage());
    
    // Retourner une réponse d'erreur
    echo json_encode([
        'available' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage(),
        'debug' => 'Veuillez contacter l\'administrateur.'
    ]);
}
?>