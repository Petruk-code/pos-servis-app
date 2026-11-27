# Checklist UAT - POS Servis (Fase 5)

Gunakan checklist ini saat UAT di staging/lingkungan target sebelum go-live. Centang setiap item yang lulus, catat bukti/issue.

## Persiapan
- [ ] `.env` target benar (`APP_ENV`, `APP_DEBUG=false` untuk produksi, DB non-root, APP_URL, MAIL).
- [ ] `composer install`/build aset sesuai env; `php artisan migrate --force` dijalankan.
- [ ] `php artisan storage:link` dijalankan (foto intake/handover).
- [ ] `php artisan config:cache route:cache view:cache` (produksi) atau clear cache saat uji.
- [ ] `GET /health` mengembalikan `200`.
- [ ] Akun uji tersedia: Owner, Kasir, Teknisi (dan Gudang jika dipakai).

## Akses & Keamanan
- [ ] Owner bisa akses semua modul admin/laporan/audit/supplier/PO/stok opname.
- [ ] Kasir tidak bisa akses modul owner-only (reports/export, audit log, supplier, PO, stok opname, manajemen karyawan).
- [ ] Teknisi hanya melihat/mengubah tiket sesuai kewenangan; tidak bisa akses POS/transaksi pembayaran yang dibatasi.
- [ ] Policy menolak akses URL langsung yang tidak berhak (403/redirect sesuai desain).

## Servis / Tiket (end-to-end)
- [ ] Buat tiket: nomor tiket unik, data pelanggan/perangkat tersimpan.
- [ ] Ubah status servis; riwayat status tercatat.
- [ ] Estimasi selesai/SLA tampil; indikator overdue tampil pada tiket lewat estimasi.
- [ ] Tambah sparepart; stok berkurang sesuai aturan; movement tercatat.
- [ ] Notifikasi in-app status servis muncul untuk user terkait.
- [ ] Email notifikasi status (jika MAIL dikonfigurasi) terkirim untuk status yang didukung.
- [ ] Device password terenkripsi/tidak bocor di tampilan yang tidak berhak; hilang saat lunas/diambil/batal.

## Pembayaran Servis
- [ ] DP/parsial: `payment_status=partial`, `paid_amount/due_amount` benar.
- [ ] Pelunasan: `payment_status=paid`, status/timestamp sesuai, nota bisa dicetak.
- [ ] Metode bayar cash/transfer/qris/debit/other tercatat.
- [ ] Pembayaran ganda pada tiket lunas ditolak/diberi error yang benar.

## Cetak / Nota
- [ ] Nota servis A4 dapat dicetak/diunduh.
- [ ] Nota servis thermal dapat dicetak/diunduh.
- [ ] Nota transaksi POS A4 dapat dicetak/diunduh.
- [ ] Nota transaksi POS thermal dapat dicetak/diunduh.

## POS / Transaksi
- [ ] Checkout cash sukses: stok berkurang, detail transaksi benar, kembalian benar.
- [ ] Checkout non-cash sukses sesuai aturan nominal.
- [ ] Checkout ditolak jika stok kurang/uang cash kurang.
- [ ] Riwayat transaksi tampil sesuai role.

## Pembatalan
- [ ] Kasir bisa ajukan pembatalan dengan alasan; duplikat pending ditolak.
- [ ] Owner bisa approve/reject; status request benar.
- [ ] Cancel transaksi mengembalikan stok; tiket servis terkait di-reset sesuai aturan.
- [ ] Cancel ganda ditolak.

## Inventaris
- [ ] Supplier CRUD owner-only.
- [ ] Produk: filter stok menipis/habis, supplier terkait tampil.
- [ ] Stok opname: hanya selisih membuat adjustment movement + audit log.
- [ ] PO draft: buat, terima barang menambah stok, update cost price, movement incoming, audit log; cancel draft.

## Laporan & Audit
- [ ] Laporan periode benar: omzet, transaksi, produk terjual, laba kotor estimasi, servis.
- [ ] Closing kas harian per metode bayar dan per kasir tampil.
- [ ] Export CSV owner-only berhasil.
- [ ] Export PDF owner-only berhasil.
- [ ] Audit log owner mencatat aksi kritis.

## Operasional / Go-Live
- [ ] Backup DB + `storage/app/public` dibuat; restore diuji di env terpisah.
- [ ] Jadwal backup/offsite dan retensi disepakati.
- [ ] Secret production aman; secret lama yang pernah terekspos dirotasi.
- [ ] Monitoring/error alert aktif (minimal log + health check; Sentry opsional).
- [ ] SOP singkat Owner/Kasir/Teknisi diserahkan.
- [ ] Channel pelaporan bug/hypercare disepakati.

## Hasil UAT
- Tanggal:
- Lingkungan:
- Penguji:
- Hasil: [ ] Lulus / [ ] Lulus dengan catatan / [ ] Tidak lulus
- Catatan/issue:
