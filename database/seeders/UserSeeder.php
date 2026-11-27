<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $ownerRole = Role::where('name', 'owner')->first();
        $kasirRole = Role::where('name', 'kasir')->first();
        $gudangRole = Role::where('name', 'gudang')->first();
        $teknisiRole = Role::where('name', 'teknisi')->first();

        $users = [
            [
                'name' => 'Owner Toko',
                'username' => 'owner',
                'email' => 'owner@tokokomputer.local',
                'password' => Hash::make('password123'),
                'role_id' => $ownerRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Kasir Utama',
                'username' => 'kasir',
                'email' => 'kasir@tokokomputer.local',
                'password' => Hash::make('password123'),
                'role_id' => $kasirRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Admin Gudang',
                'username' => 'gudang',
                'email' => 'gudang@tokokomputer.local',
                'password' => Hash::make('password123'),
                'role_id' => $gudangRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Teknisi Komputer',
                'username' => 'teknisi',
                'email' => 'teknisi@tokokomputer.local',
                'password' => Hash::make('password123'),
                'role_id' => $teknisiRole?->id,
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['username' => $user['username']],
                $user
            );
        }
    }
}
