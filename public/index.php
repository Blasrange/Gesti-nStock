<?php
// public/index.php

session_start();

// Incluir el autoload de Composer
require_once '../app/db.php'; // Asegúrate de que la ruta sea correcta
require_once '../app/Auth.php';
require '../vendor/autoload.php';

use App\Database;
use App\Auth;

// Crear instancias de las clases Database y Auth
$database = new Database();
$auth = new Auth($database);

// Verificar si el usuario está autenticado
if ($auth->isAuthenticated()) {
    // Verificar si el cliente y la ciudad han sido seleccionados
    if (isset($_SESSION['cliente_id']) && isset($_SESSION['ciudad_id'])) {
        // Redirigir al dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        // Redirigir a la selección de cliente y ciudad
        header('Location: select_client_city.php');
        exit;
    }
} else {
    // Redirigir al login
    header('Location: login.php');
    exit;
}
?>