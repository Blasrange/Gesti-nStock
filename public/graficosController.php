<?php
// --- Conexión BD ---
$conexion = new mysqli("localhost", "root", "", "reabastecimiento");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// --- Fechas actuales por defecto ---
$hoy = date("Y-m-d");
$fecha_inicio = $_GET['fecha_inicio'] ?? $hoy;
$fecha_fin = $_GET['fecha_fin'] ?? $hoy;

$condicion = "";
$params = [];
$tipos = "";

if ($fecha_inicio && $fecha_fin) {
    $condicion = "WHERE DATE(h.fecha_hora) BETWEEN ? AND ?";
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
    $tipos = "ss";
}

// --- Consulta de resumen por cliente ---
$sql = "
    SELECT 
        c.nombre AS cliente,
        SUM(h.unidades) AS total_unidades,
        SUM(h.cajas) AS total_cajas,
        COUNT(DISTINCT h.sku) AS total_skus,
        GROUP_CONCAT(DISTINCT h.turno ORDER BY h.turno ASC SEPARATOR ', ') AS turnos
    FROM historial h
    LEFT JOIN clientes c ON h.cliente_id = c.id
    $condicion
    GROUP BY c.nombre
    ORDER BY c.nombre
";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

$datos = [];
while ($fila = $resultado->fetch_assoc()) {
    $datos[] = [
        'cliente'  => $fila['cliente'] ?? 'Sin nombre',
        'unidades' => (int)$fila['total_unidades'],
        'cajas'    => (int)$fila['total_cajas'],
        'skus'     => (int)$fila['total_skus'],
        'turnos'   => $fila['turnos'] ?? 'N/A'
    ];
}
$stmt->close();
$conexion->close();

$titulo = "Gráficos por Cliente";
$seccion = "Administración";
include '../templates/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gráficos por Cliente</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f7fa;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
        }
        form {
            text-align: center;
            margin-bottom: 20px;
        }
        form input, form button {
            padding: 8px 10px;
            font-size: 14px;
            margin: 0 5px;
        }
        button {
            background-color:rgb(12, 12, 12);
            border: none;
            color: white;
            border-radius: 4px;
        }
        .graficos {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .card {
            width: 320px;
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .cliente {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .info {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 8px;
        }
        canvas {
            height: 220px !important;
        }
    </style>
</head>
<body>

<h2 style="margin-bottom: 20px">Gráficos de Reabastecimiento por Cliente </h2>

<form method="GET">
    <label>Desde:</label>
    <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
    <label>Hasta:</label>
    <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
    <button type="submit">Filtrar</button>
</form>

<div style="text-align: center; font-size: 14px; margin-bottom: 20px;">
    <strong>Actualizado:</strong> <?= date("Y-m-d") ?>
</div>

<div class="graficos" id="contenedorGraficos"></div>

<script>
    const datos = <?= json_encode($datos) ?>;
    const contenedor = document.getElementById("contenedorGraficos");

    datos.forEach((item, i) => {
        const card = document.createElement("div");
        card.className = "card";
        card.innerHTML = `
            <div class="cliente">${item.cliente}</div>
            <canvas id="chart_${i}"></canvas>
            <div class="info">
                Total SKUs: ${item.skus}<br>
                Turnos: ${item.turnos}
            </div>
        `;
        contenedor.appendChild(card);

        new Chart(document.getElementById(`chart_${i}`), {
            type: 'doughnut',
            data: {
                labels: ["Unidades", "Cajas"],
                datasets: [{
                    data: [item.unidades, item.cajas],
                    backgroundColor: ['#1abc9c', '#f39c12'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold' },
                        formatter: (value) => value
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    });
</script>


</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script>
        let table = $("#tablareabastecimientos").DataTable({
                "oLanguage": {
                    "sUrl": "assets/js/datatables_es.json"
                },
                responsive: true,
                pagingType: "full_numbers",
            });
    </script>


</body>
</html>
