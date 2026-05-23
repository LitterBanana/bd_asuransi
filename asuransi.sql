-- ==============================================================================
-- 1. SETUP DATABASE
-- ==============================================================================
DROP DATABASE IF EXISTS asuransi;
CREATE DATABASE asuransi;
USE asuransi;

-- ==============================================================================
-- 2. DDL: PEMBUATAN TABEL MASTER
-- ==============================================================================

CREATE TABLE agen (
    id_agen INT AUTO_INCREMENT PRIMARY KEY,
    kode_agen VARCHAR(20) UNIQUE NOT NULL,
    nama_agen VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20),
    persentase_komisi DECIMAL(5,2) DEFAULT 5.00,
    status_aktif ENUM('Aktif', 'Resign') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE produk_asuransi (
    id_produk INT AUTO_INCREMENT PRIMARY KEY,
    kode_produk VARCHAR(20) UNIQUE NOT NULL,
    nama_produk VARCHAR(100) NOT NULL,
    jenis_kategori ENUM('Individu', 'Keluarga', 'Kumpulan/Perusahaan') NOT NULL,
    limit_tahunan DECIMAL(15,2) NOT NULL,
    premi_dasar DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE faskes (
    id_faskes INT AUTO_INCREMENT PRIMARY KEY,
    kode_faskes VARCHAR(20) UNIQUE NOT NULL,
    nama_faskes VARCHAR(150) NOT NULL,
    tingkat_faskes ENUM('Klinik Pratama', 'Klinik Utama', 'RS Tipe A', 'RS Tipe B', 'RS Tipe C') NOT NULL,
    alamat TEXT NOT NULL,
    kota VARCHAR(100) NOT NULL,
    status_kerjasama ENUM('Aktif', 'Putus Kontrak') DEFAULT 'Aktif'
);

CREATE TABLE kategori_penyakit (
    kode_icd VARCHAR(10) PRIMARY KEY,
    nama_penyakit VARCHAR(150) NOT NULL,
    kategori_berat ENUM('Ringan', 'Sedang', 'Berat', 'Kritis') NOT NULL
);

CREATE TABLE pemegang_polis (
    id_pemegang INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(150) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    pekerjaan VARCHAR(100),
    alamat TEXT NOT NULL,
    no_telepon VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================================================
-- 3. DDL: PEMBUATAN TABEL TRANSAKSIONAL (OPERASIONAL)
-- ==============================================================================

CREATE TABLE polis (
    no_polis VARCHAR(50) PRIMARY KEY,
    id_pemegang INT NOT NULL,
    id_produk INT NOT NULL,
    id_agen INT NULL, 
    tanggal_terbit DATE NOT NULL,
    tanggal_jatuh_tempo DATE NOT NULL,
    status_polis ENUM('Inforce', 'Lapse', 'Surrender', 'Claimed') DEFAULT 'Inforce',
    total_premi_berjalan DECIMAL(15,2) DEFAULT 0.00,
    FOREIGN KEY (id_pemegang) REFERENCES pemegang_polis(id_pemegang) ON DELETE CASCADE,
    FOREIGN KEY (id_produk) REFERENCES produk_asuransi(id_produk) ON DELETE RESTRICT,
    FOREIGN KEY (id_agen) REFERENCES agen(id_agen) ON DELETE SET NULL
);

CREATE TABLE tanggungan_polis (
    id_tanggungan INT AUTO_INCREMENT PRIMARY KEY,
    no_polis VARCHAR(50) NOT NULL,
    nik VARCHAR(16) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(150) NOT NULL,
    hubungan ENUM('Suami', 'Istri', 'Anak') NOT NULL,
    tanggal_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    FOREIGN KEY (no_polis) REFERENCES polis(no_polis) ON DELETE CASCADE
);

CREATE TABLE tagihan_premi (
    no_tagihan VARCHAR(50) PRIMARY KEY,
    no_polis VARCHAR(50) NOT NULL,
    periode_bulan VARCHAR(7) NOT NULL, 
    jumlah_tagihan DECIMAL(10,2) NOT NULL,
    tanggal_cetak DATE NOT NULL,
    jatuh_tempo DATE NOT NULL,
    status_tagihan ENUM('Unpaid', 'Paid', 'Overdue') DEFAULT 'Unpaid',
    FOREIGN KEY (no_polis) REFERENCES polis(no_polis) ON DELETE CASCADE
);

CREATE TABLE pembayaran_premi (
    id_pembayaran INT AUTO_INCREMENT PRIMARY KEY,
    no_tagihan VARCHAR(50) NOT NULL,
    tanggal_bayar DATETIME NOT NULL,
    nominal_bayar DECIMAL(10,2) NOT NULL,
    metode_bayar ENUM('Transfer Bank', 'Virtual Account', 'Kartu Kredit', 'E-Wallet', 'Cash') NOT NULL,
    bank_name VARCHAR(50) NULL,
    referensi_pembayaran VARCHAR(100),
    status_pembayaran ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (no_tagihan) REFERENCES tagihan_premi(no_tagihan) ON DELETE CASCADE
);

CREATE TABLE klaim_medis (
    no_klaim VARCHAR(50) PRIMARY KEY,
    no_polis VARCHAR(50) NOT NULL,
    id_tanggungan INT NULL, 
    id_faskes INT NOT NULL,
    kode_icd VARCHAR(10) NOT NULL,
    tanggal_masuk DATE NOT NULL,
    tanggal_keluar DATE,
    jenis_perawatan ENUM('Rawat Jalan', 'Rawat Inap', 'Pembedahan') NOT NULL,
    status_klaim ENUM('Pending', 'Investigasi', 'Approved', 'Rejected') DEFAULT 'Pending',
    total_tagihan_faskes DECIMAL(15,2) DEFAULT 0.00,
    total_dibayarkan_asuransi DECIMAL(15,2) DEFAULT 0.00,
    catatan_analis TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (no_polis) REFERENCES polis(no_polis) ON DELETE CASCADE,
    FOREIGN KEY (id_tanggungan) REFERENCES tanggungan_polis(id_tanggungan) ON DELETE SET NULL,
    FOREIGN KEY (id_faskes) REFERENCES faskes(id_faskes) ON DELETE RESTRICT,
    FOREIGN KEY (kode_icd) REFERENCES kategori_penyakit(kode_icd) ON DELETE RESTRICT
);

CREATE TABLE detail_klaim (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    no_klaim VARCHAR(50) NOT NULL,
    jenis_biaya ENUM('Kamar', 'Dokter', 'Obat', 'Laboratorium', 'Tindakan') NOT NULL,
    deskripsi VARCHAR(255) NOT NULL,
    nominal_biaya DECIMAL(10,2) NOT NULL,
    status_cover ENUM('Dicover', 'Tidak Dicover') DEFAULT 'Dicover',
    FOREIGN KEY (no_klaim) REFERENCES klaim_medis(no_klaim) ON DELETE CASCADE
);


-- -- ==============================================================================
-- -- 4. User Accounts
-- -- ==============================================================================

CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Di PHP nanti gunakan fungsi password_hash()
    email VARCHAR(100) UNIQUE,
    no_telp VARCHAR(20),
    role ENUM('Admin', 'Agen', 'Customer') NOT NULL,
    id_agen INT NULL,       -- Terisi jika role = 'Agen'
    id_pemegang INT NULL,   -- Terisi jika role = 'Customer'
    status_akun ENUM('Aktif', 'Blokir') DEFAULT 'Aktif',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_agen) REFERENCES agen(id_agen) ON DELETE CASCADE,
    FOREIGN KEY (id_pemegang) REFERENCES pemegang_polis(id_pemegang) ON DELETE CASCADE
);

-- ==============================================================================
-- 5. DML: INSERT DATA DUMMY
-- ==============================================================================

INSERT INTO agen (kode_agen, nama_agen, no_telepon) VALUES 
('AG-001', 'Budi Santoso', '08111222333'),
('AG-002', 'Rina Melati', '08111444555');

INSERT INTO produk_asuransi (kode_produk, nama_produk, jenis_kategori, limit_tahunan, premi_dasar) VALUES
('PRD-INV-01', 'Sehat Personal Platinum', 'Individu', 250000000.00, 750000.00),
('PRD-FAM-01', 'Sehat Keluarga Max', 'Keluarga', 500000000.00, 1500000.00);

INSERT INTO faskes (kode_faskes, nama_faskes, tingkat_faskes, alamat, kota) VALUES
('FSK-001', 'RSUD Tangerang Selatan', 'RS Tipe C', 'Jl. Pajajaran, Pamulang', 'Tangerang Selatan'),
('FSK-002', 'RS Hermina Ciputat', 'RS Tipe B', 'Jl. Ir H. Juanda, Ciputat', 'Tangerang Selatan'),
('FSK-003', 'Klinik Medika Setu', 'Klinik Utama', 'Jl. Raya Setu', 'Tangerang Selatan');

INSERT INTO kategori_penyakit (kode_icd, nama_penyakit, kategori_berat) VALUES
('A09', 'Diare dan Gastroenteritis', 'Ringan'),
('J06.9', 'Infeksi Saluran Pernapasan Akut (ISPA)', 'Ringan'),
('K35', 'Apendisitis Akut (Usus Buntu)', 'Sedang'),
('I21', 'Infark Miokard Akut (Serangan Jantung)', 'Kritis');

INSERT INTO pemegang_polis (nik, nama_lengkap, tanggal_lahir, jenis_kelamin, pekerjaan, alamat, email) VALUES
('3674010101900001', 'Azzam', '1998-05-12', 'L', 'Software Engineer', 'Kecamatan Setu', 'azzam.dev@email.com'),
('3674010202850002', 'Diana Sari', '1985-08-20', 'P', 'Pengusaha', 'Kecamatan Pamulang', 'diana.sari@email.com');

INSERT INTO polis (no_polis, id_pemegang, id_produk, id_agen, tanggal_terbit, tanggal_jatuh_tempo, status_polis) VALUES
('POL-2025-00001', 1, 1, 1, '2025-01-10', '2026-01-10', 'Inforce'),
('POL-2025-00002', 2, 2, 2, '2025-02-15', '2026-02-15', 'Inforce');

INSERT INTO tanggungan_polis (no_polis, nik, nama_lengkap, hubungan, tanggal_lahir, jenis_kelamin) VALUES
('POL-2025-00002', '3674010303820003', 'Rudi Hermawan', 'Suami', '1982-11-10', 'L'),
('POL-2025-00002', '3674010404150004', 'Kevin Hermawan', 'Anak', '2015-04-05', 'L');

INSERT INTO tagihan_premi (no_tagihan, no_polis, periode_bulan, jumlah_tagihan, tanggal_cetak, jatuh_tempo, status_tagihan) VALUES
('INV-202505-001', 'POL-2025-00001', '2025-05', 750000.00, '2025-05-01', '2025-05-15', 'Paid'),
('INV-202505-002', 'POL-2025-00002', '2025-05', 1500000.00, '2025-05-01', '2025-05-15', 'Unpaid');

INSERT INTO pembayaran_premi (no_tagihan, tanggal_bayar, nominal_bayar, metode_bayar, bank_name, referensi_pembayaran, status_pembayaran) VALUES
('INV-202505-001', '2025-05-12 10:30:00', 750000.00, 'Virtual Account', 'BCA', 'VA-BCA-987654321', 'Verified');

INSERT INTO klaim_medis (no_klaim, no_polis, id_tanggungan, id_faskes, kode_icd, tanggal_masuk, tanggal_keluar, jenis_perawatan, status_klaim, total_tagihan_faskes, total_dibayarkan_asuransi) VALUES
('KLM-202505-001', 'POL-2025-00001', NULL, 3, 'J06.9', '2025-05-05', '2025-05-05', 'Rawat Jalan', 'Approved', 450000.00, 450000.00),
('KLM-202505-002', 'POL-2025-00002', 2, 2, 'K35', '2025-05-10', '2025-05-14', 'Rawat Inap', 'Investigasi', 25500000.00, 0.00);

INSERT INTO detail_klaim (no_klaim, jenis_biaya, deskripsi, nominal_biaya, status_cover) VALUES
('KLM-202505-001', 'Dokter', 'Konsultasi Dokter Umum', 150000.00, 'Dicover'),
('KLM-202505-001', 'Obat', 'Antibiotik dan Vitamin', 300000.00, 'Dicover'),
('KLM-202505-002', 'Kamar', 'Kamar VIP 4 Hari', 4000000.00, 'Dicover'),
('KLM-202505-002', 'Tindakan', 'Operasi Apendisitis', 15000000.00, 'Dicover'),
('KLM-202505-002', 'Obat', 'Suplemen Tambahan (Non-Medis)', 500000.00, 'Tidak Dicover'),
('KLM-202505-002', 'Laboratorium', 'Cek Darah Lengkap', 6000000.00, 'Dicover');

-- Insert Akun Admin (Tidak terikat pada agen atau pemegang polis)
-- Anggap passwordnya adalah 'admin123' (di sistem nyata ini harus di-hash)
INSERT INTO users (username, password, email, no_telp, role, id_agen, id_pemegang) 
VALUES ('admin_pusat', 'admin123', 'admin@asuransiku.com', '08111222000', 'Admin', NULL, NULL);

-- Insert Akun Agen (Terhubung ke Budi Santoso, id_agen = 1)
-- Anggap passwordnya adalah 'agen123'
INSERT INTO users (username, password, email, no_telp, role, id_agen, id_pemegang) 
VALUES ('budi_agen', 'agen123', 'budi@asuransiku.com', '08111222333', 'Agen', 1, NULL);

-- Insert Akun Customer (Terhubung ke Azzam, id_pemegang = 1)
-- Anggap passwordnya adalah 'customer123'
INSERT INTO users (username, password, email, no_telp, role, id_agen, id_pemegang) 
VALUES ('azzam_user', 'customer123', 'azzam.dev@email.com', '08123456789', 'Customer', NULL, 1);


-- -- ==============================================================================
-- -- 5. VIEWS (UNTUK MEMPERMUDAH PEMBUATAN LAPORAN DI PHP)
-- -- ==============================================================================

-- CREATE OR REPLACE VIEW vw_laporan_pendapatan AS
-- SELECT 
--     DATE_FORMAT(pp.tanggal_bayar, '%Y-%m') AS bulan,
--     COUNT(pp.id_pembayaran) AS total_transaksi,
--     SUM(pp.nominal_bayar) AS total_pendapatan,
--     pp.metode_bayar 
-- FROM pembayaran_premi pp
-- JOIN tagihan_premi tp ON pp.no_tagihan = tp.no_tagihan
-- GROUP BY bulan, pp.metode_bayar;

-- CREATE OR REPLACE VIEW vw_rasio_klaim_faskes AS
-- SELECT 
--     f.nama_faskes,
--     f.tingkat_faskes,
--     COUNT(km.no_klaim) AS jumlah_pasien,
--     SUM(km.total_tagihan_faskes) AS total_tagihan_kotor,
--     SUM(km.total_dibayarkan_asuransi) AS total_dibayarkan_perusahaan
-- FROM faskes f
-- LEFT JOIN klaim_medis km ON f.id_faskes = km.id_faskes
-- WHERE km.status_klaim = 'Approved'
-- GROUP BY f.id_faskes;

-- CREATE OR REPLACE VIEW vw_riwayat_medis_pasien AS
-- SELECT 
--     km.no_klaim,
--     p.no_polis,
--     COALESCE(t.nama_lengkap, pp.nama_lengkap) AS nama_pasien,
--     CASE 
--         WHEN t.id_tanggungan IS NOT NULL THEN 'Tanggungan' 
--         ELSE 'Pemegang Polis' 
--     END AS status_pasien,
--     kp.nama_penyakit,
--     km.tanggal_masuk,
--     km.jenis_perawatan,
--     km.status_klaim
-- FROM klaim_medis km
-- JOIN polis p ON km.no_polis = p.no_polis
-- JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
-- LEFT JOIN tanggungan_polis t ON km.id_tanggungan = t.id_tanggungan
-- JOIN kategori_penyakit kp ON km.kode_icd = kp.kode_icd;

-- -- Mengambil data lengkap saat Customer Login berdasarkan username
-- SELECT 
--     u.username, 
--     u.role, 
--     p.nama_lengkap, 
--     p.email, 
--     pol.no_polis, 
--     pol.status_polis
-- FROM users u
-- JOIN pemegang_polis p ON u.id_pemegang = p.id_pemegang
-- LEFT JOIN polis pol ON p.id_pemegang = pol.id_pemegang
-- WHERE u.username = 'azzam_user';