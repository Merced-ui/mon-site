<?php



require_once 'includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Récupérer les informations de l'utilisateur
$query = "SELECT nom, prenom, email, date_inscription FROM utilisateurs WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user) {
    set_flash_message('Utilisateur non trouvé', 'error');
    header('Location: index.php');
    exit();
}

// Mise à jour du profil
$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nom = Database::sanitize($_POST['nom']);
    $prenom = Database::sanitize($_POST['prenom']);
    $email = Database::sanitize($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    // Validation
    if(empty($nom) || empty($prenom) || empty($email)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } else {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $query = "SELECT id FROM utilisateurs WHERE email = :email AND id != :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $error = "Cette adresse email est déjà utilisée.";
        } else {
            // Mise à jour sans le mot de passe
            $query = "UPDATE utilisateurs SET nom = :nom, prenom = :prenom, email = :email WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            
            if($stmt->execute()) {
                // Mettre à jour la session
                $_SESSION['nom'] = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['email'] = $email;
                
                $success = "Profil mis à jour avec succès.";
            } else {
                $error = "Erreur lors de la mise à jour du profil.";
            }
        }
    }
}

// Changement de mot de passe
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Tous les champs du mot de passe sont obligatoires.";
    } elseif($new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif(strlen($new_password) < 8) {
        $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } else {
        // Vérifier le mot de passe actuel
        $query = "SELECT mot_de_passe FROM utilisateurs WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(password_verify($current_password, $result['mot_de_passe'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE utilisateurs SET mot_de_passe = :password WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            
            if($stmt->execute()) {
                $success = "Mot de passe changé avec succès.";
            } else {
                $error = "Erreur lors du changement de mot de passe.";
            }
        } else {
            $error = "Mot de passe actuel incorrect.";
        }
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-user"></i> Mon Profil</h1>
</div>

<div class="profile-container">
    <div class="profile-sidebar">
        <div class="profile-avatar">
            <i class="fas fa-user-circle fa-5x"></i>
            <h3><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h3>
            <p>Membre depuis <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></p>
        </div>
        
        <div class="profile-stats">
            <div class="stat">
                <i class="fas fa-calendar-check"></i>
                <div>
                    <h4>Mes réservations</h4>
                    <?php
                    $query = "SELECT COUNT(*) as total FROM reservations WHERE utilisateur_id = :user_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $stmt->execute();
                    $reservation_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    ?>
                    <p><?php echo $reservation_count; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="profile-content">
        <div class="profile-tabs">
            <button class="tab-btn active" data-tab="info">Informations personnelles</button>
            <button class="tab-btn" data-tab="password">Changer le mot de passe</button>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="tab-content active" id="info-tab">
            <h3>Modifier mes informations</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nom"><i class="fas fa-user"></i> Nom</label>
                    <input type="text" id="nom" name="nom" required 
                           value="<?php echo htmlspecialchars($user['nom']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="prenom"><i class="fas fa-user"></i> Prénom</label>
                    <input type="text" id="prenom" name="prenom" required 
                           value="<?php echo htmlspecialchars($user['prenom']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Mettre à jour
                </button>
            </form>
        </div>
        
        <div class="tab-content" id="password-tab">
            <h3>Changer mon mot de passe</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password"><i class="fas fa-lock"></i> Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password"><i class="fas fa-lock"></i> Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Changer le mot de passe
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.profile-container {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    margin-top: 30px;
}

.profile-sidebar {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: var(--shadow);
    text-align: center;
}

.profile-avatar {
    margin-bottom: 30px;
}

.profile-avatar i {
    color: var(--primary-color);
    margin-bottom: 15px;
}

.profile-avatar h3 {
    margin-bottom: 10px;
    color: var(--secondary-color);
}

.profile-avatar p {
    color: var(--gray-color);
    font-size: 0.9rem;
}

.profile-stats {
    margin-top: 30px;
}

.stat {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: var(--light-color);
    border-radius: 8px;
    margin-bottom: 10px;
}

.stat i {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.stat h4 {
    font-size: 0.9rem;
    color: var(--gray-color);
    margin-bottom: 5px;
}

.stat p {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--secondary-color);
}

.profile-content {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: var(--shadow);
}

.profile-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid var(--light-color);
    padding-bottom: 10px;
}

.tab-btn {
    padding: 10px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-size: 1rem;
    cursor: pointer;
    color: var(--gray-color);
    transition: all 0.3s;
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
    font-weight: bold;
}

.tab-btn:hover:not(.active) {
    color: var(--dark-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.tab-content h3 {
    margin-bottom: 20px;
    color: var(--secondary-color);
}

@media (max-width: 768px) {
    .profile-container {
        grid-template-columns: 1fr;
    }
    
    .profile-tabs {
        flex-direction: column;
    }
}
</style>

<script>
// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Désactiver tous les onglets
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Activer l'onglet courant
            this.classList.add('active');
            document.getElementById(tabId + '-tab').classList.add('active');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>