<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Database\QueryException;

class CleanProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando interactivo para purgar productos basado en niveles de stock y restaurar salvavidas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== ASISTENTE DE LIMPIEZA DE PRODUCTOS ===");
        
        // FASE 1: Monto a eliminar
        $stockParaEliminar = $this->ask("1. ¿Cuál es el monto de stock a buscar para ELIMINAR? (Ej. 0)");
        
        // Validar
        if(!is_numeric($stockParaEliminar)) {
            $this->error("Debes introducir un número válido.");
            return Command::FAILURE;
        }

        // FASE 2: Monto salvavidas
        $stockSalvavidas = $this->ask("2. ¿Cuál es el monto de stock salvavidas para BUSCAR Y RESTAURAR? (Ej. 7000)");

        if(!is_numeric($stockSalvavidas)) {
            $this->error("Debes introducir un número válido.");
            return Command::FAILURE;
        }

        // FASE 3: Monto final a reemplazar
        $nuevoStockFinal = $this->ask("3. ¿Por cuál cantidad deseas REEMPLAZAR el monto salvavidas? (Ej. 0)");

        if(!is_numeric($nuevoStockFinal)) {
            $this->error("Debes introducir un número válido.");
            return Command::FAILURE;
        }

        // Confirmación
        $this->warn("\nResumen de ejecución:");
        $this->line(" - Se intentarán borrar productos con stock EXACTAMENTE IGUAL a: {$stockParaEliminar}");
        $this->line(" - Los productos con stock igual a: {$stockSalvavidas} quedarán con stock: {$nuevoStockFinal}");
        
        if (!$this->confirm('¿Estás seguro de continuar con estas condiciones?')) {
            $this->info("Operación cancelada por el usuario.");
            return Command::SUCCESS;
        }

        $this->info("\n--- INICIANDO FASE 1: Eliminación ---");
        $productosAEliminar = Product::where('stock_qty', $stockParaEliminar)->get();
        $this->line("Encontrados " . $productosAEliminar->count() . " productos para purgar.");
        
        $eliminados = 0;
        $inactivados = 0;
        
        foreach ($productosAEliminar as $producto) {
            // Elimina el producto de los catálogos y el POS usando Soft Deletes nativo
            $producto->delete();
            $eliminados++;
        }
        
        $this->info("Fase 1 completada: {$eliminados} productos fueron Borrados Lógicamente (Invisibles en toda la app, conservados para facturas viejas).");

        $this->info("\n--- INICIANDO FASE 2: Restauración de Salvavidas ---");
        $productosSalvados = Product::where('stock_qty', $stockSalvavidas)->get();
        $this->line("Encontrados " . $productosSalvados->count() . " productos para restaurar.");
        
        $restaurados = 0;
        foreach ($productosSalvados as $producto) {
            $producto->stock_qty = $nuevoStockFinal;
            // Actualizar stock de las bodegas para estar seguros
            foreach($producto->warehouses as $warehouse) {
                $producto->warehouses()->updateExistingPivot($warehouse->id, [
                    'stock_qty' => $nuevoStockFinal
                ]);
            }
            $producto->save();
            $restaurados++;
        }
        
        $this->info("Fase 2 completada: {$restaurados} productos restaurados a su nuevo monto ({$nuevoStockFinal}).");
        
        $this->info("\n=== MANTENIMIENTO FINALIZADO CON ÉXITO ===");
        
        return Command::SUCCESS;
    }
}
