<?php
// public/select_client_city.php
session_start();

require_once '../app/db.php'; // Asegúrate de que la ruta sea correcta
require_once '../app/Auth.php';
require '../vendor/autoload.php'; // Autoload de Composer
use App\Database;
use App\Auth;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$auth = new Auth($database);

// Obtener el ID del usuario
$user_id = $_SESSION['user_id'];

// Consultar clientes asociados al usuario
$stmt = $database->pdo->prepare('
    SELECT c.id, c.nombre 
    FROM clientes c
    JOIN usuario_clientes uc ON c.id = uc.cliente_id
    WHERE uc.user_id = ?
');
$stmt->execute([$user_id]);
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar Cliente y Ciudad</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
    /* Estilos básicos para el formulario */
    body {
        font-family: Arial, sans-serif;
        background-color: #eef2f3;
        background-image: url('assets/images/Logistica.jpg');
        background-size: cover;
        background-position: center;
        display: flex;
        height: 100vh;
        justify-content: center;
        align-items: center;
    }

    .selection-container {
        background-color: rgba(255, 255, 255, 0.9); /* Fondo blanco semitransparente */
        padding: 20px 30px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        width: 400px;
    }
    .selection-container h2 {
        margin-bottom: 20px;
        text-align: center;
        color: #333;
    }
    .selection-container label {
        display: block;
        margin-top: 10px;
        color: #555;
    }
    .selection-container select {
        width: 100%;
        padding: 8px 10px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .selection-container button {
        margin-top: 15px;
        width: 100%;
        padding: 10px;
        background-color: #5cb85c;
        border: none;
        color: #fff;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    .selection-container button:hover {
        background-color: #4cae4c;
    }
</style>

    <script>
        // Función para cargar ciudades usando AJAX
        function loadCities() {
            var clienteId = document.getElementById("cliente").value;
            var ciudadSelect = document.getElementById("ciudad");

            if (clienteId) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "get_cities.php?cliente_id=" + clienteId, true);
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        var ciudades = JSON.parse(xhr.responseText);
                        ciudadSelect.innerHTML = '<option value="">-- Seleccione una Ciudad --</option>';
                        ciudades.forEach(function (ciudad) {
                            var option = document.createElement("option");
                            option.value = ciudad.id;
                            option.textContent = ciudad.nombre;
                            ciudadSelect.appendChild(option);
                        });
                    }
                };
                xhr.send();
            } else {
                ciudadSelect.innerHTML = '<option value="">-- Seleccione una Ciudad --</option>';
            }
        }
    </script>
</head>
<body>
    <div class="selection-container">
        <h2>Seleccionar Cliente y Ciudad</h2>
        <form action="process_client_city.php" method="POST">
            <label for="cliente">Cliente:</label>
            <select id="cliente" name="cliente_id" required onchange="loadCities()">
                <option value="">-- Seleccione un Cliente --</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?php echo $cliente['id']; ?>">
                        <?php echo htmlspecialchars($cliente['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="ciudad">Ciudad:</label>
            <select id="ciudad" name="ciudad_id" required>
                <option value="">-- Seleccione una Ciudad --</option>
                <!-- Las ciudades se cargarán dinámicamente -->
            </select>

            <button type="submit">Continuar</button>
        </form>
    </div>
</body>
</html>
