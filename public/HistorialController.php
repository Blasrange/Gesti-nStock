<?php

// public/HistorlaController.php

namespace App;

session_start();

require_once '../app/db.php';
require_once '../app/Historial.php';

use App\Database;
use App\Historial;

if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

$database = new Database();
$historialObj = new Historial($database);

// Verificar si se debe actualizar el historial
if (isset($_POST['actualizar'])) {
    // Obtener reportes y agruparlos para historial
    $historialGenerado = $historialObj->generateHistorial($cliente_id);

    // Verificar si hay errores o si se generó historial
    if (empty($historialGenerado)) {
        $_SESSION['error_message'] = "No se generó historial.";
    } else {
        $_SESSION['historial'] = $historialGenerado;
        unset($_SESSION['error_message']);
    }
}

// Cargar el historial desde la base de datos
$historial = $historialObj->getHistorial($cliente_id);

// Inicializar totales
$totalSku = count($historial);
$totalUnidades = 0;
$totalCajas = 0;

// Calcular totales de Unidades y Cajas
foreach ($historial as $entry) {
    $totalUnidades += $entry['unidades'];
    $totalCajas += $entry['cajas'];
}

// Calcular fecha actual y turno
$fecha = date('Y-m-d H:i:s');
$horaActual = date('H:i');

// Ajustar los intervalos para el cálculo del turno
if ($horaActual >= '06:00' && $horaActual < '14:00') {
    $turno = 1;
} elseif ($horaActual >= '14:00' && $horaActual < '22:00') {
    $turno = 2;
} else {
    $turno = 3;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial</title>
    <link rel="stylesheet" href="assets/css/estilos.css">

    <style>
        .btn-back {
            display: inline-block;
            background-color: rgb(96, 129, 189);
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
            background-color: rgb(96, 129, 189);
            transform: scale(1.05);
        }

        .btn-actualizar {
            background-color: rgb(96, 129, 189);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn-actualizar:hover {
            background-color: rgb(96, 129, 189);
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
            padding: 10px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000; /* Asegúrate de que esté sobre otros elementos */
        }
        
    </style>
</head>
<body>
<div class="header">
    <a href="dashboard.php" style="text-decoration: none; color: black;">
        <h1>Historial</h1>
    </a>
    <form method="POST" style="display: inline;">
        <button type="submit" name="actualizar" class="btn-refresh">Actualizar</button>
    </form>
</div>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="error-message">
        <?php echo $_SESSION['error_message']; ?>
    </div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Fecha/Hora</th>
            <th>SKU</th>
            <th>Unidades</th>
            <th>Cajas</th>
            <th>Turno</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($historial as $entry): ?>
        <tr>
            <td><?php echo htmlspecialchars($entry['fecha_hora']); ?></td>
            <td><?php echo htmlspecialchars($entry['sku']); ?></td>
            <td><?php echo htmlspecialchars($entry['unidades']); ?></td>
            <td><?php echo htmlspecialchars($entry['cajas']); ?></td>
            <td><?php echo htmlspecialchars($entry['turno']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="total">
    Total de Registros: <?php echo $totalSku; ?>
</div>
</body>
</html>
