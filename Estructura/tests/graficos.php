<?php
// public/graficos.php

require_once '../app/db.php';
require_once '../app/Historial.php';
require_once '../vendor/autoload.php';

use App\Database;
use App\Historial;

$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 1;
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('first day of this month'));
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

$db = new Database();
$historial = new Historial($db);

// Obtener el historial con el filtro de fecha
$historialData = $historial->getHistorial($cliente_id, $fecha_inicio, $fecha_fin);

$totalUnidades = 0;
$totalCajas = 0;
$totalSKU = count($historialData);

foreach ($historialData as $entry) {
    $totalUnidades += (int)$entry['unidades'];
    $totalCajas += (int)$entry['cajas'];
}

// Calcular porcentajes
$totalSum = $totalUnidades + $totalCajas + $totalSKU;
$porcentajeUnidades = $totalSum > 0 ? ($totalUnidades / $totalSum) * 100 : 0;
$porcentajeCajas = $totalSum > 0 ? ($totalCajas / $totalSum) * 50 : 0;
$porcentajeSKU = $totalSum > 0 ? ($totalSKU / $totalSum) * 50 : 0;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gráfico de Historial</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
    /* Contenedor del formulario */
    form {
        display: flex; /* Cambiar a flex para disposición horizontal */
        align-items: center; /* Alinear elementos verticalmente al centro */
        margin-bottom: 20px;
        gap: 15px; /* Espacio entre los elementos */
    }

    /* Estilo de las etiquetas */
    label {
        font-weight: bold;
        color: #333; /* Color del texto */
        text-align: left; /* Alinear texto a la izquierda */
        margin-right: 10px; /* Espacio a la derecha de las etiquetas */
    }

    /* Estilo de los campos de fecha */
    input[type="date"] {
        padding: 5px;
        border: 1px solid #ccc; /* Borde del campo */
        border-radius: 5px; /* Bordes redondeados */
        width: 150px; /* Ancho del campo */
        transition: border-color 0.3s ease; /* Transición en el borde */
    }

    /* Efecto al enfocar el campo */
    input[type="date"]:focus {
        border-color: #4CAF50; /* Color de borde al enfocar */
        outline: none; /* Sin contorno */
    }

    /* Estilo del botón */
    button {
        padding: 8px 13px;
        background-color: #4CAF50; /* Color de fondo */
        color: white; /* Color del texto */
        border: none; /* Sin borde */
        border-radius: 5px; /* Bordes redondeados */
        cursor: pointer; /* Cambiar cursor al pasar */
        transition: background-color 0.3s ease; /* Transición de color */
    }

    /* Efecto al pasar el cursor sobre el botón */
    button:hover {
        background-color: #45a049; /* Color de fondo al pasar el cursor */
    }

    /* Contenedor de los gráficos */
    .chart-container {
        display: flex;
        justify-content: space-around;
        align-items: center;
        max-width: 100%;
    }
    .donut-chart {
        max-width: 250px;
        margin: 20px;
    }
    .bar-chart {
        max-width: 400px;
        margin: 20px;
    }
    .chart-legend-inline {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 10px;
    }
    </style>

</head>
<body>   
<form method="GET" action="graficos.php">
    <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
    <label for="fecha_inicio">Fecha Inicio:</label>
    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
    
    <label for="fecha_fin">Fecha Fin:</label>
    <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
    
    <button type="submit">Filtrar</button>
</form>

    <div class="chart-container">
        <!-- Gráfico de Donut -->
        <div class="donut-chart">
            <h2>Distribución de %</h2>
            <canvas id="donutChart"></canvas>
            <div id="donutLegend" class="chart-legend-inline"></div>
        </div>

        <!-- Gráfico de Barras con Totales -->
        <div class="bar-chart">
            <h2>Totales por Tipo</h2>
            <canvas id="barChart"></canvas>
        </div>
    </div>

    <script>
        const dataExists = <?php echo $totalSum > 0 ? 'true' : 'false'; ?>;

        if (dataExists) {
            // Gráfico de Donut
            const donutCtx = document.getElementById('donutChart').getContext('2d');
            const donutChart = new Chart(donutCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Unidades', 'Cajas', 'SKU'],
                    datasets: [{
                        data: [<?php echo $porcentajeUnidades; ?>, <?php echo $porcentajeCajas; ?>, <?php echo $porcentajeSKU; ?>],
                        backgroundColor: ['#4CAF50', '#36A2EB', '#FFCE56'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.label + ': ' + Math.round(tooltipItem.raw) + '%';
                                }
                            }
                        },
                        datalabels: {
                            display: false
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });

            // Crear leyenda personalizada en una línea
            const donutLegend = document.getElementById('donutLegend');
            const labels = donutChart.data.labels;
            const backgroundColors = donutChart.data.datasets[0].backgroundColor;
            labels.forEach((label, index) => {
                const legendItem = document.createElement('span');
                legendItem.style.display = 'flex';
                legendItem.style.alignItems = 'center';
                legendItem.style.marginRight = '10px';

                const colorBox = document.createElement('span');
                colorBox.style.width = '12px';
                colorBox.style.height = '12px';
                colorBox.style.backgroundColor = backgroundColors[index];
                colorBox.style.display = 'inline-block';
                colorBox.style.marginRight = '5px';

                const labelText = document.createElement('span');
                labelText.textContent = label;

                legendItem.appendChild(colorBox);
                legendItem.appendChild(labelText);
                donutLegend.appendChild(legendItem);
            });

            // Gráfico de Barras
            const barCtx = document.getElementById('barChart').getContext('2d');
            const barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Unidades', 'Cajas', 'SKU'],
                    datasets: [{
                        label: 'Total',
                        data: [<?php echo $totalUnidades; ?>, <?php echo $totalCajas; ?>, <?php echo $totalSKU; ?>],
                        backgroundColor: ['#4CAF50', '#36A2EB', '#FFCE56'],
                        borderColor: ['#4CAF50', '#36A2EB', '#FFCE56'],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            display: true,
                            anchor: 'end',
                            align: 'top',
                            color: '#000',
                            formatter: (value) => value,
                            font: {
                                size: 12,
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        } else {
            document.querySelector('.chart-container').innerHTML = "<p>No hay datos suficientes para mostrar los gráficos.</p>";
        }
    </script>
</body>
</html>
