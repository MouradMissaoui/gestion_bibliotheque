<!-- assets/js/custom.js -->
// =================================
// JAVASCRIPT PERSONNALISÉ BIBLIOGESTION
// =================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Animation au chargement
    animateElements();
    
    // Confirmation avant suppression
    setupDeleteConfirmations();
    
    // Auto-hide des alerts
    autoHideAlerts();
    
    // Recherche en temps réel
    setupLiveSearch();
    
    // Tooltips Bootstrap
    initTooltips();
    
    // Validation des formulaires
    setupFormValidation();
    
    // Statistiques en temps réel
    updateStatistics();
});

// Animation des éléments au chargement
function animateElements() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, index * 100);
    });
}

// Confirmation avant suppression
function setupDeleteConfirmations() {
    const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const message = this.getAttribute('onsubmit').match(/'([^']+)'/)[1];
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// Auto-hide des alerts après 5 secondes
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// Recherche en temps réel (pour la page des livres)
function setupLiveSearch() {
    const searchInput = document.querySelector('input[name="recherche"]');
    if (!searchInput) return;
    
    let timeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }, 300);
    });
}

// Initialiser les tooltips Bootstrap
function initTooltips() {
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Validation des formulaires
function setupFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Mise à jour des statistiques (simulation temps réel)
function updateStatistics() {
    const statElements = document.querySelectorAll('[data-stat]');
    statElements.forEach(el => {
        const finalValue = parseInt(el.textContent);
        animateValue(el, 0, finalValue, 1000);
    });
}

// Animation des nombres
function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        element.textContent = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Fonction utilitaire: Formater la date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}

// Fonction utilitaire: Calcul jours restants
function daysRemaining(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const diff = date - today;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

// Export pour réutilisation
window.BiblioUtils = {
    formatDate,
    daysRemaining,
    animateValue
};