<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceWarrantyClaim extends Model
{
    protected $fillable = [
        'service_ticket_id', 'reported_by', 'claim_date', 'issue_description',
        'resolution', 'status', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['claim_date' => 'date', 'resolved_at' => 'datetime'];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(ServiceTicket::class, 'service_ticket_id'); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
}
