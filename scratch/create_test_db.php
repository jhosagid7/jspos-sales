<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $dbh = new PDO("mysql:host=$host", $user, $pass);
    $dbh->exec("CREATE DATABASE IF NOT EXISTS jspos_test");
    echo "Database jspos_test created successfully or already exists.\n";
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
