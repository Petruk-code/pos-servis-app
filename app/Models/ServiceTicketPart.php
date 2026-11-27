<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTicketPart extends Model
{
    protected $fillable = [
        'service_ticket_id',
        'product_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'subtotal',
    ];

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
