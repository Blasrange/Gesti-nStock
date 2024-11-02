<?php
// public/get_cities.php
session_start();

require_once '../app/db.php'; // Asegúrate de que la ruta sea correcta
require '../vendor/autoload.php'; // Autoload de Composer
use App\Database;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (isset($_GET['cliente_id'])) {
    $cliente_id = intval($_GET['cliente_id']);

    $database = new Database();

    // Obtener la ciudad asociada al cliente
    $stmt = $database->pdo->prepare('
        SELECT c.id, c.nombre 
        FROM ciudades c
        JOIN clientes cl ON c.id = cl.ciudad_id
        WHERE cl.id = ?
    ');
    $stmt->execute([$cliente_id]);
    $ciudad = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($ciudad) {
        echo json_encode($ciudad);
    } else {
        echo json_encode([]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Cliente no válido']);
}
?>