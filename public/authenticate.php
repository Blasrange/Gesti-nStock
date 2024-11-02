<?php
// public/authenticate.php // Este script procesará las credenciales ingresadas y validará al usuario.
session_start();
require_once '../app/db.php'; // Asegúrate de que la ruta sea correcta
require_once '../app/Auth.php';
require '../vendor/autoload.php'; // Autoload de Composer
use App\Database;
use App\Auth;

// Verificar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar los datos del formulario
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        // Redirigir de vuelta al login con error
        header('Location: login.php?error=1');
        exit;
    }

    // Crear instancias de Database y Auth
    $database = new Database();
    $auth = new Auth($database);

    // Intentar iniciar sesión
    if ($auth->login($username, $password)) {
        // Redirigir a la selección de cliente y ciudad
        header('Location: select_client_city.php');
        exit;
    } else {
        // Credenciales inválidas, redirigir con error
        header('Location: login.php?error=1');
        exit;
    }
} else {
    // Acceso no permitido, redirigir al login
    header('Location: login.php');
    exit;
}
?>