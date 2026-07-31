<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Traits\PrintTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PrintSaleCommand extends Command
{
    use PrintTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:print-sale {sale_id} {device_uuid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imprime el ticket de una venta en segundo plano utilizando el servicio de impresión';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $saleId = $this->argument('sale_id');
        $deviceUuid = $this->argument('device_uuid');
        if ($deviceUuid) {
            session(['device_token' => $deviceUuid]);
        }

        $sale = Sale::with(['user'])->find($saleId);

        if (!$sale) {
            $this->error("Venta #{$saleId} no encontrada.");
            Log::warning("PrintSaleCommand: Venta #{$saleId} no encontrada.");
            return 1;
        }

        // Set Auth user from sale creator so getPrinterConfig() can read user printer settings if configured
        if ($sale->user) {
            Auth::setUser($sale->user);
        }

        try {
            $this->printSale($sale->id);
            $this->info("Ticket de venta #{$sale->id} impreso correctamente.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Error al imprimir ticket de venta #{$sale->id}: " . $e->getMessage());
            Log::error("PrintSaleCommand Error (Venta #{$sale->id}): " . $e->getMessage());
            return 1;
        }
    }
}
