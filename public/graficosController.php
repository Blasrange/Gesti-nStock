<?php
// --- Configuración y Conexión BD ---
require_once '../app/db.php';

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Fechas actuales por defecto ---
$hoy = date("Y-m-d");
$primerDiaMes = date("Y-m-01"); // Primer día del mes actual

$fecha_inicio = $_GET['fecha_inicio'] ?? $primerDiaMes; // Si no se envía fecha_inicio, usa el 1er día del mes
$fecha_fin = $_GET['fecha_fin'] ?? $hoy; // Si no se envía fecha_fin, usa hoy
$cliente_id_filtro = $_GET['cliente_id'] ?? null;

try {
    $conexion = new mysqli("localhost", "root", "", "reabastecimiento");
    $conexion->set_charset("utf8mb4");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }

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
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conexion->error);
    }
    
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
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$titulo = "Indicadores por Cliente";
$seccion = "Administración";
include '../templates/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores por Cliente</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --color-primary: #3498db;
            --color-secondary:rgb(46, 67, 204);
            --color-danger: #e74c3c;
            --color-warning: #f39c12;
            --color-dark: #2c3e50;
            --color-light: #ecf0f1;
            --color-gray: #95a5a6;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
            color: var(--color-dark);
            line-height: 1.6;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }
        
        h1 {
            color: var(--color-dark);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .cliente-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
            padding: 20px;
            transition: var(--transition);
        }
        
        .cliente-container:hover {
            box-shadow: var(--shadow-md);
        }
        
        .cliente-header {
            font-size: 1.5rem;
            color: var(--color-dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            text-align: center;
        }
        
        .graficos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .graficos-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .grafico-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            min-height: 100px;
            display: flex;
            flex-direction: column;
        }
        
        .grafico-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--color-dark);
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .chart-container {
            flex: 1;
            position: relative;
            min-height: 300px;
        }
        
        canvas {
            width: 100% !important;
            height: 100% !important;
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
            color: var(--color-gray);
        }
        
        .info-value {
            font-weight: 600;
            color: var(--color-dark);
        }
        
        .table-responsive {
            margin-top: 15px;
            flex: 1;
        }
        
        .table {
            font-size: 14px;
        }
        
        .table th {
            background-color: var(--color-light);
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            color: var(--color-gray);
            font-style: italic;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 6px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .filter-form {
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: var(--border-radius);
        }
        
        .btn-dark {
            background-color: var(--color-dark);
            border-color: var(--color-dark);
        }
        
        .btn-dark:hover {
            background-color: #1a252f;
            border-color: #1a252f;
        }
        
        .form-control, .form-select {
            border-radius: 4px;
            border: 1px solid #ced4da;
            padding: 8px 12px;
        }
        
        .alert {
            border-radius: var(--border-radius);
        }
        
        /* Mejoras para el gráfico de promedios */
        .promedio-value {
            position: absolute;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Indicadores de Reabastecimiento</h1>
    <form class="filter-form" method="GET" style="margin: 0;">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="fecha_inicio" class="col-form-label">Desde:</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
            </div>
            <div class="col-auto">
                <label for="fecha_fin" class="col-form-label">Hasta:</label>
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
            <div class="col-auto">
                <button type="submit" class="btn btn-dark">
                    <i class="fas fa-filter me-2"></i>Filtrar
                </button>
            </div>
        </div>
    </form>   
</div>

<?php if (empty($datos)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        No se encontraron datos para el período seleccionado.
    </div>
<?php else: ?>
    <?php foreach ($datos as $cliente): ?>
        <div class="cliente-container">
            <div class="cliente-header">
                <i class="fas fa-user me-2"></i><?= htmlspecialchars($cliente['cliente']) ?>
            </div>
            
            <!--div class="row mb-4">
                <div class="col-md-4">
                    <div class="card summary-card h-100">
                        <div class="card-body text-center">
                            <div class="summary-value text-primary">
                                <?= number_format($cliente['turnos']['total_unidades']) ?>
                            </div>
                            <div class="summary-label">Total Unidades</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card h-100">
                        <div class="card-body text-center">
                            <div class="summary-value text-danger">
                                <?= number_format($cliente['turnos']['total_cajas']) ?>
                            </div>
                            <div class="summary-label">Total Cajas</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card h-100">
                        <div class="card-body text-center">
                            <div class="summary-value text-success">
                                <?= number_format($cliente['promedios']['dias']) ?>
                            </div>
                            <div class="summary-label">Días con actividad</div>
                        </div>
                    </div>
                </div>
            </div -->
            
            <div class="graficos-grid">
                <!-- Gráfico 1: Cajas y unidades por turno -->
                <div class="grafico-card">
                    <div class="grafico-title">
                        <i class="fas fa-clock me-2"></i>Distribución por Turno
                    </div>
                    <?php if (!empty($cliente['turnos']['labels'])): ?>
                        <div class="chart-container">
                            <canvas id="chart_turnos_<?= $cliente['cliente_id'] ?>"></canvas>
                        </div>
                        <div class="info-box">
                            <div class="info-item">
                                <span class="info-label">Turnos con actividad:</span>
                                <span class="info-value"><?= count($cliente['turnos']['labels']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Unidades por turno (promedio):</span>
                                <span class="info-value"><?= number_format($cliente['turnos']['total_unidades'] / count($cliente['turnos']['labels']), 2) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Cajas por turno (promedio):</span>
                                <span class="info-value"><?= number_format($cliente['turnos']['total_cajas'] / count($cliente['turnos']['labels']), 2) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">No hay datos de turnos para este período</div>
                    <?php endif; ?>
                </div>
                
                <!-- Gráfico 2: Top 10 SKUs por unidades -->
                <div class="grafico-card">
                    <div class="grafico-title">
                        <i class="fas fa-barcode me-2"></i>Top 10 SKUs por Unidades
                    </div>
                    <?php if (!empty($cliente['skus']['labels'])): ?>
                        <div class="chart-container">
                            <canvas id="chart_skus_<?= $cliente['cliente_id'] ?>"></canvas>
                        </div>
                        <div class="info-box">
                            <div class="info-item">
                                <span class="info-label">SKUs distintos:</span>
                                <span class="info-value"><?= $cliente['promedios']['total_skus'] ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Unidades por SKU (promedio):</span>
                                <span class="info-value"><?= number_format($cliente['promedios']['total_unidades'] / $cliente['promedios']['total_skus'], 2) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Top 10 representa:</span>
                                <span class="info-value"><?= number_format(array_sum($cliente['skus']['unidades']) / $cliente['promedios']['total_unidades'] * 100, 2) ?>%</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">No hay datos de SKUs para este período</div>
                    <?php endif; ?>
                </div>
                
                <!-- Gráfico 3: Promedios diarios -->
                <div class="grafico-card">
                    <div class="grafico-title">
                        <i class="fas fa-chart-line me-2"></i>Promedio Acumulado Diario
                    </div>
                    <?php if (!empty($cliente['diario']['fechas'])): ?>
                        <div class="chart-container">
                            <canvas id="chart_diario_<?= $cliente['cliente_id'] ?>"></canvas>
                        </div>
                        <div class="info-box">
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
                    <div class="grafico-title">
                        <i class="fas fa-star me-2"></i>SKU Destacado por Día
                    </div>
                    <?php if (!empty($cliente['sku_dia'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>SKU</th>
                                        <th class="text-end">Unidades</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cliente['sku_dia'] as $item): ?>
                                        <tr>
                                            <td style="text-align: center"><?= htmlspecialchars($item['fecha']) ?></td>
                                            <td style="text-align: center"><?= htmlspecialchars($item['sku']) ?></td>
                                            <td style="text-align: center" class="text-end"><?= number_format($item['unidades']) ?></td>
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
    
    // Función para formatear números
    const formatNumber = (num) => num.toLocaleString('es-ES');
    
    // Función para formatear fechas
    const formatDate = (dateStr) => {
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
    };
    
    datos.forEach(cliente => {
        // 1. Gráfico de barras para turnos
        if (cliente.turnos.labels?.length > 0) {
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
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'Cajas',
                            data: cliente.turnos.cajas,
                            backgroundColor: '#e74c3c',
                            borderColor: '#c0392b',
                            borderWidth: 1,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: formatNumber
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: (context) => {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    label += formatNumber(context.raw);
                                    return label;
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: formatNumber,
                            color: '#000',
                            font: {
                                weight: 'bold',
                                size: 10
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
        
        // 2. Gráfico de barras horizontales para SKUs
        if (cliente.skus.labels?.length > 0) {
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
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: formatNumber
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    label += formatNumber(context.raw);
                                    return label;
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'left',
                            formatter: formatNumber,
                            color: '#000',
                            font: {
                                weight: 'bold',
                                size: 10
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
        
        // 3. Gráfico de línea para promedios diarios
        if (cliente.diario.fechas?.length > 0) {
            const ctxDiario = document.getElementById(`chart_diario_${cliente.cliente_id}`).getContext('2d');
            
            // Configuración del gráfico
            const chartDiario = new Chart(ctxDiario, {
                type: 'line',
                data: {
                    labels: cliente.diario.fechas.map(formatDate),
                    datasets: [
                        {
                            label: 'Promedio Unidades',
                            data: cliente.diario.promedio_unidades,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 2,
                            tension: 0.1,
                            yAxisID: 'y',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Promedio Cajas',
                            data: cliente.diario.promedio_cajas,
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            borderWidth: 2,
                            tension: 0.1,
                            yAxisID: 'y1',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#fff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    label += context.parsed.y.toFixed(2);
                                    return label;
                                }
                            }
                        },
                        datalabels: {
                            display: false
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Promedio Unidades',
                                color: '#3498db'
                            },
                            ticks: {
                                color: '#3498db'
                            },
                            grid: {
                                color: 'rgba(52, 152, 219, 0.1)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Promedio Cajas',
                                color: '#e74c3c'
                            },
                            ticks: {
                                color: '#e74c3c'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
            
            // Agregar valores a las barras del gráfico de promedios
            const addAverageValuesToChart = () => {
                // Limpiar valores anteriores
                document.querySelectorAll(`#chart_diario_${cliente.cliente_id} .promedio-value`).forEach(el => el.remove());
                
                // Obtener los metadatos del gráfico
                const meta = chartDiario.getDatasetMeta(0);
                
                // Agregar valores solo si el gráfico está visible
                if (meta.visible) {
                    meta.data.forEach((bar, index) => {
                        const value = cliente.diario.promedio_unidades[index];
                        const {x, y} = bar;
                        
                        const valueElement = document.createElement('div');
                        valueElement.className = 'promedio-value';
                        valueElement.style.left = `${x}px`;
                        valueElement.style.top = `${y}px`;
                        valueElement.textContent = value.toFixed(2);
                        
                        document.getElementById(`chart_diario_${cliente.cliente_id}`).appendChild(valueElement);
                    });
                }
                
                // Hacer lo mismo para el segundo dataset (cajas)
                const metaCajas = chartDiario.getDatasetMeta(1);
                if (metaCajas.visible) {
                    metaCajas.data.forEach((bar, index) => {
                        const value = cliente.diario.promedio_cajas[index];
                        const {x, y} = bar;
                        
                        const valueElement = document.createElement('div');
                        valueElement.className = 'promedio-value';
                        valueElement.style.left = `${x}px`;
                        valueElement.style.top = `${y}px`;
                        valueElement.textContent = value.toFixed(2);
                        
                        document.getElementById(`chart_diario_${cliente.cliente_id}`).appendChild(valueElement);
                    });
                }
            };
            
            // Agregar valores iniciales
            addAverageValuesToChart();
            
            // Actualizar valores cuando cambie el tamaño de la ventana
            window.addEventListener('resize', addAverageValuesToChart);
            
            // Actualizar valores cuando se haga hover o click
            chartDiario.options.animation.onComplete = addAverageValuesToChart;
        }
    });
});
</script>

    
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">

    <script>
        let table = $("#tablagraficos").DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
                responsive: true,
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                pageLength: 10
            });
    </script>

</script>