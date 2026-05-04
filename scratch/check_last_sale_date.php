<?php
$dbh = new PDO('mysql:host=localhost;dbname=jspos-ventas-major-detal', 'root', '');

$customerId = 112;

$stmt = $dbh->prepare('SELECT MAX(created_at) FROM sales WHERE customer_id = ?');
$stmt->execute([$customerId]);
$lastSale = $stmt->fetchColumn();

echo "Last sale for customer $customerId: $lastSale\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";
echo "90 days ago: " . date('Y-m-d H:i:s', strtotime('-90 days')) . "\n";

if ($lastSale > date('Y-m-d H:i:s', strtotime('-90 days'))) {
    echo "Last sale is WITHIN the 90-day window.\n";
} else {
    echo "Last sale is OUTSIDE the 90-day window.\n";
}
