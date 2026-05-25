-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: May 25, 2026 at 03:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bd_asuransi`
--

-- --------------------------------------------------------

--
-- Table structure for table `ahli_waris`
--

CREATE TABLE `ahli_waris` (
  `id_ahli_waris` int(11) NOT NULL,
  `id_polis` int(11) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `hubungan` varchar(50) NOT NULL,
  `persentase_pembagian` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_klaim`
--

CREATE TABLE `dokumen_klaim` (
  `id_dokumen` int(11) NOT NULL,
  `id_klaim` int(11) NOT NULL,
  `jenis_dokumen` varchar(100) NOT NULL,
  `path_file` varchar(255) NOT NULL,
  `diunggah_pada` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `karyawan_agen`
--

CREATE TABLE `karyawan_agen` (
  `id_karyawan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nomor_induk` varchar(50) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `posisi` enum('Staf','Manajer','Agen') NOT NULL,
  `id_manajer` int(11) DEFAULT NULL,
  `cabang` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `klaim`
--

CREATE TABLE `klaim` (
  `id_klaim` int(11) NOT NULL,
  `nomor_klaim` varchar(50) NOT NULL,
  `id_polis` int(11) NOT NULL,
  `id_tertanggung` int(11) NOT NULL,
  `jenis_klaim` enum('Kematian','Rawat Inap','Kecelakaan','Kerusakan') NOT NULL,
  `tanggal_kejadian` date NOT NULL,
  `jumlah_dituntut` decimal(15,2) NOT NULL,
  `jumlah_disetujui` decimal(15,2) DEFAULT NULL,
  `status_terakhir` enum('Open','Investigasi','Approved','Rejected','Paid') DEFAULT 'Open',
  `tanggal_pengajuan` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `komisi_agen`
--

CREATE TABLE `komisi_agen` (
  `id_komisi` int(11) NOT NULL,
  `id_agen` int(11) NOT NULL,
  `id_pembayaran` int(11) NOT NULL,
  `persentase_komisi` decimal(5,2) NOT NULL,
  `jumlah_komisi` decimal(15,2) NOT NULL,
  `status_pencairan` enum('Pending','Paid') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nasabah`
--

CREATE TABLE `nasabah` (
  `id_nasabah` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nik_ktp` varchar(16) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `penghasilan_bulanan` decimal(15,2) DEFAULT NULL,
  `alamat_lengkap` text NOT NULL,
  `status_kyc` enum('Unverified','Verified') DEFAULT 'Unverified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_tagihan` int(11) NOT NULL,
  `metode_bayar` enum('Transfer Bank','Virtual Account','Kartu Kredit') NOT NULL,
  `jumlah_dibayar` decimal(15,2) NOT NULL,
  `tanggal_bayar` datetime DEFAULT current_timestamp(),
  `referensi_transaksi` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_asuransi`
--

CREATE TABLE `pengajuan_asuransi` (
  `id_pengajuan` int(11) NOT NULL,
  `id_nasabah` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_agen` int(11) NOT NULL,
  `tingkat_risiko` enum('Low','Medium','High') DEFAULT NULL,
  `keputusan` enum('Pending','Approved','Declined','Postponed') DEFAULT 'Pending',
  `catatan_medis` text DEFAULT NULL,
  `tanggal_pengajuan` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `polis`
--

CREATE TABLE `polis` (
  `id_polis` int(11) NOT NULL,
  `nomor_polis` varchar(50) NOT NULL,
  `id_pengajuan` int(11) NOT NULL,
  `id_pemegang_polis` int(11) NOT NULL,
  `tanggal_terbit` date NOT NULL,
  `tanggal_jatuh_tempo_berikutnya` date NOT NULL,
  `frekuensi_bayar` enum('Bulanan','Kuartalan','Tahunan') NOT NULL,
  `status_polis` enum('In-Force','Lapsed','Surrender','Claimed') DEFAULT 'In-Force'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `polis_riders`
--

CREATE TABLE `polis_riders` (
  `id_polis_rider` int(11) NOT NULL,
  `id_polis` int(11) NOT NULL,
  `id_rider` int(11) NOT NULL,
  `premi_rider` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produk_rider`
--

CREATE TABLE `produk_rider` (
  `id_rider` int(11) NOT NULL,
  `id_produk_utama` int(11) NOT NULL,
  `nama_rider` varchar(100) NOT NULL,
  `biaya_tambahan` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produk_utama`
--

CREATE TABLE `produk_utama` (
  `id_produk` int(11) NOT NULL,
  `kode_produk` varchar(20) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `kategori` enum('Jiwa','Kesehatan','Kendaraan','Properti') NOT NULL,
  `uang_pertanggungan_dasar` decimal(15,2) NOT NULL,
  `status` enum('Aktif','Inaktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_status_klaim`
--

CREATE TABLE `riwayat_status_klaim` (
  `id_riwayat` int(11) NOT NULL,
  `id_klaim` int(11) NOT NULL,
  `status_baru` varchar(50) NOT NULL,
  `diubah_oleh` int(11) NOT NULL,
  `tanggal_ubah` datetime DEFAULT current_timestamp(),
  `keterangan_internal` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id_role` int(11) NOT NULL,
  `nama_role` varchar(50) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tagihan_premi`
--

CREATE TABLE `tagihan_premi` (
  `id_tagihan` int(11) NOT NULL,
  `id_polis` int(11) NOT NULL,
  `nomor_tagihan` varchar(50) NOT NULL,
  `periode_bulan` int(11) NOT NULL,
  `periode_tahun` int(11) NOT NULL,
  `jumlah_tagihan` decimal(15,2) NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `status_tagihan` enum('Unpaid','Paid','Overdue') DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tertanggung`
--

CREATE TABLE `tertanggung` (
  `id_tertanggung` int(11) NOT NULL,
  `id_polis` int(11) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `hubungan_dengan_pemegang` enum('Diri Sendiri','Pasangan','Anak','Orang Tua') NOT NULL,
  `tanggal_lahir` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `id_role` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ahli_waris`
--
ALTER TABLE `ahli_waris`
  ADD PRIMARY KEY (`id_ahli_waris`),
  ADD KEY `id_polis` (`id_polis`);

--
-- Indexes for table `dokumen_klaim`
--
ALTER TABLE `dokumen_klaim`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD KEY `id_klaim` (`id_klaim`);

--
-- Indexes for table `karyawan_agen`
--
ALTER TABLE `karyawan_agen`
  ADD PRIMARY KEY (`id_karyawan`),
  ADD UNIQUE KEY `id_user` (`id_user`),
  ADD UNIQUE KEY `nomor_induk` (`nomor_induk`),
  ADD KEY `id_manajer` (`id_manajer`);

--
-- Indexes for table `klaim`
--
ALTER TABLE `klaim`
  ADD PRIMARY KEY (`id_klaim`),
  ADD UNIQUE KEY `nomor_klaim` (`nomor_klaim`),
  ADD KEY `id_polis` (`id_polis`),
  ADD KEY `id_tertanggung` (`id_tertanggung`);

--
-- Indexes for table `komisi_agen`
--
ALTER TABLE `komisi_agen`
  ADD PRIMARY KEY (`id_komisi`),
  ADD KEY `id_agen` (`id_agen`),
  ADD KEY `id_pembayaran` (`id_pembayaran`);

--
-- Indexes for table `nasabah`
--
ALTER TABLE `nasabah`
  ADD PRIMARY KEY (`id_nasabah`),
  ADD UNIQUE KEY `id_user` (`id_user`),
  ADD UNIQUE KEY `nik_ktp` (`nik_ktp`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD UNIQUE KEY `referensi_transaksi` (`referensi_transaksi`),
  ADD KEY `id_tagihan` (`id_tagihan`);

--
-- Indexes for table `pengajuan_asuransi`
--
ALTER TABLE `pengajuan_asuransi`
  ADD PRIMARY KEY (`id_pengajuan`),
  ADD KEY `id_nasabah` (`id_nasabah`),
  ADD KEY `id_produk` (`id_produk`),
  ADD KEY `id_agen` (`id_agen`);

--
-- Indexes for table `polis`
--
ALTER TABLE `polis`
  ADD PRIMARY KEY (`id_polis`),
  ADD UNIQUE KEY `nomor_polis` (`nomor_polis`),
  ADD KEY `id_pengajuan` (`id_pengajuan`),
  ADD KEY `id_pemegang_polis` (`id_pemegang_polis`);

--
-- Indexes for table `polis_riders`
--
ALTER TABLE `polis_riders`
  ADD PRIMARY KEY (`id_polis_rider`),
  ADD KEY `id_polis` (`id_polis`),
  ADD KEY `id_rider` (`id_rider`);

--
-- Indexes for table `produk_rider`
--
ALTER TABLE `produk_rider`
  ADD PRIMARY KEY (`id_rider`),
  ADD KEY `id_produk_utama` (`id_produk_utama`);

--
-- Indexes for table `produk_utama`
--
ALTER TABLE `produk_utama`
  ADD PRIMARY KEY (`id_produk`),
  ADD UNIQUE KEY `kode_produk` (`kode_produk`);

--
-- Indexes for table `riwayat_status_klaim`
--
ALTER TABLE `riwayat_status_klaim`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `id_klaim` (`id_klaim`),
  ADD KEY `diubah_oleh` (`diubah_oleh`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_role`);

--
-- Indexes for table `tagihan_premi`
--
ALTER TABLE `tagihan_premi`
  ADD PRIMARY KEY (`id_tagihan`),
  ADD UNIQUE KEY `nomor_tagihan` (`nomor_tagihan`),
  ADD KEY `id_polis` (`id_polis`);

--
-- Indexes for table `tertanggung`
--
ALTER TABLE `tertanggung`
  ADD PRIMARY KEY (`id_tertanggung`),
  ADD KEY `id_polis` (`id_polis`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_role` (`id_role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ahli_waris`
--
ALTER TABLE `ahli_waris`
  MODIFY `id_ahli_waris` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dokumen_klaim`
--
ALTER TABLE `dokumen_klaim`
  MODIFY `id_dokumen` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `karyawan_agen`
--
ALTER TABLE `karyawan_agen`
  MODIFY `id_karyawan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `klaim`
--
ALTER TABLE `klaim`
  MODIFY `id_klaim` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `komisi_agen`
--
ALTER TABLE `komisi_agen`
  MODIFY `id_komisi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nasabah`
--
ALTER TABLE `nasabah`
  MODIFY `id_nasabah` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengajuan_asuransi`
--
ALTER TABLE `pengajuan_asuransi`
  MODIFY `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `polis`
--
ALTER TABLE `polis`
  MODIFY `id_polis` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `polis_riders`
--
ALTER TABLE `polis_riders`
  MODIFY `id_polis_rider` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produk_rider`
--
ALTER TABLE `produk_rider`
  MODIFY `id_rider` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produk_utama`
--
ALTER TABLE `produk_utama`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `riwayat_status_klaim`
--
ALTER TABLE `riwayat_status_klaim`
  MODIFY `id_riwayat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_role` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tagihan_premi`
--
ALTER TABLE `tagihan_premi`
  MODIFY `id_tagihan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tertanggung`
--
ALTER TABLE `tertanggung`
  MODIFY `id_tertanggung` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ahli_waris`
--
ALTER TABLE `ahli_waris`
  ADD CONSTRAINT `ahli_waris_ibfk_1` FOREIGN KEY (`id_polis`) REFERENCES `polis` (`id_polis`) ON DELETE CASCADE;

--
-- Constraints for table `dokumen_klaim`
--
ALTER TABLE `dokumen_klaim`
  ADD CONSTRAINT `dokumen_klaim_ibfk_1` FOREIGN KEY (`id_klaim`) REFERENCES `klaim` (`id_klaim`) ON DELETE CASCADE;

--
-- Constraints for table `karyawan_agen`
--
ALTER TABLE `karyawan_agen`
  ADD CONSTRAINT `karyawan_agen_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `karyawan_agen_ibfk_2` FOREIGN KEY (`id_manajer`) REFERENCES `karyawan_agen` (`id_karyawan`) ON DELETE SET NULL;

--
-- Constraints for table `klaim`
--
ALTER TABLE `klaim`
  ADD CONSTRAINT `klaim_ibfk_1` FOREIGN KEY (`id_polis`) REFERENCES `polis` (`id_polis`),
  ADD CONSTRAINT `klaim_ibfk_2` FOREIGN KEY (`id_tertanggung`) REFERENCES `tertanggung` (`id_tertanggung`);

--
-- Constraints for table `komisi_agen`
--
ALTER TABLE `komisi_agen`
  ADD CONSTRAINT `komisi_agen_ibfk_1` FOREIGN KEY (`id_agen`) REFERENCES `karyawan_agen` (`id_karyawan`),
  ADD CONSTRAINT `komisi_agen_ibfk_2` FOREIGN KEY (`id_pembayaran`) REFERENCES `pembayaran` (`id_pembayaran`);

--
-- Constraints for table `nasabah`
--
ALTER TABLE `nasabah`
  ADD CONSTRAINT `nasabah_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_tagihan`) REFERENCES `tagihan_premi` (`id_tagihan`);

--
-- Constraints for table `pengajuan_asuransi`
--
ALTER TABLE `pengajuan_asuransi`
  ADD CONSTRAINT `pengajuan_asuransi_ibfk_1` FOREIGN KEY (`id_nasabah`) REFERENCES `nasabah` (`id_nasabah`),
  ADD CONSTRAINT `pengajuan_asuransi_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk_utama` (`id_produk`),
  ADD CONSTRAINT `pengajuan_asuransi_ibfk_3` FOREIGN KEY (`id_agen`) REFERENCES `karyawan_agen` (`id_karyawan`);

--
-- Constraints for table `polis`
--
ALTER TABLE `polis`
  ADD CONSTRAINT `polis_ibfk_1` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan_asuransi` (`id_pengajuan`),
  ADD CONSTRAINT `polis_ibfk_2` FOREIGN KEY (`id_pemegang_polis`) REFERENCES `nasabah` (`id_nasabah`);

--
-- Constraints for table `polis_riders`
--
ALTER TABLE `polis_riders`
  ADD CONSTRAINT `polis_riders_ibfk_1` FOREIGN KEY (`id_polis`) REFERENCES `polis` (`id_polis`) ON DELETE CASCADE,
  ADD CONSTRAINT `polis_riders_ibfk_2` FOREIGN KEY (`id_rider`) REFERENCES `produk_rider` (`id_rider`);

--
-- Constraints for table `produk_rider`
--
ALTER TABLE `produk_rider`
  ADD CONSTRAINT `produk_rider_ibfk_1` FOREIGN KEY (`id_produk_utama`) REFERENCES `produk_utama` (`id_produk`) ON DELETE CASCADE;

--
-- Constraints for table `riwayat_status_klaim`
--
ALTER TABLE `riwayat_status_klaim`
  ADD CONSTRAINT `riwayat_status_klaim_ibfk_1` FOREIGN KEY (`id_klaim`) REFERENCES `klaim` (`id_klaim`) ON DELETE CASCADE,
  ADD CONSTRAINT `riwayat_status_klaim_ibfk_2` FOREIGN KEY (`diubah_oleh`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `tagihan_premi`
--
ALTER TABLE `tagihan_premi`
  ADD CONSTRAINT `tagihan_premi_ibfk_1` FOREIGN KEY (`id_polis`) REFERENCES `polis` (`id_polis`);

--
-- Constraints for table `tertanggung`
--
ALTER TABLE `tertanggung`
  ADD CONSTRAINT `tertanggung_ibfk_1` FOREIGN KEY (`id_polis`) REFERENCES `polis` (`id_polis`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;