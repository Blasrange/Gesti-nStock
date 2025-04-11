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
$seccion = "Administración";
include '../templates/header.php';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --light-gray: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .report-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin: 20px auto;
            max-width: 98%;
        }
        
        .filter-section {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
        }
        
        .filter-title {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .movement-type-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .movement-type-btn {
            border: 2px solid #dee2e6;
            background: white;
            color: var(--secondary-color);
            border-radius: 20px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .movement-type-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .movement-type-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .report-section {
            margin-top: 25px;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .report-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
        }
        
        .report-badge {
            background-color: var(--success-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        table.dataTable {
            border-collapse: collapse !important;
            margin-top: 10px !important;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        
        .badge-movement {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .badge-entry {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-exit {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-transfer {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        @media (max-width: 768px) {
            .filter-section .col-md-3 {
                margin-bottom: 15px;
            }
            
            .movement-type-selector {
                flex-direction: column;
            }
        }

        /* Ajustes generales para la tabla */
        #tablahistorial {
            width: 200% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 4px !important;  /* Espacio entre filas */
        }
        
        #tablahistorial th, 
        #tablahistorial td {
            padding: 10px 15px !important;
            vertical-align: middle !important;
        }
        
        /* Ajustes generales para la tabla */
        #tablahistorial {
            width: 100% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;  /* Espacio entre filas */
        }
        
        #tablahistorial th, 
        #tablahistorial td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            vertical-align: middle !important;
        }
        
        /* Columnas específicas */
        #tablahistorial th:nth-child(2),  /* Descripción */
        #tablahistorial td:nth-child(2) {
            min-width: 255px !important;
            white-space: normal !important;
        }
        
        /* Scroll horizontal para pantallas pequeñas */
        .dataTables_scrollBody {
            overflow-x: auto !important;
        }

        /* Scroll horizontal para pantallas pequeñas */
        .dataTables_scrollBody {
            overflow-x: auto !important;
        }

        .loader {
            width: 48px;
            height: 48px;
            border: 3px solid #FFF;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        .loader::after {
            content: '';  
            box-sizing: border-box;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: 3px solid;
            border-color:rgb(10, 2, 77) transparent;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        } 

        .spinner-overlay {
            position: fixed; /* Fijo en la ventana */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Fondo semitransparente */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Asegura que esté por encima de todo */
        }

    </style>
</head>
<body>
<div class="spinner-overlay">
    <span class="loader"></span>
</div>

<div class="container-fluid">
    <div class="report-container"> 
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
            </div>
        <?php endif; ?>

        <div style="margin-left: 20px; margin-right: 20px">        
                <div class="table-responsive">  
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
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">
    <script>

        $(document).ready(function() {
          $(".spinner-overlay").hide();
        });

        let table = $("#tablahistorial").DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            responsive: true,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            pageLength: 10
            });
    </script>
</body>
</html>
