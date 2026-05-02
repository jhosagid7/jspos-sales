<?php
$basePath = 'C:\laragon\bin\mysql\\';
echo "Checking: $basePath\n";
if (is_dir($basePath)) {
    $dirs = glob($basePath . 'mysql-*', GLOB_ONLYDIR);
    if (!empty($dirs)) {
        rsort($dirs);
        echo "Found: " . $dirs[0] . '\bin' . "\n";
    } else {
        echo "No mysql dirs found in $basePath\n";
    }
} else {
    echo "Base path not found: $basePath\n";
}
