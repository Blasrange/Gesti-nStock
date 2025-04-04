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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Maestra de Materiales</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
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
            position: fixed;
            top: 0;
            left: 0;
            width: 98%;
            z-index: 1000; /* Asegúrate de que esté sobre otros elementos */
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Añadir sombra para destacar */
        }

        .header h1 {
            margin: 0;
        }

        /* Añadir margen al contenido para que no quede debajo del header fijo */
        body {
            margin-top: 100px;
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

        /* Estilo del modal */
        .modal {
            display: none; /* Oculto por defecto */
            position: fixed; /* Queda fijo en la pantalla */
            z-index: 1000; /* Por encima de otros elementos */
            left: 0;
            top: 0;
            width: 100%; /* Ancho completo */
            height: 100%; /* Alto completo */
            overflow: auto; /* Activa el scroll si es necesario */
            background-color: rgb(0,0,0); /* Color de fondo negro */
            background-color: rgba(0,0,0,0.4); /* Fondo con opacidad */
        }

        .modal-content {
            background-color: #f8f9fa;
            margin: 15% auto; /* 15% desde arriba y centrado */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Ancho del modal */
            border-radius: 0px; /* Bordes redondeados */
        }

        .close {
            color: #aaa;
            float: right; /* A la derecha */
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        #openModal {
            background-color:rgb(96, 129, 189);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 2px 0;
            transition: background-color 0.3s;
        }

        #openModal:hover {
            background-color: #1e3765;
        }
        .search-input {
            padding: 8px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 200px;
        }
        .search-container {
            margin-left: auto; /* Mueve el contenedor de búsqueda a la derecha */
            margin-right: 20px; /* Añade espacio a la derecha */
        }
    </style>
</head>
<body>

<div class="header">
    <a href="dashboard.php" style="text-decoration: none; color: black;">
        <h1>Maestra de Materiales</h1>
    </a>
    
    <div class="search-container" style="margin-left: auto; margin-right: 10px;">
        <input type="text" id="search-input" class="search-input" placeholder="Buscar..." oninput="filterReports()">
    </div>

    <!-- Botón para abrir el modal de agregar material -->
    <button id="openModal">Agregar Material</button>
</div>
<script>
    document.getElementById('file-upload').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
        document.getElementById('file-selected').textContent = fileName;
    });
</script>

<!-- Modal para agregar material -->
<div id="materialModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Agregar Nuevo Material</h2>
        <form action="maestraMaterialesController.php" method="post">
            <input type="hidden" name="action" value="add">
            <label>SKU:</label>
            <input type="text" name="sku" required>

            <label>LPN:</label>
            <input type="text" name="lpn" required>

            <label>Localización:</label>
            <input type="text" name="localizacion" required>

            <label>Descripción:</label>
            <input type="text" name="descripcion" required>

            <label>Stock Mínimo:</label>
            <input type="number" name="stock_minimo" required>

            <label>Stock Máximo:</label>
            <input type="number" name="stock_maximo" required>

            <label>Embalaje:</label>
            <input type="text" name="embalaje" required>

            <button type="submit">Agregar</button>
        </form>
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
</script>

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

<table>
    <thead>
        <tr>
            <th>SKU</th>
            <th>LPN</th>
            <th>Localización</th>
            <th>Descripción</th>
            <th>Stock Mínimo</th>
            <th>Stock Máximo</th>
            <th>Embalaje</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($totalRegistros > 0): ?>
            <?php foreach ($materiales as $material): ?>
                <tr>
                    <td><?= htmlspecialchars($material['sku']); ?></td>
                    <td><?= htmlspecialchars($material['lpn']); ?></td>
                    <td><?= htmlspecialchars($material['localizacion']); ?></td>
                    <td><?= htmlspecialchars($material['descripcion']); ?></td>
                    <td><?= htmlspecialchars($material['stock_minimo']); ?></td>
                    <td><?= htmlspecialchars($material['stock_maximo']); ?></td>
                    <td><?= htmlspecialchars($material['embalaje']); ?></td>
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

<?php if ($totalRegistros > 0): ?>
    <div class="total">Total de registros: <?= $totalRegistros; ?></div>
<?php endif; ?>

<!-- Formulario de edición si existe un material para editar -->
<?php if ($materialToEdit): ?>
    <div id="editModal" class="modal" style="display:block;">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
            <h2>Editar Material</h2>
            <form action="maestraMaterialesController.php" method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $materialToEdit['id']; ?>">
                <label>SKU:</label>
                <input type="text" name="sku" value="<?= htmlspecialchars($materialToEdit['sku']); ?>" required>

                <label>LPN:</label>
                <input type="text" name="lpn" value="<?= htmlspecialchars($materialToEdit['lpn']); ?>" required>

                <label>Localización:</label>
                <input type="text" name="localizacion" value="<?= htmlspecialchars($materialToEdit['localizacion']); ?>" required>

                <label>Descripción:</label>
                <input type="text" name="descripcion" value="<?= htmlspecialchars($materialToEdit['descripcion']); ?>" required>

                <label>Stock Mínimo:</label>
                <input type="number" name="stock_minimo" value="<?= htmlspecialchars($materialToEdit['stock_minimo']); ?>" required>

                <label>Stock Máximo:</label>
                <input type="number" name="stock_maximo" value="<?= htmlspecialchars($materialToEdit['stock_maximo']); ?>" required>

                <label>Embalaje:</label>
                <input type="text" name="embalaje" value="<?= htmlspecialchars($materialToEdit['embalaje']); ?>" required>

                <button type="submit">Actualizar Material</button>
            </form>
        </div>
    </div>
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

</body>
</html>
