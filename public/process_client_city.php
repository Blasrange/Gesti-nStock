<?php
// public/process_client_city.php
session_start();

require_once '../app/db.php'; // Asegúrate de que la ruta sea correcta
require_once '../app/Auth.php';
require '../vendor/autoload.php'; // Autoload de Composer
use App\Database;
use App\Auth;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica si los datos de cliente y ciudad están presentes
    if (isset($_POST['cliente_id']) && !empty($_POST['cliente_id']) &&
        isset($_POST['ciudad_id']) && !empty($_POST['ciudad_id'])) {

        $cliente_id = intval($_POST['cliente_id']);
        $ciudad_id = intval($_POST['ciudad_id']);
        $user_id = $_SESSION['user_id'];

        $database = new Database();

        // Verificar que la ciudad está asociada con el cliente y que el usuario tiene acceso a ese cliente
        $stmt = $database->pdo->prepare('
            SELECT COUNT(*) 
            FROM usuario_clientes uc
            JOIN clientes cl ON uc.cliente_id = cl.id
            WHERE uc.user_id = ? AND cl.id = ? AND cl.ciudad_id = ?
        ');
        $stmt->execute([$user_id, $cliente_id, $ciudad_id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Guardar el cliente y la ciudad en la sesión
            $_SESSION['cliente_id'] = $cliente_id;
            $_SESSION['ciudad_id'] = $ciudad_id;

            // Redirigir al dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            // Acceso no válido
            echo 'Cliente o Ciudad no válidos o no tienes acceso.';
            exit;
        }
    } else {
        // Datos faltantes
        echo 'Debe seleccionar un cliente y una ciudad.';
        exit;
    }
} else {
    // Acceso no permitido
    header('Location: select_client_city.php');
    exit;
}
?>
