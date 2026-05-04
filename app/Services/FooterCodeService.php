<?php

namespace App\Services;

class FooterCodeService
{
    public static function generate(
        $sellerName,
        $customerName,
        $freightPercent,
        $commPercent,
        $diffPercent,
        $invoicePlaceholder, // 'FC0000' or 'FC123'
        $estimatedPriceBase = 'ES0C00',
        $usdDiscount = 0,
        $prontoPagoRules = [], // Array of objects or arrays
        $moraPercent = 0,
        $creditDays = 0,
        $operatorName = ''
    ) {
        // 1. Initials
        $initials = self::getInitials($sellerName) . self::getInitials($customerName);
        if (empty($initials)) $initials = 'XX';

        // 2. Freight
        $codeF = 'F' . intval($freightPercent);

        // 3. Commission
        $codeC = 'C' . intval($commPercent);

        // 4. Diff
        $codeDF = 'DF' . intval($diffPercent);

        // 5. Invoice
        $codeFC = $invoicePlaceholder;

        // 6. Estimated
        $codeES = $estimatedPriceBase;

        // 7. PD
        $codePD = 'PD' . intval($usdDiscount);

        // 8. PP
        $ppParts = [];
        if (!empty($prontoPagoRules)) {
            foreach ($prontoPagoRules as $rule) {
                $ppPercent = 0;
                $ppDays = 0;
                
                if (is_object($rule)) {
                    $ppPercent = $rule->discount_percentage ?? 0;
                    $ppDays = $rule->days_to ?? 0;
                } elseif (is_array($rule)) {
                    $ppPercent = $rule['percent'] ?? 0;
                    $ppDays = $rule['days'] ?? 0;
                }
                
                if ($ppPercent > 0) {
                     $ppParts[] = 'PP' . intval($ppPercent) . 'D' . intval($ppDays);
                }
            }
        }
        $codePP = implode('-', $ppParts);
        if (!empty($codePP)) $codePP = '-' . $codePP;

        // 9. IM
        $codeIM = '-IM' . intval($moraPercent);

        // 10. V
        $codeV = 'V' . intval($creditDays) . 'D';

        // 11. Operator
        $operatorInitials = self::getInitials($operatorName);

        // Combine
        $part1 = $initials . $codeF;
        $part2 = $codeC . $codeDF;
        $part3 = $codeFC;
        $part4 = $codeES;
        $part5 = $codePD . $codePP . $codeIM;
        $part6 = $codeV . $operatorInitials;

        return "$part1-$part2-$part3-$part4-$part5-$part6";
    }

    public static function getInitials($name)
    {
        $name = trim($name ?? '');
        if (empty($name)) return '';
        
        $parts = explode(' ', $name);
        $initials = '';

        if (count($parts) >= 2) {
            // First letter of first word + First letter of second word
            $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        } elseif (count($parts) == 1) {
            // Fallback: First 2 letters of the single word
            $initials = strtoupper(substr($parts[0], 0, 2));
        }
        
        return $initials;
    }
}
