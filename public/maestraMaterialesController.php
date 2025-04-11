<?php
// public/maestraMaterialesController.php

session_start();

// Habilitar la visualización de errores (solo para desarrollo; eliminar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php';
require_once '../app/maestraMateriales.php';
use App\Database;
use App\MaestraMateriales;
use App\LogMateriales;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'])) {
    header('Location: login.php');
    exit;
}


$database = new Database();
$cliente_id = $_SESSION['cliente_id']; // Obtener el cliente_id de la sesión
$maestraMaterialesObj = new MaestraMateriales($database, $cliente_id); // Instancia del objeto MaestraMateriales
$logMaterialesObj = new LogMateriales($database, $cliente_id);

// Manejar la creación de un nuevo material
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // Validar si el cliente tiene permisos para agregar materiales (puedes agregar tu lógica de validación aquí)
    if ($_POST['action'] == 'add') {
        $result = $maestraMaterialesObj->addMaterial($_POST);
        if ($result['success']) {
            $_SESSION['success_message'] = "Material agregado exitosamente.";
        } else {
            $_SESSION['error_message'] = "Error al agregar material: " . $result['error'];
        }
        header('Location: maestraMaterialesController.php');
        exit;
    }

    // Manejar la edición de un material
    if ($_POST['action'] == 'edit') {
        $result = $maestraMaterialesObj->editMaterial($_POST);
        if ($result['success']) {
            $_SESSION['success_message'] = "Material editado exitosamente.";
        } else {
            $_SESSION['error_message'] = "Error al editar material: " . $result['error'];
        }
        header('Location: maestraMaterialesController.php');
        exit;
    }

    // Manejar la eliminación de un material
    if ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        $maestraMaterialesObj->deleteMaterial($id);
        header('Location: maestraMaterialesController.php');
        exit;
    }
}

// Obtener el término de búsqueda de la consulta GET, si existe
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Listar los materiales solo para el cliente específico
$materiales = $maestraMaterialesObj->getAllMaterials($searchTerm);

// Contar el total de registros
$totalRegistros = count($materiales);

// Manejar la solicitud de edición de un material
$materialToEdit = null;
if (isset($_GET['edit_id'])) {
    $materialToEdit = $maestraMaterialesObj->getMaterialById($_GET['edit_id']);
}

$titulo = "Maestra de Materiales";
$seccion = "Administración";
include '../templates/header.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Maestra de Materiales</title>
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
    #tablamaestra_de_materiales {
            width: 200% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 4px !important;  /* Espacio entre filas */
        }
        
        #tablamaestra_de_materiales th, 
        #tablamaestra_de_materiales td {
            padding: 10px 15px !important;
            vertical-align: middle !important;
        }
        
        /* Ajustes generales para la tabla */
        #tablamaestra_de_materiales {
            width: 100% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;  /* Espacio entre filas */
        }
        
        #tablamaestra_de_materiales th, 
        #tablamaestra_de_materiales td {
            padding-top: 5px !important;
            padding-bottom: 5px !important;
            vertical-align: middle !important;
        }
        
        /* Columnas específicas */
        #tablamaestra_de_materiales th:nth-child(4),  /* Descripción */
        #tablamaestra_de_materiales td:nth-child(4) {
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
        <script>
            document.getElementById('file-upload').addEventListener('change', function() {
                var fileName = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
                document.getElementById('file-selected').textContent = fileName;
            });
        </script>

        <!-- Modal Bootstrap -->
        <div class="modal fade" id="materialModal" tabindex="-1" aria-labelledby="materialModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content shadow-lg rounded-4">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="materialModalLabel">Agregar Nuevo Material</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                    <form id="formMaterial" action="maestraMaterialesController.php" method="post" class="row g-3">
                            <input type="hidden" name="action" value="add">

                            <div class="col-md-6">
                                <label for="sku" class="form-label">SKU:</label>
                                <input type="text" class="form-control" id="sku" name="sku" required>
                            </div>

                            <div class="col-md-6">
                                <label for="lpn" class="form-label">LPN:</label>
                                <input type="text" class="form-control" id="lpn" name="lpn" required>
                            </div>

                            <div class="col-md-6">
                                <label for="localizacion" class="form-label">Localización:</label>
                                <input type="text" class="form-control" id="localizacion" name="localizacion" required>
                            </div>

                            <div class="col-md-6">
                                <label for="descripcion" class="form-label">Descripción:</label>
                                <input type="text" class="form-control" id="descripcion" name="descripcion" required>
                            </div>

                            <div class="col-md-6">
                                <label for="stock_minimo" class="form-label">Stock Mínimo:</label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" required>
                            </div>

                            <div class="col-md-6">
                                <label for="stock_maximo" class="form-label">Stock Máximo:</label>
                                <input type="number" class="form-control" id="stock_maximo" name="stock_maximo" required>
                            </div>

                            <div class="col-md-6">
                                <label for="embalaje" class="form-label">Embalaje:</label>
                                <input type="text" class="form-control" id="embalaje" name="embalaje" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" class="btn btn-dark" form="formMaterial">Agregar</button>
                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>


        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div style="margin-left: 20px; margin-right: 20px">    
            <div class="table-responsive">       
                <div class="search-container"> 
                    <button type="submit" class="btn btn-sm btn-dark btn-small" data-bs-toggle="modal" data-bs-target="#materialModal">Agregar Material</button>           
                </div>        
                <table id="tablamaestra_de_materiales" class="table table-striped table-hover dataTable display">
                    <thead>
                        <tr>
                            <th style="text-align: center">SKU</th>
                            <th tyle="text-align: center;">LPN</th>
                            <th style="text-align: center;">Localización</th>
                            <th style="text-align: center;">Descripción</th>
                            <th style="text-align: center;">Stock Mínimo</th>
                            <th style="text-align: center;">Stock Máximo</th>
                            <th style="text-align: center;">Embalaje</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($totalRegistros > 0): ?>
                            <?php foreach ($materiales as $material): ?>
                                <tr>
                                    <td style="text-align: center;"><?= htmlspecialchars($material['sku']); ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($material['lpn']); ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($material['localizacion']); ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($material['descripcion']); ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($material['stock_minimo']); ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($material['stock_maximo']); ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($material['embalaje']); ?></td>
                                    <td style="text-align: center;">
                                    <a href="maestraMaterialesController.php?edit_id=<?= $material['id']; ?>" class="btn btn-sm btn-dark btn-small">Editar</a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $material['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn-eliminar btn btn-sm btn-dark btn-small"  onclick="return confirm('¿Estás seguro de que deseas eliminar este material?');">Eliminar</button>
                                        </form>                                
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No se encontraron materiales.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            // Obtener el modal
            var modal = document.getElementById("materialModal");

            // Obtener el botón que abre el modal
            var btn = document.getElementById("openModal");

            // Obtener el elemento <span> que cierra el modal
            var span = document.getElementsByClassName("close")[0];

            // Cuando el usuario hace clic en el botón, abrir el modal 
            btn.onclick = function() {
                modal.style.display = "block";
            }

            // Cuando el usuario hace clic en <span> (x), cerrar el modal
            span.onclick = function() {
                modal.style.display = "none";
            }

            // Cuando el usuario hace clic en cualquier parte fuera del modal, cerrarlo
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
                <script>
                    function cerrarModal() {
                        let modal = document.getElementById('editModal');
                        let modalBootstrap = bootstrap.Modal.getInstance(modal);
                        modalBootstrap.hide();
                }
        </script>



        </script>


        <!-- Botón para abrir el modal de edición (se muestra solo si hay un material para editar) -->
        <?php if ($materialToEdit): ?>
        <!-- Modal Bootstrap de Edición -->
        <div class="modal fade show" id="editModal" tabindex="-1" style="display:block;" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content shadow-lg rounded-4">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="editModalLabel">Editar Material</h5>
                        <button type="button" class="btn-close" onclick="cerrarModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form action="maestraMaterialesController.php" method="post" class="row g-3" id="formEditarMaterial">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= $materialToEdit['id']; ?>">

                            <div class="col-md-6">
                                <label for="sku" class="form-label">SKU:</label>
                                <input type="text" class="form-control" name="sku" value="<?= htmlspecialchars($materialToEdit['sku']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="lpn" class="form-label">LPN:</label>
                                <input type="text" class="form-control" name="lpn" value="<?= htmlspecialchars($materialToEdit['lpn']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="localizacion" class="form-label">Localización:</label>
                                <input type="text" class="form-control" name="localizacion" value="<?= htmlspecialchars($materialToEdit['localizacion']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="descripcion" class="form-label">Descripción:</label>
                                <input type="text" class="form-control" name="descripcion" value="<?= htmlspecialchars($materialToEdit['descripcion']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="stock_minimo" class="form-label">Stock Mínimo:</label>
                                <input type="number" class="form-control" name="stock_minimo" value="<?= htmlspecialchars($materialToEdit['stock_minimo']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="stock_maximo" class="form-label">Stock Máximo:</label>
                                <input type="number" class="form-control" name="stock_maximo" value="<?= htmlspecialchars($materialToEdit['stock_maximo']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="embalaje" class="form-label">Embalaje:</label>
                                <input type="text" class="form-control" name="embalaje" value="<?= htmlspecialchars($materialToEdit['embalaje']); ?>" required>
                            </div>

                            <div class="modal-footer border-0">
                                <button type="submit" class="btn btn-dark">Actualizar</button>
                                <button type="button" class="btn btn-dark" onclick="cerrarModal()">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function cerrarModal() {
                document.getElementById('editModal').style.display = 'none';
                // También puedes redirigir a la misma página para limpiar la URL:
                window.location.href = "maestraMaterialesController.php";
            }
        </script>
        <?php endif; ?>



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

        let table = $("#tablamaestra_de_materiales").DataTable({
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
