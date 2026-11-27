<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('due_amount', 15, 2)->default(0)->after('change_amount');
            $table->string('payment_status', 20)->default('paid')->after('status'); // paid, partial
            $table->text('payment_notes')->nullable()->after('payment_status');
        });

        DB::table('transactions')->whereNull('payment_status')->update(['payment_status' => 'paid']);
        DB::table('transactions')->where('status', 'completed')->where('paid_amount', '<', DB::raw('total'))->update(['payment_status' => 'partial']);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['due_amount', 'payment_status', 'payment_notes']);
        });
    }
};
