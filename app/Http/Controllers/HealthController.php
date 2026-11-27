<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $ok = true;
        $db = 'ok';
        try { DB::select('select 1'); } catch (\Throwable $e) { $ok = false; $db = 'error'; }
        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'time' => now()->toIso8601String(),
            'checks' => ['database' => $db],
        ], $ok ? 200 : 503);
    }
}
