<?php
// app/get_clients.php

require 'db.php';

$db = new Database();
$conn = $db->connect();

$query = "SELECT c.id, c.nombre, c.email, ci.nombre AS ciudad FROM clientes c LEFT JOIN ciudades ci ON c.ciudad_id = ci.id";
$stmt = $conn->prepare($query);
$stmt->execute();

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($clientes as $cliente) {
    echo "ID: " . $cliente['id'] . " - Nombre: " . $cliente['nombre'] . " - Email: " . $cliente['email'] . " - Ciudad: " . $cliente['ciudad'] . "<br>";
}
?>
