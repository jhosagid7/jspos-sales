<?php
$base = 175.2615;
$target = 254.4598;

$c_options = [0.0, 4.0, 8.0];
$r_options = [0.0, 4.0];
$f_options = [0.0, 6.0];
$d_options = [0.0, 30.0, 35.0];

// Let's test combinations using unrounded and rounded values
foreach ($c_options as $c) {
    foreach ($r_options as $r) {
        foreach ($f_options as $f) {
            foreach ($d_options as $d) {
                // Scenario 1: standard
                // intermediate = base * (1 + c + f + r)
                // total = intermediate * (1 + d)
                $val1 = ($base * (1 + $c/100 + $f/100 + $r/100)) * (1 + $d/100);
                
                // Scenario 2: broken down freight
                // intermediate = base * (1 + c + r)
                // total = intermediate * (1 + d) + base * f
                $val2 = ($base * (1 + $c/100 + $r/100)) * (1 + $d/100) + ($base * $f/100);
                
                // Scenario 3: What if commission or recargo is NOT applied to differential?
                // intermediate = base * (1 + r)
                // total = (intermediate * (1 + d) + base * c + base * f)
                $val3 = ($base * (1 + $r/100)) * (1 + $d/100) + ($base * $c/100) + ($base * $f/100);
                
                // Scenario 4: What if differential is applied only on base + recargo?
                $val4 = ($base * (1 + $r/100)) * (1 + $d/100) + ($base * $c/100) + ($base * $f/100);
                
                // Scenario 5: What if no differential is applied to recargo?
                // intermediate = base + comm
                // total = intermediate * (1 + d) + freight + recargo
                $val5 = ($base * (1 + $c/100)) * (1 + $d/100) + ($base * $f/100) + ($base * $r/100);
                
                // Scenario 6: Let's test if some components are rounded
                $comm_r = round($base * $c / 100, 4);
                $rec_r = round($base * $r / 100, 4);
                $freight_r = round($base * $f / 100, 4);
                
                $interA = round($base + $comm_r + $freight_r + $rec_r, 4);
                $diffA = round($interA * $d / 100, 4);
                $totalA = round($interA + $diffA, 4);
                
                $interB = round($base + $comm_r + $rec_r, 4);
                $diffB = round($interB * $d / 100, 4);
                $totalB = round($interB + $diffB + $freight_r, 4);
                
                $totalC = round($interB + $diffB, 4) + $freight_r;
                
                if (abs($val1 - $target) < 0.01) echo "Val1 match: c=$c, r=$r, f=$f, d=$d -> $val1\n";
                if (abs($val2 - $target) < 0.01) echo "Val2 match: c=$c, r=$r, f=$f, d=$d -> $val2\n";
                if (abs($val3 - $target) < 0.01) echo "Val3 match: c=$c, r=$r, f=$f, d=$d -> $val3\n";
                if (abs($val5 - $target) < 0.01) echo "Val5 match: c=$c, r=$r, f=$f, d=$d -> $val5\n";
                
                if (abs($totalA - $target) < 0.01) echo "TotalA match: c=$c, r=$r, f=$f, d=$d -> $totalA\n";
                if (abs($totalB - $target) < 0.01) echo "TotalB match: c=$c, r=$r, f=$f, d=$d -> $totalB\n";
                if (abs($totalC - $target) < 0.01) echo "TotalC match: c=$c, r=$r, f=$f, d=$d -> $totalC\n";
            }
        }
    }
}
