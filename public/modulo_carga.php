<?php
session_start();

// Verificar si el usuario tiene sesión activa y permisos
require_once '../app/db.php';
require_once '../vendor/autoload.php';

use App\Database;

$database = new Database();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Carga</title>
    <link rel="stylesheet" href="../css/styles.css"> <!-- Asegúrate de incluir tu archivo CSS -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            text-align: center;
        }

        .form-upload {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            text-align: center;
        }

        .form-upload form {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-file-upload {
            display: inline-block;
            padding: 10px 15px;
            cursor: pointer;
            background-color: #28a745;
            color: white;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        /* Efecto hover para cambiar el color al pasar el mouse */
        .custom-file-upload:hover {
            background-color: #218838; /* Cambia a un tono más oscuro de azul */
        }

        .file-box {
            display: inline-block;
            padding: 10px;
            font-weight: bold;
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 5px;
            margin: 0 10px;
        }

        .upload-button {
            padding: 12px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .upload-button:hover {
            background-color: #218838;
        }

        .error {
            color: red;
            text-align: center;
        }

        .success {
            color: green;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: auto;
        }
    </style>
</head>
<body>
    
<div class="header">
    <a href="dashboard.php" style="text-decoration: none; color: black;">
        <h1>Módulo de Carga de Archivos</h1>
    </a>

    <!-- Formulario para cargar inventario -->
    <div class="form-upload">
        <h2>Cargar Inventario</h2>
        <form action="cargar_inventario.php" method="post" enctype="multipart/form-data">
            <label for="file-upload-inventario" class="custom-file-upload">
                Seleccionar archivo
            </label>
            <span id="file-selected-inventario" class="file-box">Ningún archivo seleccionado</span>
            <input type="file" name="file" id="file-upload-inventario" accept=".xlsx, .xls" required style="display: none;">
            <button type="submit" class="upload-button">Cargar Inventario</button>
        </form>
    </div>

    <!-- Formulario para cargar maestra de materiales -->
    <div class="form-upload">
        <h2>Cargar Maestra de Materiales</h2>
        <form action="cargar_Materiales.php" method="post" enctype="multipart/form-data">
            <label for="file-upload-materiales" class="custom-file-upload">
                Seleccionar archivo
            </label>
            <span id="file-selected-materiales" class="file-box">Ningún archivo seleccionado</span>
            <input type="file" name="file" id="file-upload-materiales" accept=".xlsx, .xls" required style="display: none;">
            <button type="submit" class="upload-button">Cargar Materiales</button>
        </form>
    </div>

    <!-- Mostrar mensajes de error o éxito -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <p class="error"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <p class="success"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
</div>

<script>
    // JavaScript para mostrar el nombre del archivo seleccionado para cada formulario
    document.getElementById('file-upload-inventario').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : "Ningún archivo seleccionado";
        document.getElementById('file-selected-inventario').textContent = fileName;
    });

    document.getElementById('file-upload-materiales').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : "Ningún archivo seleccionado";
        document.getElementById('file-selected-materiales').textContent = fileName;
    });
</script>
</body>
</html>
