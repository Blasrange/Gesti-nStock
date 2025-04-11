<?php
// public/MovimientoController.php

namespace App;

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php';
require_once '../app/MovimientoLogger.php';
require_once '../app/LogMateriales.php';
require_once '../app/Usuario.php';
require_once '../app/Cliente.php';
require_once '../app/MaestraMateriales.php';

use App\Database;
use App\MovimientoLogger;
use App\LogMateriales;
use App\Usuario;
use App\Cliente;
use App\MaestraMateriales;

if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'])) {
    header('Location: login.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

$database = new Database();
$movimientoLogger = new MovimientoLogger($database);
$materialLog = new LogMateriales($database, $cliente_id);
$usuarioModel = new Usuario($database);
$clienteModel = new Cliente($database);
$maestraMateriales = new MaestraMateriales($database, $_SESSION['cliente_id']);

$action = $_GET['action'] ?? 'index';
$tab = $_GET['tab'] ?? 'movimientos'; // 'movimientos' o 'materiales'

if ($action === 'index') {
    // Configuración de fechas por defecto
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $tipoMovimiento = $_GET['tipo_movimiento'] ?? null;
    $tipoCambioMaterial = $_GET['tipo_cambio_material'] ?? null;

    // Obtener datos según la pestaña activa
    if ($tab === 'movimientos') {
        $movimientos = $movimientoLogger->obtenerMovimientos(
            $_SESSION['cliente_id'],
            $fechaInicio,
            $fechaFin,
            null,
            $tipoMovimiento
        );
        $tiposMovimiento = $movimientoLogger->obtenerTiposMovimiento();
    } else {
        $cambiosMateriales = $materialLog->obtenerMovimientosMateriales(
            $fechaInicio,
            $fechaFin,
            $tipoCambioMaterial
        );
        $tiposCambioMaterial = $materialLog->obtenerTiposMovimiento();
    }

    $titulo = ($tab === 'movimientos') ? "Reporte de Movimientos" : "Registro de Cambios en Materiales";
    $seccion = "Administración";
    include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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

        .btn-editar, .btn-eliminar {
            background-color: #212529; /* Color de fondo similar al de la imagen */
            color: white; /* Color del texto */
            padding: 8px 10px; /* Espaciado */
            border: none; /* Quitar borde */
            border-radius: 6px; /* Bordes redondeados */
            cursor: pointer;
            text-decoration: none; /* Para enlaces */
            display: inline-block;
            font-size: 16px;
            margin: 2px; /* Espaciado entre botones */
        }

        .btn-editar:hover, .btn-eliminar:hover {
            background-color: #343a40; /* Un color más claro para el efecto hover */
        }

        .acciones {
            display: flex;
            justify-content: center; /* Centrar horizontalmente los botones */
            gap: 10px; /* Espacio entre botones */
        }       

        .modal-content {
        border-radius: 1rem;
    }

    /* Ajustes generales para la tabla */
    #movementsTable {
            width: 200% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 4px !important;  /* Espacio entre filas */
        }
        
        #movementsTable th, 
        #movementsTable td {
            padding: 10px 15px !important;
            vertical-align: middle !important;
        }
        
        /* Ajustes generales para la tabla */
        #movementsTable {
            width: 100% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;  /* Espacio entre filas */
        }
        
        #movementsTable th, 
        #movementsTable td {
            padding-top: 5px !important;
            padding-bottom: 5px !important;
            vertical-align: middle !important;
        }
        
        /* Columnas específicas */
        #movementsTable th:nth-child(4),  /* Descripción */
        #movementsTable td:nth-child(4) {
            min-width: 250px !important;
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Pestañas para alternar entre vistas -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'movimientos' ? 'active' : '' ?>" 
                   href="?action=index&tab=movimientos">
                   <i class="fas fa-exchange-alt me-2"></i>Movimientos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'materiales' ? 'active' : '' ?>" 
                   href="?action=index&tab=materiales">
                   <i class="fas fa-boxes me-2"></i>Cambios en Materiales
                </a>
            </li>
        </ul>

        <div class="filter-section">
            <form id="filterForm" method="GET" action="MovimientoController.php">
                <input type="hidden" name="action" value="index">
                <input type="hidden" name="tab" value="<?= $tab ?>">
                
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="fecha_inicio" class="form-label">Desde</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                               value="<?= htmlspecialchars($fechaInicio) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label">Hasta</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                               value="<?= htmlspecialchars($fechaFin) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="tipo_filter" class="form-label">Tipo</label>
                        <select class="form-select" id="tipo_filter" name="<?= $tab === 'movimientos' ? 'tipo_movimiento' : 'tipo_cambio_material' ?>">
                            <option value="">Todos los tipos</option>
                            <?php if ($tab === 'movimientos'): ?>
                                <?php foreach ($tiposMovimiento as $tipo): ?>
                                    <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipoMovimiento === $tipo ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tipo) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php foreach ($tiposCambioMaterial as $tipo): ?>
                                    <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipoCambioMaterial === $tipo ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($tipo)) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($tab === 'movimientos'): ?>
            <!-- Vista de Movimientos de Inventario -->
            <?php if (!empty($movimientos)): ?>
                <div class="report-section">
                    <div class="report-header">
                        <h3 class="report-title">
                            <i class="fas fa-file-alt me-2"></i>Resultados del Reporte
                        </h3>
                        <span class="report-badge">
                            <?= count($movimientos) ?> registros encontrados
                        </span>
                    </div>
                    
                    <div style="margin-left: 20px; margin-right: 20px">
                        <div class="table-responsive">
                            <table id="movementsTable" class="table table-striped table-hover dataTable display">
                                <thead>
                                    <tr>
                                        <th style="text-align: center">Fecha</th>
                                        <th style="text-align: center">Usuario</th>
                                        <th style="text-align: center">SKU</th>
                                        <th style="text-align: center">Descripción</th>
                                        <th style="text-align: center">Origen</th>
                                        <th style="text-align: center">Destino</th>
                                        <th style="text-align: center">Cantidad</th>
                                        <th style="text-align: center">Lote</th>
                                        <th style="text-align: center">Tipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimientos as $mov): ?>
                                        <tr>
                                            <td style="text-align: center"><?= htmlspecialchars($mov['fecha_movimiento']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($mov['usuario_nombre']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($mov['sku']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($mov['descripcion']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($mov['localizacion_origen']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($mov['localizacion_destino']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($mov['cantidad']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($mov['lote']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($mov['tipo_movimiento']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>No se encontraron movimientos con los filtros seleccionados.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Vista de Cambios en Materiales -->
            <?php if (!empty($cambiosMateriales)): ?>
                <div class="report-section">
                    <div class="report-header">
                        <h3 class="report-title">
                            <i class="fas fa-file-alt me-2"></i>Registro de Cambios
                        </h3>
                        <span class="report-badge">
                            <?= count($cambiosMateriales) ?> registros encontrados
                        </span>
                    </div>
                    
                    <div style="margin-left: 20px; margin-right: 20px">
                        <div class="table-responsive">
                             <table id="movementsTable" class="table table-striped table-hover dataTable display">
                                <thead>
                                    <tr>
                                        <th style="text-align: center">Fecha</th>
                                        <th style="text-align: center">Usuario</th>
                                        <th style="text-align: center">SKU</th>
                                        <th style="text-align: center">Descripción</th>
                                        <th style="text-align: center">Tipo Cambio</th>
                                        <th style="text-align: center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cambiosMateriales as $cambio): ?>
                                        <tr>
                                            <td style="text-align: center"><?= htmlspecialchars($cambio['fecha_movimiento']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($cambio['usuario_nombre']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($cambio['sku']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($cambio['descripcion']) ?></td>
                                            <td style="text-align: center">
                                                <span class="badge 
                                                    <?= $cambio['tipo_movimiento'] == 'creación' ? 'bg-success' : 
                                                       ($cambio['tipo_movimiento'] == 'edición' ? 'bg-primary' : 'bg-danger') ?>">
                                                    <?= ucfirst($cambio['tipo_movimiento']) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center">
                                                <button class="btn btn-sm btn-info btn-detalle-cambio" 
                                                        data-id="<?= $cambio['id'] ?>"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#detalleCambioModal">
                                                    <i class="fas fa-info-circle"></i> Detalles
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>No se encontraron cambios en materiales con los filtros seleccionados.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para detalles de cambios en materiales -->
<div class="modal fade" id="detalleCambioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Cambio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Datos Anteriores:</h6>
                        <div class="change-details">
                            <pre id="datosAnteriores">No disponible</pre>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Datos Nuevos:</h6>
                        <div class="change-details">
                            <pre id="datosNuevos">No disponible</pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
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

        // Inicializar DataTables según la pestaña activa
        <?php if ($tab === 'movimientos'): ?>
            $('#movementsTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                responsive: true,
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                pageLength: 10
            });
        <?php else: ?>
            $('#movementsTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                responsive: true,
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                pageLength: 10
            });
        <?php endif; ?>
        
        // Auto-enviar el formulario al cambiar filtros
        $('#fecha_inicio, #fecha_fin, #tipo_filter').change(function() {
            if ($('#fecha_inicio').val() && $('#fecha_fin').val()) {
                $('#filterForm').submit();
            }
        });
        
        // Manejar clic en botón de detalles
        $('.btn-detalle-cambio').click(function() {
            const cambioId = $(this).data('id');
            $(".spinner-overlay").show();
            
            // Obtener detalles del cambio via AJAX
            $.get('getCambioMaterial.php?id=' + cambioId, function(response) {
                if (response.success) {
                    const datos = response.data;
                    
                    // Formatear los datos para mostrarlos
                    const formatData = (data) => {
                        if (!data) return 'No disponible';
                        try {
                            const parsed = JSON.parse(data);
                            return JSON.stringify(parsed, null, 2);
                        } catch (e) {
                            return data;
                        }
                    };
                    
                    $('#datosAnteriores').text(formatData(datos.datos_anteriores));
                    $('#datosNuevos').text(formatData(datos.datos_nuevos));
                } else {
                    alert('Error al cargar los detalles: ' + response.error);
                }
            }).fail(function() {
                alert('Error en la solicitud');
            }).always(function() {
                $(".spinner-overlay").hide();
            });
        });
    });
</script>
</body>
</html>
<?php
} elseif ($action === 'registrar') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Registrar movimiento de inventario
            if (isset($_POST['tipo_movimiento'])) {
                $movimiento = [
                    'cliente_id' => $_SESSION['cliente_id'],
                    'cliente_nombre' => $_SESSION['cliente_nombre'],
                    'usuario_id' => $_SESSION['user_id'],
                    'usuario_nombre' => $_SESSION['user_name'],
                    'sku' => $_POST['sku'],
                    'descripcion' => $_POST['descripcion'],
                    'lpn_origen' => $_POST['lpn_origen'] ?? null,
                    'localizacion_origen' => $_POST['localizacion_origen'] ?? null,
                    'lpn_destino' => $_POST['lpn_destino'] ?? null,
                    'localizacion_destino' => $_POST['localizacion_destino'] ?? null,
                    'cantidad' => $_POST['cantidad'],
                    'lote' => $_POST['lote'] ?? null,
                    'tipo_movimiento' => $_POST['tipo_movimiento']
                ];

                $movimientoLogger->registrarMovimiento($movimiento);
            }
            
            // Registrar cambio de material si es necesario
            if (isset($_POST['material_id'])) {
                $params = [
                    'usuario_id' => $_SESSION['user_id'],
                    'usuario_nombre' => $_SESSION['user_name'],
                    'cliente_nombre' => $_SESSION['cliente_nombre'],
                    'material_id' => $_POST['material_id'],
                    'sku' => $_POST['sku'],
                    'descripcion' => $_POST['descripcion'],
                    'tipo_movimiento' => $_POST['tipo_cambio'] ?? 'actualización',
                    'datos_anteriores' => json_encode($_POST['datos_anteriores'] ?? null),
                    'datos_nuevos' => json_encode($_POST['datos_nuevos'] ?? null)
                ];

                $materialLog->registrarMovimiento($params);
            }
            
            $_SESSION['success'] = 'Operación registrada exitosamente.';
            header('Location: MovimientoController.php');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al registrar operación: ' . $e->getMessage();
            header('Location: MovimientoController.php');
            exit;
        }
    }
}