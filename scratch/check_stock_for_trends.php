<?php
$dbh = new PDO('mysql:host=localhost;dbname=jspos-ventas-major-detal', 'root', '');

$customerId = 112;
$warehouseId = 1;

$stmt = $dbh->prepare('
    SELECT p.name, pw.stock_qty 
    FROM product_warehouse pw 
    JOIN products p ON p.id = pw.product_id 
    WHERE pw.warehouse_id = ? 
    AND p.id IN (
        SELECT product_id 
        FROM sale_details sd 
        JOIN sales s ON s.id = sd.sale_id 
        WHERE s.customer_id = ?
    )
');
$stmt->execute([$warehouseId, $customerId]);

echo "Stock status for customer's historical products in WH $warehouseId:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['name'] . ": " . $row['stock_qty'] . "\n";
}
