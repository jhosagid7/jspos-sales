<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SellerConfig;
use App\Models\Customer;
use App\Helpers\CurrencyHelper;
use Illuminate\Support\Facades\Log;

class PriceCalculatorService
{
    /**
     * Calculate product price based on seller and customer configuration.
     * 
     * @param Product $product
     * @param mixed $sellerConfig (SellerConfig model or null)
     * @param mixed $customer (Customer model or array or null)
     * @return array
     */
    public function calculate(Product $product, $sellerConfig = null, $customer = null)
    {
        // 1. Get Base Price (Converted to Primary Currency)
        $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
        $exchangeRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
        
        // Determine base price (using standard logic, assuming qty 1 for price list)
        $basePriceInPrimary = $product->price * $exchangeRate;
        
        // 2. Determine Configuration to Use
        $applyCommissions = false;
        $applyFreight = false;
        
        if ($customer) {
             // Customer specific logic could go here if Customer model had specific overrides
             // For now, we assume customer falls back to Seller Config unless specified
             // But based on user request "cliente... tiene condiciones establecidas", 
             // we should check if we need to implement customer-specific commission fields later.
             // For now, we use the Seller Config of the user assigned to this customer.
        }

        if ($sellerConfig) {
            $applyCommissions = true;
             // Should we apply freight? Sales.php logic implies it's optional/configurable
             // For a price list, we generally want to show the full price including overheads?
             // Let's assume Yes for "Foreign Sellers" logic.
            $applyFreight = true; 
        }

        $comm = 0;
        $freight = 0;
        $diff = 0;

        if ($applyCommissions && $sellerConfig) {
            
            // Commission
            $comm = ($basePriceInPrimary * $sellerConfig->commission_percent) / 100;
            
            // Exchange Diff
            $diff = ($basePriceInPrimary * $sellerConfig->exchange_diff_percent) / 100;

            // Freight (Smart Logic)
            if ($product->freight_type != 'none') {
                if ($product->freight_type == 'fixed') {
                    $freightUnit = $product->freight_value;
                } else {
                    $freightUnit = ($basePriceInPrimary * $product->freight_value) / 100;
                }
            } else {
                // General Seller Freight
                $freightUnit = ($basePriceInPrimary * $sellerConfig->freight_percent) / 100;
            }
            $freight = $freightUnit;
            
            $salePrice = $basePriceInPrimary + $comm + $freight + $diff;
        } else {
            $salePrice = $basePriceInPrimary;
        }

        // Tax Calculation (IVA)
        // We need to know if we should display with IVA or not. 
        // Typically price lists show final price.
        $iva = \App\Services\ConfigurationService::getVat() / 100;
        $priceWithTax = $salePrice * (1 + $iva);

        return [
            'base_price' => $basePriceInPrimary,
            'commission' => $comm,
            'freight' => $freight,
            'exchange_diff' => $diff,
            'net_price' => $salePrice, // Price before Tax
            'final_price' => $priceWithTax, // Price after Tax
            'tax_amount' => $priceWithTax - $salePrice
        ];
    }
}
