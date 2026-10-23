# UTS Konstruksi dan Evolusi Perangkat Lunak (KEPL)
**NIM:** 535493  
**Nama:** Hafidz Rizqullah Prasetya  
**Program Studi:** Sarjana Terapan Teknologi Rekayasa Perangkat Lunak (TRPL)  
**Departemen:** Teknik Elektro dan Informatika (TEDI), Sekolah Vokasi, Universitas Gadjah Mada  

---

## Deskripsi Sistem
Sistem **Manajemen Inventaris Aset Laboratorium** dengan backend dan frontend terpisah:

1. **Backend (`backend/`):** RESTful API Laravel dengan autentikasi token Laravel Sanctum. CRUD aset dalam format JSON dengan status `200`, `201`, `422`, dan `401`. Di dalam container, Laravel dilayani oleh **FrankenPHP**.
2. **Frontend (`frontend/`):** Vue 3 (Vite) dan Axios. Login menyimpan token di `localStorage`, lalu token dikirim pada setiap permintaan. Alamat API diatur lewat `VITE_API_BASE_URL`.
3. **Basis data:** PostgreSQL, datanya disimpan di named volume `pgdata`.
4. **CI/CD (`.github/workflows/ci.yml`):**
   - `Backend Test`: `php artisan test` terhadap PostgreSQL, dengan `APP_KEY` dan `DB_PASSWORD` dari GitHub Secrets.
   - `Frontend Build`: `npm ci` dan `npm run build`.
   - `Deploy to GHCR`: hanya pada push ke `main`, membangun image backend dan frontend lalu mengunggahnya ke `ghcr.io` dengan tag commit SHA.

---

## Menjalankan dengan Docker Compose
```bash
cp .env.example .env
# isi APP_KEY (format base64:...) dan DB_PASSWORD di .env
docker compose up -d --build
```

| Service | Isi | Akses dari host |
|---|---|---|
| `db` | PostgreSQL 18.6 | tidak dibuka |
| `backend` | Laravel + FrankenPHP | `http://localhost:${BACKEND_PORT}` (bawaan 8000) |
| `frontend` | Nginx berisi hasil build Vue | `http://localhost:${FRONTEND_PORT}` (bawaan 8080) |

Frontend memanggil `/api` di origin yang sama, lalu Nginx meneruskannya ke `http://backend:8000` lewat jaringan Docker Compose. Backend terhubung ke basis data lewat host `db`.

---

## Struktur Direktori
```text
uts-p1-535493/
├── .github/workflows/ci.yml   # CI (test + build) dan CD ke GHCR
├── docker-compose.yml         # db, backend, frontend
├── .env.example               # contoh kredensial untuk Docker Compose
├── backend/
│   ├── app/                   # Controller dan Model
│   ├── database/              # Migrasi dan seeder
│   ├── docker/start.sh        # migrasi, seeder, lalu FrankenPHP
│   ├── postman/               # Postman Collection
│   ├── tests/Feature/         # Feature test API
│   └── Dockerfile             # FrankenPHP, layer caching Composer
└── frontend/
    ├── src/                   # Komponen Vue dan klien Axios
    ├── nginx.conf             # SPA + proxy /api ke backend
    └── Dockerfile             # Multi-stage: Node.js lalu Nginx
```

---

## Akun Pengujian
Dibuat oleh seeder: `admin@ugm.ac.id` / `password123`.

## Koleksi Postman
`backend/postman/uts_kepl_asset_api.postman_collection.json`. Variabel `baseUrl` bawaannya `http://127.0.0.1:8000/api`; sesuaikan dengan `BACKEND_PORT` jika diubah.
