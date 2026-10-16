# UTS Konstruksi dan Evolusi Perangkat Lunak (KEPL)
**NIM:** 535493  
**Nama:** Hafidz Rizqullah Prasetya  
**Program Studi:** Sarjana Terapan Teknologi Rekayasa Perangkat Lunak (TRPL)  
**Departemen:** Teknik Elektro dan Informatika (TEDI), Sekolah Vokasi, Universitas Gadjah Mada  

---

## Deskripsi Sistem
Proyek ini merupakan implementasi sistem **Manajemen Inventaris Aset Laboratorium (Asset Management)** berbasis arsitektur terpisah (*decoupled architecture*):
1. **Backend (`backend/`):** RESTful API berbasis **Laravel** dengan autentikasi token berbasis **Laravel Sanctum**. Menyediakan operasi CRUD lengkap pada resource Aset dengan format JSON dan penanganan status HTTP presisi (`200 OK`, `201 Created`, `422 Unprocessable Content`, `401 Unauthorized`).
2. **Frontend (`frontend/`):** Antarmuka web modern berbasis **Vue 3 (Vite)** dan **Axios**. Mengimplementasikan alur login token, penyimpanan sesi di `localStorage`, serta interaksi CRUD aset secara reaktif. URL API dikonfigurasi melalui variabel lingkungan (`VITE_API_BASE_URL`).
3. **CI/CD Pipeline (`.github/workflows/ci.yml`):**
   - **Continuous Integration:** Menjalankan unit & feature testing Laravel (`php artisan test`) dan proses build frontend (`npm ci && npm run build`).
   - **Continuous Deployment:** Secara otomatis membangun Docker image backend dan frontend, kemudian mempublikasikannya ke **GitHub Container Registry (`ghcr.io`)** dengan tag commit SHA ketika terdapat push/merge pada branch `main`.

---

## Struktur Direktori
```text
uts-p1-535493/
├── .github/
│   └── workflows/
│       └── ci.yml               # Pipeline GitHub Actions (CI & CD ke GHCR)
├── backend/                     # Layanan Backend Laravel RESTful API
│   ├── app/                     # Controller, Model, Middleware
│   ├── database/                # Migrasi & Seeder Aset
│   ├── postman/                 # Postman Collection JSON
│   ├── tests/                   # Automated Feature Tests (PHPUnit)
│   ├── Dockerfile               # Dockerfile Backend (Layer caching Composer)
│   └── .env.example
├── frontend/                    # Antarmuka Pengguna Vue 3 (Vite)
│   ├── src/                     # Komponen Vue & Konfigurasi Axios
│   ├── nginx.conf               # Konfigurasi Nginx untuk SPA
│   ├── Dockerfile               # Multi-stage Dockerfile (Node.js -> Nginx)
│   ├── package.json
│   └── .env.example
├── .gitignore
└── README.md
```

---

## Kredensial Akun Pengujian
- **Email:** `admin@ugm.ac.id`
- **Password:** `password123`

---

## Koleksi Postman
Koleksi Postman resmi untuk pengujian seluruh endpoint API tersedia di:
`backend/postman/uts_kepl_asset_api.postman_collection.json`
