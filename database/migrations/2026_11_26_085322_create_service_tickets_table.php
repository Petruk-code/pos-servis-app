<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // Contoh: SRV-20261126-001

            // Data Pelanggan
            $table->string('customer_name');
            $table->string('customer_phone', 25);

            // Data Perangkat Komputer/Laptop
            $table->string('device_type'); // Laptop, PC Rakitan, Printer, Monitor, dll
            $table->string('device_brand_model'); // Contoh: Asus ROG Strix G15
            $table->string('serial_number')->nullable(); // SN / Service Tag
            $table->string('device_password')->nullable(); // Password Windows/BIOS (opsional)
            $table->text('accessories')->nullable(); // Kelengkapan: Charger, Tas, Mouse, dll

            // Keluhan & Diagnosa Teknisi
            $table->text('problem_description'); // Keluhan pelanggan
            $table->text('technician_notes')->nullable(); // Hasil cek / tindakan teknisi

            // Biaya & Status
            $table->decimal('estimated_cost', 12, 2)->default(0); // Estimasi awal
            $table->decimal('service_fee', 12, 2)->default(0); // Biaya jasa final
            $table->integer('warranty_days')->default(0); // Garansi servis (hari)
            $table->enum('status', [
                'antre',
                'dicek',
                'menunggu_sparepart',
                'selesai',
                'diambil',
                'batal'
            ])->default('antre');

            // Relasi User & Transaksi Kasir
            $table->foreignId('received_by')->constrained('users'); // Kasir/Admin penerima
            $table->foreignId('technician_id')->nullable()->constrained('users'); // Teknisi yang mengerjakan
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete(); // Terhubung saat dibayar di POS

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamps();
        });

        // Tabel pemakaian sparepart dari stok toko pada tiket servis
        Schema::create('service_ticket_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2); // Harga jual saat dipasang
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_parts');
        Schema::dropIfExists('service_tickets');
    }
};
