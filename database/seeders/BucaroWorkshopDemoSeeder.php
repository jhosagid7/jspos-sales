<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BucaroWorkshopDemoSeeder extends Seeder
{
    /**
     * Run the database seeds for Taller Automotriz BÃºcaro C.A.
     */
    public function run(): void
    {
        // 1. Ensure 'workshop' addon is active in configuration
        $config = Configuration::first();
        if ($config) {
            $addons = is_array($config->addon_modules) ? $config->addon_modules : [];
            if (!in_array('workshop', $addons)) {
                $addons[] = 'workshop';
                $config->addon_modules = $addons;
                $config->save();
            }
        }

        // 2. Ensure Mecanico Role exists
        if (!Role::where('name', 'Mecanico')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'Mecanico', 'guard_name' => 'web']);
        }

        // 3. Create Mechanics
        $mecanico1 = User::firstOrCreate(
            ['email' => 'juan.mecanico@talleronline.test'],
            [
                'name' => 'JUAN BUCARO (MAESTRO MECANICO)',
                'password' => Hash::make('password123'),
                'profile' => 'Mecanico',
                'phone' => '0414-7501234',
            ]
        );
        $mecanico1->assignRole('Mecanico');

        $mecanico2 = User::firstOrCreate(
            ['email' => 'pedro.frenos@talleronline.test'],
            [
                'name' => 'PEDRO MENDEZ (ESPECIALISTA EN FRENOS)',
                'password' => Hash::make('password123'),
                'profile' => 'Mecanico',
                'phone' => '0424-7405678',
            ]
        );
        $mecanico2->assignRole('Mecanico');

        $defaultCat = Category::first();
        $defaultCatId = $defaultCat ? $defaultCat->id : 1;
        $defaultSup = Supplier::first();
        $defaultSupId = $defaultSup ? $defaultSup->id : 1;

        // 4. Create Labor Fallback Product if not exists
        Product::firstOrCreate(
            ['sku' => 'SRV-LABOR'],
            [
                'name' => 'SERVICIO DE MANO DE OBRA TALLER',
                'cost' => 0.00,
                'price' => 20.00,
                'type' => 'service',
                'status' => 'available',
                'manage_stock' => 0,
                'stock_qty' => 999999,
                'low_stock' => 0,
                'category_id' => $defaultCatId,
                'supplier_id' => $defaultSupId,
            ]
        );

        // 5. Create Workshop Inventory Parts (Filtros, Pastillas, Aceites)
        $aceite = Product::firstOrCreate(
            ['sku' => 'REP-AC-15W40'],
            [
                'name' => 'ACEITE MOTOR 15W40 MINERAL (LITRO)',
                'cost' => 4.50,
                'price' => 7.00,
                'type' => 'physical',
                'status' => 'available',
                'manage_stock' => 1,
                'stock_qty' => 120,
                'low_stock' => 10,
                'category_id' => $defaultCatId,
                'supplier_id' => $defaultSupId,
            ]
        );

        $filtroAceite = Product::firstOrCreate(
            ['sku' => 'REP-FLT-OIL01'],
            [
                'name' => 'FILTRO DE ACEITE PH-8A / MILLENIUM',
                'cost' => 3.20,
                'price' => 6.50,
                'type' => 'physical',
                'status' => 'available',
                'manage_stock' => 1,
                'stock_qty' => 45,
                'low_stock' => 5,
                'category_id' => $defaultCatId,
                'supplier_id' => $defaultSupId,
            ]
        );

        $pastillasFreno = Product::firstOrCreate(
            ['sku' => 'REP-FRN-PST01'],
            [
                'name' => 'PASTILLAS DE FRENO DELANTERAS CERAMICA',
                'cost' => 18.00,
                'price' => 32.00,
                'type' => 'physical',
                'status' => 'available',
                'manage_stock' => 1,
                'stock_qty' => 25,
                'low_stock' => 4,
                'category_id' => $defaultCatId,
                'supplier_id' => $defaultSupId,
            ]
        );

        // 6. Corporate Customer 349: AGUAS DE MÃ‰RIDA C.A.
        $aguasMerida = Customer::firstOrCreate(
            ['taxpayer_id' => 'G-20007690-9'],
            [
                'name' => 'AGUAS DE MERIDA C.A.',
                'address' => 'Av. Gonzalo PicÃ³n, Edif. Aguas de MÃ©rida, MÃ©rida',
                'phone' => '0274-2634511',
                'email' => 'flota.operaciones@aguasdemerida.gob.ve',
            ]
        );

        // 7. Fleet of Aguas de MÃ©rida (4 Vehicles from FileMaker forensic records)
        $v1 = Vehicle::firstOrCreate(
            ['plate' => '24T LAH'],
            [
                'customer_id' => $aguasMerida->id,
                'brand' => 'FORD',
                'model' => 'F-150 FORTALEZA 4X4',
                'year' => 2007,
                'color' => 'BLANCO',
                'current_mileage' => 455820,
                'notes' => 'VehÃ­culo de cuadrilla operativa sector El VigÃ­a. TracciÃ³n 4x4.',
            ]
        );

        $v2 = Vehicle::firstOrCreate(
            ['plate' => 'A12BC34'],
            [
                'customer_id' => $aguasMerida->id,
                'brand' => 'CHEVROLET',
                'model' => 'LUV D-MAX',
                'year' => 2011,
                'color' => 'BLANCO INSTITUCIONAL',
                'current_mileage' => 312450,
                'notes' => 'Mantenimiento de bombas y estaciones de bombeo.',
            ]
        );

        $v3 = Vehicle::firstOrCreate(
            ['plate' => '78K MNB'],
            [
                'customer_id' => $aguasMerida->id,
                'brand' => 'TOYOTA',
                'model' => 'HILUX 2.7',
                'year' => 2015,
                'color' => 'AZUL',
                'current_mileage' => 220100,
                'notes' => 'Unidad de supervisiÃ³n de redes de distribuciÃ³n.',
            ]
        );

        $v4 = Vehicle::firstOrCreate(
            ['plate' => '55X ZZZ'],
            [
                'customer_id' => $aguasMerida->id,
                'brand' => 'IVECO',
                'model' => 'DAILY 59.12 CAMION CHUTO',
                'year' => 2008,
                'color' => 'BLANCO',
                'current_mileage' => 589200,
                'notes' => 'Transporte de tuberÃ­as de alta presiÃ³n y maquinaria pesada.',
            ]
        );

        // 8. Particular Customer: JUAN PARRA (Propietario / Particular)
        $clienteJuan = Customer::firstOrCreate(
            ['taxpayer_id' => 'V-11888999'],
            [
                'name' => 'JUAN PARRA',
                'address' => 'Sector La Inmaculada, El VigÃ­a',
                'phone' => '0414-7589900',
            ]
        );

        $vJuan = Vehicle::firstOrCreate(
            ['plate' => 'AE832RM'],
            [
                'customer_id' => $clienteJuan->id,
                'brand' => 'TOYOTA',
                'model' => 'COROLLA GLI',
                'year' => 2014,
                'color' => 'PLATA',
                'current_mileage' => 142500,
            ]
        );

        // 9. Initial Sample Work Order for Aguas de MÃ©rida (In Progress)
        $adminUser = User::first() ?? $mecanico1;

        $existingOrder = WorkOrder::where('order_number', 'OT-00001')->first();
        if (!$existingOrder) {
            $ot1 = WorkOrder::create([
                'order_number' => 'OT-00001',
                'customer_id' => $aguasMerida->id,
                'vehicle_id' => $v1->id,
                'user_id' => $adminUser->id,
                'status' => 'in_progress',
                'entry_mileage' => 455820,
                'initial_diagnosis' => 'Ruido en tren delantero al frenar y servicio de cambio de aceite para cuadrilla',
                'technical_notes' => 'Discos rayados y chirrido metÃ¡lico. Fuga leve por filtro de aceite viejo.',
                'promised_at' => now()->addDays(2),
            ]);

            // Add Items: 1 Labor, 1 Oil, 1 Oil Filter, 1 Brake Pads
            WorkOrderItem::create([
                'work_order_id' => $ot1->id,
                'mechanic_id' => $mecanico2->id,
                'type' => 'labor',
                'description' => 'Reemplazo de pastillas de freno y rectificaciÃ³n de discos delanteros',
                'quantity' => 1,
                'unit_price' => 35.00,
                'mechanic_commission_percentage' => 30.00,
            ]);

            WorkOrderItem::create([
                'work_order_id' => $ot1->id,
                'product_id' => $pastillasFreno->id,
                'type' => 'part',
                'description' => $pastillasFreno->name,
                'quantity' => 1,
                'unit_cost' => $pastillasFreno->cost,
                'unit_price' => $pastillasFreno->price,
            ]);

            WorkOrderItem::create([
                'work_order_id' => $ot1->id,
                'product_id' => $aceite->id,
                'type' => 'part',
                'description' => $aceite->name,
                'quantity' => 5,
                'unit_cost' => $aceite->cost,
                'unit_price' => $aceite->price,
            ]);

            WorkOrderItem::create([
                'work_order_id' => $ot1->id,
                'product_id' => $filtroAceite->id,
                'type' => 'part',
                'description' => $filtroAceite->name,
                'quantity' => 1,
                'unit_cost' => $filtroAceite->cost,
                'unit_price' => $filtroAceite->price,
            ]);

            $ot1->recalculateTotals();
        }
    }
}