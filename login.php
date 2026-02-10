<?php
require_once 'includes/header.php';

if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $email = Database::sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    if(empty($email) || empty($password)) {
        $error = "Email et mot de passe sont obligatoires.";
    } else {
        // Récupérer l'utilisateur
        $query = "SELECT id, nom, prenom, email, mot_de_passe, role FROM utilisateurs WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $user['mot_de_passe'])) {
                // Créer la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = "Mot de passe incorrect.";
            }
        } else {
            $error = "Aucun compte trouvé avec cet email.";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-form">
        <h2><i class="fas fa-sign-in-alt"></i> Connexion</h2>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
            
            <p class="auth-link">
                Pas encore de compte? <a href="register.php">S'inscrire</a>
            </p>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>