<?php

$dir = __DIR__ . '/../app/Livewire/Reports';
$files = glob($dir . '/*.php');

$replacements = [
    'Ã¡' => 'á',
    'Ã©' => 'é',
    'Ã­' => 'í',
    'Ã³' => 'ó',
    'Ãº' => 'ú',
    'Ã±' => 'ñ',
    'Ã‘' => 'Ñ',
    'Ã' => 'Á',
    'Ã‰' => 'É',
    'Ã' => 'Í',
    'Ã“' => 'Ó',
    'Ãš' => 'Ú',
    'Â¿' => '¿',
    'Â¡' => '¡',
    'Âº' => 'º',
    'Âª' => 'ª',
    'â€¢' => '•',
    'â€“' => '–',
    'â€”' => '—',
    'â€™' => '’',
    'â€œ' => '“',
    'â€' => '”',
    'â€¦' => '…',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    foreach ($replacements as $bad => $good) {
        $content = str_replace($bad, $good, $content);
    }
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Fixed encoding in: " . basename($file) . "\n";
    }
}

echo "Done fixing encoding.\n";
