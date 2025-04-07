<?php
require '../app/db.php'; // Ajusta la ruta según la ubicación de tu archivo

use App\Database;

try {
    $database = new Database();
    echo "Conexión exitosa a la base de datos.";
} catch (Exception $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
