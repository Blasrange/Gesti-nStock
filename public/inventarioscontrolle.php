<?php
// public/inventarioscontrolle.php

session_start();

// Habilitar la visualización de errores (solo para desarrollo; eliminar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php';
require_once '../app/inventarios.php';
use App\Database;
use App\inventarios;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$cliente_id = $_SESSION['cliente_id']; // Obtener el cliente_id de la sesión
$inventariosObj = new inventarios($database, $cliente_id); // Pasar ambos argumentos

// Generar un token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener el término de búsqueda de la consulta GET
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Obtener y listar los inventarios de la base de datos, filtrando por el término de búsqueda
$inventarios = $inventariosObj->getAllItems($searchTerm); // Aquí necesitas modificar tu método para aceptar el término de búsqueda

// Contar el total de registros
$totalRegistros = count($inventarios); // Contar los elementos en el array de inventarios
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventarios</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        .btn-back {
            display: inline-block;
            background-color: #007bff;
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
            background-color: #0056b3;
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
            position: fixed;
            top: 0;
            left: 0;
            width: 98%;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
       
    </style>
</head>
<body>

<div class="header">
    <a href="dashboard.php" style="text-decoration: none; color: black;">
        <h1>Inventarios</h1>
    </a>
    
    
    <!-- Formulario para cargar inventario -->
    <div class="search-container">
        <input type="text" id="search-input" class="search-input" placeholder="Buscar..." oninput="filterReports()">
    </div>

    <!--div class="form-upload">                
        <form action="cargar_inventario.php" method="post" enctype="multipart/form-data">
            <label for="file-upload" class="custom-file-upload">
                Seleccionar archivo
            </label>
            <span id="file-selected" class="file-box">Ningún archivo seleccionado</span>
            <input type="file" name="file" id="file-upload" accept=".xlsx, .xls" required>
            <button type="submit">Cargar Inventario</button>
        </form>
    </div!-->  
</div>

<script>
    document.getElementById('file-upload').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
        document.getElementById('file-selected').textContent = fileName;
    });
</script>

<?php
// Mostrar un mensaje de error, si existe
if (isset($_SESSION['error_message'])) {
    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']); // Limpiar el mensaje de error después de mostrarlo
}
?>

<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>LPN</th>
            <th>Localización</th>
            <th>Área Picking</th>
            <th>SKU</th>
            <th>SKU2</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Tipo Material</th>
            <th>Categoría Material</th>
            <th>Unidades</th>
            <th>Cajas</th>
            <th>Reserva</th>
            <th>Disponible</th>
            <th>UDM</th>
            <th>Embalaje</th>
            <th>Fecha Entrada</th>
            <th>Estado</th>
            <th>Lote</th>
            <th>Fecha Fabricación</th>
            <th>Fecha Vencimiento</th>
            <th>FPC</th>
            <th>Peso</th>
            <th>Serial</th>
            <th>Cliente ID</th>
            <!--th>Acciones</th-->
        </tr>
    </thead>
    
    <tbody>
        <?php foreach ($inventarios as $inventario): ?>
            <tr>
                <td><?php echo htmlspecialchars($inventario['codigo']); ?></td>
                <td><?php echo htmlspecialchars($inventario['lpn']); ?></td>
                <td><?php echo htmlspecialchars($inventario['localizacion']); ?></td>
                <td><?php echo htmlspecialchars($inventario['area_picking']?? ''); ?></td>
                <td><?php echo htmlspecialchars($inventario['sku']); ?></td>
                <td><?php echo htmlspecialchars($inventario['sku2']?? ''); ?></td>
                <td><?php echo htmlspecialchars($inventario['descripcion']); ?></td>
                <td><?php echo htmlspecialchars(number_format($inventario['precio'], 2)); ?></td>
                <td><?php echo htmlspecialchars($inventario['tipo_material']); ?></td>
                <td><?php echo htmlspecialchars($inventario['categoria_material']?? ''); ?></td>
                <td><?php echo htmlspecialchars($inventario['unidades']); ?></td>
                <td><?php echo htmlspecialchars($inventario['cajas']); ?></td>
                <td><?php echo htmlspecialchars($inventario['reserva']); ?></td>
                <td><?php echo htmlspecialchars($inventario['disponible']); ?></td>
                <td><?php echo htmlspecialchars($inventario['udm']); ?></td>
                <td><?php echo htmlspecialchars($inventario['embalaje']); ?></td>
                <td><?php echo htmlspecialchars($inventario['fecha_entrada']?? ''); ?></td>
                <td><?php echo htmlspecialchars($inventario['estado']); ?></td>
                <td><?php echo htmlspecialchars($inventario['lote']?? ''); ?></td>
                <td><?php echo htmlspecialchars($inventario['fecha_fabricacion']?? ''); ?></td>
                <td><?php echo htmlspecialchars($inventario['fecha_vencimiento']?? ''); ?></td>
                <td><?php echo htmlspecialchars($inventario['fpc']); ?></td>
                <td><?php echo htmlspecialchars(number_format($inventario['peso']?? '')); ?></td>
                <td><?php echo htmlspecialchars($inventario['serial']?? ''); ?></td>
                <td><?php echo htmlspecialchars($inventario['cliente_id']
            ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Mostrar el total de registros -->
<div class="total">Total de registros: <?php echo $totalRegistros; ?></div>

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