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

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$cliente_id = $_SESSION['cliente_id']; // Obtener el cliente_id de la sesión
$maestraMaterialesObj = new MaestraMateriales($database, $cliente_id); // Instancia del objeto MaestraMateriales

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

        td {
            text-align: center; /* Centra el contenido de la celda */
            vertical-align: middle; /* Alinea verticalmente los botones */
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

    .btn-success {
        background-color: #343a40;
        color: #343a40;
        border: none;
    }

    .btn-outline-secondary {
        background-color: #f8f9fa;
        color: #6c757d;
    }

    .form-control, .form-select {
        border-radius: 0.5rem;
    }

    label {
        font-weight: 500;
    }

    .modal-content {
        border-radius: 1rem;
    }

    .btn-warning {
        background-color: #fff3cd;
        color: #856404;
        border: none;
    }

    .btn-primary {
        background-color: #343a40;
        color:#343a40;
        border: none;
    }

    .btn-outline-secondary {
        background-color: #343a40;
        color: #6c757d;
    }

    .form-control, .form-select {
        border-radius: 0.5rem;
    }

    label {
        font-weight: 500;
    }
    </style>
</head>
<body>

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
            <button type="submit" class="btn btn-dark btn-small" data-bs-toggle="modal" data-bs-target="#materialModal">Agregar Material</button>           
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
                            <td>
                            <a href="maestraMaterialesController.php?edit_id=<?= $material['id']; ?>" class="btn-editar">Editar</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $material['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar este material?');">Eliminar</button>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


<script>
    let table = $("#tablamaestra_de_materiales").DataTable({
            "oLanguage": {
                "sUrl": "assets/js/datatables_es.json"
            },
            responsive: true,
            pagingType: "full_numbers"
        });
</script>

</body>
</html>
