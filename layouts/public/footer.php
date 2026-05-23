    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <h3>AsuransiKu</h3>
                <p style="color: rgba(255,255,255,0.7); line-height: 1.6; margin-bottom: 20px;">Platform asuransi kesehatan digital terdepan di Indonesia, memberikan perlindungan maksimal dengan proses minimal.</p>
                <div style="display: flex; gap: 15px;">
                    <a href="#" style="color: white; font-size: 20px;"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" style="color: white; font-size: 20px;"><i class="fa-brands fa-linkedin"></i></a>
                    <a href="#" style="color: white; font-size: 20px;"><i class="fa-brands fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h3 style="color: white;">Tautan Penting</h3>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="keunggulan.php">Keunggulan</a></li>
                    <li><a href="produk.php">Produk Kami</a></li>
                    <li><a href="faskes.php">Faskes Rekanan</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3 style="color: white;">Perusahaan</h3>
                <ul>
                    <li><a href="#">Tentang Kami</a></li>
                    <li><a href="#">Karir</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Hubungi Kami</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3 style="color: white;">Bantuan</h3>
                <ul>
                    <li><a href="#">Pusat Bantuan</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Cara Klaim</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date("Y"); ?> AsuransiKu. Hak Cipta Dilindungi Undang-Undang. <br>
            Berizin dan diawasi oleh Otoritas Jasa Keuangan (OJK).
        </div>
    </footer>

    <script>
        const mobileMenuBtn = document.getElementById('mobile-menu');
        const navMenu = document.getElementById('nav-menu');
        if(mobileMenuBtn && navMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                navMenu.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
