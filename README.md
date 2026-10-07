# Aplikasi Rental Mobil

Blueprint program plan menjadi sumber acuan tunggal untuk seluruh modul.

## Menjalankan lokal

1. `docker compose up --build -d`
2. `docker compose exec app php artisan migrate --seed`
3. Buka `http://localhost:8000`

MySQL dapat diakses dari host melalui port `3307`. Vite tersedia pada port `5173`.

## Aturan arsitektur

- Aturan bisnis berada di service atau action.
- Harga, availability, status, dan pembayaran divalidasi di backend.
- Dokumen identitas wajib private dan terotorisasi.
- Nama tabel, enum, dan workflow blueprint tidak berubah tanpa revisi sadar.

## Status fase

- Phase 0: Docker dan aturan proyek tersedia.
- Phase 1: Laravel, React/Inertia, autentikasi Breeze, dan role awal tersedia.
- Berikutnya: Phase 2 domain Vehicle.
