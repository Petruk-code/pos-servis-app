<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->default(0)->after('unit_price');
        });
        Schema::table('service_ticket_parts', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) { $table->dropColumn('unit_cost'); });
        Schema::table('service_ticket_parts', function (Blueprint $table) { $table->dropColumn('unit_cost'); });
    }
};
