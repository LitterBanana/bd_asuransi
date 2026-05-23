<?php 
    include "db.php";
    include "layouts/public/header.php"; 
?>

<link rel="stylesheet" href="layouts/css/style.css?v=<?php echo time(); ?>">

<div class="faskes-header-alt">
    <h1><i class="fa-solid fa-hospital" style="color: var(--color-aqua); margin-right: 10px;"></i>Fasilitas Kesehatan</h1>
    <p>Temukan dokter dan rumah sakit terbaik di dekat Anda dengan fasilitas tanpa uang muka (cashless) dari jaringan AsuransiKu.</p>
</div>

<div class="faskes-search-container">
    <div class="faskes-search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Cari nama rumah sakit, klinik, atau kota...">
    </div>
</div>

<div class="faskes-grid" id="faskesGrid">
    <?php
        $query = "SELECT * FROM faskes WHERE status_kerjasama = 'Aktif' ORDER BY kota ASC";
        $result = $conn->query($query);

        if($result && $result->rowCount() > 0) {
            while($row = $result->fetch()) {
                $faskes_name = htmlspecialchars($row['nama_faskes']);
                $faskes_type = htmlspecialchars($row['tingkat_faskes']);
                $faskes_city = htmlspecialchars($row['kota']);
                $faskes_address = htmlspecialchars($row['alamat']);
                $faskes_status = htmlspecialchars($row['status_kerjasama']);
                
                // For search filtering
                $search_data = strtolower($faskes_name . " " . $faskes_city . " " . $faskes_type);
                ?>
                <div class="faskes-card" data-search="<?php echo $search_data; ?>">
                    <div class="faskes-badges">
                        <span class="faskes-badge-type"><?php echo $faskes_type; ?></span>
                        <span class="faskes-badge-status"><i class="fa-solid fa-circle-check"></i> <?php echo $faskes_status; ?></span>
                    </div>
                    <h3><?php echo $faskes_name; ?></h3>
                    <div class="faskes-info">
                        <i class="fa-solid fa-city"></i>
                        <span><?php echo $faskes_city; ?></span>
                    </div>
                    <div class="faskes-info">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?php echo $faskes_address; ?></span>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<div class=" . '"faskes-empty"' . "><i class=" . '"fa-solid fa-hospital-user"' . " style=" . '"font-size: 40px; color: #cbd5e1; margin-bottom: 15px;"' . "></i><br>Belum ada fasilitas kesehatan rekanan yang aktif.</div>";
        }
    ?>
</div>

<script>
    // Live Search Functionality
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const cards = document.querySelectorAll('.faskes-card');
        const grid = document.getElementById('faskesGrid');
        
        if(searchInput) {
            searchInput.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                let hasVisible = false;
                
                cards.forEach(card => {
                    const text = card.getAttribute('data-search');
                    if(text.includes(term)) {
                        card.style.display = 'flex';
                        hasVisible = true;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Handle empty state
                let emptyMsg = document.getElementById('empty-search-msg');
                if(!hasVisible) {
                    if(!emptyMsg) {
                        emptyMsg = document.createElement('div');
                        emptyMsg.id = 'empty-search-msg';
                        emptyMsg.className = 'faskes-empty';
                        emptyMsg.innerHTML = '<i class="fa-solid fa-search" style="font-size: 40px; color: #cbd5e1; margin-bottom: 15px;"></i><br>Fasilitas kesehatan tidak ditemukan.';
                        grid.appendChild(emptyMsg);
                    }
                    emptyMsg.style.display = 'block';
                } else if(emptyMsg) {
                    emptyMsg.style.display = 'none';
                }
            });
        }
    });
</script>

<?php include "layouts/public/footer.php"; ?>
