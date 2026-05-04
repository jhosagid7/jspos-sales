<?php

namespace App\Services;

use Exception;
use App\Services\PricingService;
use App\Services\ConfigurationService;

class PreciCartItemService
{
    public $title;
    public $code;
    public $reference;
    public $description = false;
    public $units;
    public $quantity;
    public $price_per_unit;
    public $sub_total_price;
    public $discount;
    public $discount_percentage;
    public $tax;
    public $tax_percentage;

    public function __construct()
    {
        $this->quantity = 1.0;
        $this->discount = 0.0;
        $this->tax = 0.0;
    }

    public static function make($title)
    {
        return (new self())->title($title);
    }

    public function title(string $title)
    {
        $this->title = $title;
        return $this;
    }

    public function code(string $code)
    {
        $this->code = $code;
        return $this;
    }

    public function reference(string $reference)
    {
        $this->reference = $reference;
        return $this;
    }

    public function description(string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function units(string $units)
    {
        $this->units = $units;
        return $this;
    }

    public function quantity(float $quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function pricePerUnit(float $price)
    {
        $this->price_per_unit = $price;
        return $this;
    }

    public function subTotalPrice(float $price)
    {
        $this->sub_total_price = $price;
        return $this;
    }

    public function discount(float $amount, bool $byPercent = false)
    {
        if ($this->hasDiscount()) {
            throw new Exception('InvoiceItem: unable to set discount twice.');
        }

        $this->discount = $amount;
        !$byPercent ?: $this->discount_percentage = $amount;
        return $this;
    }

    public function tax(float $amount, bool $byPercent = false)
    {
        if ($this->hasTax()) {
            throw new Exception('InvoiceItem: unable to set tax twice.');
        }

        $this->tax = $amount;
        !$byPercent ?: $this->tax_percentage = $amount;
        return $this;
    }

    public function discountByPercent(float $amount)
    {
        $this->discount($amount, true);
        return $this;
    }

    public function taxByPercent(float $amount)
    {
        $this->tax($amount, true);
        return $this;
    }

    public function hasUnits()
    {
        return !is_null($this->units);
    }

    public function hasCode()
    {
        return !is_null($this->code);
    }

    public function hasReference()
    {
        return !is_null($this->reference);
    }

    public function hasDiscount()
    {
        return $this->discount !== 0.0;
    }

    public function hasTax()
    {
        return $this->tax !== 0.0;
    }

    public function calculate()
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        $vat = ConfigurationService::getVat();

        if (!is_null($this->sub_total_price)) {
            return $this;
        }

        $this->sub_total_price = PricingService::applyQuantity($this->price_per_unit, $this->quantity, $decimals);
        $this->calculateDiscount($decimals);
        $this->calculateTax($vat, $decimals);

        return $this;
    }

    public function calculateDiscount(int $decimals): void
    {
        $subTotal = $this->sub_total_price;

        if ($this->discount_percentage) {
            $newSubTotal = PricingService::applyDiscount($subTotal, $this->discount_percentage, $decimals, true);
        } else {
            $newSubTotal = PricingService::applyDiscount($subTotal, $this->discount, $decimals);
        }

        $this->sub_total_price = $newSubTotal;
        $this->discount = $subTotal - $newSubTotal;
    }

    public function calculateTax(float $vat, int $decimals): void
    {
        $subTotal = $this->sub_total_price;

        if ($this->tax_percentage) {
            $newSubTotal = PricingService::applyTax($subTotal, $this->tax_percentage, $decimals, true);
        } else {
            $newSubTotal = PricingService::applyTax($subTotal, $vat, $decimals);
        }

        $this->sub_total_price = $newSubTotal;
        $this->tax = $newSubTotal - $subTotal;
    }

    public function validate()
    {
        if (is_null($this->title)) {
            throw new Exception('InvoiceItem: title not defined.');
        }

        if (is_null($this->price_per_unit)) {
            throw new Exception('InvoiceItem: price_per_unit not defined.');
        }
    }
}
