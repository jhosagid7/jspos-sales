<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Transfer;
use App\Models\SaleReturn;
use App\Models\TransferStockLayer;
use App\Models\SaleLayerConsumption;

class PartnerStockService
{
    /**
     * Check if the partner sales module is enabled in configuration / license.
     */
    public static function isModuleActive(): bool
    {
        return function_exists('has_module') ? has_module('module_partner_sales') : true;
    }

    /**
     * Registers stock layers when a transfer is completed.
     * Each transferred product creates a new layer with remaining_quantity = received_quantity.
     */
    public function registerTransferLayers(Transfer $transfer): void
    {
        if (!static::isModuleActive()) {
            return;
        }

        $transfer->loadMissing(['details.product', 'fromWarehouse']);

        // Only register partner stock layers if the origin warehouse is explicitly marked as a partner warehouse
        if (!$transfer->fromWarehouse?->is_partner_warehouse) {
            return;
        }

        foreach ($transfer->details as $detail) {
            $receivedQty = (float) ($detail->received_quantity > 0 ? $detail->received_quantity : $detail->quantity);

            if ($receivedQty <= 0) {
                continue;
            }

            TransferStockLayer::create([
                'transfer_id' => $transfer->id,
                'transfer_detail_id' => $detail->id,
                'product_id' => $detail->product_id,
                'origin_warehouse_id' => $transfer->from_warehouse_id,
                'destination_warehouse_id' => $transfer->to_warehouse_id,
                'initial_quantity' => $receivedQty,
                'remaining_quantity' => $receivedQty,
                'cost_price' => (float) ($detail->product->cost ?? 0),
            ]);
        }
    }

    /**
     * Consumes stock layers in FIFO order (id ASC) when a sale is confirmed.
     * Slices the consumption across multiple partner layers if needed.
     * If there are no layers available (e.g., initial or unlayered stock),
     * creates an unlayered consumption record attributed to the sale warehouse as fallback.
     */
    public function consumeLayersForSale(Sale $sale): void
    {
        if (!static::isModuleActive()) {
            return;
        }

        $sale->loadMissing(['details.product']);

        foreach ($sale->details as $detail) {
            $productId = $detail->product_id;
            $qtyNeeded = (float) $detail->quantity;
            $warehouseId = $detail->warehouse_id ?: ($sale->warehouse_id ?? null);
            $unitPrice = (float) $detail->sale_price;

            if ($qtyNeeded <= 0) {
                continue;
            }

            // Lock and retrieve available layers for this product and destination warehouse in FIFO order
            $query = TransferStockLayer::where('product_id', $productId)
                ->where('remaining_quantity', '>', 0);

            if ($warehouseId) {
                $query->where('destination_warehouse_id', $warehouseId);
            }

            $availableLayers = $query->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $remainingToConsume = $qtyNeeded;

            foreach ($availableLayers as $layer) {
                if ($remainingToConsume <= 0) {
                    break;
                }

                $availableInLayer = (float) $layer->remaining_quantity;
                $take = min($remainingToConsume, $availableInLayer);

                // Deduct from layer
                $layer->remaining_quantity = round($availableInLayer - $take, 4);
                $layer->save();

                $unitCost = (float) (($layer->cost_price !== null && (float) $layer->cost_price > 0) ? $layer->cost_price : ($detail->product->cost ?? 0));
                $totalCost = round($take * $unitCost, 4);

                // Create consumption slice
                SaleLayerConsumption::create([
                    'sale_id' => $sale->id,
                    'sale_detail_id' => $detail->id,
                    'transfer_stock_layer_id' => $layer->id,
                    'origin_warehouse_id' => $layer->origin_warehouse_id,
                    'product_id' => $productId,
                    'quantity' => $take,
                    'unit_price' => $unitPrice,
                    'total_price' => round($take * $unitPrice, 4),
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                ]);

                $remainingToConsume = round($remainingToConsume - $take, 4);
            }

            // Fallback for stock without transfer layers (e.g., direct inventory, initial stock)
            if ($remainingToConsume > 0) {
                $fallbackWarehouseId = $warehouseId ?: ($sale->warehouse_id ?? $detail->warehouse_id);
                $fallbackUnitCost = (float) ($detail->product->cost ?? 0);
                $fallbackTotalCost = round($remainingToConsume * $fallbackUnitCost, 4);

                SaleLayerConsumption::create([
                    'sale_id' => $sale->id,
                    'sale_detail_id' => $detail->id,
                    'transfer_stock_layer_id' => null,
                    'origin_warehouse_id' => $fallbackWarehouseId,
                    'product_id' => $productId,
                    'quantity' => $remainingToConsume,
                    'unit_price' => $unitPrice,
                    'total_price' => round($remainingToConsume * $unitPrice, 4),
                    'unit_cost' => $fallbackUnitCost,
                    'total_cost' => $fallbackTotalCost,
                ]);
            }
        }
    }

    /**
     * Restores layer quantities and removes consumption records when a sale is voided/deleted.
     */
    public function restoreLayersForSale(Sale $sale): void
    {
        $consumptions = SaleLayerConsumption::where('sale_id', $sale->id)->get();

        foreach ($consumptions as $consumption) {
            if ($consumption->transfer_stock_layer_id) {
                $layer = TransferStockLayer::find($consumption->transfer_stock_layer_id);
                if ($layer) {
                    $layer->remaining_quantity = min(
                        (float) $layer->initial_quantity,
                        round((float) $layer->remaining_quantity + (float) $consumption->quantity, 4)
                    );
                    $layer->save();
                }
            }
            $consumption->delete();
        }
    }

    /**
     * Restores layer quantities when a sale return is processed.
     */
    public function restoreLayersForReturn(SaleReturn $saleReturn): void
    {
        $saleReturn->loadMissing('details');

        foreach ($saleReturn->details as $returnDetail) {
            $qtyToReturn = (float) $returnDetail->quantity_returned;

            if ($qtyToReturn <= 0 || $returnDetail->stock_action !== 'returned_to_stock') {
                continue;
            }

            // Find consumptions for this sale detail ordered backwards (LIFO rollback of consumption)
            $consumptions = SaleLayerConsumption::where('sale_detail_id', $returnDetail->sale_detail_id)
                ->orderBy('id', 'desc')
                ->get();

            $remainingToRestore = $qtyToReturn;

            foreach ($consumptions as $consumption) {
                if ($remainingToRestore <= 0) {
                    break;
                }

                $consumedQty = (float) $consumption->quantity;
                $restoreQty = min($remainingToRestore, $consumedQty);

                if ($consumption->transfer_stock_layer_id) {
                    $layer = TransferStockLayer::find($consumption->transfer_stock_layer_id);
                    if ($layer) {
                        $layer->remaining_quantity = min(
                            (float) $layer->initial_quantity,
                            round((float) $layer->remaining_quantity + $restoreQty, 4)
                        );
                        $layer->save();
                    }
                }

                if ($restoreQty >= $consumedQty) {
                    $consumption->delete();
                } else {
                    $consumption->quantity = round($consumedQty - $restoreQty, 4);
                    $consumption->total_price = round($consumption->quantity * (float) $consumption->unit_price, 4);
                    $consumption->save();
                }

                $remainingToRestore = round($remainingToRestore - $restoreQty, 4);
            }
        }
    }
}