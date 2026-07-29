<?php
$base = 175.2615;
$target = 254.4598;

// Loop over percentages in steps of 0.5%
// up to 80% for comm, freight, markup, and diff
for ($c = 0; $c <= 80; $c += 0.5) {
    for ($f = 0; $f <= 80; $f += 0.5) {
        for ($r = 0; $r <= 80; $r += 0.5) {
            for ($d = 0; $d <= 80; $d += 0.5) {
                // Test Standard Rounded
                $comm_r = round($base * $c / 100, 4);
                $markup_r = round($base * $r / 100, 4);
                $freight_r = round($base * $f / 100, 4);
                
                $interA_r = round($base + $comm_r + $freight_r + $markup_r, 4);
                $diffA_r = round($interA_r * $d / 100, 4);
                $totalA_r = round($interA_r + $diffA_r, 4);
                
                if (abs($totalA_r - $target) < 1e-4) {
                    echo "Match Standard: c=$c, r=$r, f=$f, d=$d -> $totalA_r\n";
                }
                
                // Test Broken Down Rounded
                $interB_r = round($base + $comm_r + $markup_r, 4);
                $diffB_r = round($interB_r * $d / 100, 4);
                $totalB_r = round($interB_r + $diffB_r + $freight_r, 4);
                
                if (abs($totalB_r - $target) < 1e-4) {
                    echo "Match Broken Down: c=$c, r=$r, f=$f, d=$d -> $totalB_r\n";
                }
            }
        }
    }
}
