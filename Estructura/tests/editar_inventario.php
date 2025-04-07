<?php
// public/editar_inventario.php

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

// Verificar que se ha proporcionado un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de inventario inválido.');
}

$id = (int)$_GET['id'];

// Obtener el inventario existente
$inventario = $inventariosObj->getItem($id);

// Verificar que el inventario existe y pertenece al cliente actual
if (!$inventario) {
    die('Inventario no encontrado o acceso no autorizado.');
}

// Generar un token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar el token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido.');
    }

    // Recoger y validar los datos del formulario
    $data = [
        ':codigo' => $_POST['codigo'] ?? '',
        ':lpn' => $_POST['lpn'] ?? '',
        ':localizacion' => $_POST['localizacion'] ?? '',
        ':area_picking' => $_POST['area_picking'] ?? '',
        ':sku' => $_POST['sku'] ?? '',
        ':sku2' => $_POST['sku2'] ?? '',
        ':descripcion' => $_POST['descripcion'] ?? '',
        ':precio' => isset($_POST['precio']) ? floatval($_POST['precio']) : 0,
        ':tipo_material' => $_POST['tipo_material'] ?? '',
        ':categoria_material' => $_POST['categoria_material'] ?? '',
        ':unidades' => isset($_POST['unidades']) ? intval($_POST['unidades']) : 0,
        ':cajas' => isset($_POST['cajas']) ? intval($_POST['cajas']) : 0,
        ':reserva' => isset($_POST['reserva']) ? intval($_POST['reserva']) : 0,
        ':disponible' => isset($_POST['disponible']) ? intval($_POST['disponible']) : 0,
        ':udm' => $_POST['udm'] ?? '',
        ':fecha_entrada' => $_POST['fecha_entrada'] ?? '',
        ':estado' => $_POST['estado'] ?? '',
        ':lote' => $_POST['lote'] ?? '',
        ':fecha_fabricacion' => $_POST['fecha_fabricacion'] ?? '',
        ':fecha_vencimiento' => $_POST['fecha_vencimiento'] ?? '',
        ':fpc' => $_POST['fpc'] ?? '',
        ':peso' => isset($_POST['peso']) ? floatval($_POST['peso']) : 0,
        ':serial' => $_POST['serial'] ?? '',
        ':cliente_id' => $cliente_id
    ];

    // Actualizar el inventario
    if ($inventariosObj->updateItem($id, $data)) {
        // Redirigir con un mensaje de éxito
        header('Location: inventarioscontrolle.php?mensaje=actualizado');
        exit;
    } else {
        $error = 'Error al actualizar el inventario.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Inventario</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <h1>Editar Inventario</h1>
    
    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="editar_inventario.php?id=<?php echo $id; ?>">
        <!-- Campo Token CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        
        <!-- Campo Código -->
        <label for="codigo">Código:</label>
        <input type="text" name="codigo" id="codigo" value="<?php echo htmlspecialchars($inventario['codigo']); ?>" required><br>

        <!-- Campo LPN -->
        <label for="lpn">LPN:</label>
        <input type="text" name="lpn" id="lpn" value="<?php echo htmlspecialchars($inventario['lpn']); ?>" required><br>

        <!-- Campo Localización -->
        <label for="localizacion">Localización:</label>
        <input type="text" name="localizacion" id="localizacion" value="<?php echo htmlspecialchars($inventario['localizacion']); ?>" required><br>

        <!-- Campo Área Picking -->
        <label for="area_picking">Área Picking:</label>
        <input type="text" name="area_picking" id="area_picking" value="<?php echo htmlspecialchars($inventario['area_picking']); ?>" required><br>

        <!-- Campo SKU -->
        <label for="sku">SKU:</label>
        <input type="text" name="sku" id="sku" value="<?php echo htmlspecialchars($inventario['sku']); ?>" required><br>

        <!-- Campo SKU2 -->
        <label for="sku2">SKU2:</label>
        <input type="text" name="sku2" id="sku2" value="<?php echo htmlspecialchars($inventario['sku2']); ?>"><br>

        <!-- Campo Descripción -->
        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion" required><?php echo htmlspecialchars($inventario['descripcion']); ?></textarea><br>

        <!-- Campo Precio -->
        <label for="precio">Precio:</label>
        <input type="number" step="0.01" name="precio" id="precio" value="<?php echo htmlspecialchars($inventario['precio']); ?>" required><br>

        <!-- Campo Tipo Material -->
        <label for="tipo_material">Tipo Material:</label>
        <input type="text" name="tipo_material" id="tipo_material" value="<?php echo htmlspecialchars($inventario['tipo_material']); ?>" required><br>

        <!-- Campo Categoría Material -->
        <label for="categoria_material">Categoría Material:</label>
        <input type="text" name="categoria_material" id="categoria_material" value="<?php echo htmlspecialchars($inventario['categoria_material']); ?>" required><br>

        <!-- Campo Unidades -->
        <label for="unidades">Unidades:</label>
        <input type="number" name="unidades" id="unidades" value="<?php echo htmlspecialchars($inventario['unidades']); ?>" required><br>

        <!-- Campo Cajas -->
        <label for="cajas">Cajas:</label>
        <input type="number" name="cajas" id="cajas" value="<?php echo htmlspecialchars($inventario['cajas']); ?>" required><br>

        <!-- Campo Reserva -->
        <label for="reserva">Reserva:</label>
        <input type="number" name="reserva" id="reserva" value="<?php echo htmlspecialchars($inventario['reserva']); ?>" required><br>

        <!-- Campo Disponible -->
        <label for="disponible">Disponible:</label>
        <input type="number" name="disponible" id="disponible" value="<?php echo htmlspecialchars($inventario['disponible']); ?>" required><br>

        <!-- Campo UDM -->
        <label for="udm">UDM:</label>
        <input type="text" name="udm" id="udm" value="<?php echo htmlspecialchars($inventario['udm']); ?>" required><br>

        <!-- Campo Fecha Entrada -->
        <label for="fecha_entrada">Fecha Entrada:</label>
        <input type="date" name="fecha_entrada" id="fecha_entrada" value="<?php echo htmlspecialchars($inventario['fecha_entrada']); ?>" required><br>

        <!-- Campo Estado -->
        <label for="estado">Estado:</label>
        <input type="text" name="estado" id="estado" value="<?php echo htmlspecialchars($inventario['estado']); ?>" required><br>

        <!-- Campo Lote -->
        <label for="lote">Lote:</label>
        <input type="text" name="lote" id="lote" value="<?php echo htmlspecialchars($inventario['lote']); ?>"><br>

        <!-- Campo Fecha Fabricación -->
        <label for="fecha_fabricacion">Fecha Fabricación:</label>
        <input type="date" name="fecha_fabricacion" id="fecha_fabricacion" value="<?php echo htmlspecialchars($inventario['fecha_fabricacion']); ?>"><br>

        <!-- Campo Fecha Vencimiento -->
        <label for="fecha_vencimiento">Fecha Vencimiento:</label>
        <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" value="<?php echo htmlspecialchars($inventario['fecha_vencimiento']); ?>"><br>

        <!-- Campo FPC -->
        <label for="fpc">FPC:</label>
        <input type="text" name="fpc" id="fpc" value="<?php echo htmlspecialchars($inventario['fpc']); ?>"><br>

        <!-- Campo Peso -->
        <label for="peso">Peso:</label>
        <input type="number" step="0.01" name="peso" id="peso" value="<?php echo htmlspecialchars($inventario['peso']); ?>" required><br>

        <!-- Campo Serial -->
        <label for="serial">Serial:</label>
        <input type="text" name="serial" id="serial" value="<?php echo htmlspecialchars($inventario['serial']); ?>"><br>

        <!-- Botón de Envío -->
        <button type="submit">Actualizar</button>
    </form>
</body>
</html>
