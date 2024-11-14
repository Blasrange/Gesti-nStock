<?php
// public/ReportsController.php
namespace App;
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php';
require_once '../app/Reports.php';
require_once '../app/Historial.php'; // Asegúrate de incluir el archivo de Historial
require_once '../vendor/autoload.php'; // Asegúrate de incluir el autoload de Composer

use App\Database;
use App\Reports;
use App\Historial;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$database = new Database();
$reportesObj = new Reports($database);
$historialObj = new Historial($database); // Nueva instancia de Historial

if (isset($_POST['actualizar'])) {
    // Generar reportes
    $nuevosReportes = $reportesObj->generateReports($cliente_id);

    if (empty($nuevosReportes)) {
        unset($_SESSION['reportes']);
        $_SESSION['error_message'] = "No se generaron reportes.";
    } else {
        // Actualizar reportes en la sesión
        if (isset($_SESSION['reportes'])) {
            $reportesExistentes = $_SESSION['reportes'];
            $existingReportes = [];
            foreach ($reportesExistentes as $reporte) {
                $existingReportes[$reporte['sku'] . '|' . $reporte['lpn_inventario'] . '|' . $reporte['localizacion_origen']] = $reporte;
            }
            foreach ($nuevosReportes as $nuevoReporte) {
                $key = $nuevoReporte['sku'] . '|' . $nuevoReporte['lpn_inventario'] . '|' . $nuevoReporte['localizacion_origen'];
                if (isset($existingReportes[$key])) {
                    if ($existingReportes[$key]['unidades_reabastecer'] !== $nuevoReporte['unidades_reabastecer']) {
                        $existingReportes[$key]['unidades_reabastecer'] = $nuevoReporte['unidades_reabastecer'];
                        $existingReportes[$key]['cajas_reabastecer'] = $nuevoReporte['cajas_reabastecer'];
                    }
                } else {
                    $existingReportes[$key] = $nuevoReporte;
                }
            }
            $_SESSION['reportes'] = array_values($existingReportes);
        } else {
            $_SESSION['reportes'] = $nuevosReportes;
        }
        unset($_SESSION['error_message']);
    }

    // Generar el historial a partir de los reportes
    $historialObj->generateHistorial($cliente_id); // Llamar a la función para actualizar el historial
}

if (isset($_POST['descargar_excel'])) {
    $reportes = $_SESSION['reportes'] ?? [];

    if (!empty($reportes)) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Comprobamos si el cliente_id es 1 para elegir qué columnas mostrar
        if ($cliente_id == 1) {
            // Encabezados específicos para cliente_id 1
            $sheet->setCellValue('A1', 'LTLD_LPN_SRC');
            $sheet->setCellValue('B1', 'LTLD_SKU');
            $sheet->setCellValue('C1', 'LTLD_LOT');
            $sheet->setCellValue('D1', 'LTLD_QTY');
            $sheet->setCellValue('E1', 'LTLD_LPN_DST');
            $sheet->setCellValue('F1', 'LTLD_LOCATION_DST');

            // Llenar datos específicos para cliente_id 1
            $row = 2;
            foreach ($reportes as $reporte) {
                $sheet->setCellValue('A' . $row, $reporte['lpn_inventario'] ?? '');
                $sheet->setCellValue('B' . $row, $reporte['sku']);
                $sheet->setCellValue('C' . $row, $reporte['lote'] ?? '');
                $sheet->setCellValue('D' . $row, $reporte['unidades_reabastecer'] ?? 0);
                $sheet->setCellValue('E' . $row, $reporte['lpn_max_min'] ?? '');
                $sheet->setCellValue('F' . $row, $reporte['localizacion_destino'] ?? '');
                $row++;
            }
        } else {
            // Plantilla general
            $sheet->setCellValue('A1', 'SKU');
            $sheet->setCellValue('B1', 'Descripción');
            $sheet->setCellValue('C1', 'LPN Inventario');
            $sheet->setCellValue('D1', 'Localización Origen');
            $sheet->setCellValue('E1', 'LPN Max Min');
            $sheet->setCellValue('F1', 'Localización Destino');
            $sheet->setCellValue('G1', 'Estado');
            $sheet->setCellValue('H1', 'Unidades a Reabastecer');
            $sheet->setCellValue('I1', 'Cajas a Reabastecer');

            $row = 2;
            foreach ($reportes as $reporte) {
                $sheet->setCellValue('A' . $row, $reporte['sku']);
                $sheet->setCellValue('B' . $row, $reporte['descripcion'] ?? '');
                $sheet->setCellValue('C' . $row, $reporte['lpn_inventario'] ?? '');
                $sheet->setCellValue('D' . $row, $reporte['localizacion_origen'] ?? '');
                $sheet->setCellValue('E' . $row, $reporte['lpn_max_min'] ?? '');
                $sheet->setCellValue('F' . $row, $reporte['localizacion_destino'] ?? '');
                $sheet->setCellValue('G' . $row, $reporte['estado'] ?? '');
                $sheet->setCellValue('H' . $row, $reporte['unidades_reabastecer'] ?? 0);
                $sheet->setCellValue('I' . $row, $reporte['cajas_reabastecer'] ?? 0);
                $row++;
            }
        }

        $fileName = 'reportes_cliente_' . $cliente_id . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

$reportes = $_SESSION['reportes'] ?? [];

if (empty($reportes)) {
    unset($_SESSION['reportes']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        /* Estilos personalizados */
        .btn-actualizar, .btn-descargar {
            background-color: #81c781;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            margin-right: 10px; /* Espacio entre botones */
        }
        .btn-actualizar:hover {
            background-color: #218838;
        }
        .btn-descargar {
            background-color: #81c781;
        }
        .btn-descargar:hover {
            background-color: #218838;
        }
        .form-upload {
            margin: 0;
            border: 1px solid #ccc;
            padding: 1px;
            border-radius: 5px;
            background-color: #f9f9f9;
            display: flex; /* Para alinear los botones uno al lado del otro */
            align-items: center; /* Centra los botones verticalmente */
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
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
            z-index: 1000;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        body {
            margin-top: 100px;
        }
        .total {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            text-align: center;
            padding: 1px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        .search-container {
            display: flex;
            gap: 8px; /* Ajusta el espacio entre el campo de búsqueda y el botón */
            align-items: center;
        }
        table td:nth-child(8) {
            text-align: center;
        }
        .search-input {
            padding: 8px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 200px;
        }
    </style>
    <script>
        function filterReports() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach(row => {
                const textContent = row.textContent.toLowerCase();
                row.style.display = textContent.includes(searchTerm) ? '' : 'none';
            });
        }
    </script>
</head>
<body>
    <div class="header">
        <a href="dashboard.php" style="text-decoration: none; color: black;">
            <h1>Reportes</h1>
        </a>
        <div class="search-container">
            <input type="text" id="search-input" class="search-input" placeholder="Buscar..." oninput="filterReports()">
                <form method="POST" style="display: inline;">
                <button type="submit" name="actualizar" class="btn-actualizar">Actualizar</button>
                <input type="submit" name="descargar_excel" class="btn-descargar" value="Descargar Excel">
            </form>
            
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['error_message'])): ?>
            <p class="error"><?php echo $_SESSION['error_message']; ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Descripción</th>
                    <th>LPN Inventario</th>
                    <th>Localización Origen</th>
                    <th>LPN Max Min</th>
                    <th>Localización Destino</th>
                    <th>Estado</th>
                    <th>Unidades a Reabastecer</th>
                    <th>Cajas a Reabastecer</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reportes)): ?>
                    <?php foreach ($reportes as $reporte): ?>
                        <tr>
                            <td><?php echo $reporte['sku']; ?></td>
                            <td><?php echo $reporte['descripcion'] ?? ''; ?></td>
                            <td><?php echo $reporte['lpn_inventario'] ?? ''; ?></td>
                            <td><?php echo $reporte['localizacion_origen'] ?? ''; ?></td>
                            <td><?php echo $reporte['lpn_max_min'] ?? ''; ?></td>
                            <td><?php echo $reporte['localizacion_destino'] ?? ''; ?></td>
                            <td><?php echo $reporte['estado'] ?? ''; ?></td>
                            <td><?php echo $reporte['unidades_reabastecer'] ?? 0; ?></td>
                            <td><?php echo $reporte['cajas_reabastecer'] ?? 0; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">No hay reportes disponibles.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="total">
        <p>Total de registros: <?php echo count($reportes); ?></p>
    </div>
</body>
</html>
