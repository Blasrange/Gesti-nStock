<?php
// controllers/ClienteController.php

namespace App;
session_start();

require_once '../app/db.php';
require_once '../app/Cliente.php';
require_once '../app/Ciudad.php';

use App\Database;
use App\Cliente;
use App\Ciudad;

$database = new Database();
$clienteModel = new Cliente($database);
$ciudadModel = new Ciudad($database);

// Procesar acciones del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre' => $_POST['nombre'],
        'email' => $_POST['email'],
        'telefono' => $_POST['telefono'],
        'ciudad_id' => $_POST['ciudad_id'],
        'estado' => isset($_POST['estado']) ? 1 : 0,
        'nodo' => $_POST['nodo'],
        'deposito' => $_POST['deposito'],
        'propietario' => $_POST['propietario']
    ];
    

    if (!empty($_POST['id'])) {
        $clienteModel->update($_POST['id'], $data);
    } else {
        $clienteModel->create($data);
    }

    header("Location: ClienteController.php");
    exit;
}


$titulo = "Clientes";
$seccion = "Mantenimiento";
include '../templates/header.php';

$clientes = $clienteModel->getAll();
$ciudades = $ciudadModel->getAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
    <meta charset="UTF-8">
    <title>Usuarios</title>
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
            background-color:rgb(140, 189, 152);
            color: #155724;
        }
        
        .badge-exit {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-transfer {
            background-color:rgb(19, 19, 20);
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
        #tablaclientes {
            width: 200% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 4px !important;  /* Espacio entre filas */
        }
        
        #tablaclientes th, 
        #tablaclientes td {
            padding: 10px 15px !important;
            vertical-align: middle !important;
        }
        
        /* Ajustes generales para la tabla */
        #tablaclientes {
            width: 100% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;  /* Espacio entre filas */
        }
        
        #tablaclientes th, 
        #tablaclientes td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            vertical-align: middle !important;
        }
        
        /* Columnas específicas */
        #tablaclientes th:nth-child(2),  /* Descripción */
        #tablaclientes td:nth-child(9) {
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

        /* Badges de estado - Tamaño y alineación mejorados */
        .badge-estado {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35em 0.65em !important;  /* Ajusta el tamaño vertical/horizontal */
            font-size: 0.8rem !important;        /* Tamaño de fuente */
            border-radius: 0.375rem !important;  /* Bordes ligeramente redondeados */
            font-weight: 500;
            min-width: 70px;  /* Ancho mínimo para consistencia */
            height: 26px;     /* Altura fija para alinear mejor */
        }

        /* Colores para "Activo" */
        .badge-estado.activo {
            background-color: rgba(40, 199, 111, 0.15) !important;
            color: #28C76F !important;
        }

        /* Colores para "Inactivo" */
        .badge-estado.inactivo {
            background-color: rgba(234, 84, 85, 0.15) !important;
            color: #EA5455 !important;
        }

    </style>
</head>
<body>
<div class="spinner-overlay">
    <span class="loader"></span>
</div>
<div class="container-fluid">
    <div class="report-container"> 
        <div style="margin-left: 20px; margin-right: 20px">
            <div class="table-responsive">
                <div class="search-container">
                    <button class="btn btn-sm btn-dark btn-small" onclick="mostrarModal()">Agregar Cliente</button>
                </div>

                <table id="tablaclientes" class="table table-striped table-hover dataTable display">
                    <thead>
                        <tr>
                            <th style="text-align: center">ID</th>
                            <th style="text-align: center">Nombre</th>
                            <th style="text-align: center">Email</th>
                            <th style="text-align: center">Teléfono</th>
                            <th style="text-align: center">Ciudad</th>
                            <th style="text-align: center">Nodo</th>
                            <th style="text-align: center">Depósito</th>
                            <th style="text-align: center">Propietario</th>
                            <th style="text-align: center">Fecha Registro</th>
                            <th style="text-align: center">Estado</th>
                            <th style="text-align: center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td style="text-align: center"><?= $cliente['id'] ?></td>
                            <td style="text-align: center"><?= htmlspecialchars($cliente['nombre']) ?></td>
                            <td style="text-align: center"><?= htmlspecialchars($cliente['email']) ?></td>
                            <td style="text-align: center"><?= htmlspecialchars($cliente['telefono']) ?></td>
                            <td style="text-align: center"><?= htmlspecialchars($cliente['ciudad']) ?></td>
                            <td style="text-align: center"><?= htmlspecialchars($cliente['nodo'] ?? '') ?></td>
                            <td style="text-align: center"><?= htmlspecialchars($cliente['deposito'] ) ?></td>
                            <td style="text-align: center"><?= htmlspecialchars($cliente['propietario']) ?></td>
                            <td style="text-align: center"><?= $cliente['created_at'] ?></td>
                            <td style="text-align: center">
                                    <span class="badge-estado <?=  $cliente['estado'] ? 'activo' : 'inactivo' ?>">
                                        <?=  $cliente['estado'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>

                            <td style="text-align: center">
                                <button class="btn btn-sm btn-dark btn-small" onclick='editarCliente(<?= json_encode($cliente) ?>)'>Editar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clienteModalLabel">Agregar Cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="clienteId">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="ciudad_id" class="form-label">Ciudad</label>
                            <select name="ciudad_id" id="ciudad_id" class="form-select">
                                <option value="">Selecciona una ciudad</option>
                                <?php foreach ($ciudades as $ciudad): ?>
                                    <option value="<?= $ciudad['id'] ?>"><?= htmlspecialchars($ciudad['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nodo" class="form-label">Nodo</label>
                            <input type="text" name="nodo" id="nodo" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="deposito" class="form-label">Depósito</label>
                            <input type="text" name="deposito" id="deposito" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="propietario" class="form-label">Propietario</label>
                            <input type="text" name="propietario" id="propietario" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado:</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="estado" id="estado">
                                <label class="form-check-label" for="estado">Activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-dark">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
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
        });

    let table = $("#tablaclientes").DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        responsive: true,
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        pageLength: 10
    });

    const clienteModal = new bootstrap.Modal(document.getElementById('clienteModal'))

    function mostrarModal() {
        document.getElementById('clienteId').value = '';
        document.getElementById('nombre').value = '';
        document.getElementById('email').value = '';
        document.getElementById('telefono').value = '';
        document.getElementById('ciudad_id').value = '';
        document.getElementById('nodo').value = '';
        document.getElementById('deposito').value = '';
        document.getElementById('propietario').value = '';
        document.getElementById('estado').checked = true;
        clienteModal.show();
    }


    function editarCliente(cliente) {
        document.getElementById('clienteId').value = cliente.id;
        document.getElementById('nombre').value = cliente.nombre;
        document.getElementById('email').value = cliente.email;
        document.getElementById('telefono').value = cliente.telefono;
        document.getElementById('ciudad_id').value = cliente.ciudad_id;
        document.getElementById('nodo').value = cliente.nodo;
        document.getElementById('deposito').value = cliente.deposito;
        document.getElementById('propietario').value = cliente.propietario;
        document.getElementById('estado').checked = cliente.estado == 1;
        clienteModal.show();
    }

</script>

</body>
</html>
