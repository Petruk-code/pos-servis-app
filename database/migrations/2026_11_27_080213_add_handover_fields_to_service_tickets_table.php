<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->json('intake_checklist')->nullable()->after('accessories');
            $table->string('intake_photo_path')->nullable()->after('intake_checklist');
            $table->string('handover_photo_path')->nullable()->after('intake_photo_path');
            $table->string('received_signature_name')->nullable()->after('handover_photo_path');
            $table->string('pickup_signature_name')->nullable()->after('received_signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'intake_checklist',
                'intake_photo_path',
                'handover_photo_path',
                'received_signature_name',
                'pickup_signature_name',
            ]);
        });
    }
};
