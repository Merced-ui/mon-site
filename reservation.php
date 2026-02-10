<?php
// Démarrer la session au tout début

// Inclure les dépendances
require_once 'config/database.php';
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Veuillez vous connecter pour réserver une salle.";
    $_SESSION['message_type'] = 'error';
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$salle_id = isset($_GET['salle_id']) ? intval($_GET['salle_id']) : 0;

// Récupérer les informations de la salle
$query = "SELECT * FROM salles WHERE id = :id AND disponible = 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $salle_id);
$stmt->execute();
$salle = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$salle) {
    $_SESSION['message'] = "Salle non trouvée ou indisponible.";
    $_SESSION['message_type'] = 'error';
    header('Location: salles.php');
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date_reservation = Database::sanitize($_POST['date_reservation']);
    $heure_debut = Database::sanitize($_POST['heure_debut']);
    $heure_fin = Database::sanitize($_POST['heure_fin']);
    
    // Validation
    $today = date('Y-m-d');
    if($date_reservation < $today) {
        $error = "La date de réservation ne peut pas être dans le passé.";
    } elseif($heure_debut >= $heure_fin) {
        $error = "L'heure de fin doit être après l'heure de début.";
    } elseif(strtotime($heure_fin) - strtotime($heure_debut) < 1800) {
        $error = "La réservation doit durer au moins 30 minutes.";
    } else {
        // Vérifier les conflits de réservation - CORRECTION IMPORTANTE
        $query = "SELECT id FROM reservations 
                 WHERE salle_id = :salle_id 
                 AND date_reservation = :date_reservation 
                 AND statut IN ('confirmee', 'en_attente')
                 AND NOT (
                     heure_fin <= :heure_debut OR 
                     heure_debut >= :heure_fin
                 )";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':salle_id', $salle_id);
        $stmt->bindParam(':date_reservation', $date_reservation);
        $stmt->bindParam(':heure_debut', $heure_debut);
        $stmt->bindParam(':heure_fin', $heure_fin);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $error = "Cette salle est déjà réservée pour cette plage horaire. Veuillez choisir un autre créneau.";
        } else {
            // Calculer le montant
            $debut = strtotime($heure_debut);
            $fin = strtotime($heure_fin);
            $duree_heures = ($fin - $debut) / 3600;
            $montant = $duree_heures * $salle['prix_heure'];
            
            // Créer la réservation
            $query = "INSERT INTO reservations 
                     (utilisateur_id, salle_id, date_reservation, heure_debut, heure_fin, montant_paye, statut) 
                     VALUES (:utilisateur_id, :salle_id, :date_reservation, :heure_debut, :heure_fin, :montant, 'confirmee')";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':utilisateur_id', $_SESSION['user_id']);
            $stmt->bindParam(':salle_id', $salle_id);
            $stmt->bindParam(':date_reservation', $date_reservation);
            $stmt->bindParam(':heure_debut', $heure_debut);
            $stmt->bindParam(':heure_fin', $heure_fin);
            $stmt->bindParam(':montant', $montant);
            
            if($stmt->execute()) {
                $reservation_id = $conn->lastInsertId();
                $_SESSION['message'] = "Réservation #$reservation_id créée avec succès!";
                $_SESSION['message_type'] = 'success';
                header('Location: mes_reservations.php');
                exit();
            } else {
                $error = "Erreur lors de la création de la réservation.";
            }
        }
    }
}
?>

<!-- Le reste du code HTML/JavaScript reste inchangé -->
<div class="page-header">
    <h1><i class="fas fa-calendar-plus"></i> Réserver <?php echo htmlspecialchars($salle['nom']); ?></h1>
</div>

<div class="reservation-container">
    <div class="salle-info-card">
        <h3>Informations de la salle</h3>
        <p><strong>Type:</strong> <?php echo $salle['type'] == 'cours' ? 'Salle de cours' : 'Salle de réunion'; ?></p>
        <p><strong>Capacité:</strong> <?php echo $salle['capacite']; ?> personnes</p>
        <p><strong>Tarif:</strong> <?php echo number_format($salle['prix_heure'], 2); ?> €/heure</p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($salle['description']); ?></p>
    </div>
    
    <div class="reservation-form-card">
        <h3>Formulaire de réservation</h3>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="reservationForm">
            <div class="form-group">
                <label for="date_reservation"><i class="fas fa-calendar-day"></i> Date de réservation</label>
                <input type="date" id="date_reservation" name="date_reservation" required 
                       min="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo isset($_POST['date_reservation']) ? htmlspecialchars($_POST['date_reservation']) : ''; ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="heure_debut"><i class="fas fa-clock"></i> Heure de début</label>
                    <input type="time" id="heure_debut" name="heure_debut" required 
                           min="08:00" max="20:00"
                           value="<?php echo isset($_POST['heure_debut']) ? htmlspecialchars($_POST['heure_debut']) : '09:00'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="heure_fin"><i class="fas fa-clock"></i> Heure de fin</label>
                    <input type="time" id="heure_fin" name="heure_fin" required 
                           min="09:00" max="21:00"
                           value="<?php echo isset($_POST['heure_fin']) ? htmlspecialchars($_POST['heure_fin']) : '10:00'; ?>">
                </div>
            </div>
            
            <div class="calculation-box">
                <h4>Calcul du montant</h4>
                <p id="duree_calculee">Durée: 0 heure(s)</p>
                <p id="montant_calcule">Montant: 0.00 €</p>
            </div>
            
            <div id="disponibilite"></div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-check-circle"></i> Confirmer la réservation
            </button>
        </form>
    </div>
</div>

<script>

// Vérification de disponibilité en temps réel
function verifierDisponibilite() {
    const salleId = <?php echo $salle_id; ?>;
    const dateReservation = document.getElementById('date_reservation').value;
    const heureDebut = document.getElementById('heure_debut').value;
    const heureFin = document.getElementById('heure_fin').value;
    
    if(dateReservation && heureDebut && heureFin && heureDebut < heureFin) {
        // Afficher un indicateur de chargement
        const disponibiliteDiv = document.getElementById('disponibilite');
        if(!disponibiliteDiv) {
            const div = document.createElement('div');
            div.id = 'disponibilite';
            document.querySelector('.calculation-box').after(div);
        }
        
        document.getElementById('disponibilite').innerHTML = 
            '<div class="loading" style="padding: 10px; background: #f8f9fa; border-radius: 5px; margin: 10px 0; text-align: center;">' +
            '<i class="fas fa-spinner fa-spin"></i> Vérification de la disponibilité...</div>';
        
        // Envoyer la requête AJAX
        $.ajax({
            url: 'check_availability.php',
            method: 'POST',
            data: {
                salle_id: salleId,
                date_reservation: dateReservation,
                heure_debut: heureDebut,
                heure_fin: heureFin
            },
            success: function(response) {
                console.log('Réponse serveur:', response); // Pour déboguer
                
                let result;
                try {
                    result = typeof response === 'string' ? JSON.parse(response) : response;
                } catch(e) {
                    console.error('Erreur parsing JSON:', e, response);
                    document.getElementById('disponibilite').innerHTML = 
                        '<div class="alert alert-error" style="margin: 10px 0;">' +
                        '<i class="fas fa-exclamation-triangle"></i> Erreur de réponse du serveur' +
                        '</div>';
                    return;
                }
                
                let html = '';
                
                if(result.available === true) {
                    html = '<div class="alert alert-success" style="margin: 10px 0;">' +
                           '<i class="fas fa-check-circle"></i> ' + (result.message || 'Créneau disponible') +
                           '</div>';
                } else {
                    html = '<div class="alert alert-error" style="margin: 10px 0;">' +
                           '<i class="fas fa-times-circle"></i> ' + (result.message || result.error || 'Créneau non disponible') +
                           '</div>';
                    
                    // Afficher les conflits s'il y en a
                    if(result.conflicts && result.conflicts.length > 0) {
                        html += '<div class="conflicts-list" style="margin-top: 10px; font-size: 0.9em; padding: 10px; background: #f8d7da; border-radius: 5px;">';
                        html += '<strong>Réservations existantes :</strong><ul style="margin-top: 5px; margin-left: 20px;">';
                        result.conflicts.forEach(conflict => {
                            const debut = conflict.heure_debut ? conflict.heure_debut.substr(0,5) : '';
                            const fin = conflict.heure_fin ? conflict.heure_fin.substr(0,5) : '';
                            const nom = (conflict.prenom || '') + ' ' + (conflict.nom || '');
                            const statut = conflict.statut === 'confirmee' ? 'Confirmée' : 'En attente';
                            
                            html += `<li><strong>${nom}</strong> de ${debut} à ${fin} (${statut})</li>`;
                        });
                        html += '</ul></div>';
                    }
                }
                
                document.getElementById('disponibilite').innerHTML = html;
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', status, error);
                document.getElementById('disponibilite').innerHTML = 
                    '<div class="alert alert-error" style="margin: 10px 0;">' +
                    '<i class="fas fa-exclamation-triangle"></i> Erreur de connexion au serveur' +
                    '</div>';
            }
        });
    } else if(heureDebut && heureFin && heureDebut >= heureFin) {
        document.getElementById('disponibilite').innerHTML = 
            '<div class="alert alert-error" style="margin: 10px 0;">' +
            '<i class="fas fa-times-circle"></i> L\'heure de fin doit être après l\'heure de début' +
            '</div>';
    } else {
        document.getElementById('disponibilite').innerHTML = '';
    }
}

// Calcul automatique du montant
function calculerMontant() {
    const dateReservation = document.getElementById('date_reservation').value;
    const heureDebut = document.getElementById('heure_debut').value;
    const heureFin = document.getElementById('heure_fin').value;
    const prixHeure = <?php echo $salle['prix_heure']; ?>;
    
    if(dateReservation && heureDebut && heureFin) {
        const debut = new Date(dateReservation + 'T' + heureDebut);
        const fin = new Date(dateReservation + 'T' + heureFin);
        
        if(fin > debut) {
            const dureeMs = fin - debut;
            const dureeHeures = dureeMs / (1000 * 60 * 60);
            
            document.getElementById('duree_calculee').textContent = 
                'Durée: ' + dureeHeures.toFixed(1) + ' heure(s)';
            
            const montant = dureeHeures * prixHeure;
            document.getElementById('montant_calcule').textContent = 
                'Montant: ' + montant.toFixed(2) + ' €';
            
            // Vérifier la disponibilité
            setTimeout(verifierDisponibilite, 300); // Petit délai pour éviter trop de requêtes
        } else {
            document.getElementById('duree_calculee').textContent = 'Durée: 0 heure(s)';
            document.getElementById('montant_calcule').textContent = 'Montant: 0.00 €';
            document.getElementById('disponibilite').innerHTML = 
                '<div class="alert alert-error" style="margin: 10px 0;">' +
                '<i class="fas fa-times-circle"></i> L\'heure de fin doit être après l\'heure de début' +
                '</div>';
        }
    } else {
        document.getElementById('duree_calculee').textContent = 'Durée: 0 heure(s)';
        document.getElementById('montant_calcule').textContent = 'Montant: 0.00 €';
    }
}

// Empêcher la soumission si non disponible
document.getElementById('reservationForm').addEventListener('submit', function(e) {
    const disponibiliteDiv = document.getElementById('disponibilite');
    let peutSoumettre = true;
    
    if(disponibiliteDiv) {
        const alertError = disponibiliteDiv.querySelector('.alert-error');
        if(alertError) {
            e.preventDefault();
            alert('Veuillez choisir un créneau disponible avant de soumettre la réservation.');
            peutSoumettre = false;
        }
    }
    
    // Vérification supplémentaire côté client
    const heureDebut = document.getElementById('heure_debut').value;
    const heureFin = document.getElementById('heure_fin').value;
    
    if(heureDebut >= heureFin) {
        e.preventDefault();
        alert('L\'heure de fin doit être après l\'heure de début.');
        peutSoumettre = false;
    }
    
    // Calculer la durée en minutes
    const debut = new Date('2000-01-01T' + heureDebut);
    const fin = new Date('2000-01-01T' + heureFin);
    const dureeMinutes = (fin - debut) / (1000 * 60);
    
    if(dureeMinutes < 30) {
        e.preventDefault();
        alert('La réservation doit durer au moins 30 minutes.');
        peutSoumettre = false;
    }
    
    // Vérifier la date
    const dateReservation = document.getElementById('date_reservation').value;
    const aujourdhui = new Date().toISOString().split('T')[0];
    
    if(dateReservation < aujourdhui) {
        e.preventDefault();
        alert('La date de réservation ne peut pas être dans le passé.');
        peutSoumettre = false;
    }
    
    return peutSoumettre;
});

// Initialiser le calcul et les événements
document.addEventListener('DOMContentLoaded', function() {
    // Événements pour le calcul automatique
    document.getElementById('date_reservation').addEventListener('change', calculerMontant);
    document.getElementById('heure_debut').addEventListener('change', calculerMontant);
    document.getElementById('heure_fin').addEventListener('change', calculerMontant);
    
    // Initialiser le calcul
    calculerMontant();
    
    // Débogage : vérifier que les éléments existent
    console.log('Éléments trouvés:');
    console.log('date_reservation:', document.getElementById('date_reservation'));
    console.log('heure_debut:', document.getElementById('heure_debut'));
    console.log('heure_fin:', document.getElementById('heure_fin'));
});

</script>

<?php require_once 'includes/footer.php'; ?>