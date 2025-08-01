// ==================== EVENTOS AL CARGAR LA PÁGINA ====================
window.addEventListener("load", () => {
    paginateTable();
});

// ==================== FUNCIONALIDAD DOMContentLoaded ====================
document.addEventListener('DOMContentLoaded', () => {
    // -------- Botón hamburguesa (sidebar) --------
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');

    if (hamburger && sidebar) {
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
    
    document.querySelectorAll('.submenu-toggle').forEach(toggle => {
        toggle.addEventListener('click', function () {
            this.parentElement.classList.toggle('active');
        });
    });
});

