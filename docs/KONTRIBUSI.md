# Alur kerja dan kontribusi

## Branch

| Branch | Isi |
|---|---|
| `main` | Versi yang dikumpulkan dan di-deploy ke GHCR. Dilindungi, hanya menerima PR dari `dev` |
| `dev` | Tempat menggabungkan fitur sebelum naik ke `main` |
| `feat/...`, `fix/...`, `docs/...` | Satu branch per perubahan, dibuat dari `dev` |

Alurnya: buat branch dari `dev`, buka PR ke `dev`, tunggu `Backend Test` dan `Frontend Build` lulus, merge. Setelah itu buka PR `dev` ke `main`.

## Pesan commit

Format mengikuti Conventional Commits dengan deskripsi berbahasa Indonesia:

```text
feat: tambah endpoint health yang memeriksa basis data dan cache
fix: log mencatat user_id, bukan email pengguna
docs: tambah PRD dan dokumen arsitektur
chore: hapus docker-compose.yml duplikat
```

Satu commit untuk satu perubahan yang utuh. Tulis apa yang berubah, bukan nama tugas.

## Sebelum membuka PR

```bash
docker compose exec backend php artisan test
cd frontend && npm run build
```

Kalau mengubah endpoint, perbarui `docs/API.md` dan koleksi Postman. Kalau mengubah `compose.yaml`, perbarui `docs/OPERASIONAL.md`.

## Rahasia

Jangan commit `.env`, token, atau password. Nilai contoh ditaruh di `.env.example` dengan isi kosong.
