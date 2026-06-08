<?php
// Let's test reverse calculation exactness.
$mismatches = 0;
$totalTests = 0;

for ($totalCents = 1; $totalCents <= 100000; $totalCents += 5) { // test up to $1000.00, step 5 cents
    $total = $totalCents / 100;
    
    // Test percentages from 1% to 50%
    foreach ([5, 10, 13, 16, 20, 25, 30, 43, 50] as $percent) {
        $totalTests++;
        
        $rate = 1 + ($percent / 100);
        $base = round($total / $rate, 2);
        
        $comm = round($base * ($percent / 100), 2);
        
        $sum = round($base + $comm, 2);
        
        if (abs($sum - $total) > 0.001) {
            $mismatches++;
            if ($mismatches <= 10) {
                echo "Mismatch found: Total={$total}, Percent={$percent}% => Base={$base}, Comm={$comm}, Sum={$sum} (Diff: " . ($sum - $total) . ")" . PHP_EOL;
            }
        }
    }
}

echo "Total tests: {$totalTests}" . PHP_EOL;
echo "Total mismatches: {$mismatches}" . PHP_EOL;
