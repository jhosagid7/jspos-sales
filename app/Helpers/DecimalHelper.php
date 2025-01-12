<?php

namespace App\Helpers;

class DecimalHelper
{
    public static function add($leftOperand, $rightOperand, $scale = 2)
    {
        return bcadd($leftOperand, $rightOperand, $scale);
    }

    public static function subtract($leftOperand, $rightOperand, $scale = 2)
    {
        return bcsub($leftOperand, $rightOperand, $scale);
    }

    public static function multiply($leftOperand, $rightOperand, $scale = 2)
    {
        return bcmul($leftOperand, $rightOperand, $scale);
    }

    public static function divide($leftOperand, $rightOperand, $scale = 2)
    {
        return bcdiv($leftOperand, $rightOperand, $scale);
    }

    public static function round($value, $scale = 2)
    {
        return number_format((float)$value, $scale, '.', '');
    }
}
