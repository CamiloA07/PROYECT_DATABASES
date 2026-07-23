<?php
$host   = 'localhost';
$db     = 'empanadas_db';
$user   = 'root';
$pass   = 'Camilo12345.';           // cambia si tu root tiene contraseña
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Conexión fallida: ' . $e->getMessage()]));
}
