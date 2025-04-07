<?php
// public/authenticate.php
session_start();
require_once '../app/db.php'; // Asegúrate de que la ruta sea correcta
require_once '../app/Auth.php';
require '../vendor/autoload.php';

use App\Database;
use App\Auth;

// Verificar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar los datos del formulario
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header('Location: login.php?error=1');
        exit;
    }

    $database = new Database();
    $auth = new Auth($database);

    // Resultado del login (puede ser true, '0', o false)
    $loginResult = $auth->login($username, $password);

    if ($loginResult === true) {
        header('Location: select_client_city.php');
        exit;
    } elseif ($loginResult === '0') {
        // Usuario inactivo
        header('Location: login.php?error=0');
        exit;
    } else {
        // Credenciales inválidas
        header('Location: login.php?error=1');
        exit;
    }
} else {
    // Si no es POST, redirigir al login
    header('Location: login.php');
    exit;
}
