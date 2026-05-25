---
trigger: always_on
---

---

## trigger: manual

# Panduan AI (Agent Context) - Proyek Asuransi

## 1. Identitas Proyek

- **Bahasa:** PHP Native (Minimal PHP 8.x)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, Vanilla JavaScript (atau Bootstrap jika Anda pakai)

## 2. Aturan Menulis Kode (Coding Standards)

- **DILARANG** menggunakan sintaks framework (Laravel, Symfony, CodeIgniter). Semua harus murni PHP Native.
- Gunakan `<?php` yang standar, hindari _short open tags_ `<?`.
- Pisahkan logika bisnis (PHP) dengan tampilan (HTML) sebisa mungkin. Jangan mencampur logika query SQL di dalam tag HTML.

## 3. Aturan Database & Keamanan

- Gunakan ekstensi **PDO** atau **MySQLi** (Object-Oriented) untuk koneksi database.
- WAJIB menggunakan **Prepared Statements** untuk setiap query SQL yang melibatkan input user untuk mencegah SQL Injection.
- Contoh yang diizinkan: `$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");`
- WAJIB memvalidasi dan melakukan sanitasi pada setiap variabel global seperti `$_POST`, `$_GET`, dan `$_COOKIE`. Gunakan `htmlspecialchars()` saat menampilkan data ke HTML untuk mencegah XSS.

## 4. Manajemen Error

- Jangan tampilkan error database langsung ke layar (_production mode_). Tangkap dengan `try-catch` block.
- Gunakan `error_log()` untuk mencatat error sistem.

## 5. Aturan Deployment

- Lokasi files : C:\xampp\htdocs\asuransi\*\*
- Server : Apache

## 6. Role dan Hak Akses

- **Admin:** Memiliki akses penuh ke semua fitur sistem, termasuk manajemen data, pelaporan, dan konfigurasi sistem.
- **Agen:** Memiliki akses terbatas ke fitur-fitur yang berkaitan dengan penjualan dan pelayanan nasabah, seperti input data nasabah, pembuatan polis, dan input data klaim.
- **Customer:** Memiliki akses terbatas ke fitur-fitur yang berkaitan dengan pelayanan nasabah, seperti input data nasabah, pembuatan polis, dan input data klaim.

## 7. Aturan Styling (CSS)

- Gunakan variabel CSS untuk konsistensi warna dan ukuran font.
- Jaga konsistensi layout antar halaman. Jangan membuat layout yang berbeda untuk halaman yang berbeda.

## 8. Aturan JavaScript

- Gunakan Vanilla JavaScript untuk konsistensi tampilan antar halaman.
- Hindari penggunaan framework JavaScript.

## 9. Aturan Framework (Framework-Specific Rules)

- Jangan menggunakan framework frontend seperti React, Angular, Vue.js, atau framework backend seperti Laravel, Symfony, CodeIgniter. Gunakan Vanilla JS dan PHP Native.
- Jangan membuat framework JavaScript baru tanpa izin.

## 10. Deployment (Deployment Rules)

- Jangan mengubah konfigurasi server Apache.

## 11. Testing

- Lakukan pengujian pada setiap fitur yang ditambahkan.
- Pastikan tidak ada error yang terjadi.

## 12. Documentation (Document Rules)

- Buat dokumentasi untuk setiap fitur yang ditambahkan.
- Dokumentasi harus mudah dipahami.

## 13. Layout Universal

- gunakan style.css untuk konsistensi tampilan
- Kamu Boleh copy css yang sudah ada di folder `/layouts/css` dan sesuaikan dengan kebutuhan

## 14. layout admin(layouts/admin)

- header admin
- sidebar admin
- content admin
- footer admin

## 15. layout agen(layouts/agen)

- header agen
- sidebar agen
- content agen
- footer agen

## 16. layout customer(layouts/customer)

- header customer
- content customer
- footer customer

## 17. Halaman admin (admin/index.php)

- Halaman ini adalah halaman utama untuk admin.
- Struktur folder admin adalah sebagai berikut(jika foldernya belum ada maka buatlah foldernya dan ikuti struktur folder yang ada):

```text
admin/
├── agen/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
├── user/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
```

-

## 18. Halaman agen (agen/index.php)

- Halaman ini adalah halaman utama untuk agen.
- Struktur folder agen adalah sebagai berikut(jika foldernya belum ada maka buatlah foldernya dan ikuti struktur folder yang ada):

```text
agen/
├── index.php
├── nasabah/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
```

## 19. Halaman customer (customer/index.php)

- Halaman ini adalah halaman utama untuk customer.
- Struktur folder customer adalah sebagai berikut(jika foldernya belum ada maka buatlah foldernya dan ikuti struktur folder yang ada):

```text
customer/
├── index.php
├── fungsi/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── delete.php
```

## 20. Role Agen (Agent Rules)

- **Batasan Akses:** Akses dibatasi hanya pada data nasabah yang mereka rekrut (terikat melalui `id_agen` di tabel polis).
- **Perekrutan & Registrasi:**
  - Melihat katalog Produk Asuransi dan Faskes untuk bahan presentasi ke calon klien.
  - Mendaftarkan data profil Pemegang Polis baru ke dalam sistem.
  - Menginput data anggota keluarga yang akan dijadikan Tanggungan Polis.
  - Membantu mendaftarkan aplikasi Polis baru untuk kliennya.
- **Pemantauan & Retensi:**
  - Melihat daftar Tagihan Premi milik kliennya sendiri (berguna untuk menagih atau mengingatkan nasabah sebelum jatuh tempo).
  - Melihat riwayat pembayaran premi kliennya.
  - Membantu memantau progres Klaim Medis milik kliennya.
  - Melihat dan memperbarui password profil akun agen mereka sendiri.

## 21. Role Admin (Admin Rules)

- **Manajemen Data Master (CRUD):**
  - Mengelola data Produk Asuransi (menambah plan baru, menyesuaikan harga premi bulanan).
  - Mengelola data Faskes (menambahkan rumah sakit/klinik rekanan, memperbarui status kontrak kerjasama).
  - Mengelola data Agen (mendaftarkan akun agen baru, mengatur persentase komisi, memberhentikan agen).
  - Mengelola daftar Kategori Penyakit (ICD-10).
  - Mengelola Users (mereset password, memblokir akun yang mencurigakan).
- **Operasional & Transaksi:**
  - Melihat seluruh daftar Pemegang Polis beserta Tanggungan keluarganya.
  - Menerbitkan atau menonaktifkan (Lapse/Surrender) Polis.
  - Mencetak (generate) Tagihan Premi secara massal untuk bulan berjalan.
  - Memverifikasi Pembayaran Premi yang masuk dan merubah status tagihan menjadi 'Paid'.
- **Persetujuan & Laporan (Reporting):**
  - Menginvestigasi dan mengambil keputusan (Setuju/Tolak) atas pengajuan Klaim Medis beserta rincian biaya medisnya.
  - Memantau Laporan Pemasukan Premi bulanan.
  - Memantau Rasio Klaim per Faskes untuk evaluasi kerjasama dengan rumah sakit.

## 22. Role Customer (Customer Rules)

- **Batasan Akses:** Hanya bisa mengakses informasi pribadi mereka sendiri (terikat melalui `id_pemegang`). Tujuannya adalah transparansi dan kemandirian.
- **Informasi Kepesertaan:**
  - Melihat status dan detail Polis utama mereka (batas limit tahunan, tanggal jatuh tempo).
  - Melihat daftar anggota keluarga yang dijamin dalam Tanggungan Polis.
  - Mencari daftar Faskes rekanan aktif yang terdekat dengan wilayah mereka.
  - Memperbarui data kontak (telepon, alamat, email) pada profil Pemegang Polis.
- **Transaksi & Penggunaan Manfaat:**
  - Melihat daftar kewajiban Tagihan Premi bulanan.
  - Melakukan konfirmasi Pembayaran Premi (menginput referensi bayar/upload bukti transfer).
  - Melihat riwayat pembayaran premi sebelumnya.
  - Mengajukan form Klaim Medis secara mandiri (misalnya untuk sistem reimbursement).
  - Melacak secara real-time status pengajuan klaim (apakah sedang Pending, Investigasi, atau Approved).
