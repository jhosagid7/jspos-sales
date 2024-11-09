<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Bank;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // test category
        $category = Category::create(['name' => 'Electrónica']);


        // test supplier
        $supplier = Supplier::create([
            'name' => 'Testing Supplier',
            'address' => 'México D.F.',
            'phone' => '555555'
        ]);



        // test customes
        Customer::create([
            'name' => 'Testing Customer',
            'address' => 'Address',
            'email' => 'customer@a.com',
            'phone' => '81000333',
            'type' => 'Consumidor Final'
        ]);


        // test bank
        Bank::create([
            'name' => 'Banco Nacional de Credito',
            'state' => 1,
            'sort' => 0
        ]);



        // test product
        $product = Product::create([
            'sku' => '750',
            'name' => 'PC Gaming',
            'description' => 'Una pc de alto rendimiento para gaming',
            'type' => 'physical',
            'status' => 'available',
            'cost' => 549,
            'price' => 899,
            'manage_stock' => 1,
            'stock_qty' => 100,
            'low_stock' => 5,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
        ]);


        //company
        Configuration::create(
            [
                'business_name' => 'IT COMPANY',
                'address' => 'VENEZUELA',
                'phone' => '5555555',
                'taxpayer_id' => 'RUT123456',
                'vat' => 16,
                'printer_name' => '80mm',
                'leyend' => 'Gracias por su compra!',
                'website' => 'https://jhonnypirela.dev',
                'credit_days' => 15
            ]
        );


        // test user
        $adminUser = User::create([
            'name' => 'Jhonny Pirela',
            'email' => 'jhosagid7@gmail.com',
            'password' => bcrypt('jhosagid'),
            'profile' => 'Administrador'
        ]);

        // test role
        $adminRole = Role::create(['name' => 'Administrador']);

        // create permissions
        Permission::insert([
            ['name' => 'ventas', 'guard_name' => 'web'],
            ['name' => 'compras', 'guard_name' => 'web'],
            ['name' => 'inventarios', 'guard_name' => 'web'],
            ['name' => 'usuarios', 'guard_name' => 'web'],
            ['name' => 'clientes', 'guard_name' => 'web'],
            ['name' => 'categorias', 'guard_name' => 'web'],
            ['name' => 'productos', 'guard_name' => 'web'],
            ['name' => 'roles', 'guard_name' => 'web'],
            ['name' => 'asignacion', 'guard_name' => 'web'],
            ['name' => 'proveedores', 'guard_name' => 'web'],
            ['name' => 'reportes', 'guard_name' => 'web'],
            ['name' => 'catalogos', 'guard_name' => 'web'],
            ['name' => 'personal', 'guard_name' => 'web'],
            ['name' => 'settings', 'guard_name' => 'web'],
        ]);

        // sync permissions to admin
        $permissions = Permission::all();
        $adminRole->syncPermissions($permissions);

        // Asignar el rol de Admin al usuario creado
        $adminUser->assignRole($adminRole);
    }
}
