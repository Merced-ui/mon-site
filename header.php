<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';

if($isLoggedIn) {
    // Vérifier que les clés existent avant de les utiliser
    $prenom = isset($_SESSION['prenom']) ? $_SESSION['prenom'] : '';
    $nom = isset($_SESSION['nom']) ? $_SESSION['nom'] : '';
    $userName = trim($prenom . ' ' . $nom);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation de Salles</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <a href="index.php" class="nav-logo">
                    <i class="fas fa-calendar-alt"></i> Réservation Salles
                </a>
                <ul class="nav-menu">
                    <li><a href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="salles.php"><i class="fas fa-door-open"></i> Salles</a></li>
                    
                    <?php if($isLoggedIn): ?>
                        <li><a href="mes_reservations.php"><i class="fas fa-list"></i> Mes Réservations</a></li>
                        <?php if(!empty($userName)): ?>
                            <li><a href="profil.php"><i class="fas fa-user"></i> <?php echo htmlspecialchars($userName); ?></a></li>
                        <?php else: ?>
                            <li><a href="profil.php"><i class="fas fa-user"></i> Mon Profil</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> Inscription</a></li>
                    <?php endif; ?>
                </ul>
                <div class="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </nav>
    </header>

    <main class="container">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>