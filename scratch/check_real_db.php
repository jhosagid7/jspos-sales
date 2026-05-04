<?php
$dbh = new PDO('mysql:host=localhost;dbname=jspos-ventas-major-detal', 'root', '');

echo "--- DATABASE STATUS: jspos-ventas-major-detal ---\n";

// Check Users
try {
    $stmt = $dbh->query('SELECT count(*) FROM users');
    echo "Users count: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Error checking users: " . $e->getMessage() . "\n";
}

// Check Products
try {
    $stmt = $dbh->query('SELECT count(*) FROM products');
    echo "Products count: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Error checking products: " . $e->getMessage() . "\n";
}

// Check Sales
try {
    $stmt = $dbh->query('SELECT count(*) FROM sales');
    echo "Sales count: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Error checking sales: " . $e->getMessage() . "\n";
}
