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
$cliente_id_filtro = $_GET['cliente_id'] ?? null;

// --- Consulta para obtener todos los clientes con actividad en el período ---
$sql_clientes = "
    SELECT DISTINCT 
        c.id AS cliente_id,
        c.nombre AS cliente
    FROM historial h
    JOIN clientes c ON h.cliente_id = c.id
    WHERE DATE(h.fecha_hora) BETWEEN ? AND ?
    ORDER BY c.nombre
";

$stmt = $conexion->prepare($sql_clientes);
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$resultado_clientes = $stmt->get_result();

$clientes = [];
while ($fila = $resultado_clientes->fetch_assoc()) {
    $clientes[] = $fila;
}
$stmt->close();

// --- Obtener datos para cada indicador por cliente ---
$datos = [];

foreach ($clientes as $cliente) {
    $cliente_id = $cliente['cliente_id'];
    $nombre_cliente = $cliente['cliente'];
    
    // Si hay un filtro por cliente y este no coincide, saltar
    if ($cliente_id_filtro && $cliente_id_filtro != $cliente_id) {
        continue;
    }
    
    // 1. Cajas y unidades por turno
    $sql_turnos = "
        SELECT 
            h.turno,
            SUM(h.unidades) AS total_unidades,
            SUM(h.cajas) AS total_cajas
        FROM historial h
        WHERE h.cliente_id = ? AND DATE(h.fecha_hora) BETWEEN ? AND ?
        GROUP BY h.turno
        ORDER BY h.turno
    ";
    
    $stmt = $conexion->prepare($sql_turnos);
    $stmt->bind_param("iss", $cliente_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $resultado_turnos = $stmt->get_result();
    
    $turnos_labels = [];
    $unidades_por_turno = [];
    $cajas_por_turno = [];
    
    while ($fila = $resultado_turnos->fetch_assoc()) {
        $turnos_labels[] = $fila['turno'];
        $unidades_por_turno[] = (int)$fila['total_unidades'];
        $cajas_por_turno[] = (int)$fila['total_cajas'];
    }
    $stmt->close();
    
    // 2. Top 10 SKUs por unidades
    $sql_skus = "
        SELECT 
            h.sku,
            SUM(h.unidades) AS total_unidades
        FROM historial h
        WHERE h.cliente_id = ? AND DATE(h.fecha_hora) BETWEEN ? AND ?
        GROUP BY h.sku
        ORDER BY total_unidades DESC
        LIMIT 10
    ";
    
    $stmt = $conexion->prepare($sql_skus);
    $stmt->bind_param("iss", $cliente_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $resultado_skus = $stmt->get_result();
    
    $skus_labels = [];
    $unidades_por_sku = [];
    
    while ($fila = $resultado_skus->fetch_assoc()) {
        $skus_labels[] = $fila['sku'];
        $unidades_por_sku[] = (int)$fila['total_unidades'];
    }
    $stmt->close();
    
    // 3. Promedios diarios - Nueva consulta para obtener datos por día
    $sql_diario = "
        SELECT 
            DATE(h.fecha_hora) AS fecha,
            COUNT(DISTINCT h.sku) AS skus_dia,
            SUM(h.unidades) AS unidades_dia,
            SUM(h.cajas) AS cajas_dia
        FROM historial h
        WHERE h.cliente_id = ? AND DATE(h.fecha_hora) BETWEEN ? AND ?
        GROUP BY DATE(h.fecha_hora)
        ORDER BY DATE(h.fecha_hora)
    ";
    
    $stmt = $conexion->prepare($sql_diario);
    $stmt->bind_param("iss", $cliente_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $resultado_diario = $stmt->get_result();
    
    $fechas = [];
    $skus_diarios = [];
    $unidades_diarias = [];
    $cajas_diarias = [];
    $promedio_unidades_diarias = [];
    $promedio_cajas_diarias = [];
    
    $total_unidades = 0;
    $total_cajas = 0;
    $dias = 0;
    
    while ($fila = $resultado_diario->fetch_assoc()) {
        $fechas[] = $fila['fecha'];
        $skus_diarios[] = (int)$fila['skus_dia'];
        $unidades_diarias[] = (int)$fila['unidades_dia'];
        $cajas_diarias[] = (int)$fila['cajas_dia'];
        
        // Acumular para promedios
        $total_unidades += (int)$fila['unidades_dia'];
        $total_cajas += (int)$fila['cajas_dia'];
        $dias++;
        
        // Calcular promedio acumulado hasta este día
        $promedio_unidades_diarias[] = $total_unidades / $dias;
        $promedio_cajas_diarias[] = $total_cajas / $dias;
    }
    $stmt->close();
    
    // Calcular promedios generales
    $total_skus = array_sum($skus_diarios);
    $promedio_skus = $dias > 0 ? round($total_skus / $dias, 2) : 0;
    $promedio_unidades = $dias > 0 ? round($total_unidades / $dias, 2) : 0;
    $promedio_cajas = $dias > 0 ? round($total_cajas / $dias, 2) : 0;
    
    // 4. SKU con más movimiento por día
    $sql_sku_dia = "
        SELECT 
            DATE(h.fecha_hora) AS fecha,
            h.sku,
            SUM(h.unidades) AS total_unidades
        FROM historial h
        WHERE h.cliente_id = ? AND DATE(h.fecha_hora) BETWEEN ? AND ?
        GROUP BY DATE(h.fecha_hora), h.sku
        ORDER BY fecha, total_unidades DESC
    ";
    
    $stmt = $conexion->prepare($sql_sku_dia);
    $stmt->bind_param("iss", $cliente_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $resultado_sku_dia = $stmt->get_result();
    
    $sku_dia_data = [];
    $current_date = null;
    
    while ($fila = $resultado_sku_dia->fetch_assoc()) {
        if ($fila['fecha'] != $current_date) {
            $current_date = $fila['fecha'];
            $sku_dia_data[] = [
                'fecha' => $current_date,
                'sku' => $fila['sku'],
                'unidades' => (int)$fila['total_unidades']
            ];
        }
    }
    $stmt->close();
    
    // Preparar datos para el cliente actual
    $datos[] = [
        'cliente_id' => $cliente_id,
        'cliente' => $nombre_cliente,
        'turnos' => [
            'labels' => $turnos_labels,
            'unidades' => $unidades_por_turno,
            'cajas' => $cajas_por_turno,
            'total_unidades' => array_sum($unidades_por_turno),
            'total_cajas' => array_sum($cajas_por_turno)
        ],
        'skus' => [
            'labels' => $skus_labels,
            'unidades' => $unidades_por_sku,
            'total_skus' => count($skus_labels),
            'total_unidades' => array_sum($unidades_por_sku)
        ],
        'promedios' => [
            'skus' => $promedio_skus,
            'unidades' => $promedio_unidades,
            'cajas' => $promedio_cajas,
            'dias' => $dias,
            'total_skus' => $total_skus,
            'total_unidades' => $total_unidades,
            'total_cajas' => $total_cajas
        ],
        'diario' => [
            'fechas' => $fechas,
            'skus' => $skus_diarios,
            'unidades' => $unidades_diarias,
            'cajas' => $cajas_diarias,
            'promedio_unidades' => $promedio_unidades_diarias,
            'promedio_cajas' => $promedio_cajas_diarias
        ],
        'sku_dia' => $sku_dia_data
    ];
}

$conexion->close();

$titulo = "Indicadores por Cliente";
$seccion = "Administración";
include '../templates/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Indicadores por Cliente</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .cliente-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            padding: 20px;
        }
        .cliente-header {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .graficos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .grafico-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            min-height: 4px;
        }
        .grafico-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #34495e;
            text-align: center;
        }
        canvas {
            width: 100% !important;
            height: 300px !important;
            margin-bottom: 10px;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .info-label {
            font-weight: 500;
            color: #7f8c8d;
        }
        .info-value {
            font-weight: 600;
            color: #2c3e50;
        }
        .table-responsive {
            margin-top: 15px;
        }
        .table {
            font-size: 14px;
        }
        .no-data {
            text-align: center;
            color: #95a5a6;
            font-style: italic;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .summary-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .summary-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .summary-label {
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        .filter-form .col-form-label {
            color: #495057;
            font-weight: 500;
        }
        .cliente-selector {
            margin-left: 20px;
            min-width: 200px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1 style="margin-left: 20px">Indicadores</h1>
    
    <form class="filter-form" method="GET">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="fecha_inicio" class="col-form-label" style="margin-left: 20px">Desde:</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
            </div>
            <div class="col-auto">
                <label for="fecha_fin" class="col-form-label" style="margin-left: 20px">Hasta:</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
            </div>
            <div class="col-auto">
                <select class="form-select cliente-selector" name="cliente_id" id="cliente_id">
                    <option value="">Todos los clientes</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['cliente_id'] ?>" <?= ($cliente_id_filtro == $cliente['cliente_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cliente['cliente']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto" style="margin-left: 20px">
                <button type="submit" class="btn btn-dark">Filtrar</button>
            </div>
        </div>
    </form>   
</div>

<?php if (empty($datos)): ?>
    <div class="alert alert-warning">
        No se encontraron datos para el período seleccionado.
    </div>
<?php else: ?>
    <?php foreach ($datos as $cliente): ?>
        <div class="cliente-container">
            <div style="text-align: center" class="cliente-header"><?= htmlspecialchars($cliente['cliente']) ?></div>
            
            <div class="graficos-grid">
                <!-- Gráfico 1: Cajas y unidades por turno -->
                <div class="grafico-card">
                    <div class="grafico-title">1. Distribución por Turno</div>
                    <?php if (!empty($cliente['turnos']['labels'])): ?>
                        <canvas id="chart_turnos_<?= $cliente['cliente_id'] ?>"></canvas>
                        <div class="info-box">
                            <div class="info-item">
                                <span class="info-label">Total Unidades:</span>
                                <span class="info-value"><?= number_format($cliente['turnos']['total_unidades']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Total Cajas:</span>
                                <span class="info-value"><?= number_format($cliente['turnos']['total_cajas']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Turnos con actividad:</span>
                                <span class="info-value"><?= count($cliente['turnos']['labels']) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">No hay datos de turnos para este período</div>
                    <?php endif; ?>
                </div>
                
                <!-- Gráfico 2: Top 10 SKUs por unidades -->
                <div class="grafico-card">
                    <div class="grafico-title">2. Top 10 SKUs por Unidades</div>
                    <?php if (!empty($cliente['skus']['labels'])): ?>
                        <canvas id="chart_skus_<?= $cliente['cliente_id'] ?>"></canvas>
                        <div class="info-box">
                            <div class="info-item">
                                <span class="info-label">SKUs distintos:</span>
                                <span class="info-value"><?= $cliente['promedios']['total_skus'] ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Total Unidades:</span>
                                <span class="info-value"><?= number_format($cliente['promedios']['total_unidades']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Top 10 SKUs:</span>
                                <span class="info-value"><?= count($cliente['skus']['labels']) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">No hay datos de SKUs para este período</div>
                    <?php endif; ?>
                </div>
                
                <!-- Gráfico 3: Promedios diarios -->
                <div class="grafico-card">
                    <div class="grafico-title">3. Promedio Acumulado Diario (Unidades y Cajas)</div>
                    <?php if (!empty($cliente['diario']['fechas'])): ?>
                        <canvas id="chart_diario_<?= $cliente['cliente_id'] ?>"></canvas>
                        <div class="info-box">
                            <div class="info-item">
                                <span class="info-label">Días con actividad:</span>
                                <span class="info-value"><?= $cliente['promedios']['dias'] ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Promedio SKUs/día:</span>
                                <span class="info-value"><?= $cliente['promedios']['skus'] ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Promedio Unidades/día:</span>
                                <span class="info-value"><?= number_format($cliente['promedios']['unidades'], 2) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Promedio Cajas/día:</span>
                                <span class="info-value"><?= number_format($cliente['promedios']['cajas'], 2) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">No hay datos diarios para este período</div>
                    <?php endif; ?>
                </div>
                
                <!-- Tabla 4: SKU con más movimiento por día -->
                <div class="grafico-card">
                    <div style="text-align: center" class="grafico-title">4. SKU con Más Movimiento por Día</div>
                    <?php if (!empty($cliente['sku_dia'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="text-align: center">Fecha</th>
                                        <th style="text-align: center">SKU</th>
                                        <th style="text-align: center">Unidades</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cliente['sku_dia'] as $item): ?>
                                        <tr>
                                            <td style="text-align: center"><?= htmlspecialchars($item['fecha']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($item['sku']) ?></td>
                                            <td style="text-align: center"><?= number_format($item['unidades']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">No hay datos diarios para este período</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Registrar el plugin de datalabels
    Chart.register(ChartDataLabels);
    
    const datos = <?= json_encode($datos) ?>;
    
    datos.forEach(cliente => {
        // 1. Gráfico de barras para turnos
        if (cliente.turnos.labels && cliente.turnos.labels.length > 0) {
            const ctxTurnos = document.getElementById(`chart_turnos_${cliente.cliente_id}`).getContext('2d');
            new Chart(ctxTurnos, {
                type: 'bar',
                data: {
                    labels: cliente.turnos.labels,
                    datasets: [
                        {
                            label: 'Unidades',
                            data: cliente.turnos.unidades,
                            backgroundColor: '#3498db',
                            borderColor: '#2980b9',
                            borderWidth: 1
                        },
                        {
                            label: 'Cajas',
                            data: cliente.turnos.cajas,
                            backgroundColor: '#e74c3c',
                            borderColor: '#c0392b',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: (value) => value.toLocaleString(),
                            color: '#000',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
        
        // 2. Gráfico de barras horizontales para SKUs
        if (cliente.skus.labels && cliente.skus.labels.length > 0) {
            const ctxSkus = document.getElementById(`chart_skus_${cliente.cliente_id}`).getContext('2d');
            new Chart(ctxSkus, {
                type: 'bar',
                data: {
                    labels: cliente.skus.labels,
                    datasets: [{
                        label: 'Unidades',
                        data: cliente.skus.unidades,
                        backgroundColor: '#2ecc71',
                        borderColor: '#27ae60',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'left',
                            formatter: (value) => value.toLocaleString(),
                            color: '#000',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
        
        // 3. Gráfico de línea para promedios diarios
        if (cliente.diario.fechas && cliente.diario.fechas.length > 0) {
            const ctxDiario = document.getElementById(`chart_diario_${cliente.cliente_id}`).getContext('2d');
            
            // Formatear fechas para mostrarlas mejor
            const fechasFormateadas = cliente.diario.fechas.map(fecha => {
                const d = new Date(fecha);
                return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
            });
            
            new Chart(ctxDiario, {
                type: 'line',
                data: {
                    labels: fechasFormateadas,
                    datasets: [
                        {
                            label: 'Promedio Unidades',
                            data: cliente.diario.promedio_unidades,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 2,
                            tension: 0.1,
                            yAxisID: 'y',
                            borderDash: [5, 5],
                            pointRadius: 3
                        },
                        {
                            label: 'Promedio Cajas',
                            data: cliente.diario.promedio_cajas,
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            borderWidth: 2,
                            tension: 0.1,
                            yAxisID: 'y1',
                            borderDash: [5, 5],
                            pointRadius: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y.toFixed(2);
                                    return label;
                                }
                            }
                        },
                        datalabels: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Promedio Acumulado por Día',
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Promedio Unidades'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Promedio Cajas'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>