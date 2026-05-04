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
        
        $customerConfig = null;
        if ($customer) {
            if (is_object($customer)) {
                $customerConfig = $customer->latestCustomerConfig;
            } elseif (is_array($customer) && isset($customer['id'])) {
                $customerModel = \App\Models\Customer::find($customer['id']);
                if ($customerModel) {
                    $customerConfig = $customerModel->latestCustomerConfig;
                }
            }
        }

        if ($customerConfig || $sellerConfig) {
            $applyCommissions = true;
        }

        $comm = 0;
        $freight = 0;
        $diff = 0;

        if ($applyCommissions) {
            
            // Priority 1: Customer Config
            $commissionPercent = $customerConfig && $customerConfig->commission_percent > 0 ? $customerConfig->commission_percent : ($sellerConfig ? $sellerConfig->commission_percent : 0);
            $freightPercent = $customerConfig && $customerConfig->freight_percent > 0 ? $customerConfig->freight_percent : ($sellerConfig ? $sellerConfig->freight_percent : 0);
            $exchangeDiffPercent = $customerConfig && $customerConfig->exchange_diff_percent > 0 ? $customerConfig->exchange_diff_percent : ($sellerConfig ? $sellerConfig->exchange_diff_percent : 0);
            
            // Commission
            $comm = ($basePriceInPrimary * $commissionPercent) / 100;
            
            // Exchange Diff
            $diff = ($basePriceInPrimary * $exchangeDiffPercent) / 100;

            // Freight (Smart Logic)
            if ($product->freight_type != 'none') {
                if ($product->freight_type == 'fixed') {
                    $freightUnit = $product->freight_value;
                } else {
                    $freightUnit = ($basePriceInPrimary * $product->freight_value) / 100;
                }
            } else {
                // General Freight
                $freightUnit = ($basePriceInPrimary * $freightPercent) / 100;
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
