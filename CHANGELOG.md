# Changelog

## [Unreleased] - POS Servis

### Added
- Rebrand ke POS Servis, flow servis/tiket, role teknisi, dashboard teknisi.
- Pembayaran servis fleksibel (DP/parsial/lunas), metode cash/transfer/qris/debit/other.
- Garansi servis, klaim garansi, riwayat pelanggan/perangkat, SLA estimasi selesai.
- Cetak nota servis & transaksi A4/thermal.
- Laporan laba kotor estimasi (snapshot unit_cost), closing kas harian, export CSV/PDF.
- Inventaris: supplier, filter/alert stok, stok opname adjustment, PO dasar dan penerimaan stok.
- Audit log owner, health check `/health`, index performa, filter SLA overdue.
- Guard double-submit global, checklist release (`docs/checklist-release.md`).

### Security/Hardening
- Policy+authorize untuk resource utama, defense-in-depth POS kasir.
- Device password terenkripsi, disembunyikan saat lunas/diambil/batal.
- Audit log untuk aksi kritis.

### Notes
- Fase 4-5 masih memiliki item operasional/environment: Sentry/alert, backup/restore nyata, UAT/pelatihan, go-live.
