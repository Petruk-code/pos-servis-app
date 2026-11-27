<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTicket extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_number',
        'customer_name',
        'customer_phone',
        'device_type',
        'device_brand_model',
        'serial_number',
        'device_password',
        'accessories',
        'problem_description',
        'technician_notes',
        'estimated_cost',
        'service_fee',
        'warranty_days',
        'status',
        'received_by',
        'technician_id',
        'transaction_id',
        'completed_at',
        'picked_up_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'completed_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'device_password' => 'encrypted',
        ];
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function parts()
    {
        return $this->hasMany(ServiceTicketPart::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(ServiceTicketStatusHistory::class)->latest();
    }

    // Total tagihan (Biaya Jasa + Total Sparepart)
    public function getGrandTotalAttribute(): float
    {
        $partsTotal = $this->parts->sum('subtotal');
        return (float) $this->service_fee + (float) $partsTotal;
    }
}
