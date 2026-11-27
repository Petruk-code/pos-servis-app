# Konfigurasi Environment (F0.1)

Dokumen ini menginventarisasi variabel `.env` untuk **dev**, **staging**, dan **produksi**. Jangan commit secret nyata; gunakan `.env.example` / `.env.testing.example` sebagai template tanpa kredensial sensitif.

## Aturan Umum

- `.env`, `.env.testing`, `.env.production`, dan varian lokal **tidak boleh di-commit**.
- `APP_KEY` wajib unik per environment; generate dengan `php artisan key:generate`.
- Password seed default **harus diganti** sebelum staging/produksi.
- DB produksi/staging **tidak boleh** memakai user `root/superuser`; gunakan user PostgreSQL khusus hak minimal.

## Matriks Variabel

| Variabel | Dev | Staging | Produksi | Catatan |
|---|---|---|---|---|
| `APP_NAME` | `"POS Servis"` | `"POS Servis"` | `"POS Servis"` | Branding. |
| `APP_ENV` | `local` | `staging` | `production` | Wajib benar. |
| `APP_KEY` | generate | generate | generate | Rahasia, unik per env. |
| `APP_DEBUG` | `true` | `false` | `false` | Jangan true di publik. |
| `APP_URL` | `http://localhost:8000` | URL staging HTTPS | URL produksi HTTPS | Untuk link/notifikasi/export. |
| `APP_LOCALE` | `id` | `id` | `id` |  |
| `LOG_CHANNEL` | `stack` | `stack/daily` | `stack/daily` | Sesuaikan retensi log. |
| `LOG_LEVEL` | `debug` | `info` | `warning/info` | Hindari debug di prod. |
| `DB_CONNECTION` | `pgsql` | `pgsql` | `pgsql` |  |
| `DB_HOST` | `127.0.0.1` | host DB staging | host DB prod |  |
| `DB_PORT` | `5432` | `5432` | `5432` |  |
| `DB_DATABASE` | `pos_servis`/lokal | `pos_servis_staging` | `pos_servis_prod` | Pisahkan DB per env. |
| `DB_USERNAME` | user lokal | user staging khusus | user prod khusus | **Bukan root**. |
| `DB_PASSWORD` | lokal | secret manager/env | secret manager/env | Rahasia. |
| `SESSION_DRIVER` | `database` | `database/redis` | `database/redis` | `database` butuh tabel sessions. |
| `SESSION_LIFETIME` | `120` | sesuai kebijakan | sesuai kebijakan | Menit. |
| `SESSION_ENCRYPT` | `false` | `true` opsional | `true` opsional | Pertimbangkan. |
| `SESSION_SECURE_COOKIE` | `false` | `true` | `true` | Wajib HTTPS. |
| `SESSION_SAME_SITE` | `lax` | `lax/strict` | `lax/strict` | Uji alur login. |
| `CACHE_STORE` | `database/file` | `database/redis` | `database/redis` | Konsisten dengan infra. |
| `QUEUE_CONNECTION` | `sync/database` | `database/redis` | `database/redis` | `database` butuh worker untuk job async. |
| `FILESYSTEM_DISK` | `local` | `local/s3` | `local/s3` | Untuk foto bukti serah terima nanti. |
| `MAIL_MAILER` | `log` | SMTP/provider test | SMTP/provider prod | Untuk notifikasi pelanggan nanti. |
| `MAIL_FROM_ADDRESS` | dev | staging | resmi usaha |  |
| `MAIL_FROM_NAME` | `"${APP_NAME}"` | `"${APP_NAME}"` | `"${APP_NAME}"` |  |

## PostgreSQL: User Non-Root (F0.3)

Contoh pembuatan user khusus (jalankan sebagai admin postgres, sesuaikan nama DB/user):

```sql
CREATE USER pos_servis_app WITH PASSWORD 'ganti_password_kuat';
CREATE DATABASE pos_servis_prod OWNER pos_servis_app;
GRANT CONNECT ON DATABASE pos_servis_prod TO pos_servis_app;
-- Setelah migration, pastikan hak pada schema/tabel/sequence sesuai kebutuhan aplikasi.
```

Prinsip: berikan hak minimal yang dibutuhkan aplikasi (CONNECT, USAGE schema, CRUD tabel, sequence). Hindari SUPERUSER/CREATEDB/CREATEROLE untuk user aplikasi.

## Backup & Restore (F0.4)

Command tersedia:
- `php artisan db:backup --path=storage/app/backups/manual.sql`
- `php artisan db:restore storage/app/backups/manual.sql --force`

Persyaratan: `pg_dump` dan `psql` ada di PATH; kredensial DB diambil dari config `.env`.

SOP minimal:
1. Backup harian otomatis (Task Scheduler/cron) ke `storage/app/backups` atau path aman.
2. Salin backup ke lokasi offsite/cloud/NAS.
3. Uji restore berkala di DB staging terpisah; catat tanggal, hasil, dan petugas.
4. Sebelum deploy/migration besar, ambil backup manual.

## Retensi Data Sensitif (F1.3)

- `ServiceTicket.device_password` disimpan terenkripsi (`encrypted` cast) dan hanya boleh dilihat owner/teknisi saat tiket aktif.
- Sistem otomatis menghapus `device_password` saat tiket berstatus `diambil` atau `batal`, termasuk saat pembayaran servis.
- SOP operasional: jangan meminta password perangkat bila tidak perlu; setelah unit diserahterimakan, pastikan field kosong; batasi akses akun owner/teknisi.

## Seed Aman (F0.5)

- `UserSeeder` membuat akun default dengan password acak saat akun baru dibuat, dan mencetaknya ke console. Simpan segera, lalu ganti lewat UI/fitur ganti password.
- Opsional untuk dev terkontrol: set `SEED_OWNER_PASSWORD`, `SEED_KASIR_PASSWORD`, `SEED_GUDANG_PASSWORD`, `SEED_TEKNISI_PASSWORD` sebelum `php artisan db:seed`. Jangan simpan nilai ini di file yang di-commit.
- Sebelum produksi: ganti semua password default, nonaktifkan akun dummy, dan pastikan hanya akun aktif yang dibutuhkan.

## Notifikasi Pelanggan (F2.1)

- Notifikasi in-app internal tetap berjalan untuk owner/kasir/teknisi terkait.
- Notifikasi pelanggan saat ini: **email opsional** via `customer_email` + checkbox `notify_customer` pada tiket servis.
- Status yang memicu email pelanggan: `dicek`, `menunggu_sparepart`, `selesai`, `diambil`, `batal`.
- Produksi memerlukan konfigurasi `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_*` di `.env`. Default dev `MAIL_MAILER=log`.
- WA/SMS belum diimplementasikan; dapat ditambahkan sebagai channel berikutnya (provider + template + opt-in).

## Kebijakan Secret & Kredensial (F1.2)

- **Jangan commit secret**: `.env`, `.env.*`, key/cert, token, dump DB dengan data sensitif. `.gitignore` mencakup `.env*` yang umum.
- **Sumber secret**: simpan di `.env` server/secret manager CI-CD, bukan di repo/chat/ticket publik.
- **Rotasi**: ganti `APP_KEY` hanya dengan prosedur aman (perhatikan data terenkripsi), rotasi password DB/user API bila terekspos, cabut token lama.
- **Akses minimum**: akun DB produksi khusus, hak minimal; akun owner/teknisi dibatasi orang yang butuh.
- **Pemeriksaan**: sebelum push, jalankan scan secret sederhana (`git grep` pola key/password/token) dan pastikan `git status` bersih dari file env.
- **Insiden**: jika secret ter-commit/ter-push, anggap bocor: rotasi segera, bersihkan history bila perlu, catat insiden.

## Checklist Keamanan Cepat

- [ ] `APP_DEBUG=false` di staging/prod.
- [ ] HTTPS aktif; `SESSION_SECURE_COOKIE=true`.
- [ ] Secret tidak ada di git/log.
- [ ] Password seed default diganti / akun dummy dinonaktifkan.
- [ ] DB user bukan root.
- [ ] Backup terjadwal dan restore pernah diuji.
