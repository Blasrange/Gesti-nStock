<?php
session_start();

ini_set('memory_limit', '1024M');

require_once '../app/db.php';
require_once '../app/Auth.php';
require '../vendor/autoload.php';

use App\Database;
use App\Auth;

if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$auth = new Auth($database);

$stmt = $database->pdo->prepare('SELECT nombre FROM clientes WHERE id = ?');
$stmt->execute([$_SESSION['cliente_id']]);
$cliente = $stmt->fetch();

$stmt = $database->pdo->prepare('SELECT nombre FROM ciudades WHERE id = ?');
$stmt->execute([$_SESSION['ciudad_id']]);
$ciudad = $stmt->fetch();
include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            margin: 0;
        }
        .filter-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }
        .filter-container input {
            width: 150px;
            text-align: center;
        }

        .btn-soporte {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color:rgb(12, 12, 12);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-family: Arial, sans-serif;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: background-color 0.3s;
        }

        .btn-soporte:hover {
            background-color: #084298;
        }

        .btn-soporte i {
            margin-right: 8px;
        }
    </style>



</head>
<body>

 

<!-- CONTENIDO PRINCIPAL -->
<div class="content">
    <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>Cliente seleccionado: <strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong></p>
    <p>Ciudad seleccionada: <strong><?php echo htmlspecialchars($ciudad['nombre']); ?></strong></p>
    
     <!-- Logo y mensaje central -->
     <div class="logo">
            <img src="assets/img/logo.png" alt="CCL">
        </div>

    <h4>Sistema de Reabastecimiento CCL</h4>
    <p>Versión 0.1.0</p>

    <!-- Asegúrate de tener Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

        <!-- Enlace flotante -->
        <a href="https://helpdesk.cclcloud.co/" target="_blank" class="btn-soporte">
        <i class="fas fa-headset"></i> Soporte
        </a>


</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
