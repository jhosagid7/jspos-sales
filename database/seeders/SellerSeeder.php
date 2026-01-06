<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Role
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Vendedor']);

        // Create Default User
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'oficina@gmail.com'],
            [
                'name' => 'OFICINA',
                'password' => bcrypt('123'),
                'profile' => 'Vendedor',
                'status' => 'Active',
                'commission_percentage' => 0
            ]
        );

        $user->assignRole($role);
    }
}
