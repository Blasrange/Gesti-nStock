<?php

// public/HistorlaController.php

namespace App;

session_start();

require_once '../app/db.php';
require_once '../app/Historial.php';

use App\Database;
use App\Historial;

if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

$database = new Database();
$historialObj = new Historial($database);

// Verificar si se debe actualizar el historial
if (isset($_POST['actualizar'])) {
    // Obtener reportes y agruparlos para historial
    $historialGenerado = $historialObj->generateHistorial($cliente_id);

    // Verificar si hay errores o si se generó historial
    if (empty($historialGenerado)) {
        $_SESSION['error_message'] = "No se generó historial.";
    } else {
        $_SESSION['historial'] = $historialGenerado;
        unset($_SESSION['error_message']);
    }
}

// Cargar el historial desde la base de datos
$historial = $historialObj->getHistorial($cliente_id);

// Inicializar totales
$totalSku = count($historial);
$totalUnidades = 0;
$totalCajas = 0;

// Calcular totales de Unidades y Cajas
foreach ($historial as $entry) {
    $totalUnidades += $entry['unidades'];
    $totalCajas += $entry['cajas'];
}

// Calcular fecha actual y turno
$fecha = date('Y-m-d H:i:s');
$horaActual = date('H:i');

// Ajustar los intervalos para el cálculo del turno
if ($horaActual >= '06:00' && $horaActual < '14:00') {
    $turno = 1;
} elseif ($horaActual >= '14:00' && $horaActual < '22:00') {
    $turno = 2;
} else {
    $turno = 3;
}

$titulo = "Historial";
include '../templates/header.php';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial</title>
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
                <table id="tablahistorial" class="table table-striped table-hover dataTable display">
                <thead>
                <tr>
                    <th style="text-align: center">Fecha/Hora</th>
                    <th style="text-align: center">SKU</th>
                    <th style="text-align: center">Unidades</th>
                    <th style="text-align: center">Cajas</th>
                    <th style="text-align: center">Turno</th>
                </tr>
            </thead>
        <tbody>
            <?php foreach ($historial as $entry): ?>
            <tr>
                <td style="text-align: center"><?php echo htmlspecialchars($entry['fecha_hora']); ?></td>
                <td style="text-align: center"><?php echo htmlspecialchars($entry['sku']); ?></td>
                <td style="text-align: center"><?php echo htmlspecialchars($entry['unidades']); ?></td>
                <td style="text-align: center"><?php echo htmlspecialchars($entry['cajas']); ?></td>
                <td style="text-align: center"><?php echo htmlspecialchars($entry['turno']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script>
        let table = $("#tablahistorial").DataTable({
                "oLanguage": {
                    "sUrl": "assets/js/datatables_es.json"
                },
                responsive: true,
                pagingType: "full_numbers",
            });
    </script>
</body>
</html>
