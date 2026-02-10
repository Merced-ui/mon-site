<?php


require_once 'includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$salle_id = isset($_GET['salle_id']) ? intval($_GET['salle_id']) : 0;
$date = isset($_GET['date']) ? Database::sanitize($_GET['date']) : date('Y-m-d');

// Récupérer les informations de la salle
$query = "SELECT * FROM salles WHERE id = :id AND disponible = 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $salle_id);
$stmt->execute();
$salle = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$salle) {
    set_flash_message("Salle non trouvée ou indisponible.", 'error');
    header('Location: salles.php');
    exit();
}

// Récupérer les disponibilités
$creneaux_disponibles = $database->getCreneauxDisponibles($salle_id, $date);

// Récupérer les réservations pour cette salle à cette date
$query = "SELECT 
            r.heure_debut,
            r.heure_fin,
            r.statut,
            u.prenom,
            u.nom
          FROM reservations r
          JOIN utilisateurs u ON r.utilisateur_id = u.id
          WHERE r.salle_id = :salle_id 
          AND r.date_reservation = :date
          AND r.statut IN ('confirmee', 'en_attente')
          ORDER BY r.heure_debut";
$stmt = $conn->prepare($query);
$stmt->bindParam(':salle_id', $salle_id);
$stmt->bindParam(':date', $date);
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1><i class="fas fa-calendar-check"></i> Disponibilités - <?php echo htmlspecialchars($salle['nom']); ?></h1>
    <p>Date : <?php echo date('d/m/Y', strtotime($date)); ?></p>
</div>

<div class="availability-container">
    <div class="date-selector">
        <form method="GET" action="" class="date-form">
            <input type="hidden" name="salle_id" value="<?php echo $salle_id; ?>">
            <label for="date">Changer de date :</label>
            <input type="date" id="date" name="date" 
                   value="<?php echo htmlspecialchars($date); ?>"
                   min="<?php echo date('Y-m-d'); ?>">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Voir
            </button>
        </form>
    </div>
    
    <div class="availability-grid">
        <div class="reservations-list">
            <h3><i class="fas fa-calendar-times"></i> Réservations existantes</h3>
            <?php if(empty($reservations)): ?>
                <div class="empty-state">
                    <p>Aucune réservation pour cette date.</p>
                </div>
            <?php else: ?>
                <div class="reservations-timeline">
                    <?php foreach($reservations as $reservation): ?>
                        <div class="reservation-slot <?php echo $reservation['statut']; ?>">
                            <div class="slot-time">
                                <?php echo substr($reservation['heure_debut'], 0, 5); ?> - 
                                <?php echo substr($reservation['heure_fin'], 0, 5); ?>
                            </div>
                            <div class="slot-info">
                                <strong><?php echo htmlspecialchars($reservation['prenom'] . ' ' . $reservation['nom']); ?></strong>
                                <span class="badge badge-<?php echo $reservation['statut']; ?>">
                                    <?php echo $reservation['statut'] == 'confirmee' ? 'Confirmée' : 'En attente'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="available-slots">
            <h3><i class="fas fa-calendar-check"></i> Créneaux disponibles</h3>
            <?php if(empty($creneaux_disponibles)): ?>
                <div class="empty-state">
                    <p>Aucun créneau disponible pour cette date.</p>
                </div>
            <?php else: ?>
                <div class="slots-grid">
                    <?php foreach($creneaux_disponibles as $creneau): 
                        $debut = strtotime($creneau['debut']);
                        $fin = strtotime($creneau['fin']);
                        $duree_heures = ($fin - $debut) / 3600;
                        
                        if($duree_heures >= 0.5): // Au moins 30 minutes
                    ?>
                        <div class="available-slot">
                            <div class="slot-header">
                                <h4><?php echo date('H:i', $debut); ?> - <?php echo date('H:i', $fin); ?></h4>
                                <span class="duration"><?php echo number_format($duree_heures, 1); ?>h</span>
                            </div>
                            <div class="slot-body">
                                <p>Durée : <?php echo ($fin - $debut) / 60; ?> minutes</p>
                                <p>Prix : <?php echo number_format($duree_heures * $salle['prix_heure'], 2); ?> €</p>
                                <a href="reservation.php?salle_id=<?php echo $salle_id; ?>&date=<?php echo $date; ?>&debut=<?php echo date('H:i', $debut); ?>&fin=<?php echo date('H:i', $fin); ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-book"></i> Réserver
                                </a>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.availability-container {
    max-width: 1200px;
    margin: 0 auto;
}

.date-form {
    display: flex;
    gap: 10px;
    align-items: center;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: var(--shadow);
    margin-bottom: 30px;
}

.date-form label {
    font-weight: 500;
    color: var(--dark-color);
}

.availability-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.reservations-list, .available-slots {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.reservations-list h3, .available-slots h3 {
    margin-bottom: 20px;
    color: var(--secondary-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.reservations-timeline {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.reservation-slot {
    padding: 15px;
    border-left: 4px solid var(--primary-color);
    background-color: var(--light-color);
    border-radius: 5px;
}

.reservation-slot.confirmee {
    border-left-color: var(--success-color);
}

.reservation-slot.en_attente {
    border-left-color: var(--warning-color);
}

.slot-time {
    font-weight: bold;
    color: var(--dark-color);
    margin-bottom: 5px;
}

.slot-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
}

.badge-confirmee {
    background-color: var(--success-color);
    color: white;
}

.badge-en_attente {
    background-color: var(--warning-color);
    color: white;
}

.slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.available-slot {
    border: 2px solid var(--success-color);
    border-radius: 8px;
    padding: 15px;
    transition: transform 0.3s;
}

.available-slot:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.slot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.slot-header h4 {
    color: var(--success-color);
    margin: 0;
}

.duration {
    background-color: var(--success-color);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
}

.slot-body p {
    margin: 5px 0;
    color: var(--dark-color);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.9rem;
    margin-top: 10px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray-color);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #ddd;
}

@media (max-width: 768px) {
    .availability-grid {
        grid-template-columns: 1fr;
    }
    
    .date-form {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>