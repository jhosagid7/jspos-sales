<?php
$dbh = new PDO('mysql:host=localhost;dbname=test-de-db', 'root', '');

echo "--- DATABASE STATUS: test-de-db ---\n";

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

// List all tables
echo "\nTables found:\n";
$stmt = $dbh->query('SHOW TABLES');
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo "- " . $row[0] . "\n";
}
