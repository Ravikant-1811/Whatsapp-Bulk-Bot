<?php
/**
 * Database connection (PDO)
 * XAMPP + phpMyAdmin
 */

$host = 'localhost';
$db   = 'whatsapp_campaign';      // 👈 your database name
$user = 'root';          // default XAMPP user
$pass = '';              // default XAMPP password (empty)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
