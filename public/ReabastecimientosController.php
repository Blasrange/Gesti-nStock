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
require_once '../app/MovimientoLogger.php';


use App\Database;
use App\MovimientoLogger;
use App\Replenishment;

$database = new Database();  // <-- Esto falta en tu código
$logger = new MovimientoLogger($database);  // Ahora sí funciona

// Instancia del logger
$database = new \App\Database();
$movimientoLogger = new MovimientoLogger($database);

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

    // Obtener datos del cliente y del usuario desde la base de datos
    $cliente_nombre = $database->getClienteNombreById($cliente_id);
    $usuario_nombre = $database->getUsuarioNombreById($_SESSION['user_id']);

    
    foreach ($nuevosReabastecimientos as $reabastecimientos) {
        $movimientoLogger->registrarMovimiento([
            'cliente_id' => $cliente_id,
            'cliente_nombre' => $cliente_nombre,
            'usuario_id' => $_SESSION['user_id'],
            'usuario_nombre' => $usuario_nombre,
            'sku' => $reabastecimientos['sku'],
            'descripcion' => $reabastecimientos['descripcion'],
            'lpn_origen' => $reabastecimientos['lpn_inventario'],
            'localizacion_origen' => $reabastecimientos['localizacion_origen'],
            'lpn_destino' => $reabastecimientos['lpn_max_min'],
            'localizacion_destino' => $reabastecimientos['localizacion_destino'],
            'cantidad' => $reabastecimientos['unidades_reabastecer'] ?? 0,
            'lote' => $reabastecimientos['lote'],
            'tipo_movimiento' => 'Generación de reabastecimiento'
        ]);
    }

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
        #tablareabastecimientos {
            width: 200% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 4px !important;  /* Espacio entre filas */
        }
        
        #tablareabastecimientos th, 
        #tablareabastecimientos td {
            padding: 10px 15px !important;
            vertical-align: middle !important;
        }
        
        /* Ajustes generales para la tabla */
        #tablareabastecimientos {
            width: 100% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;  /* Espacio entre filas */
        }
        
        #tablareabastecimientos th, 
        #tablareabastecimientos td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            vertical-align: middle !important;
        }
        
        /* Columnas específicas */
        #tablareabastecimientos th:nth-child(2),  /* Descripción */
        #tablareabastecimientos td:nth-child(2) {
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
                                <th style="text-align: center">FPC</th>
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
                                        <td style="text-align: center"><?php echo htmlspecialchars($reabastecimiento['fpc'] ?? ''); ?></td>
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

        let table = $("#tablareabastecimientos").DataTable({
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