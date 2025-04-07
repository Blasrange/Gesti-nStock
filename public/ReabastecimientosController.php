<?php
// public/ReabastecimientosController.php

namespace App;

session_start();

// Habilitar la visualización de errores (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php'; // Cargar la clase Database
require_once '../app/Replenishment.php'; // Cargar la lógica de reabastecimientos

use App\Database;
use App\Replenishment;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'])) {
    header('Location: login.php');
    exit;
}

// Inicializar variables
$cliente_id = $_SESSION['cliente_id'];

// Crear conexión a la base de datos
$database = new Database();

// Crear instancia de reabastecimientos
$reabastecimientosObj = new Replenishment($database);

// Verificar si el botón "Actualizar" fue presionado
if (isset($_POST['actualizar'])) {
    // Obtener los reabastecimientos generados
    $nuevosReabastecimientos = $reabastecimientosObj->generateReplenishments($cliente_id);

    // Verificar si se generaron reabastecimientos
    if (empty($nuevosReabastecimientos)) {
        $_SESSION['error_message'] = "No se generaron reabastecimientos.";
        // Eliminar los reabastecimientos de la sesión si no hay nuevos
        unset($_SESSION['reabastecimientos']);
    } else {
        unset($_SESSION['error_message']); // Limpiar mensajes de error si la generación fue exitosa
        $_SESSION['reabastecimientos'] = $nuevosReabastecimientos; // Almacenar en sesión
    }
}

$titulo = "Reabastecimientos";
$seccion = "Administración";
// Cargar los reabastecimientos desde la sesión si existen
$reabastecimientos = $_SESSION['reabastecimientos'] ?? [];
include '../templates/header.php'; 

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reabastecimientos</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">
    <style>
        .btn-back {
            display: inline-block;
            background-color: #1e3765            ;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-back:hover {
            background-color: #1e3765;
            transform: scale(1.05);
        }

        .form-upload {
            margin: 0px 0; /* Espaciado */
            border: 1px solid #ccc; /* Borde */
            padding: 1px; /* Espaciado interno */
            border-radius: 5px; /* Bordes redondeados */
            background-color: #f9f9f9; /* Fondo */
        }

        .total {
            font-weight: bold;
            margin-top: 20px;
        }

        /* Estilo para alinear el título y el formulario */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white; /* Fondo blanco */
        }

        .header h1 {
            margin: 0;
        }

        /*.error-message {
            color: red;
            font-weight: bold;
            margin: 10px 0;
            padding: 10px;
            border: 2px solid red;
            background-color: #ffe6e6; /* Fondo claro para destacar el error */
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 98%;
            background-color: white;
            z-index: 1000; /* Asegúrate de que esté sobre otros elementos */
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Añadir sombra para destacar */
        }

        .total {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            text-align: center;
            padding: 3px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000; /* Asegúrate de que esté sobre otros elementos */
        }

        .search-container {
            text-align: right;
        }
        .search-input {
            padding: 8px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 200px;
            margin-left: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            position: relative;
            top: 0;
            left: 0;
            width: 98%;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }       

       
    </style>
</head>
<body>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message">
            <?php echo $_SESSION['error_message']; ?>
        </div>
    <?php endif; ?>

    <div style="margin-left: 20px; margin-right: 20px">        
        <div class="table-responsive">
        <div class="search-container">
            <form method="POST" style="display: inline;">
                <button type="submit" name="actualizar" class="btn btn-dark btn-small">Actualizar</button>
            </form>
        </div>
            <table id="tablareabastecimientos" class="table table-striped table-hover dataTable display">
                <thead>
                    <tr>
                        <th style="text-align: center">SKU</th>
                        <th style="text-align: center">Descripción</th>
                        <th style="text-align: center">LPN Inventario</th>
                        <th style="text-align: center">Localización Origen</th>
                        <th style="text-align: center">Unidades a Reabastecer</th>
                        <th style="text-align: center">Embalaje</th>
                        <th style="text-align: center">Lote</th>
                        <th style="text-align: center">Fecha Vencimiento</th>
                        <th style="text-align: center">LPN Max Min</th>
                        <th style="text-align: center">Localización Destino</th>
                        <th style="text-align: center">Estado</th>                
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reabastecimientos)): ?>
                        <?php foreach ($reabastecimientos as $reabastecimiento): ?>
                            <tr>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['sku']); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['descripcion'] ?? ''); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['lpn_inventario'] ?? ''); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['localizacion_origen'] ?? ''); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['unidades_reabastecer']); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['embalaje'] ?? ''); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['lote']); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['fecha_vencimiento'] ?? ''); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['lpn_max_min'] ?? ''); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['localizacion_destino'] ?? ''); ?></td>
                                <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['estado']); ?></td>                     
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function filterReports() {
        const searchInput = document.getElementById('search-input').value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(searchInput) ? '' : 'none';
        });
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script>
        let table = $("#tablareabastecimientos").DataTable({
                "oLanguage": {
                    "sUrl": "assets/js/datatables_es.json"
                },
                responsive: true,
                pagingType: "full_numbers",
            });
    </script>
</body>
</html>