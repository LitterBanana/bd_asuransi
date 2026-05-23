document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // 1. Mobile Menu Toggle (Public Layout)
    // ==========================================
    const mobileMenuBtn = document.getElementById('mobile-menu');
    const navMenu = document.getElementById('nav-menu');
    if (mobileMenuBtn && navMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }

    // ==========================================
    // 2. Sidebar Toggle (Customer/Admin/Agen)
    // ==========================================
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    if (sidebarToggle && sidebar && sidebarOverlay) {
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        }
        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // ==========================================
    // 3. Konfirmasi Paket Modal (Customer)
    // ==========================================
    const chkSyarat = document.getElementById('chkSyarat');
    const btnSubmit = document.getElementById('btnSubmit');
    const modal = document.getElementById('tncModal');
    const btnModal = document.getElementById('btnModal');
    const closeModal = document.getElementById('closeModal');
    const btnMengerti = document.getElementById('btnMengerti');
    const btnBatal = document.getElementById('btnBatal');

    if (chkSyarat && btnSubmit) {
        // Sync button state with checkbox on page load (in case browser caches checkbox state)
        if (chkSyarat.checked) {
            btnSubmit.disabled = false;
            btnSubmit.style.opacity = '1';
            btnSubmit.style.cursor = 'pointer';
        }

        chkSyarat.addEventListener('change', function() {
            if (this.checked) {
                btnSubmit.disabled = false;
                btnSubmit.style.opacity = '1';
                btnSubmit.style.cursor = 'pointer';
            } else {
                btnSubmit.disabled = true;
                btnSubmit.style.opacity = '0.5';
                btnSubmit.style.cursor = 'not-allowed';
            }
        });
    }

    if (btnModal && modal) {
        btnModal.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = "flex";
            document.body.style.overflow = "hidden"; // Prevent scrolling behind modal
        });
    }

    if (closeModal && modal) {
        closeModal.addEventListener('click', function() {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        });
    }
    
    if (btnBatal && modal) {
        btnBatal.addEventListener('click', function() {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        });
    }

    if (btnMengerti && chkSyarat && modal) {
        btnMengerti.addEventListener('click', function() {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
            chkSyarat.checked = true;
            // Trigger change event to enable button
            chkSyarat.dispatchEvent(new Event('change'));
        });
    }

    if (modal) {
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
                document.body.style.overflow = "auto";
            }
        });
    }
});
