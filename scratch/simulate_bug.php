<?php
// Let's simulate the PHP reference leak bug

$digitalPayments = [
    'unified' => [
        'bank' => [
            'MERCANTIL (*075370)' => [
                'VED' => ['mercantil_item']
            ],
            'BANCO PROVINCIAL (*119059)' => [
                'VED' => ['provincial_item']
            ],
            'Banco de Venezuela' => [
                'VED' => ['venezuela_item']
            ]
        ]
    ]
];

echo "Before sorting loop:\n";
print_r($digitalPayments['unified']['bank']);

// 1. Run the sorting loop with references, exactly like ReportController.php
foreach($digitalPayments['unified']['bank'] as $bank => &$currenciesInBank) {
    foreach($currenciesInBank as $c => &$items) {
        // simulate sortItems($items)
    }
}

echo "\nAfter sorting loop (but before Blade loop):\n";
print_r($digitalPayments['unified']['bank']);

// 2. Run the Blade loop (without & references)
echo "\n--- Simulating Blade Loop ---\n";
foreach($digitalPayments['unified']['bank'] as $bank => $currenciesInBank) {
    echo "Rendering bank: '$bank'\n";
    print_r($currenciesInBank);
}

echo "\nAfter Blade loop - Let's see the final array state:\n";
print_r($digitalPayments['unified']['bank']);
