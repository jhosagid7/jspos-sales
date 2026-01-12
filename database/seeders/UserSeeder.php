<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Jhonny Pirela',
            'email' => 'jhosagid77@gmail.com',
            'password' => bcrypt('jhosagid'),
            'profile' => 'Admin',
            'status' => 'Active',
            'commission_percentage' => 0,
        ]);
        $admin->assignRole('Admin');

        // Generic Seller for Testing
        $seller = User::create([
            'name' => 'Vendedor de Prueba',
            'email' => 'vendedor@prueba.com',
            'password' => bcrypt('12345678'),
            'profile' => 'Vendedor',
            'status' => 'Active',
            'commission_percentage' => 5.00,
        ]);
        $seller->assignRole('Vendedor');
    }
}
