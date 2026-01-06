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

        // Sellers
        $sellers = [
            [
                'name' => 'Oficina',
                'email' => 'oficina@gmail.com',
            ],
            [
                'name' => 'Elizabeth Hernandez',
                'email' => 'elizabeth@gmail.com',
            ],
            [
                'name' => 'Orlando Udaneta',
                'email' => 'orlando@gmail.com',
            ],
            [
                'name' => 'Javier Ramirez',
                'email' => 'javier@gmail.com',
            ],
        ];

        foreach ($sellers as $sellerData) {
            $seller = User::create([
                'name' => $sellerData['name'],
                'email' => $sellerData['email'],
                'password' => bcrypt('1234'),
                'profile' => 'Vendedor',
                'status' => 'Active',
                'commission_percentage' => 8.00,
            ]);
            $seller->assignRole('Vendedor');
        }
    }
}
