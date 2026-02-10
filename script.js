// Menu hamburger
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if(hamburger) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }
    
    // Fermer le menu en cliquant sur un lien
    document.querySelectorAll('.nav-menu a').forEach(n => {
        n.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });
    
    // Validation des formulaires
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if(!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Créer un message d'erreur
                    let errorMsg = field.nextElementSibling;
                    if(!errorMsg || !errorMsg.classList.contains('field-error')) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'field-error';
                        errorMsg.style.color = '#e74c3c';
                        errorMsg.style.fontSize = '0.9rem';
                        errorMsg.style.marginTop = '5px';
                        errorMsg.textContent = 'Ce champ est obligatoire';
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.classList.remove('error');
                    const errorMsg = field.nextElementSibling;
                    if(errorMsg && errorMsg.classList.contains('field-error')) {
                        errorMsg.remove();
                    }
                }
            });
            
            if(!isValid) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
            }
        });
    });
    
    // Gestion des dates
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if(!input.min) {
            const today = new Date().toISOString().split('T')[0];
            input.min = today;
        }
    });
    
    // Confirmation de suppression
    const deleteButtons = document.querySelectorAll('a.btn-danger');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if(!confirm('Êtes-vous sûr de vouloir effectuer cette action?')) {
                e.preventDefault();
            }
        });
    });
    
    // Animation des cartes
    const cards = document.querySelectorAll('.salle-card, .reservation-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'transform 0.3s ease';
        });
    });
});

// Fonction pour vérifier la disponibilité en temps réel
function checkAvailability(salleId, date, startTime, endTime) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'check_availability.php',
            method: 'POST',
            data: {
                salle_id: salleId,
                date_reservation: date,
                heure_debut: startTime,
                heure_fin: endTime
            },
            success: function(response) {
                resolve(response.available);
            },
            error: function() {
                reject('Erreur de vérification');
            }
        });
    });
}

// Formatage des dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Gestion des messages toast
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background-color: ${type === 'success' ? '#2ecc71' : '#e74c3c'};
        color: white;
        border-radius: 5px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Ajouter des styles d'animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .field-error {
        color: #e74c3c !important;
        font-size: 0.9rem !important;
        margin-top: 5px !important;
    }
    
    input.error {
        border-color: #e74c3c !important;
    }
`;
document.head.appendChild(style);