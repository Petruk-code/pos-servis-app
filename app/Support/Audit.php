<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Audit
{
    public static function log(string $action, ?Model $auditable = null, array $oldValues = [], array $newValues = [], ?string $description = null): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id' => $auditable?->getKey(),
                'old_values' => $oldValues ?: null,
                'new_values' => $newValues ?: null,
                'description' => $description,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 1000),
            ]);
        } catch (\Throwable $e) {
            report($e); // audit must not break main flow
        }
    }
}
