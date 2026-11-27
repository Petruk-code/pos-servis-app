<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('unpaid')->after('status'); // unpaid, dp, paid
            $table->decimal('paid_amount', 15, 2)->default(0)->after('payment_status');
            $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_amount', 'due_amount']);
        });
    }
};
