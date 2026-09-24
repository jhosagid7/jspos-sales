<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\NameNormalizer;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class NormalizeEntitiesNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'names:normalize {--dry-run : Muestra los cambios previstos sin alterar la base de datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normaliza los nombres de Usuarios, Clientes y Proveedores a formato Título propio, y Productos y Bodegas a MAYÚSCULAS.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('--- MODO SIMULACIÓN (DRY-RUN): Ningún registro será modificado ---');
        } else {
            $this->info('--- INICIANDO NORMALIZACIÓN DE NOMBRES EN BASE DE DATOS ---');
        }

        $stats = [
            'users' => $this->normalizeUsers($dryRun),
            'customers' => $this->normalizeCustomers($dryRun),
            'suppliers' => $this->normalizeSuppliers($dryRun),
            'warehouses' => $this->normalizeWarehouses($dryRun),
            'products' => $this->normalizeProducts($dryRun),
        ];

        $this->newLine();
        $this->table(
            ['Entidad', 'Formato Aplicado', 'Registros Modificados / Desfasados'],
            [
                ['Usuarios (users)', 'Nombre Propio (Title Case)', $stats['users']],
                ['Clientes (customers)', 'Nombre Propio (Title Case)', $stats['customers']],
                ['Proveedores (suppliers)', 'Nombre Propio (Title Case)', $stats['suppliers']],
                ['Bodegas / Depósitos (warehouses)', 'MAYÚSCULAS (Socio en Título)', $stats['warehouses']],
                ['Productos (products)', 'MAYÚSCULAS COMPLETAS', $stats['products']],
            ]
        );

        if ($dryRun) {
            $this->info("Simulación finalizada. Para aplicar los cambios reales ejecuta: php artisan names:normalize");
        } else {
            $this->info("¡Normalización completada exitosamente!");
        }

        return 0;
    }

    protected function normalizeUsers(bool $dryRun): int
    {
        $count = 0;
        User::cursor()->each(function ($user) use (&$count, $dryRun) {
            $target = NameNormalizer::personOrEntityName($user->name);
            if ($user->name !== $target) {
                $count++;
                if (!$dryRun) {
                    DB::table('users')->where('id', $user->id)->update(['name' => $target]);
                }
            }
        });
        return $count;
    }

    protected function normalizeCustomers(bool $dryRun): int
    {
        $count = 0;
        Customer::cursor()->each(function ($customer) use (&$count, $dryRun) {
            $target = NameNormalizer::personOrEntityName($customer->name);
            if ($customer->name !== $target) {
                $count++;
                if (!$dryRun) {
                    DB::table('customers')->where('id', $customer->id)->update(['name' => $target]);
                }
            }
        });
        return $count;
    }

    protected function normalizeSuppliers(bool $dryRun): int
    {
        $count = 0;
        Supplier::cursor()->each(function ($supplier) use (&$count, $dryRun) {
            $target = NameNormalizer::personOrEntityName($supplier->name);
            if ($supplier->name !== $target) {
                $count++;
                if (!$dryRun) {
                    DB::table('suppliers')->where('id', $supplier->id)->update(['name' => $target]);
                }
            }
        });
        return $count;
    }

    protected function normalizeWarehouses(bool $dryRun): int
    {
        $count = 0;
        Warehouse::cursor()->each(function ($wh) use (&$count, $dryRun) {
            $targetName = NameNormalizer::uppercase($wh->name);
            $targetPartner = $wh->partner_name ? NameNormalizer::personOrEntityName($wh->partner_name) : null;

            if ($wh->name !== $targetName || $wh->partner_name !== $targetPartner) {
                $count++;
                if (!$dryRun) {
                    DB::table('warehouses')->where('id', $wh->id)->update([
                        'name' => $targetName,
                        'partner_name' => $targetPartner,
                    ]);
                }
            }
        });
        return $count;
    }

    protected function normalizeProducts(bool $dryRun): int
    {
        $count = 0;
        Product::cursor()->each(function ($prod) use (&$count, $dryRun) {
            $target = NameNormalizer::uppercase($prod->name);
            if ($prod->name !== $target) {
                $count++;
                if (!$dryRun) {
                    DB::table('products')->where('id', $prod->id)->update(['name' => $target]);
                }
            }
        });
        return $count;
    }
}
