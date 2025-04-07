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
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
    <div class="table-responsive">
        <div class="search-container text-end mb-2">
            <button class="btn btn-dark" onclick="mostrarModal()">Agregar Cliente</button>
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
                    <td style="text-align: center"><?= htmlspecialchars($cliente['deposito'] ?? '') ?></td>
                    <td style="text-align: center"><?= htmlspecialchars($cliente['propietario'] ?? '') ?></td>
                    <td style="text-align: center"><?= $cliente['created_at'] ?></td>
                    <td style="text-align: center">
                        <span class="badge <?= $cliente['estado'] ? 'text-success-light' : 'text-danger-light' ?>">
                            <?= $cliente['estado'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td style="text-align: center">
                        <button class="btn btn-dark btn-small" onclick='editarCliente(<?= json_encode($cliente) ?>)'>Editar</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>

<script>
    let table = $("#tablaclientes").DataTable({
        "oLanguage": {
            "sUrl": "assets/js/datatables_es.json"
        },
        responsive: true,
        pagingType: "full_numbers",
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
