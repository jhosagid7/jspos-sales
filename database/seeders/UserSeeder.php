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
         // Super Admins (Developers/Owners)
         $superAdmins = [
             [
                 'name' => 'Jhonny Pirela (77)',
                 'email' => 'jhosagid77@gmail.com',
                 'password' => bcrypt('jhosagid'),
                 'profile' => 'Super Admin',
             ],
             [
                 'name' => 'Jhonny Sagid Pirela',
                 'email' => 'jhosagid7@gmail.com',
                 'password' => bcrypt('jhosagid'),
                 'profile' => 'Super Admin',
             ],
         ];
 
         foreach ($superAdmins as $data) {
             $user = User::updateOrCreate(
                 ['email' => $data['email']],
                 [
                     'name' => $data['name'],
                     'password' => $data['password'],
                     'profile' => $data['profile'],
                     'status' => 'Active',
                     'commission_percentage' => 0,
                 ]
             );
             $user->assignRole('Super Admin');
         }
 
         // Generic Seller for Testing
         $seller = User::updateOrCreate(
             ['email' => 'vendedor@prueba.com'],
             [
                 'name' => 'Vendedor de Prueba',
                 'password' => bcrypt('12345678'),
                 'profile' => 'Vendedor',
                 'status' => 'Active',
                 'commission_percentage' => 5.00,
             ]
         );
         $seller->assignRole('Vendedor');
     }
 }
