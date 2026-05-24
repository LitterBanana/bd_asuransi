<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/customer/header.php";

    $id_user = $_SESSION['id_user'] ?? 0;
    $no_tagihan = $_GET['no_tagihan'] ?? '';

    if (empty($no_tagihan)) {
        $_SESSION['toast_error'] = 'Nomor tagihan tidak valid.';
        header("Location: index.php");
        exit;
    }

    // Verify tagihan
    $stmt = $conn->prepare("
        SELECT t.no_tagihan, t.jumlah_tagihan, t.periode_bulan, p.no_polis, pr.nama_produk, u.id_pemegang, u.email, u.username, pp.nama_lengkap
        FROM tagihan_premi t
        JOIN polis p ON t.no_polis = p.no_polis
        JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
        JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
        JOIN users u ON p.id_pemegang = u.id_pemegang
        WHERE t.no_tagihan = ? AND u.id_user = ? AND t.status_tagihan IN ('Unpaid', 'Overdue')
    ");
    $stmt->execute([$no_tagihan, $id_user]);
    $tagihan = $stmt->fetch();

    if (!$tagihan) {
        $_SESSION['toast_error'] = 'Tagihan tidak ditemukan atau sudah lunas.';
        header("Location: index.php");
        exit;
    }

    // Cek pending payment
    $stmt_cek = $conn->prepare("SELECT id_pembayaran FROM pembayaran_premi WHERE no_tagihan = ? AND status_pembayaran = 'Pending'");
    $stmt_cek->execute([$no_tagihan]);
    if ($stmt_cek->fetch()) {
        $_SESSION['toast_error'] = 'Anda sudah melakukan pembayaran untuk tagihan ini dan sedang menunggu verifikasi admin.';
        header("Location: index.php");
        exit;
    }

    // Process payment form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $metode_bayar = $_POST['payment_method'] ?? '';
        $bank_name = $_POST['bank'] ?? '';
        $referensi_pembayaran = $_POST['referensi_pembayaran'] ?? '';
        
        // Cek jika tunai
        if ($metode_bayar == 'tunai') {
            $metode_bayar = 'Cash';
            $bank_name = '';
        } elseif ($metode_bayar == 'transfer') {
            $metode_bayar = 'Transfer Bank';
        }

        if (empty($metode_bayar)) {
            $error = "Metode pembayaran wajib dipilih.";
        } else {
            // Upload proof
            $bukti_pembayaran = null;
            if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../uploads/payments/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
                if (in_array($file_ext, $allowed_ext)) {
                    $file_name = 'PAY_' . time() . '_' . uniqid() . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $upload_dir . $file_name)) {
                        $bukti_pembayaran = $file_name;
                    }
                }
            }

            try {
                $stmt_insert = $conn->prepare("
                    INSERT INTO pembayaran_premi (no_tagihan, tanggal_bayar, nominal_bayar, metode_bayar, bank_name, referensi_pembayaran, bukti_pembayaran, status_pembayaran)
                    VALUES (?, NOW(), ?, ?, ?, ?, ?, 'Pending')
                ");
                $stmt_insert->execute([
                    $no_tagihan,
                    $tagihan['jumlah_tagihan'],
                    $metode_bayar,
                    $bank_name,
                    $referensi_pembayaran,
                    $bukti_pembayaran
                ]);
                
                $success = true;
            } catch (PDOException $e) {
                error_log("Error insert pembayaran: " . $e->getMessage());
                $error = "Terjadi kesalahan saat memproses pembayaran.";
            }
        }
    }
?>

<style>
    :root {
        --color-bg-alt: #f1f5f9;
        --color-success-border: #34d399;
        --color-border: #cbd5e1;
        --color-primary-300: #93c5fd;
        --color-primary-50: #eff6ff;
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-primary: 0 4px 14px 0 rgba(0, 118, 255, 0.39);
        --radius-full: 9999px;
        --leading-relaxed: 1.625;
    }
    .payment-container { padding-bottom: 50px; }
    
    .payment-form-footer { padding: 20px 25px; border-top: 1px solid var(--color-border-light); display: flex; align-items: center; justify-content: space-between; gap: 20px; background: #f8fafc; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; }
    
    .form-section { margin-bottom: var(--space-8); }
    .form-section:last-child { margin-bottom: 0; }
    .form-section-title { font-size: 18px; font-weight: 600; color: var(--color-text); margin-bottom: var(--space-4); padding-bottom: var(--space-3); border-bottom: 1px solid var(--color-border-light); display: flex; align-items: center; gap: var(--space-2); }
    .form-section-title .section-icon { color: var(--color-blue); font-size: 20px; }
    
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-5); margin-bottom: 15px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-label { font-size: 14px; font-weight: 600; color: var(--color-dark); }
    .form-input { padding: 12px; border-radius: 8px; border: 1px solid var(--color-border); font-family: inherit; font-size: 15px; transition: border-color 0.3s; }
    .form-input:focus { border-color: var(--color-blue); outline: none; }
    .form-input[readonly] { background: #f1f5f9; color: #64748b; cursor: not-allowed; }
    .form-hint { font-size: 12px; color: var(--color-text-secondary); }
    .required { color: #ef4444; }

    /* Radio Group Methods */
    .radio-group { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .radio-card input[type="radio"] { display: none; }
    .radio-card-label { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 20px; background: #fff; border: 2px solid var(--color-border); border-radius: 12px; cursor: pointer; transition: all 0.3s; text-align: center; }
    .radio-card-label:hover { border-color: var(--color-primary-300); }
    .radio-card input[type="radio"]:checked + .radio-card-label { border-color: var(--color-blue); background: var(--color-primary-50); box-shadow: 0 0 0 3px rgba(119, 133, 172, 0.1); }
    .radio-icon { font-size: 28px; }
    .radio-text { font-weight: 600; color: var(--color-dark); font-size: 16px; }
    .radio-desc { font-size: 12px; color: var(--color-text-secondary); }

    /* Bank Options */
    .bank-options { display: none; margin-top: 25px; animation: fadeIn 0.3s ease; }
    .bank-options.visible { display: block; }
    .bank-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .bank-option { position: relative; }
    .bank-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .bank-option-label { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 15px; background: #fff; border: 2px solid var(--color-border); border-radius: 12px; cursor: pointer; transition: all 0.3s; text-align: center; }
    .bank-option-label:hover { border-color: var(--color-primary-300); }
    .bank-option input[type="radio"]:checked + .bank-option-label { border-color: var(--color-blue); background: var(--color-primary-50); box-shadow: 0 0 0 3px rgba(119, 133, 172, 0.1); }
    .bank-logo { font-size: 20px; font-weight: 800; color: var(--color-dark); display: flex; align-items: center; justify-content: center; height: 30px; }
    .bank-name { font-size: 12px; font-weight: 600; color: var(--color-text-secondary); }

    /* Bank Info Panel */
    .bank-account-info { display: none; margin-top: 20px; background: #fff; border: 1px solid var(--color-primary-300); border-radius: 12px; overflow: hidden; animation: fadeIn 0.3s ease; }
    .bank-account-info.visible { display: block; }
    .bank-account-info-header { padding: 12px 15px; background: var(--color-primary-50); border-bottom: 1px solid var(--color-primary-300); font-size: 14px; font-weight: 600; color: var(--color-blue); display: flex; align-items: center; gap: 8px; }
    .bank-account-info-body { padding: 15px; }
    .bank-account-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--color-border-light); }
    .bank-account-row:last-child { border-bottom: none; }
    .bank-account-label { font-size: 14px; color: var(--color-text-secondary); }
    .bank-account-value { font-size: 14px; font-weight: 600; color: var(--color-dark); }
    .bank-account-number { font-size: 18px; font-weight: 800; color: var(--color-blue); font-family: monospace; letter-spacing: 1px; }

    /* File Upload */
    .file-upload { border: 2px dashed var(--color-border); padding: 30px 20px; text-align: center; border-radius: 12px; cursor: pointer; transition: all 0.3s; background: #fafafa; position: relative; }
    .file-upload:hover { border-color: var(--color-blue); background: var(--color-primary-50); }
    .file-upload input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .file-upload-icon { font-size: 32px; color: var(--color-blue); margin-bottom: 10px; }
    .file-upload-text { font-size: 15px; color: var(--color-dark); margin-bottom: 5px; }
    .file-upload-hint { font-size: 12px; color: var(--color-text-secondary); }
    
    .file-preview-card { display: none; align-items: center; gap: 15px; padding: 15px; background: #fff; border: 1px solid var(--color-border-light); border-radius: 12px; margin-top: 15px; box-shadow: var(--shadow-sm); animation: fadeIn 0.3s ease; }
    .file-preview-card.visible { display: flex; }
    .file-preview-thumb { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--color-text-muted); }
    .file-preview-info { flex: 1; min-width: 0; }
    .file-preview-name { font-size: 14px; font-weight: 600; color: var(--color-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .file-preview-size { font-size: 12px; color: var(--color-text-secondary); }

    /* Summary */
    .payment-summary { background: #f8fafc; border-radius: 12px; padding: 20px; margin-top: 30px; border: 1px solid var(--color-border-light); }
    .payment-summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
    .payment-summary-row.total { border-top: 2px dashed var(--color-border); margin-top: 10px; padding-top: 15px; font-size: 18px; font-weight: 700; color: var(--color-blue); }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    
    .payment-success { display: none; text-align: center; padding: 50px 30px; animation: fadeIn 0.5s ease; background: #fff; border-radius: var(--radius-2xl); box-shadow: var(--shadow-md); }
    .payment-success.visible { display: block; }
    .success-icon { width: 80px; height: 80px; background: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; font-size: 40px; color: #10b981; border: 3px solid #34d399; }
    .payment-success h2 { font-size: 28px; margin-bottom: 15px; color: var(--color-dark); }
    .payment-success p { font-size: 16px; color: var(--color-text-secondary); margin-bottom: 30px; }
</style>

<div class="payment-container">

  <?php if (isset($error)): ?>
      <div style="background-color: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fca5a5;">
          <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
      </div>
  <?php endif; ?>

  <?php if (isset($success) && $success): ?>
    <!-- Success State -->
    <div class="payment-success visible">
      <div class="success-icon"><i class="fa-solid fa-check"></i></div>
      <h2>Pembayaran Berhasil!</h2>
      <p>Data pembayaran Anda untuk tagihan polis <strong><?php echo htmlspecialchars($tagihan['no_polis']); ?></strong> telah berhasil dikirimkan dan akan segera diverifikasi oleh admin.</p>
      <div style="display: flex; justify-content: center; gap: 15px;">
        <a href="index.php" class="btn btn-primary" style="padding: 12px 25px; border-radius: 8px;">📋 Lihat Riwayat Tagihan</a>
        <a href="../index.php" class="btn btn-ghost" style="padding: 12px 25px; border-radius: 8px; border: 1px solid var(--color-border);">📊 Ke Dashboard</a>
      </div>
    </div>
  <?php else: ?>
    <!-- Payment Form -->
    <div class="admin-card animate-fade-in-up" id="paymentFormCard">
      <div class="admin-card-header" style="display: block;">
        <h3 style="font-size: 18px; margin-bottom: 5px;">📝 Konfirmasi Pembayaran Tagihan</h3>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 0; font-weight: normal;">Lengkapi formulir berikut untuk melakukan konfirmasi pembayaran Anda.</p>
      </div>

      <form id="paymentForm" class="admin-card-body" style="padding: 30px 25px;" method="POST" enctype="multipart/form-data">
        
        <!-- Informasi Anggota -->
        <div class="form-section">
          <h3 class="form-section-title">
            <span class="section-icon"><i class="fa-solid fa-user-shield"></i></span>
            Informasi Pemegang Polis
          </h3>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nama Lengkap</label>
              <input type="text" class="form-input" value="<?php echo htmlspecialchars($tagihan['nama_lengkap']); ?>" readonly>
            </div>
            <div class="form-group">
              <label class="form-label">Email Pemegang</label>
              <input type="text" class="form-input" value="<?php echo htmlspecialchars($tagihan['email']); ?>" readonly>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Produk Asuransi</label>
              <input type="text" class="form-input" value="<?php echo htmlspecialchars($tagihan['nama_produk']); ?>" readonly>
            </div>
            <div class="form-group">
              <label class="form-label">Nomor Polis</label>
              <input type="text" class="form-input" value="<?php echo htmlspecialchars($tagihan['no_polis']); ?>" readonly>
            </div>
          </div>
        </div>

        <!-- Nominal Pembayaran -->
        <div class="form-section">
          <h3 class="form-section-title">
            <span class="section-icon"><i class="fa-solid fa-money-bill-wave"></i></span>
            Nominal Pembayaran
          </h3>
          <div class="form-group">
            <label class="form-label">Jumlah Tagihan (Periode: <?php echo htmlspecialchars($tagihan['periode_bulan']); ?>)</label>
            <div style="position: relative;">
              <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-weight: 600; color: var(--color-dark);">Rp</span>
              <input type="text" class="form-input" value="<?php echo number_format($tagihan['jumlah_tagihan'], 0, ',', '.'); ?>" readonly style="padding-left: 45px; font-size: 18px; font-weight: 700; color: var(--color-blue); background: #f8fafc;">
            </div>
          </div>
        </div>

        <!-- Metode Pembayaran -->
        <div class="form-section">
          <h3 class="form-section-title">
            <span class="section-icon"><i class="fa-solid fa-building-columns"></i></span>
            Metode Pembayaran <span class="required">*</span>
          </h3>
          <div class="radio-group" id="paymentMethodGroup">
            <div class="radio-card">
              <input type="radio" name="payment_method" id="methodTransfer" value="transfer" required>
              <label for="methodTransfer" class="radio-card-label">
                <span class="radio-icon">🏦</span>
                <span class="radio-text">Transfer Bank</span>
                <span class="radio-desc">Pembayaran via transfer</span>
              </label>
            </div>
            <div class="radio-card">
              <input type="radio" name="payment_method" id="methodCash" value="tunai" required>
              <label for="methodCash" class="radio-card-label">
                <span class="radio-icon">💵</span>
                <span class="radio-text">Tunai / Cash</span>
                <span class="radio-desc">Pembayaran langsung di kantor</span>
              </label>
            </div>
          </div>

          <!-- Bank Options -->
          <div class="bank-options" id="bankOptions">
            <label class="form-label" style="display: block; margin-top: 20px; margin-bottom: 10px;">
              Pilih Bank Tujuan <span class="required">*</span>
            </label>
            <div class="bank-grid">
              <div class="bank-option">
                <input type="radio" name="bank" id="bankBCA" value="BCA">
                <label for="bankBCA" class="bank-option-label">
                  <span class="bank-logo" style="color:#0066ae;">BCA</span>
                  <span class="bank-name">Bank BCA</span>
                </label>
              </div>
              <div class="bank-option">
                <input type="radio" name="bank" id="bankMandiri" value="Mandiri">
                <label for="bankMandiri" class="bank-option-label">
                  <span class="bank-logo" style="color:#003d8f;">Mandiri</span>
                  <span class="bank-name">Bank Mandiri</span>
                </label>
              </div>
              <div class="bank-option">
                <input type="radio" name="bank" id="bankBNI" value="BNI">
                <label for="bankBNI" class="bank-option-label">
                  <span class="bank-logo" style="color:#f7a600;">BNI</span>
                  <span class="bank-name">Bank BNI</span>
                </label>
              </div>
              <div class="bank-option">
                <input type="radio" name="bank" id="bankBRI" value="BRI">
                <label for="bankBRI" class="bank-option-label">
                  <span class="bank-logo" style="color:#00529b;">BRI</span>
                  <span class="bank-name">Bank BRI</span>
                </label>
              </div>
            </div>

            <div class="bank-account-info" id="bankAccountInfo">
              <div class="bank-account-info-header">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Informasi Rekening AsuransiKu</span>
              </div>
              <div class="bank-account-info-body">
                <div class="bank-account-row">
                  <span class="bank-account-label">Bank Tujuan</span>
                  <span class="bank-account-value" id="bankInfoName">-</span>
                </div>
                <div class="bank-account-row">
                  <span class="bank-account-label">No. Rekening</span>
                  <span class="bank-account-value bank-account-number" id="bankInfoNumber">-</span>
                </div>
                <div class="bank-account-row">
                  <span class="bank-account-label">Atas Nama</span>
                  <span class="bank-account-value">PT AsuransiKu Indonesia</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bukti Pembayaran -->
        <div class="form-section">
          <h3 class="form-section-title">
            <span class="section-icon"><i class="fa-solid fa-file-arrow-up"></i></span>
            Referensi & Bukti
          </h3>
          
          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="ref">Nomor Referensi Transaksi <span class="required">*</span></label>
            <input type="text" name="referensi_pembayaran" id="ref" class="form-input" placeholder="Masukkan ID transaksi / nomor referensi" required>
          </div>

          <label class="form-label">Upload Bukti Transfer / Kwitansi</label>
          <div class="file-upload" id="uploadArea">
            <input type="file" id="proofFile" name="proof_of_payment" accept="image/jpeg,image/png,image/jpg,application/pdf">
            <div class="file-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <div class="file-upload-text">Drag & drop atau <strong>klik untuk upload</strong></div>
            <div class="file-upload-hint">Format: JPG, PNG, PDF • Maksimal 5MB</div>
          </div>
          
          <div class="file-preview-card" id="filePreviewCard">
            <div class="file-preview-thumb" id="filePreviewThumb"><i class="fa-solid fa-file-invoice"></i></div>
            <div class="file-preview-info">
              <span class="file-preview-name" id="fileName">nama_file.jpg</span>
              <span class="file-preview-size" id="fileSize">0 KB</span>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div class="payment-summary">
          <div class="payment-summary-row">
            <span class="payment-summary-label">Metode Pembayaran</span>
            <span class="payment-summary-value" id="summaryMethod">Belum dipilih</span>
          </div>
          <div class="payment-summary-row total">
            <span class="payment-summary-label">Total Dibayar</span>
            <span class="payment-summary-value">Rp <?php echo number_format($tagihan['jumlah_tagihan'], 0, ',', '.'); ?></span>
          </div>
        </div>

      </form>

      <div class="payment-form-footer">
        <a href="index.php" class="btn btn-ghost" style="padding: 12px 20px; font-weight: 600; text-decoration: none; border-radius: 8px;">← Batal</a>
        <button type="submit" form="paymentForm" class="btn btn-primary" style="padding: 12px 30px; border-radius: 8px; font-weight: 600; font-size: 16px;">
          <i class="fa-solid fa-paper-plane" style="margin-right: 8px;"></i> Kirim Pembayaran
        </button>
      </div>
    </div>
  <?php endif; ?>

</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const methodTransfer = document.getElementById('methodTransfer');
    const methodCash = document.getElementById('methodCash');
    const bankOptions = document.getElementById('bankOptions');
    const summaryMethod = document.getElementById('summaryMethod');
    
    // Bank details map
    const bankData = {
      'BCA': '8901234567',
      'Mandiri': '1370001234567',
      'BNI': '0098765432',
      'BRI': '034101000123456'
    };

    const bankAccountInfo = document.getElementById('bankAccountInfo');
    const bankInfoName = document.getElementById('bankInfoName');
    const bankInfoNumber = document.getElementById('bankInfoNumber');
    const bankRadios = document.querySelectorAll('input[name="bank"]');
    
    // Handle Metode Pembayaran Change
    function updateMethod() {
      if (methodTransfer.checked) {
        bankOptions.classList.add('visible');
        summaryMethod.textContent = 'Transfer Bank';
        // Make bank required if transfer
        bankRadios.forEach(r => r.required = true);
      } else if (methodCash.checked) {
        bankOptions.classList.remove('visible');
        summaryMethod.textContent = 'Tunai / Cash';
        // Reset bank selection
        bankRadios.forEach(r => {
          r.checked = false;
          r.required = false;
        });
        bankAccountInfo.classList.remove('visible');
      }
    }

    methodTransfer.addEventListener('change', updateMethod);
    methodCash.addEventListener('change', updateMethod);

    // Handle Bank Selection
    bankRadios.forEach(radio => {
      radio.addEventListener('change', function() {
        if (this.checked) {
          const bank = this.value;
          bankInfoName.textContent = 'Bank ' + bank;
          bankInfoNumber.textContent = bankData[bank];
          bankAccountInfo.classList.add('visible');
          summaryMethod.textContent = 'Transfer Bank (' + bank + ')';
        }
      });
    });

    // Handle File Upload Preview
    const proofFile = document.getElementById('proofFile');
    const filePreviewCard = document.getElementById('filePreviewCard');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');

    proofFile.addEventListener('change', function(e) {
      if (this.files && this.files[0]) {
        const file = this.files[0];
        fileName.textContent = file.name;
        
        // Format size
        let size = file.size / 1024;
        if (size > 1024) {
          fileSize.textContent = (size / 1024).toFixed(2) + ' MB';
        } else {
          fileSize.textContent = size.toFixed(0) + ' KB';
        }
        
        filePreviewCard.classList.add('visible');
      } else {
        filePreviewCard.classList.remove('visible');
      }
    });
  });
</script>

<?php require_once __DIR__ . "/../../layouts/customer/footer.php"; ?>
