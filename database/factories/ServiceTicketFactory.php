<?php

namespace Database\Factories;

use App\Models\ServiceTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceTicket>
 */
class ServiceTicketFactory extends Factory
{
    protected $model = ServiceTicket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => 'SRV-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('###'),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('08##########'),
            'device_type' => 'Laptop',
            'device_brand_model' => fake()->randomElement(['Asus Vivobook', 'Lenovo ThinkPad', 'Acer Aspire']),
            'serial_number' => strtoupper(fake()->bothify('SN-########')),
            'device_password' => null,
            'accessories' => 'Unit + Charger',
            'problem_description' => fake()->sentence(),
            'technician_notes' => null,
            'estimated_cost' => 0,
            'service_fee' => 0,
            'warranty_days' => 0,
            'status' => 'antre',
            'received_by' => User::factory(),
            'technician_id' => null,
            'transaction_id' => null,
            'completed_at' => null,
            'picked_up_at' => null,
        ];
    }
}
