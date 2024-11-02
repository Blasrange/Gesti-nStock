<?php
// public/eliminar_inventario.php

session_start();

// Habilitar la visualización de errores (solo para desarrollo; eliminar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php';
require_once '../app/inventarios.php';

use App\Database;
use App\inventarios;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método de solicitud no permitido.');
}

// Verificar el token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Token CSRF inválido.');
}

// Verificar que se ha proporcionado un ID válido
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die('ID de inventario inválido.');
}

$id = (int)$_POST['id'];

$database = new Database();
$cliente_id = $_SESSION['cliente_id']; // Usar el cliente_id de la sesión
$inventariosObj = new inventarios($database, $cliente_id); // Pasar el cliente_id al constructor

// Opcional: Verificar que el inventario pertenece al cliente actual
$inventario = $inventariosObj->getItem($id);
if (!$inventario || $inventario['cliente_id'] != $cliente_id) {
    die('Inventario no encontrado o acceso no autorizado.');
}

// Intentar eliminar el inventario
if ($inventariosObj->deleteItem($id)) {
    // Redirigir con un mensaje de éxito
    header('Location: inventarioscontrolle.php?mensaje=eliminado');
    exit;
} else {
    // Redirigir con un mensaje de error
    header('Location: inventarioscontrolle.php?mensaje=error');
    exit;
}
?>