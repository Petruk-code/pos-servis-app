# Checklist Release / Deploy

## Sebelum deploy
- [ ] Backup database & file `storage/app/public` (verifikasi restore terakhir).
- [ ] Pastikan `.env` produksi benar (APP_ENV=production, APP_DEBUG=false, DB kredensial non-root, MAIL/queue jika dipakai).
- [ ] Review `git status` bersih, branch/tag rilis jelas.
- [ ] Jalankan test: `php artisan test` pada env CI/staging yang sesuai.

## Deploy
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan migrate --force`
- [ ] `php artisan storage:link` (sekali per env)
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Restart queue/worker jika ada.

## Setelah deploy / smoke test
- [ ] Login owner/kasir/teknisi.
- [ ] Buat tiket servis, ubah status, tambah part, bayar/pelunasan, cetak A4/thermal.
- [ ] POS checkout cash/non-cash, cetak nota.
- [ ] Cek laporan, export CSV/PDF, closing kas.
- [ ] Cek `/health` return 200.

## Rollback
- [ ] Siapkan backup DB sebelum migrate.
- [ ] Jika rilis bermasalah: rollback kode ke tag/commit sebelumnya, restore DB bila migrasi destruktif.
- [ ] Jalankan `php artisan config:clear route:clear view:clear`.
- [ ] Catat insiden dan akar masalah di audit/changelog.
