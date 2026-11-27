<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'owner',
                'description' => 'Pemilik toko dengan hak akses penuh.',
            ],
            [
                'name' => 'kasir',
                'description' => 'Mengelola POS, penerimaan servis, dan transaksi penjualan.',
            ],
            [
                'name' => 'gudang',
                'description' => 'Mengelola inventaris sparepart dan pergerakan stok.',
            ],
            [
                'name' => 'teknisi',
                'description' => 'Menangani antrean servis komputer/laptop dan pemakaian sparepart.',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }
}
