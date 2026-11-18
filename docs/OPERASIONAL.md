# Panduan operasional

## Prasyarat

- Docker Engine dengan plugin Compose v2 (`docker compose version`).
- Port 8001 dan 8080 kosong di host, atau ubah lewat `.env`.

## Menjalankan

```bash
cp .env.example .env
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"   # salin ke APP_KEY
# isi DB_PASSWORD dengan password bebas
docker compose up -d --build
docker compose ps
```

Tunggu sampai `db` dan `backend` berstatus `healthy`. Frontend baru dijalankan setelah backend sehat, jadi butuh sekitar 20 detik pada start pertama.

| URL | Isi |
|---|---|
| http://localhost:8080 | Aplikasi (masuk dengan `admin@ugm.ac.id` / `password123`) |
| http://localhost:8001/health | Status basis data dan cache |
| http://localhost:8001/api | API untuk Postman |
| http://localhost:9090/targets | Status target Prometheus |
| http://localhost:9090/alerts | Status aturan alarm |
| http://localhost:3000 | Grafana, dashboard ada di folder "Aset Lab TRPL" |

## Variabel lingkungan (`.env` di akar repo)

| Variabel | Contoh | Fungsi |
|---|---|---|
| `APP_KEY` | `base64:...` | Kunci enkripsi Laravel, wajib diisi |
| `DB_DATABASE` | `uts_kepl` | Nama basis data |
| `DB_USERNAME` | `uts_kepl` | Pengguna PostgreSQL |
| `DB_PASSWORD` | (rahasia) | Password PostgreSQL, wajib diisi |
| `BACKEND_PORT` | `8001` | Port host untuk backend. Jika tidak diisi, Compose memakai 8001 |
| `FRONTEND_PORT` | `8080` | Port host untuk frontend |
| `GRAFANA_ADMIN_USER` | `admin` | Nama pengguna admin Grafana |
| `GRAFANA_ADMIN_PASSWORD` | (rahasia) | Password admin Grafana, wajib diisi. Compose menolak jalan jika kosong |
| `PROMETHEUS_PORT` | `9090` | Port Prometheus di `127.0.0.1` |
| `GRAFANA_PORT` | `3000` | Port Grafana di `127.0.0.1` |

`.env` tidak ikut di-commit. Di CI, `APP_KEY` dan `DB_PASSWORD` disimpan sebagai GitHub Secrets.

## Perintah sehari-hari

```bash
docker compose logs -f backend          # log aplikasi (stdout)
docker compose exec backend php artisan test
docker compose exec backend php artisan migrate:status
docker compose exec db psql -U uts_kepl -d uts_kepl
docker compose stop                     # matikan, data tetap ada
docker compose down                     # hapus container, volume pgdata tetap
docker compose down -v                  # hapus juga volume, data hilang
```

## Membaca log

Backend menulis log ke stdout dengan kanal `stdout`. Setiap log aplikasi punya konteks yang sama:

```text
production.WARNING: Validasi data aset gagal {"siapa":"user_id:1","sedang_mengerjakan":"validasi_penyimpanan_aset","sebab":"Atribut masukan tidak memenuhi aturan validasi","kolom_gagal":["code","price"]}
```

| Tingkat | Kapan muncul |
|---|---|
| `info` | Aset disimpan, diubah, atau dihapus |
| `warning` | Validasi tambah atau ubah aset gagal |
| `error` | Basis data atau cache gagal, baik saat permintaan biasa maupun saat `/health` |

Password, token, dan email tidak pernah ditulis ke log. Test `test_asset_logs_do_not_contain_personal_data_or_token` memeriksa hal ini.

Ukuran log dibatasi driver `json-file`: 10 MB per berkas, maksimal 3 berkas per container.

## Healthcheck

| Layanan | Perintah | Interval | Timeout | Retries | Start period |
|---|---|---|---|---|---|
| `db` | `pg_isready` | 5s | 5s | 10 | 5s |
| `backend` | `curl -fs http://localhost:8000/health` | 10s | 5s | 5 | 15s |

Uji dua arah:

```bash
docker compose stop db
sleep 60 && docker compose ps backend   # STATUS menjadi (unhealthy)
curl -i http://localhost:8001/health    # 503, bagian_bermasalah: ["database"]
docker compose start db
sleep 30 && docker compose ps backend   # kembali (healthy)
```

## Monitoring dan alarm

Password admin Grafana diambil dari `GRAFANA_ADMIN_PASSWORD` saat volume `grafana_data` pertama kali dibuat. Untuk menggantinya setelah itu:

```bash
docker compose exec grafana grafana cli admin reset-admin-password '<password-baru>'
```

Uji alarm:

```bash
docker compose stop backend
# http://localhost:9090/alerts: BackendTidakBisaDihubungi menjadi PENDING, lalu FIRING setelah 1 menit
docker compose start backend
```

Dashboard tersimpan sebagai berkas JSON dan dipasang lewat provisioning dengan `allowUiUpdates: false`. Perubahan panel dilakukan di `monitoring/grafana/dashboards/aset-lab.json`, lalu `docker compose restart grafana`.

## Masalah yang pernah terjadi

| Gejala | Penyebab | Penanganan |
|---|---|---|
| `bind: address already in use` di port 8000 | Port 8000 di laptop pengembang dipakai aplikasi lain | Pakai `BACKEND_PORT=8001` |
| Backend tertahan di `health: starting` | Healthcheck bawaan image FrankenPHP memeriksa admin endpoint port 2019 yang mati di mode `php-server` | Healthcheck diganti ke `/health` di `compose.yaml` |
| `docker compose up` gagal dengan `failed commit on ref "layer-sha256:..."` | Beberapa image ditarik bersamaan dan containerd gagal memindahkan layer | Tarik image satu per satu dengan `docker pull`, lalu `up` lagi |
| Compose memperingatkan "Found multiple config files" | Dulu ada `docker-compose.yml` lama di samping `compose.yaml` | Berkas lama dihapus, hanya `compose.yaml` yang dipakai |
