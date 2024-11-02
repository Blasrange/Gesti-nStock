<?php
// templates/header.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'Sistema de Reabastecimiento'; ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <!-- Puedes agregar más enlaces a CSS o scripts aquí -->
</head>
<body>
    <div class="header">
        <div>
            <h1>Sistema de Reabastecimiento</h1>
        </div>
        <div class="nav">
            <a href="inventarios.php">Inventarios</a>
            <a href="maestra_materiales.php">Maestra de Materiales</a>
            <a href="reabastecimientos.php">Reabastecimientos</a>
            <a href="reportes.php">Reportes</a>
            <a href="historial.php">Historial</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
    <div class="content">
