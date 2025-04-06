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
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">
    <style>
        .btn-back {
            display: inline-block;
            background-color: #1e3765;
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

        .total {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            text-align: center;
            padding: 3px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
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

        .badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 12px;
        }

        .bg-success-light {
            background-color: rgb(118, 226, 176) !important;
            color: #000;
        }

        .bg-danger-light {
            background-color: rgb(255, 168, 168) !important;
            color: #000;
        }

        .text-success-light {
            background-color: rgb(218, 255, 239);
            color: rgb(0, 128, 0);
        }

        .text-danger-light {
            background-color: rgb(255, 218, 218);
            color: rgb(200, 0, 0);
        }

        .badge {
            padding: 0.35em 0.6em;
            font-size: 0.875em;
            font-weight: 600;
            border-radius: 0.5rem;
            display: inline-block;
        }
    </style>
</head>
<body>
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
                <button class="btn btn-dark btn-small" data-bs-toggle="modal" data-bs-target="#usuarioModal">Crear Usuario</button>
            </div>

            <table id="tablasuarios" class="table table-striped table-hover dataTable display">
                <thead>
                <tr>
                    <th style="text-align: center">ID</th>
                    <th style="text-align: center">Nombre</th>
                    <th style="text-align: center">Usuario</th>
                    <th style="text-align: center">Contraseña</th>
                    <th style="text-align: center">Cliente</th>
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
                        <td style="text-align: center">********</td>
                        <td style="text-align: center"><?= htmlspecialchars($usuario['cliente_nombre'] ?? '') ?></td>
                        <td style="text-align: center">
                        <span class="badge <?= $usuario['estado'] ? 'text-success-light' : 'text-danger-light' ?>">
                            <?= $usuario['estado'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                        </td>
                        <td style="text-align: center">
                            <a href="UsuarioController.php?action=edit&id=<?= $usuario['id'] ?>" class="btn btn-dark btn-small">Editar</a>
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
                        <input type="password" name="password" class="form-control">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
<script>
    let table = $("#tablasuarios").DataTable({
        "oLanguage": {
            "sUrl": "assets/js/datatables_es.json"
        },
        responsive: true,
        pagingType: "full_numbers",
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
