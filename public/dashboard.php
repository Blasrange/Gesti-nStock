<?php
// public/dashboard.php //Esta es la página principal que verán los usuarios después de iniciar sesión y seleccionar cliente y ciudad.
session_start();

ini_set('memory_limit', '1024M');

require_once '../app/db.php'; // Asegúrate de que la ruta sea correcta
require_once '../app/Auth.php';
require '../vendor/autoload.php'; // Autoload de Composer
use App\Database;
use App\Auth;

// Verificar si el usuario está autenticado y ha seleccionado cliente y ciudad
if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$auth = new Auth($database);

// Obtener información del cliente y la ciudad
$stmt = $database->pdo->prepare('SELECT nombre FROM clientes WHERE id = ?');
$stmt->execute([$_SESSION['cliente_id']]);
$cliente = $stmt->fetch();

$stmt = $database->pdo->prepare('SELECT nombre FROM ciudades WHERE id = ?');
$stmt->execute([$_SESSION['ciudad_id']]);
$ciudad = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Reabastecimiento</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Incluyendo Font Awesome -->
    <style>
        /* Estilos básicos para el dashboard */
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #5cb85c;
            color: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .nav a {
            color: #fff;
            margin-left: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .nav a:hover {
            text-decoration: underline;
        }
        .nav a i {
            font-size: 0.8em; /* Tamaño más pequeño para los íconos */
            margin-right: 5px; /* Espacio entre el ícono y el texto */
            vertical-align: middle; /* Alinea verticalmente el ícono con el texto */
        }
        .content {
            padding: 20px; /* Ajustado para un mejor espaciado */
            text-align: left; /* Alinea el contenido a la izquierda */
        }
        .info {
            margin-bottom: 20px; /* Espaciado inferior aumentado */
            text-align: left; /* Alinea el texto a la izquierda */
        }
        .chart-container {
            margin-top: 40px; /* Espaciado superior para el gráfico */
        }
        iframe {
            width: 100%; /* Ancho completo */
            height: 400px; /* Altura del gráfico */
            border: none; /* Sin borde */
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Gestión de Stock</h1>
        </div>
        <div class="nav">
            <a href="inventarioscontrolle.php"><i class="fas fa-box"></i> Inventarios</a>
            <a href="maestraMaterialesController.php"><i class="fas fa-cubes"></i> Maestra de Materiales</a>
            <a href="ReabastecimientosController.php"><i class="fas fa-arrow-up"></i> Reabastecimientos</a>
            <a href="ReportsController.php"><i class="fas fa-chart-line"></i> Reportes</a>
            <a href="HistorialController.php"><i class="fas fa-history"></i> Historial</a>
            <a href="modulo_carga.php"><i class="fas fa-upload"></i> Interfaces</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </div>
    <div class="content">
        <div class="info">
            <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Cliente seleccionado: <strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong></p>
            <p>Ciudad seleccionada: <strong><?php echo htmlspecialchars($ciudad['nombre']); ?></strong></p>
        </div>

        <!-- Sección del gráfico -->
        <div class="chart-container">
            <!--h2>Gráfico de Totales por Cliente</h2!-->
            <iframe src="graficos.php?cliente_id=<?php echo htmlspecialchars($_SESSION['cliente_id']); ?>"></iframe>
        </div>
        
    </div>
</body>
</html>
