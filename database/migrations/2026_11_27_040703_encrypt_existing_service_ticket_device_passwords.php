<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_tickets')
            ->select('id', 'device_password')
            ->whereNotNull('device_password')
            ->orderBy('id')
            ->chunkById(100, function ($tickets): void {
                foreach ($tickets as $ticket) {
                    $value = $ticket->device_password;
                    if ($value === '') {
                        continue;
                    }

                    try {
                        Crypt::decryptString($value);
                        continue; // already encrypted
                    } catch (\Throwable) {
                        // plaintext, encrypt below
                    }

                    DB::table('service_tickets')
                        ->where('id', $ticket->id)
                        ->update(['device_password' => Crypt::encryptString($value)]);
                }
            });
    }

    public function down(): void
    {
        // No safe automatic decryption rollback; data remains encrypted.
    }
};
