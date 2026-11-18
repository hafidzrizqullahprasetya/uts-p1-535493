# PRD: Inventaris Aset Laboratorium TRPL

| | |
|---|---|
| Pemilik | Hafidz Rizqullah Prasetya (NIM 535493) |
| Mata kuliah | Konstruksi dan Evolusi Perangkat Lunak 2026 |
| Status | Berjalan, dipakai sebagai dasar tugas sesudah UTS |

## Latar belakang

Aplikasi ini mencatat peralatan di laboratorium Departemen Teknik Elektro dan Informatika SV UGM: komputer, perangkat jaringan, instrumen elektronika, dan perangkat presentasi. Tiap alat punya kode inventaris, lokasi ruang, nilai perolehan, dan status. Data disimpan di satu basis data dan hanya bisa diubah oleh pengguna yang sudah masuk. Proyek ini dibuat untuk UTS dan dipakai lagi sebagai dasar tugas berikutnya (logging, healthcheck, monitoring).

## Tujuan

1. Admin lab bisa menambah, mengubah, melihat, dan menghapus data aset dari browser.
2. Setiap aset punya kode unik dan salah satu dari empat status: `tersedia`, `dipinjam`, `rusak`, `perawatan`.
3. Halaman depan menampilkan berapa aset di tiap status dan total nilai aset.
4. API bisa dipakai klien lain (Postman, skrip) dengan token yang sama.

## Pengguna

Satu peran saja, yaitu admin lab. Akun awal dibuat oleh seeder (`admin@ugm.ac.id`). Pendaftaran akun lewat aplikasi belum ada.

## Kebutuhan fungsional

| Kode | Kebutuhan | Endpoint/halaman |
|---|---|---|
| F-01 | Admin masuk dengan email dan password, lalu menerima token Bearer | `POST /api/login` |
| F-02 | Admin keluar dan tokennya dicabut | `POST /api/logout` |
| F-03 | Melihat daftar aset, terbaru di atas | `GET /api/assets` |
| F-04 | Melihat detail satu aset | `GET /api/assets/{id}` |
| F-05 | Menambah aset dengan validasi (kode unik, harga tidak negatif, status dari daftar) | `POST /api/assets` |
| F-06 | Mengubah aset | `PUT /api/assets/{id}` |
| F-07 | Menghapus aset | `DELETE /api/assets/{id}` |
| F-08 | Mencari aset berdasarkan nama, kode, kategori, atau ruang | Halaman frontend (penyaringan di sisi klien) |
| F-09 | Ringkasan jumlah aset per status dan total nilai | Halaman frontend |
| F-10 | Pemeriksaan kesehatan yang benar-benar menguji basis data dan cache | `GET /health` |

## Kebutuhan nonfungsional

- Semua endpoint aset menolak permintaan tanpa token dengan status `401` dan pesan JSON.
- Validasi gagal dibalas `422` beserta daftar kolom yang salah.
- Log aplikasi keluar ke stdout dalam bentuk terstruktur (siapa, sedang mengerjakan apa, sebabnya) dan tidak boleh memuat password, token, atau email pengguna.
- Seluruh layanan berjalan dengan `docker compose up -d --build`. Versi image dikunci (PostgreSQL 18.6, FrankenPHP 1.13.2 dengan PHP 8.5.12, Nginx 1.27).
- Ukuran log container dibatasi 10 MB per berkas, maksimal 3 berkas.
- Setiap perubahan masuk lewat pull request. CI menjalankan test backend dan build frontend sebelum merge ke `main`.

## Di luar lingkup

- Peminjaman aset (siapa meminjam, kapan kembali). Status `dipinjam` hanya penanda.
- Banyak peran atau hak akses berbeda.
- Unggah foto aset dan riwayat perubahan per aset.
- Paginasi di API. Jumlah aset lab masih puluhan, jadi daftar dikirim sekaligus.

## Kriteria penerimaan

- `php artisan test` lulus, termasuk test bahwa log aset tidak memuat data pribadi atau token.
- `docker compose ps` menunjukkan `db` dan `backend` berstatus `healthy`.
- `GET /health` membalas `200` saat basis data dan cache normal, dan `503` beserta `bagian_bermasalah` saat salah satunya mati.
- Admin bisa menjalankan alur masuk, tambah, ubah, hapus, lalu keluar dari browser tanpa galat.
