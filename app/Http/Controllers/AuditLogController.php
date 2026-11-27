<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->role?->name === 'owner', 403);
        $validated = $request->validate([
            'event' => ['nullable', 'string', 'max:100'],
            'user' => ['nullable', 'string', 'max:100'],
        ]);
        $event = trim($validated['event'] ?? '');
        $user = trim($validated['user'] ?? '');
        $logs = AuditLog::query()->with('user')
            ->when($event !== '', fn($q) => $q->where('event', 'like', "%{$event}%"))
            ->when($user !== '', fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$user}%")))
            ->latest()->paginate(25)->withQueryString();
        return view('audit-logs.index', compact('logs', 'event', 'user'));
    }
}
