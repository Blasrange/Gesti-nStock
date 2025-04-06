<?php
// public/select_client_city.php
session_start();

require_once '../app/db.php';
require_once '../app/Auth.php';
require '../vendor/autoload.php';
use App\Database;
use App\Auth;

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$auth = new Auth($database);

$user_id = $_SESSION['user_id'];

$stmt = $database->pdo->prepare('
    SELECT c.id, c.nombre 
    FROM clientes c
    JOIN usuario_clientes uc ON c.id = uc.cliente_id
    WHERE uc.user_id = ?
    AND c.estado = "1"
');
$stmt->execute([$user_id]);
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Cliente y Ciudad</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            background: url('assets/images/Logistica.jpg') no-repeat center center/cover;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .selection-container {
            position: relative;
            z-index: 2;
            width: 350px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            margin-right: 10%;
        }

        .selection-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .selection-container label {
            display: block;
            margin: 10px 0 5px;
            color: #555;
        }

        .selection-container select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            background: white;
        }

        .selection-container button {
            width: 100%;
            padding: 10px;
            background-color: #212529;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 15px;
        }

        .selection-container button:hover {
            background-color: #424649;
        }

    </style>
    <script>
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
            </select>

            <button type="submit">Continuar</button>
        </form>
    </div>
</body>
</html>
