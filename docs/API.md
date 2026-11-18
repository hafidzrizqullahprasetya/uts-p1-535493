# Referensi API

Base URL lewat backend langsung: `http://localhost:8001/api` (port mengikuti `BACKEND_PORT`).
Lewat frontend: `http://localhost:8080/api` (diteruskan Nginx ke backend).

Semua permintaan sebaiknya membawa header `Accept: application/json` supaya galat dibalas dalam JSON. Endpoint bertanda token butuh header `Authorization: Bearer <token>`.

Koleksi Postman ada di `backend/postman/uts_kepl_asset_api.postman_collection.json` dan bisa dijalankan berurutan dari atas.

## Ringkasan

| Metode | Path | Token | Keterangan |
|---|---|---|---|
| POST | `/api/login` | tidak | Masuk, menerima token |
| POST | `/api/logout` | ya | Mencabut token yang sedang dipakai |
| GET | `/api/me` | ya | Data pengguna yang sedang masuk |
| GET | `/api/assets` | ya | Daftar aset, `id` terbesar di atas |
| POST | `/api/assets` | ya | Tambah aset |
| GET | `/api/assets/{id}` | ya | Detail aset |
| PUT/PATCH | `/api/assets/{id}` | ya | Ubah aset (semua kolom wajib dikirim) |
| DELETE | `/api/assets/{id}` | ya | Hapus aset |
| GET | `/health` dan `/api/health` | tidak | Pemeriksaan basis data dan cache |
| GET | `/up` | tidak | Rute kesehatan bawaan Laravel |

## Autentikasi

### POST /api/login

```json
{ "email": "admin@ugm.ac.id", "password": "password123" }
```

`200`:

```json
{
  "message": "Login berhasil",
  "token": "1|xxxxxxxxxxxxxxxx",
  "token_type": "Bearer",
  "user": { "id": 1, "name": "Administrator Lab TRPL", "email": "admin@ugm.ac.id" }
}
```

`401` jika email atau password salah, `422` jika email kosong atau formatnya salah.

### POST /api/logout

`200` `{"message": "Logout berhasil"}`. Token yang dipakai langsung tidak berlaku.

### Tanpa token atau token salah

Semua endpoint bertanda token membalas `401`:

```json
{ "message": "Unauthenticated." }
```

## Aset

### Kolom

| Kolom | Aturan |
|---|---|
| `name` | wajib, teks, maks. 255 |
| `code` | wajib, teks, maks. 50, unik di tabel `assets` |
| `category` | wajib, teks, maks. 100 |
| `status` | wajib, salah satu dari `tersedia`, `dipinjam`, `rusak`, `perawatan` |
| `price` | wajib, angka, minimal 0 |
| `location` | boleh kosong, teks, maks. 255 |

### POST /api/assets

```json
{
  "name": "Laptop Dell Latitude 5430",
  "code": "AST-LAB-011",
  "category": "Komputer Portable",
  "status": "tersedia",
  "price": 17800000,
  "location": "HU.104 Laboratorium RPL 4"
}
```

`201`:

```json
{
  "message": "Aset berhasil dibuat",
  "data": { "id": 11, "name": "Laptop Dell Latitude 5430", "code": "AST-LAB-011", "...": "..." }
}
```

`422` jika validasi gagal:

```json
{
  "message": "Validasi gagal",
  "errors": { "code": ["The code has already been taken."] }
}
```

### GET /api/assets

`200` `{"message": "Daftar aset berhasil diambil", "data": [ ... ]}`

### GET /api/assets/{id}

`200` dengan `data` berisi satu aset, `404` jika `id` tidak ada.

### PUT /api/assets/{id}

Body sama seperti POST. Kode boleh sama dengan kode aset itu sendiri. `200` dengan `message` "Aset berhasil diperbarui".

### DELETE /api/assets/{id}

`200` `{"message": "Aset berhasil dihapus"}`

### Galat basis data

Jika basis data tidak bisa dihubungi saat membaca atau menulis aset, API membalas `500` dengan `message` "Gagal menghubungi basis data" dan mencatat log `error`.

## Kesehatan

### GET /health

Sehat, `200`:

```json
{
  "status": "healthy",
  "timestamp": "2026-11-18T06:12:06+00:00",
  "checks": {
    "database": { "status": "healthy", "pesan": "Koneksi basis data aktif dan responsif" },
    "cache": { "status": "healthy", "pesan": "Operasi baca dan tulis cache berfungsi normal" }
  }
}
```

Basis data mati, `503`:

```json
{
  "status": "unhealthy",
  "checks": {
    "database": { "status": "unhealthy", "pesan": "Gagal menghubungi basis data", "detail": "..." },
    "cache": { "status": "healthy", "pesan": "Operasi baca dan tulis cache berfungsi normal" }
  },
  "bagian_bermasalah": ["database"]
}
```
