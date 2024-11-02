<?php
// public/logout.php // Permite a los usuarios cerrar su sesión de manera segura.
session_start();

require_once '../app/db.php'; // Asegúrate de que la ruta sea correcta
require_once '../app/Auth.php';
require '../vendor/autoload.php'; // Autoload de Composer
use App\Database;
use App\Auth;

$database = new Database();
$auth = new Auth($database);

// Cerrar sesión
$auth->logout();

// Redirigir al login
header('Location: login.php');
exit;
?>
