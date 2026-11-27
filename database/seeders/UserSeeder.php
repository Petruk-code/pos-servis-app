<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
                'role_id' => $ownerRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Kasir Utama',
                'username' => 'kasir',
                'email' => 'kasir@tokokomputer.local',
                'role_id' => $kasirRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Admin Gudang',
                'username' => 'gudang',
                'email' => 'gudang@tokokomputer.local',
                'role_id' => $gudangRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Teknisi Komputer',
                'username' => 'teknisi',
                'email' => 'teknisi@tokokomputer.local',
                'role_id' => $teknisiRole?->id,
                'is_active' => true,
            ],
        ];

        $generated = [];

        foreach ($users as $user) {
            $plain = env('SEED_' . strtoupper($user['username']) . '_PASSWORD') ?: Str::random(20);
            $model = User::firstOrCreate(
                ['username' => $user['username']],
                [...$user, 'password' => Hash::make($plain)]
            );

            if ($model->wasRecentlyCreated) {
                $generated[$user['username']] = $plain;
            }
        }

        if ($generated !== []) {
            $this->command?->warn('Akun seeder dibuat. Simpan password ini dan ganti setelah login:');
            foreach ($generated as $username => $plain) {
                $this->command?->line("  {$username}: {$plain}");
            }
        }
    }
}
