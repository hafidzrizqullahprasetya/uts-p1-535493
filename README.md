# Inventaris Aset Laboratorium TRPL

Proyek UTS dan tugas lanjutan mata kuliah Konstruksi dan Evolusi Perangkat Lunak (KEPL) 2026.

| | |
|---|---|
| Nama | Hafidz Rizqullah Prasetya |
| NIM | 535493 |
| Program studi | Sarjana Terapan Teknologi Rekayasa Perangkat Lunak (TRPL), Departemen Teknik Elektro dan Informatika, Sekolah Vokasi UGM |

Aplikasi untuk mencatat peralatan laboratorium: admin masuk, lalu menambah, mengubah, mencari, dan menghapus data aset. Backend berupa REST API Laravel 13 dengan token Sanctum yang dilayani FrankenPHP, frontend Vue 3 di balik Nginx, dan data disimpan di PostgreSQL 18.6.

## Dokumentasi

| Dokumen | Isi |
|---|---|
| [docs/PRD.md](docs/PRD.md) | Tujuan, kebutuhan fungsional dan nonfungsional, batas lingkup |
| [docs/ARSITEKTUR.md](docs/ARSITEKTUR.md) | Komponen, model data, alur permintaan, CI/CD, keputusan desain |
| [docs/API.md](docs/API.md) | Daftar endpoint, contoh request dan response, kode status |
| [docs/OPERASIONAL.md](docs/OPERASIONAL.md) | Menjalankan dengan Docker Compose, variabel `.env`, log, healthcheck, masalah yang pernah terjadi |
| [docs/KONTRIBUSI.md](docs/KONTRIBUSI.md) | Alur branch dan PR, format pesan commit |

## Menjalankan

```bash
cp .env.example .env
# isi APP_KEY (format base64:...), DB_PASSWORD, dan GRAFANA_ADMIN_PASSWORD
docker compose up -d --build
docker compose ps
```

| Layanan | Isi | Akses dari host |
|---|---|---|
| `db` | PostgreSQL 18.6 | tidak dibuka |
| `backend` | Laravel + FrankenPHP | `http://localhost:${BACKEND_PORT}` (8001 jika tidak diisi) |
| `frontend` | Nginx berisi hasil build Vue | `http://localhost:${FRONTEND_PORT}` (bawaan 8080) |
| `prometheus` | Prometheus 3.16 | `http://127.0.0.1:9090` |
| `grafana` | Grafana 13.3 | `http://127.0.0.1:3000` |
| `cadvisor`, `node-exporter` | Metrik container dan host | tidak dibuka |

Akun awal dari seeder: `admin@ugm.ac.id` / `password123`.

Frontend memanggil `/api` di origin yang sama, lalu Nginx meneruskannya ke `http://backend:8000`. Kesehatan basis data dan cache bisa dicek di `http://localhost:8001/health`.

## Struktur direktori

```text
uts-p1-535493/
├── .github/workflows/ci.yml   # test backend, build frontend, push image ke GHCR
├── compose.yaml               # db, backend, frontend
├── .env.example               # contoh variabel untuk Docker Compose
├── docs/                      # PRD, arsitektur, API, operasional, kontribusi
├── monitoring/                # prometheus.yml, alerts.yml, provisioning dan dashboard Grafana
├── backend/
│   ├── app/                   # controller (Asset, Auth, Health) dan model
│   ├── database/              # migrasi dan seeder
│   ├── docker/start.sh        # migrasi, seeder, lalu FrankenPHP
│   ├── docker/Caddyfile       # server :8000 dan metrik :9180
│   ├── postman/               # koleksi Postman
│   ├── tests/Feature/         # feature test API, health, dan log
│   └── Dockerfile             # FrankenPHP dengan cache layer Composer
└── frontend/
    ├── src/                   # komponen Vue dan klien Axios
    ├── nginx.conf             # SPA dan proxy /api ke backend
    └── Dockerfile             # multi-stage: Node lalu Nginx
```

## Koleksi Postman

`backend/postman/uts_kepl_asset_api.postman_collection.json`. Variabel `baseUrl` bawaannya `http://127.0.0.1:8000/api`; ganti port sesuai `BACKEND_PORT`.
