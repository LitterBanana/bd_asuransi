            </div> <!-- End of content-area -->
            <div class="footer">
                &copy; <?php echo date("Y"); ?> AsuransiKu. All rights reserved.
            </div>
        </div> <!-- End of main-content -->
    </div> <!-- End of dashboard-container -->
    
    <script src="<?php echo $base_url; ?>/layouts/js/script.js"></script>

    <?php if (isset($_SESSION['toast_success'])): ?>
    <div id="toast-success" style="position: fixed; bottom: 20px; right: 20px; background: white; border-left: 4px solid var(--color-success); padding: 15px 20px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; z-index: 9999; animation: slideInRight 0.3s ease-out forwards; max-width: 350px;">
        <i class="fa-solid fa-circle-check" style="color: var(--color-success); font-size: 20px;"></i>
        <div style="color: var(--color-dark); font-size: 14px; font-weight: 500; line-height: 1.4;">
            <?php echo htmlspecialchars($_SESSION['toast_success']); ?>
        </div>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--color-slate); cursor: pointer; margin-left: auto; padding: 0;">&times;</button>
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('toast-success');
            if (toast) {
                toast.style.animation = 'slideOutRight 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    </script>
    <style>
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
    <?php unset($_SESSION['toast_success']); endif; ?>

    <?php if (isset($_SESSION['toast_error'])): ?>
    <div id="toast-error" style="position: fixed; bottom: 20px; right: 20px; background: white; border-left: 4px solid var(--color-danger); padding: 15px 20px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; z-index: 9999; animation: slideInRight 0.3s ease-out forwards; max-width: 350px;">
        <i class="fa-solid fa-circle-exclamation" style="color: var(--color-danger); font-size: 20px;"></i>
        <div style="color: var(--color-dark); font-size: 14px; font-weight: 500; line-height: 1.4;">
            <?php echo htmlspecialchars($_SESSION['toast_error']); ?>
        </div>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--color-slate); cursor: pointer; margin-left: auto; padding: 0;">&times;</button>
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('toast-error');
            if (toast) {
                toast.style.animation = 'slideOutRight 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    </script>
    <?php unset($_SESSION['toast_error']); endif; ?>
</body>
</html>
