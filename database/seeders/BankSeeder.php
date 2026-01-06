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
            'state' => 1,
            'sort' => 0,
            'currency_code' => 'COP'
        ]);

        Bank::create([
            'name' => 'Banco de Venezuela',
            'state' => 1,
            'sort' => 1,
            'currency_code' => 'VED'
        ]);

        Bank::create([
            'name' => 'Zelle',
            'state' => 1,
            'sort' => 2,
            'currency_code' => 'USD'
        ]);

        Bank::create([
            'name' => 'Banesco',
            'state' => 1,
            'sort' => 3,
            'currency_code' => 'VED'
        ]);
    }
}
