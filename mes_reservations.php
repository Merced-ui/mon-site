<?php

require_once 'includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Annuler une réservation
if(isset($_GET['annuler']) && isset($_GET['id'])) {
    $reservation_id = intval($_GET['id']);
    
    // Vérifier que la réservation appartient à l'utilisateur
    $query = "SELECT * FROM reservations WHERE id = :id AND utilisateur_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $reservation_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if($stmt->rowCount() == 1) {
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérifier si la réservation est déjà passée
        $date_reservation = new DateTime($reservation['date_reservation']);
        $aujourdhui = new DateTime();
        
        if($date_reservation < $aujourdhui) {
            $_SESSION['message'] = "Impossible d'annuler une réservation passée.";
            $_SESSION['message_type'] = 'error';
        } elseif($reservation['statut'] == 'annulee') {
            $_SESSION['message'] = "Cette réservation est déjà annulée.";
            $_SESSION['message_type'] = 'warning';
        } else {
            $updateQuery = "UPDATE reservations SET statut = 'annulee' WHERE id = :id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':id', $reservation_id);
            
            if($updateStmt->execute()) {
                $_SESSION['message'] = "Réservation annulée avec succès.";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Erreur lors de l'annulation de la réservation.";
                $_SESSION['message_type'] = 'error';
            }
        }
    } else {
        $_SESSION['message'] = "Réservation non trouvée.";
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: mes_reservations.php');
    exit();
}

// Récupérer les réservations de l'utilisateur
$query = "SELECT r.*, s.nom as salle_nom, s.type as salle_type, s.prix_heure 
          FROM reservations r 
          JOIN salles s ON r.salle_id = s.id 
          WHERE r.utilisateur_id = :user_id 
          ORDER BY r.date_reservation DESC, r.heure_debut DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1><i class="fas fa-list"></i> Mes Réservations</h1>
</div>

<div class="reservations-container">
    <?php if(empty($reservations)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times fa-3x"></i>
            <h3>Aucune réservation</h3>
            <p>Vous n'avez pas encore effectué de réservation.</p>
            <a href="salles.php" class="btn btn-primary">
                <i class="fas fa-door-open"></i> Voir les salles disponibles
            </a>
        </div>
    <?php else: ?>
        <div class="reservations-list">
            <?php foreach($reservations as $reservation): 
                $date_reservation = new DateTime($reservation['date_reservation']);
                $aujourdhui = new DateTime();
                $peut_annuler = $date_reservation > $aujourdhui && $reservation['statut'] == 'confirmee';
            ?>
                <div class="reservation-card <?php echo $reservation['statut']; ?>">
                    <div class="reservation-header">
                        <h3><?php echo htmlspecialchars($reservation['salle_nom']); ?></h3>
                        <span class="reservation-status status-<?php echo $reservation['statut']; ?>">
                            <?php 
                                switch($reservation['statut']) {
                                    case 'confirmee': echo 'Confirmée'; break;
                                    case 'annulee': echo 'Annulée'; break;
                                    case 'en_attente': echo 'En attente'; break;
                                }
                            ?>
                        </span>
                    </div>
                    
                    <div class="reservation-body">
                        <div class="reservation-info">
                            <div class="info-item">
                                <i class="fas fa-calendar-day"></i>
                                <span><?php echo $date_reservation->format('d/m/Y'); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo substr($reservation['heure_debut'], 0, 5); ?> - <?php echo substr($reservation['heure_fin'], 0, 5); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-euro-sign"></i>
                                <span><?php echo number_format($reservation['montant_paye'], 2); ?> €</span>
                            </div>
                        </div>
                        
                        <div class="reservation-actions">
                            <?php if($peut_annuler): ?>
                                <a href="?annuler=1&id=<?php echo $reservation['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?');">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
