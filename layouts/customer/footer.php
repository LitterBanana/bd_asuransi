            </div> <!-- End of content-area -->
            <div class="footer">
                &copy; <?php echo date("Y"); ?> AsuransiKu. All rights reserved.
            </div>
        </div> <!-- End of main-content -->
    </div> <!-- End of dashboard-container -->
    
    <script>
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        if(sidebarToggle && sidebar && sidebarOverlay) {
            function toggleSidebar() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            }
            
            sidebarToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }
    </script>
</body>
</html>
