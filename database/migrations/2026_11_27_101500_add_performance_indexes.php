<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->index('customer_phone');
            $table->index('serial_number');
            $table->index('status');
            $table->index('estimated_finish_at');
            $table->index('technician_id');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('status');
            $table->index('payment_method');
            $table->index('created_at');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('stock');
        });
    }
    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropIndex(['customer_phone']);
            $table->dropIndex(['serial_number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['estimated_finish_at']);
            $table->dropIndex(['technician_id']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['created_at']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['stock']);
        });
    }
};
