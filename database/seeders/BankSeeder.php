<?php
 
 namespace Database\Seeders;
 
 use App\Models\Bank;
 use Illuminate\Database\Seeder;
 
 class BankSeeder extends Seeder
 {
     /**
      * Run the database seeds.
      */
     public function run(): void
     {
         Bank::create([
             'name' => 'Bancolombia',
             'account_number' => 'N/A',
             'cedula' => 'N/A',
             'phone' => 'N/A',
             'state' => 1,
             'sort' => 0,
             'currency_code' => 'COP'
         ]);
 
         Bank::create([
             'name' => 'Banco de Venezuela',
             'account_number' => 'N/A',
             'cedula' => 'N/A',
             'phone' => 'N/A',
             'state' => 1,
             'sort' => 1,
             'currency_code' => 'VED'
         ]);
 
         Bank::create([
             'name' => 'Zelle',
             'account_number' => 'N/A',
             'cedula' => 'N/A',
             'phone' => 'N/A',
             'state' => 1,
             'sort' => 2,
             'currency_code' => 'USD'
         ]);
 
         Bank::create([
             'name' => 'Banesco',
             'account_number' => 'N/A',
             'cedula' => 'N/A',
             'phone' => 'N/A',
             'state' => 1,
             'sort' => 3,
             'currency_code' => 'VED'
         ]);
     }
 }
