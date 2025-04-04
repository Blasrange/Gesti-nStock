<?php
// public/Tarea.php
namespace App;
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php';
require_once '../app/Tarea.php';
require_once '../app/Usuario.php'; // Para manejar asignaciones de usuario
require_once '../vendor/autoload.php'; // Cargar dependencias de Composer

use App\Database;
use App\Tarea;
use App\Usuario;

if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$tareaObj = new Tarea($database);
$usuarioObj = new Usuario($database);

if (isset($_POST['asignar_usuario'])) {
    $tarea_id = $_POST['tarea_id'] ?? null;
    $usuario_id = $_POST['usuario_id'] ?? null;

    if ($tarea_id && $usuario_id) {
        $tareaObj->asignarUsuario($tarea_id, $usuario_id);
        $_SESSION['mensaje'] = "Usuario asignado correctamente.";
    } else {
        $_SESSION['error_mensaje'] = "Error al asignar usuario.";
    }
}

$tareas = $tareaObj->obtenerTareas();
$usuarios = $usuarioObj->obtenerUsuarios();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tareas</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <h1>Gestión de Tareas</h1>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <p class="mensaje"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_mensaje'])): ?>
        <p class="error"><?php echo $_SESSION['error_mensaje']; unset($_SESSION['error_mensaje']); ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>SKU</th>
                <th>Descripción</th>
                <th>Localización Origen</th>
                <th>Localización Destino</th>
                <th>Estado</th>
                <th>Cajas a Reabastecer</th>
                <th>Usuario Asignado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tareas as $tarea): ?>
                <tr>
                    <td><?php echo $tarea['id']; ?></td>
                    <td><?php echo $tarea['sku']; ?></td>
                    <td><?php echo $tarea['descripcion']; ?></td>
                    <td><?php echo $tarea['localizacion_origen']; ?></td>
                    <td><?php echo $tarea['localizacion_destino']; ?></td>
                    <td><?php echo $tarea['estado']; ?></td>
                    <td><?php echo $tarea['cajas_reabastecer']; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="tarea_id" value="<?php echo $tarea['id']; ?>">
                            <select name="usuario_id" onchange="this.form.submit()">
                                <option value="">Sin asignar</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?php echo $usuario['id']; ?>" <?php echo ($tarea['usuario_id'] == $usuario['id']) ? 'selected' : ''; ?>>
                                        <?php echo $usuario['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="asignar_usuario" value="1">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
