# Rencana Pengembangan POS Servis

Dokumen ini menjadi acuan pengembangan agar aplikasi **POS Servis** layak dipakai untuk operasional usaha jasa servis komputer. Pengerjaan dilakukan **bertahap, satu per satu**, dan setiap item harus diverifikasi sebelum lanjut.

## Prinsip Urutan

- **Go-live adalah gerbang akhir**, bukan fase pertama. Persiapan go-live/final checklist dilakukan setelah fitur, hardening, dan UAT yang dibutuhkan lulus.
- **Hardening dasar dimulai lebih awal** (staging, konfigurasi env, backup, keamanan dasar) agar pengembangan dan pengujian berjalan di lingkungan yang aman.
- Item ditandai `[ ]` belum, `[~]` proses, `[x]` selesai. Update dokumen ini setiap fase selesai.

## Status Saat Ini (Baseline)

Sudah ada / selesai:
- [x] POS transaksi, inventaris, kategori, stok, laporan dasar.
- [x] Modul servis: tiket, sparepart, jasa, pembayaran servis, cetak tanda terima/nota.
- [x] Role: Owner, Kasir, Gudang, Teknisi; dashboard per role.
- [x] Policy/otorisasi object-level dasar + route middleware.
- [x] Audit trail status servis.
- [x] Notifikasi in-app status servis.
- [x] Export CSV laporan.
- [x] Command `db:backup` dan `db:restore` PostgreSQL.
- [x] Encrypt `device_password` + migrasi data lama.
- [x] Test fitur kritis dasar (service flow, audit/policy, POS/cancel) lulus.

Belum layak produksi penuh:
- [ ] Hardening produksi lengkap, SOP backup/restore teruji, UAT end-to-end, dan fitur operasional servis lanjutan.

## Fase 0 - Baseline & Staging

**Tujuan:** menyiapkan lingkungan kerja yang aman untuk implementasi dan pengujian.

| ID | Item | Status | Kriteria Selesai |
|---|---|---|---|
| F0.1 | Inventaris konfigurasi `.env` | [x] | Daftar variabel wajib dev/staging/prod terdokumentasi; tidak ada secret di git. (`docs/konfigurasi-environment.md`, `.env.example` diperbarui) |
| F0.2 | Staging environment | [ ] | Ada environment staging mirip produksi untuk UAT. |
| F0.3 | DB user non-root | [~] | Panduan user PostgreSQL khusus ditambahkan; implementasi aktual di DB staging/prod perlu dilakukan saat environment tersedia. |
| F0.4 | SOP backup/restore | [~] | SOP tertulis di `docs/sop-backup-restore.md`; command `db:backup`/`db:restore` ada. Jadwal aktual, offsite, dan uji restore perlu dijalankan saat environment operasional tersedia. |
| F0.5 | Data seed/akun default | [~] | `UserSeeder` tidak lagi hardcode password; password acak dicetak saat seed/opsional via `SEED_*_PASSWORD`. Kebijakan ganti password & nonaktifkan akun dummy masih perlu SOP sebelum produksi. |

## Fase 1 - Keamanan & Hardening Dasar

**Tujuan:** menutup risiko dasar sebelum fitur operasional ditambah.

| ID | Item | Status | Kriteria Selesai |
|---|---|---|---|
| F1.1 | Konfigurasi produksi aman | [~] | `AppServiceProvider` force HTTPS saat production; `.env.example` diberi panduan secure cookie. Checklist deploy aktual (APP_DEBUG=false, APP_URL HTTPS, SESSION_SECURE_COOKIE=true) harus diverifikasi di staging/prod. |
| F1.2 | Kebijakan secret & kredensial | [~] | `.gitignore` diperketat untuk pola `.env*`; SOP secret/rotasi/pemeriksaan ditulis di `docs/konfigurasi-environment.md`. Rotasi secret yang pernah terekspos tetap tindakan operasional. |
| F1.3 | Retensi data sensitif | [~] | `device_password` dienkripsi cast; akses view dibatasi owner/teknisi; otomatis di-null saat status `diambil/batal` atau pembayaran servis. Kebijakan SOP retensi dokumen masih perlu difinalkan. |
| F1.4 | Otorisasi UI/policy polish | [~] | `@can` di view sensitif; policy+authorize untuk Category/Product/Employee/StockMovement/ServiceTicket/Transaction/CancellationRequest; defense-in-depth pada POS/Owner/Report/StockMovement. Final audit pass awal selesai, perlu review berkala saat fitur baru. |
| F1.5 | Audit log umum | [~] | Tabel `audit_logs` + `Audit` helper ditambahkan; dicatat untuk employee/category/product CRUD, stock movement manual, cancel transaksi, approve/reject pembatalan. Cakupan setting/aksi sensitif lain masih bisa diperluas. |
| F1.6 | Validasi input & error handling | [~] | Tidak ada debug dump; `APP_DEBUG` default false; login throttle+max length; `abort(422)` diganti `ValidationException`; search/filter index endpoint divalidasi. Final audit pass awal selesai, perlu review berkala saat fitur baru. |

## Fase 2 - Operasional Servis

**Tujuan:** membuat alur servis siap dipakai harian oleh usaha servis.

| ID | Item | Status | Kriteria Selesai |
|---|---|---|---|
| F2.1 | Notifikasi pelanggan | [~] | Email opsional ke pelanggan ditambahkan (opt-in `notify_customer` + `customer_email`) untuk status penting termasuk batal. WA/SMS/provider produksi dan template lanjutan masih pending. |
| F2.2 | Bukti serah terima | [~] | Checklist masuk, foto masuk/keluar opsional, nama penerima/pengambil ditambahkan di tiket servis. Tanda tangan digital/gambar dan cetak bukti khusus masih bisa ditingkatkan. |
| F2.3 | Pembayaran fleksibel | [~] | DP/pelunasan servis, metode pembayaran cash/transfer/qris/debit/other, sisa tagihan dan status bayar tercatat. POS masih pembayaran penuh; akuntansi/rekonsiliasi lanjutan belum. |
| F2.4 | Garansi & riwayat pelanggan | [~] | Riwayat servis by no HP/serial tampil di detail tiket; garansi dihitung dari picked_up/completed + warranty_days; klaim garansi bisa dicatat/diupdate. Lookup global pelanggan masih bisa diperdalam. |
| F2.5 | SLA & estimasi | [~] | `estimated_finish_at` ditambah; indikator overdue di list/detail servis dan dashboard teknisi. Reminder internal otomatis (notifikasi/jadwal) belum. |
| F2.6 | Cetak/nota operasional | [x] | Nota servis & transaksi punya 2 versi: A4 dan thermal (80mm), bisa dipilih dari detail/print route `?format=a4|thermal`. |

## Fase 3 - Kontrol Bisnis & Integritas

**Tujuan:** memberi visibilitas bisnis dan kontrol data.

| ID | Item | Status | Kriteria Selesai |
|---|---|---|---|
| F3.1 | Laporan laba | [~] | Laba kotor estimasi (omzet-HPP) ditambah via snapshot `unit_cost` pada detail transaksi/part servis. Pemisahan laba jasa vs sparepart bisa diperdalam. |
| F3.2 | Closing kas harian | [~] | Laporan owner punya closing harian: total paid/due, ringkasan per metode bayar dan per kasir. Kas keluar/rekonsiliasi fisik formal belum. |
| F3.3 | Inventaris lanjutan | [x] | Supplier master, filter/alert stok minimum, stok opname sederhana (adjustment+audit), PO dasar dengan penerimaan barang menambah stok dan update cost. |
| F3.4 | Export & arsip | [ ] | Export CSV/PDF sesuai kebutuhan, format rapi, akses dibatasi role. |

## Fase 4 - Kualitas, Monitoring, Skalabilitas

**Tujuan:** menjaga stabilitas saat dipakai nyata.

| ID | Item | Status | Kriteria Selesai |
|---|---|---|---|
| F4.1 | Monitoring & error tracking | [ ] | Log terpusat/Sentry/alert error; health check dasar. |
| F4.2 | Test cakupan kritis | [ ] | Test untuk permission boundaries, concurrency stok, pembayaran/cancel edge case, fitur baru. |
| F4.3 | Performa & UX lapangan | [ ] | Mobile-friendly teknisi/kasir, pencarian cepat, cegah double submit, loading/feedback jelas. |
| F4.4 | Proses release | [ ] | Checklist migration/deploy/rollback, changelog, versioning sederhana. |

## Fase 5 - UAT, Pelatihan, Go-Live (Gerbang Akhir)

**Tujuan:** memastikan sistem benar-benar siap dipakai usaha.

| ID | Item | Status | Kriteria Selesai |
|---|---|---|---|
| F5.1 | UAT manual end-to-end | [ ] | Semua role uji alur nyata di staging: login, POS, servis, sparepart, bayar, cancel, nota, laporan, notifikasi. |
| F5.2 | Uji backup/restore nyata | [ ] | Backup terjadwal berjalan; restore diuji di environment terpisah dan hasilnya valid. |
| F5.3 | Pelatihan & SOP pengguna | [ ] | SOP singkat untuk Owner/Kasir/Gudang/Teknisi; akun produksi siap. |
| F5.4 | Checklist go-live | [ ] | Domain/HTTPS, env produksi, queue/scheduler jika ada, monitoring aktif, rollback plan siap. |
| F5.5 | Go-live & hypercare | [ ] | Deploy produksi, pemantauan awal, channel pelaporan bug, perbaikan cepat pasca rilis. |

## Cara Kerja Eksekusi

1. Pilih item berikutnya berdasarkan prioritas fase.
2. Implementasi kode/migrasi/config/docs.
3. Verifikasi: test otomatis relevan + uji manual alur terkait.
4. Update status item di dokumen ini (`[ ]/[~]/[x]`) dan catat temuan.
5. Lanjut item berikutnya hanya jika kriteria selesai terpenuhi.

## Catatan Keputusan

- **Pengujian manual utama** akan dilakukan pada `.env` lokal/lingkungan target, bukan hanya `.env.example`.
- Fitur lanjutan (multi-cabang, payment gateway otomatis, integrasi eksternal) dipertimbangkan setelah Fase 5 stabil.
