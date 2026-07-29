<?php
$base = 175.2615;
$target = 254.4598;

// We will loop through possible integer percent values for c, r, f, d
// because these are the percentages stored in DB or configs (e.g. 4%, 6%, 8%, 30%, 35%, etc.)
$percents = [0, 2, 4, 5, 6, 8, 10, 15, 20, 25, 30, 35, 40, 50, 70, 80];

foreach ($percents as $c) {
    foreach ($percents as $r) {
        foreach ($percents as $f) {
            foreach ($percents as $d) {
                // Test 1: Freight NOT broken down (Standard) - unrounded
                $comm = $base * $c / 100;
                $markup = $base * $r / 100;
                $freight = $base * $f / 100;
                
                $interA = $base + $comm + $freight + $markup;
                $diffA = $interA * $d / 100;
                $totalA = $interA + $diffA;
                
                // Test 2: Freight broken down - unrounded
                $interB = $base + $comm + $markup;
                $diffB = $interB * $d / 100;
                $totalB = $interB + $diffB + $freight;
                
                // Test 3: Standard rounded
                $comm_r = round($base * $c / 100, 4);
                $markup_r = round($base * $r / 100, 4);
                $freight_r = round($base * $f / 100, 4);
                
                $interA_r = round($base + $comm_r + $freight_r + $markup_r, 4);
                $diffA_r = round($interA_r * $d / 100, 4);
                $totalA_r = round($interA_r + $diffA_r, 4);
                
                // Test 4: Broken down rounded
                $interB_r = round($base + $comm_r + $markup_r, 4);
                $diffB_r = round($interB_r * $d / 100, 4);
                $totalB_r = round($interB_r + $diffB_r + $freight_r, 4);
                
                if (abs($totalA_r - $target) < 0.005) {
                    echo "Match Standard Rounded: c=$c, r=$r, f=$f, d=$d -> $totalA_r\n";
                }
                if (abs($totalB_r - $target) < 0.005) {
                    echo "Match Broken Down Rounded: c=$c, r=$r, f=$f, d=$d -> $totalB_r\n";
                }
                if (abs($totalA - $target) < 0.005) {
                    echo "Match Standard Unrounded: c=$c, r=$r, f=$f, d=$d -> $totalA\n";
                }
                if (abs($totalB - $target) < 0.005) {
                    echo "Match Broken Down Unrounded: c=$c, r=$r, f=$f, d=$d -> $totalB\n";
                }
            }
        }
    }
}
