<?php
// public/UsuarioController.php

namespace App;

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php';
require_once '../app/Usuario.php';
require_once '../app/Cliente.php';

use App\Database;
use App\Usuario;
use App\Cliente;

if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$usuarioModel = new Usuario($database);
$clienteModel = new Cliente($database);

$action = $_GET['action'] ?? 'index';

if ($action === 'index' || $action === 'edit') {
    $usuarios = $usuarioModel->getAll();
    $clientes = $clienteModel->getAll();
    $titulo = "Usuarios";
    $seccion = "Mantenimiento";
    $usuarioEdit = null;

    if ($action === 'edit' && isset($_GET['id'])) {
        $usuarioEdit = $usuarioModel->find($_GET['id']);
    }

    include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
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
        #tablasuarios {
            width: 200% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 4px !important;  /* Espacio entre filas */
        }
        
        #tablasuarios th, 
        #tablasuarios td {
            padding: 10px 15px !important;
            vertical-align: middle !important;
        }
        
        /* Ajustes generales para la tabla */
        #tablasuarios {
            width: 100% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;  /* Espacio entre filas */
        }
        
        #tablasuarios th, 
        #tablasuarios td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            vertical-align: middle !important;
        }
        
        /* Columnas específicas */
        #tablasuarios th:nth-child(2),  /* Descripción */
        #tablasuarios td:nth-child(2) {
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
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div style="margin-left: 20px; margin-right: 20px">
                <div class="table-responsive">
                    <div class="search-container">
                        <button class="btn btn-sm btn-dark btn-small" data-bs-toggle="modal" data-bs-target="#usuarioModal">Crear Usuario</button>
                    </div>

                    <table id="tablasuarios" class="table table-striped table-hover dataTable display">
                        <thead>
                        <tr>
                            <th style="text-align: center">ID</th>
                            <th style="text-align: center">Nombre</th>
                            <th style="text-align: center">Usuario</th>
                            <!--th style="text-align: center">Contraseña</th>
                            <th style="text-align: center">Cliente</th!-->
                            <th style="text-align: center">Estado</th>
                            <th style="text-align: center">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td style="text-align: center"><?= htmlspecialchars($usuario['id']) ?></td>
                                <td style="text-align: center"><?= htmlspecialchars($usuario['name']) ?></td>
                                <td style="text-align: center"><?= htmlspecialchars($usuario['username']) ?></td>
                                <!--td style="text-align: center">********</td>
                                <td style="text-align: center"><?= htmlspecialchars($usuario['cliente_nombre'] ?? '') ?></td!-->
                                <td style="text-align: center">
                                    <span class="badge-estado <?= $usuario['estado'] ? 'activo' : 'inactivo' ?>">
                                        <?= $usuario['estado'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td style="text-align: center">
                                    <a href="UsuarioController.php?action=edit&id=<?= $usuario['id'] ?>" class="btn btn-sm btn-dark btn-small">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL PARA CREAR O EDITAR USUARIO -->
        <div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="usuarioModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content shadow-lg rounded-4">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="usuarioModalLabel"><?= $usuarioEdit ? 'Editar Usuario' : 'Crear Nuevo Usuario' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formUsuario" action="UsuarioController.php?action=<?= $usuarioEdit ? 'update' : 'store' ?>" method="POST" class="row g-3">
                            <?php if ($usuarioEdit): ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($usuarioEdit['id']) ?>">
                            <?php endif; ?>

                            <div class="col-md-6">
                                <label class="form-label">Nombre:</label>
                                <input type="text" name="nombre" class="form-control" required value="<?= $usuarioEdit['name'] ?? '' ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Usuario:</label>
                                <input type="text" name="username" class="form-control" required value="<?= $usuarioEdit['username'] ?? '' ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Contraseña <?= $usuarioEdit ? '(dejar vacío para no cambiar)' : '' ?>:</label>
                                <input type="password" name="password" class="form-control" <?= $usuarioEdit ? '' : 'required' ?>>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Cliente:</label>
                                <select name="cliente_id" class="form-control" required>
                                    <option value="">Selecciona un cliente</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id'] ?>" <?= ($usuarioEdit && $cliente['id'] == $usuarioEdit['cliente_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cliente['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Estado:</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="estado" id="estado" <?= isset($usuarioEdit['estado']) && $usuarioEdit['estado'] == 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="estado">Activo</label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" class="btn btn-dark" form="formUsuario"><?= $usuarioEdit ? 'Actualizar' : 'Guardar' ?></button>
                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
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

    let table = $("#tablasuarios").DataTable({
    language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        responsive: true,
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        pageLength: 10
    });
    
    <?php if ($action === 'edit'): ?>
    const modal = new bootstrap.Modal(document.getElementById('usuarioModal'));
    modal.show();
    <?php endif; ?>
</script>
</body>
</html>

<?php
} elseif ($action === 'store') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'nombre'     => $_POST['nombre'] ?? '',
            'username'   => $_POST['username'] ?? '',
            'password'   => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
            'cliente_id' => $_POST['cliente_id'] ?? null,
            'estado'     => isset($_POST['estado']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $usuarioModel->create($data);
        $_SESSION['success'] = 'Usuario creado exitosamente.';
        header('Location: UsuarioController.php?action=index');
        exit;
    }

} elseif ($action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'];
        $data = [
            'nombre'     => $_POST['nombre'] ?? '',
            'username'   => $_POST['username'] ?? '',
            'cliente_id' => $_POST['cliente_id'] ?? null,
            'estado'     => isset($_POST['estado']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $usuarioModel->update($id, $data);
        $_SESSION['success'] = 'Usuario actualizado exitosamente.';
        header('Location: UsuarioController.php?action=index');
        exit;
    }
}
?>
