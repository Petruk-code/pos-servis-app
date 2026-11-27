# SOP Backup & Restore Database (F0.4)

Prosedur ini untuk database PostgreSQL POS Servis. Command aplikasi tersedia, tetapi **jadwal, offsite, dan uji restore wajib dijalankan oleh operator**.

## Prasyarat

- PostgreSQL client tools terpasang dan ada di PATH: `pg_dump`, `psql`.
- Kredensial DB di `.env` benar (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- User DB backup punya hak read; user restore punya hak tulis pada DB target.
- Direktori backup aman: `storage/app/backups` (default) atau path khusus di luar webroot.

## Backup Manual

```powershell
php artisan db:backup
# atau path khusus
php artisan db:backup --path=D:\backup-pos\pos_servis_YYYYMMDD_HHmm.sql
```

Output default: `storage/app/backups/pos_servis_YYYYMMDD_HHMMSS.sql`.

## Restore Manual

> **PERINGATAN:** restore menimpa data DB target. Pastikan backup valid dan environment benar.

```powershell
php artisan db:restore storage/app/backups/pos_servis_YYYYMMDD_HHMMSS.sql --force
```

Tanpa `--force`, command akan meminta konfirmasi.

## Jadwal Backup (Wajib Produksi)

Pilih salah satu:

- **Windows Task Scheduler**: jalankan harian, mis. 02:00.
  - Program: `powershell.exe`
  - Argumen: `-NoProfile -ExecutionPolicy Bypass -Command "cd 'C:\path\to\app'; php artisan db:backup --path='D:\backup-pos\pos_servis_%date:~-4,4%%date:~-7,2%%date:~-10,2%.sql'"`
- **Cron Linux**: `0 2 * * * cd /var/www/pos-servis && php artisan db:backup >> /var/log/pos-servis-backup.log 2>&1`

Retensi yang disarankan: harian 7 hari, mingguan 4 minggu, bulanan 6-12 bulan (sesuaikan kebijakan usaha).

## Offsite / Salinan Kedua

Backup lokal saja tidak cukup. Salin ke minimal satu lokasi lain:

- NAS / external drive yang tidak selalu terpasang.
- Cloud storage terenkripsi (S3-compatible, Google Drive bisnis, dsb.).
- Server backup terpisah.

Pastikan akses offsite dibatasi dan terenkripsi bila memungkinkan.

## Uji Restore (Wajib Berkala)

Lakukan minimal bulanan atau setelah perubahan skema besar:

1. Ambil backup terbaru.
2. Restore ke **DB staging/terpisah** (bukan produksi).
3. Jalankan `php artisan migrate:status` dan cek aplikasi bisa login.
4. Cek data kritis: transaksi terbaru, tiket servis, stok.
5. Catat hasil di log uji.

Template log uji:

| Tanggal | File Backup | DB Target Uji | Hasil | Petugas | Catatan |
|---|---|---|---|---|---|
| YYYY-MM-DD | nama file | pos_servis_restore_test | OK/Gagal | nama |  |

## Sebelum Deploy/Migration Besar

- [ ] Ambil backup manual.
- [ ] Pastikan backup bisa dibaca (ukuran wajar, tidak 0 byte).
- [ ] Simpan lokasi file backup di catatan deploy.
- [ ] Siapkan prosedur rollback/migration rollback bila relevan.

## Batasan Command Saat Ini

- `db:backup` menggunakan `pg_dump` format plain `.sql`.
- `db:restore` menggunakan `psql --file`; akan menimpa objek/data sesuai isi dump.
- Belum ada enkripsi backup built-in, rotasi otomatis, atau upload offsite otomatis. Ini item operasional yang bisa ditambahkan di Fase 4/operasional.