<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or retrieve default departments
        $miscelaneos = Department::firstOrCreate(
            ['name' => 'Misceláneos'],
            ['report_type' => 'local']
        );

        $papeleria = Department::firstOrCreate(
            ['name' => 'Papelería'],
            ['report_type' => 'local']
        );

        $otros = Department::firstOrCreate(
            ['name' => 'Otros'],
            ['report_type' => 'local']
        );

        $servicios = Department::firstOrCreate(
            ['name' => 'Servicios'],
            ['report_type' => 'gravado']
        );

        // 2. Associate existing categories that don't have a department assigned yet
        Category::whereNull('department_id')->update([
            'department_id' => $otros->id
        ]);
    }
}
