<?php


require_once 'includes/header.php';


if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Récupérer les salles
$query = "SELECT * FROM salles WHERE disponible = 1 ORDER BY type, nom";
$stmt = $conn->prepare($query);
$stmt->execute();
$salles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtrer par type si spécifié
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
?>

<div class="page-header">
    <h1><i class="fas fa-door-open"></i> Nos Salles</h1>
    <p>Réservez la salle parfaite pour vos besoins</p>
</div>

<div class="filter-container">
    <div class="filter-buttons">
        <a href="?type=all" class="filter-btn <?php echo $type_filter == 'all' ? 'active' : ''; ?>">
            Toutes les salles
        </a>
        <a href="?type=cours" class="filter-btn <?php echo $type_filter == 'cours' ? 'active' : ''; ?>">
            Salles de cours
        </a>
        <a href="?type=reunion" class="filter-btn <?php echo $type_filter == 'reunion' ? 'active' : ''; ?>">
            Salles de réunion
        </a>
    </div>
</div>

<div class="salles-grid">
    <?php foreach($salles as $salle): 
        if($type_filter != 'all' && $salle['type'] != $type_filter) continue;
    ?>
        <div class="salle-card">
            <div class="salle-header">
                <h3><?php echo htmlspecialchars($salle['nom']); ?></h3>
                <span class="salle-type <?php echo $salle['type']; ?>">
                    <?php echo $salle['type'] == 'cours' ? 'Cours' : 'Réunion'; ?>
                </span>
            </div>
            
            <div class="salle-body">
                <p class="salle-description"><?php echo htmlspecialchars($salle['description']); ?></p>
                
                <div class="salle-info">
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span>Capacité: <?php echo $salle['capacite']; ?> personnes</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo number_format($salle['prix_heure'], 2); ?> €/heure</span>
                    </div>
                </div>
                
                <div class="salle-actions">
    <a href="disponibilite.php?salle_id=<?php echo $salle['id']; ?>" class="btn btn-info">
        <i class="fas fa-calendar-check"></i> Voir disponibilités
    </a>
    <a href="reservation.php?salle_id=<?php echo $salle['id']; ?>" class="btn btn-primary">
        <i class="fas fa-calendar-plus"></i> Réserver
    </a>
</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>