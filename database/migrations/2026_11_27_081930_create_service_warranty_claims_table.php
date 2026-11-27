<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('claim_date');
            $table->text('issue_description');
            $table->text('resolution')->nullable();
            $table->string('status', 20)->default('open'); // open, in_progress, resolved, rejected
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['service_ticket_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_warranty_claims');
    }
};
