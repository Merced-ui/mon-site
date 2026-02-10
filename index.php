<?php


require_once 'includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Récupérer les statistiques
$stats = [];
$query = "SELECT COUNT(*) as total FROM salles WHERE disponible = 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['salles'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

if(isset($_SESSION['user_id'])) {
    $query = "SELECT COUNT(*) as total FROM reservations WHERE utilisateur_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $stats['mes_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Récupérer les prochaines réservations
$prochaines_reservations = [];
if(isset($_SESSION['user_id'])) {
    $query = "SELECT r.*, s.nom as salle_nom 
              FROM reservations r 
              JOIN salles s ON r.salle_id = s.id 
              WHERE r.utilisateur_id = :user_id 
              AND r.date_reservation >= CURDATE() 
              AND r.statut = 'confirmee'
              ORDER BY r.date_reservation, r.heure_debut 
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $prochaines_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Réservation de Salles Intelligente</h1>
        <p>Gérez facilement vos réservations de salles de cours et de réunion</p>
        
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </a>
                <a href="login.php" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </a>
            </div>
        <?php else: ?>
            <div class="hero-buttons">
                <a href="salles.php" class="btn btn-primary">
                    <i class="fas fa-door-open"></i> Voir les salles
                </a>
                <a href="reservation.php" class="btn btn-success">
                    <i class="fas fa-calendar-plus"></i> Nouvelle réservation
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-door-open"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['salles']; ?></h3>
            <p>Salles disponibles</p>
        </div>
    </div>
    
    <?php if(isset($_SESSION['user_id'])): ?>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['mes_reservations']; ?></h3>
                <p>Mes réservations</p>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3>24/7</h3>
            <p>Réservation en ligne</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-content">
            <h3>Transparent</h3>
            <p>Pas de frais cachés</p>
        </div>
    </div>
</div>

<?php if(isset($_SESSION['user_id']) && !empty($prochaines_reservations)): ?>
    <div class="upcoming-reservations">
        <h2><i class="fas fa-calendar-alt"></i> Mes prochaines réservations</h2>
        <div class="reservations-list">
            <?php foreach($prochaines_reservations as $reservation): ?>
                <div class="upcoming-card">
                    <div class="upcoming-header">
                        <h4><?php echo htmlspecialchars($reservation['salle_nom']); ?></h4>
                        <span class="date-badge">
                            <?php echo date('d/m', strtotime($reservation['date_reservation'])); ?>
                        </span>
                    </div>
                    <div class="upcoming-body">
                        <p>
                            <i class="fas fa-clock"></i>
                            <?php echo substr($reservation['heure_debut'], 0, 5); ?> - 
                            <?php echo substr($reservation['heure_fin'], 0, 5); ?>
                        </p>
                        <a href="mes_reservations.php" class="btn btn-sm btn-outline">
                            Voir détails
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="features-section">
    <h2>Pourquoi choisir notre plateforme ?</h2>
    <div class="features-grid">
        <div class="feature-card">
            <i class="fas fa-bolt"></i>
            <h3>Rapide et simple</h3>
            <p>Réservez une salle en quelques clics seulement</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-shield-alt"></i>
            <h3>Sécurisé</h3>
            <p>Vos données et paiements sont protégés</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-sync-alt"></i>
            <h3>Mises à jour en temps réel</h3>
            <p>Disponibilité des salles mise à jour instantanément</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-headset"></i>
            <h3>Support 24/7</h3>
            <p>Notre équipe est là pour vous aider</p>
        </div>
    </div>
</div>

<style>
.hero-section {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 80px 20px;
    text-align: center;
    border-radius: 10px;
    margin-bottom: 50px;
}

.hero-content h1 {
    font-size: 2.5rem;
    margin-bottom: 20px;
}

.hero-content p {
    font-size: 1.2rem;
    margin-bottom: 30px;
    opacity: 0.9;
}

.hero-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-secondary {
    background-color: transparent;
    border: 2px solid white;
    color: white;
}

.btn-secondary:hover {
    background-color: white;
    color: var(--primary-color);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 40px 0;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    background-color: var(--primary-color);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-content h3 {
    font-size: 2rem;
    color: var(--secondary-color);
    margin-bottom: 5px;
}

.upcoming-reservations {
    margin: 50px 0;
}

.upcoming-reservations h2 {
    text-align: center;
    margin-bottom: 30px;
    color: var(--secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.upcoming-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: var(--shadow);
}

.upcoming-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.date-badge {
    background-color: var(--primary-color);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
}

.upcoming-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.9rem;
}

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background-color: var(--primary-color);
    color: white;
}

.features-section {
    margin: 60px 0;
}

.features-section h2 {
    text-align: center;
    margin-bottom: 40px;
    color: var(--secondary-color);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.feature-card {
    text-align: center;
    padding: 30px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: var(--shadow);
    transition: transform 0.3s;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-card i {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 20px;
}

.feature-card h3 {
    margin-bottom: 10px;
    color: var(--secondary-color);
}

.feature-card p {
    color: var(--gray-color);
}
</style>

<?php require_once 'includes/footer.php'; ?>