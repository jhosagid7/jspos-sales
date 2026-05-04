<?php
$dbh = new PDO('mysql:host=localhost;dbname=jspos-ventas-major-detal', 'root', '');

$customerName = 'TODO PLASTICO LA DOCE C.A';
$stmt = $dbh->prepare('SELECT id FROM customers WHERE name LIKE ? LIMIT 1');
$stmt->execute(['%' . $customerName . '%']);
$customerId = $stmt->fetchColumn();

if (!$customerId) {
    die("Customer not found\n");
}

echo "Customer ID: $customerId\n";

$stmt = $dbh->prepare('SELECT count(*) FROM sales WHERE customer_id = ?');
$stmt->execute([$customerId]);
echo "Sales count: " . $stmt->fetchColumn() . "\n";

$stmt = $dbh->prepare('
    SELECT p.name, count(*) as qty 
    FROM sale_details sd 
    JOIN sales s ON s.id = sd.sale_id 
    JOIN products p ON p.id = sd.product_id
    WHERE s.customer_id = ? 
    GROUP BY p.id
');
$stmt->execute([$customerId]);
echo "Purchased products:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['name'] . " (" . $row['qty'] . ")\n";
}
