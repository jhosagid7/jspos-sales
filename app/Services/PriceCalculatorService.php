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
    public function calculate(Product $product, $config = null, $customer = null)
    {
        // 1. Get Base Price (Converted to Primary Currency)
        $primaryCurrency = CurrencyHelper::getPrimaryCurrency();
        $exchangeRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
        
        // Determine base price (using standard logic, assuming qty 1 for price list)
        $basePriceInPrimary = $product->price * $exchangeRate;
        
        // 2. Determine Configuration to Use
        $applyCommissions = false;
        
        $customerConfig = null;
        if ($config) {
            $customerConfig = $config;
        } elseif ($customer) {
            if (is_object($customer)) {
                $customerConfig = $customer->latestCustomerConfig;
            } elseif (is_array($customer) && isset($customer['id'])) {
                $customerModel = \App\Models\Customer::find($customer['id']);
                if ($customerModel) {
                    $customerConfig = $customerModel->latestCustomerConfig;
                }
            }
        }

        if ($customerConfig) {
            $applyCommissions = true;
        }

        $comm = 0;
        $freight = 0;
        $markup = 0;
        $diff = 0;

        if ($applyCommissions) {
            $decimals = \App\Services\ConfigurationService::getDecimalPlaces();
            $commissionPercent = floatval($customerConfig->commission_percent);
            $freightPercent = floatval($customerConfig->freight_percent);
            $exchangeDiffPercent = floatval($customerConfig->exchange_diff_percent);
            $baseMarkupPercent = floatval($customerConfig->base_markup_percent ?? 0);
            
            // Commission
            $comm = round(($basePriceInPrimary * $commissionPercent) / 100, $decimals);

            // Markup
            $markup = round(($basePriceInPrimary * $baseMarkupPercent) / 100, $decimals);
            
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
            $freight = round($freightUnit, $decimals);

            // Intermediate Price (Base + Markup + Comm + Freight)
            $intermediatePrice = round($basePriceInPrimary + $comm + $freight + $markup, $decimals);
            
            // Exchange Diff (Applied on Intermediate Price)
            $diff = round(($intermediatePrice * $exchangeDiffPercent) / 100, $decimals);
            
            $salePrice = round($intermediatePrice + $diff, $decimals);
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
            'base_markup' => $markup,
            'exchange_diff' => $diff,
            'net_price' => $salePrice, // Price before Tax
            'final_price' => $priceWithTax, // Price after Tax
            'tax_amount' => $priceWithTax - $salePrice
        ];
    }
}
