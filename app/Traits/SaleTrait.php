<?php

namespace App\Traits;

use App\Services\PricingService;
use Illuminate\Support\Collection;
use App\Services\ConfigurationService;

trait SaleTrait
{
    public function applyTaxToCartItem($item, float $taxRate)
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        $item->tax = PricingService::applyTax($item->price, $taxRate, $decimals, true);
        return $item;
    }

    public function applyDiscountToCartItem($item, float $discountRate)
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        $item->discount = PricingService::applyTax($item->price, -$discountRate, $decimals, true);
        return $item;
    }

    public function calculateCartTotals(Collection $cart)
    {
        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($cart as $item) {
            $subtotal += $item->price;
            $totalTax += $item->tax;
            $totalDiscount += $item->discount;
        }

        $total = round($subtotal + $totalTax - $totalDiscount, ConfigurationService::getDecimalPlaces());

        return [
            'subtotal' => round($subtotal, ConfigurationService::getDecimalPlaces()),
            'totalTax' => round($totalTax, ConfigurationService::getDecimalPlaces()),
            'totalDiscount' => round($totalDiscount, ConfigurationService::getDecimalPlaces()),
            'total' => $total,
        ];
    }
}
