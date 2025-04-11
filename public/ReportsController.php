<?php
// public/ReportsController.php
namespace App;
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../app/db.php';
require_once '../app/Reports.php';
require_once '../app/MovimientoLogger.php';
require_once '../app/Historial.php'; // Asegúrate de incluir el archivo de Historial
require_once '../vendor/autoload.php'; // Asegúrate de incluir el autoload de Composer


use App\Database;
use App\MovimientoLogger;
use App\Reports;
use App\Historial;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Asegúrate de incluir la clase para formato de números
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$database = new Database();  // <-- Esto falta en tu código
$logger = new MovimientoLogger($database);  // Ahora sí funciona

// Instancia del logger
$database = new \App\Database();
$movimientoLogger = new MovimientoLogger($database);

if (!isset($_SESSION['user_id'], $_SESSION['cliente_id'], $_SESSION['ciudad_id'])) {
    header('Location: login.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$database = new Database();
$reportesObj = new Reports($database);
$historialObj = new Historial($database); // Nueva instancia de Historial

if (isset($_POST['actualizar'])) {

    // Generar reportes primero
    $nuevosReportes = $reportesObj->generateReports($cliente_id);

    // Obtener datos del cliente y del usuario desde la base de datos
$cliente_nombre = $database->getClienteNombreById($cliente_id);
$usuario_nombre = $database->getUsuarioNombreById($_SESSION['user_id']);

    
    foreach ($nuevosReportes as $reporte) {
        $movimientoLogger->registrarMovimiento([
            'cliente_id' => $cliente_id,
            'cliente_nombre' => $cliente_nombre,
            'usuario_id' => $_SESSION['user_id'],
            'usuario_nombre' => $usuario_nombre,
            'sku' => $reporte['sku'],
            'descripcion' => $reporte['descripcion'],
            'lpn_origen' => $reporte['lpn_inventario'],
            'localizacion_origen' => $reporte['localizacion_origen'],
            'lpn_destino' => $reporte['lpn_max_min'],
            'localizacion_destino' => $reporte['localizacion_destino'],
            'cantidad' => $reporte['cajas_reabastecer'] ?? 0,
            'lote' => $reporte['lote'],
            'tipo_movimiento' => 'Generación de reporte'
        ]);
    }
    

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
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle("Interfaz Sigware");

        // Hoja 1 - Interfaz Sigware
        $sheet1->setCellValue('A1', 'LTLD_LPN_SRC');
        $sheet1->setCellValue('B1', 'LTLD_SKU');
        $sheet1->setCellValue('C1', 'LTLD_LOT');
        $sheet1->setCellValue('D1', 'LTLD_QTY');
        $sheet1->setCellValue('E1', 'LTLD_LPN_DST');
        $sheet1->setCellValue('F1', 'LTLD_LOCATION_DST');

        $row1 = 2;
        $sheet1->getStyle('B')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        foreach ($reportes as $reporte) {
            $embalaje = $reporte['embalaje'] ?? 1;
            $cajas = $reporte['cajas_reabastecer'] ?? 0;
            $ltldQty = $cajas * $embalaje;

            $sheet1->setCellValue('A' . $row1, (string)($reporte['lpn_inventario'] ?? ''));
            $sheet1->setCellValueExplicit('B' . $row1, $reporte['sku'], DataType::TYPE_STRING);
            $sheet1->setCellValue('C' . $row1, (string)($reporte['lote'] ?? ''));
            $sheet1->setCellValue('D' . $row1, (string)($ltldQty));
            $sheet1->setCellValue('E' . $row1, (string)($reporte['lpn_max_min'] ?? ''));
            $sheet1->setCellValue('F' . $row1, (string)($reporte['localizacion_destino'] ?? ''));
            $row1++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet1->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet1->getStyle('A1:F1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        foreach (range('A', 'F') as $col) {
            $sheet1->getStyle($col . '2:' . $col . $row1)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        }

        // Hoja 2 - Informe Montacarguista
        
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle("Informe Montacarguista");

        $sheet2->setCellValue('A1', 'Ubicación Origen'); 
        $sheet2->setCellValue('B1', 'LPN Inventario');
        $sheet2->setCellValue('C1', 'SKU');
        $sheet2->setCellValue('D1', 'Descripción');
        $sheet2->setCellValue('E1', 'Lote');
        $sheet2->setCellValue('F1', 'Cajas a Reabastecer');
        $sheet2->setCellValue('G1', 'LPN Destino');
        $sheet2->setCellValue('H1', 'Ubicación Destino');
        $sheet2->setCellValue('I1', 'Estado');
        $sheet2->setCellValue('J1', 'fpc');

        

        $row2 = 2;
        $sheet2->getStyle('C')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        foreach ($reportes as $reporte) {
            $embalaje = $reporte['embalaje'] ?? 1;
            $cajas = $reporte['cajas_reabastecer'] ?? 0;
            $unidades = $embalaje * $cajas;

            $sheet2->setCellValue('A' . $row2, (string)($reporte['localizacion_origen'] ?? ''));
			$sheet2->setCellValue('B' . $row2, (string)($reporte['lpn_inventario'] ?? ''));
			$sheet2->setCellValueExplicit('C' . $row2, $reporte['sku'], DataType::TYPE_STRING);
            $sheet2->setCellValue('D' . $row2, (string)($reporte['descripcion'] ?? ''));
			$sheet2->setCellValue('E' . $row2, (string)($reporte['lote'] ?? ''));
			$sheet2->setCellValue('F' . $row2, (string)($cajas));
            $sheet2->setCellValue('G' . $row2, (string)($reporte['lpn_max_min'] ?? ''));                    
            $sheet2->setCellValue('H' . $row2, (string)($reporte['localizacion_destino'] ?? ''));
            $sheet2->setCellValue('I' . $row2, (string)($reporte['estado'] ?? ''));
            $sheet2->setCellValue('J' . $row2, (string)($reporte['fpc'] ?? ''));
            $row2++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet2->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet2->getStyle('A1:J1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        foreach (range('A', 'J') as $col) {
            $sheet2->getStyle($col . '2:' . $col . $row2)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        }

        // Descargar archivo
        $fileName = 'reportes_cliente_' . $cliente_id . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

$reportes = $_SESSION['reportes'] ?? [];

if (empty($reportes)) {
    unset($_SESSION['reportes']);
}
$titulo = "Reportes";
$seccion = "Administración";
include '../templates/header.php';
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --light-gray: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .report-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin: 20px auto;
            max-width: 98%;
        }
        
        .filter-section {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
        }
        
        .filter-title {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .movement-type-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .movement-type-btn {
            border: 2px solid #dee2e6;
            background: white;
            color: var(--secondary-color);
            border-radius: 20px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .movement-type-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .movement-type-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .report-section {
            margin-top: 25px;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .report-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
        }
        
        .report-badge {
            background-color: var(--success-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        table.dataTable {
            border-collapse: collapse !important;
            margin-top: 10px !important;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        
        .badge-movement {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .badge-entry {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-exit {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-transfer {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        @media (max-width: 768px) {
            .filter-section .col-md-3 {
                margin-bottom: 15px;
            }
            
            .movement-type-selector {
                flex-direction: column;
            }
        }

       /* Ajustes generales para la tabla */
       #tablareportes {
            width: 200% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 4px !important;  /* Espacio entre filas */
        }
        
        #tablareportes th, 
        #tablareportes td {
            padding: 10px 15px !important;
            vertical-align: middle !important;
        }
        
        /* Ajustes generales para la tabla */
        #tablareportes {
            width: 100% !important;
            table-layout: auto !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;  /* Espacio entre filas */
        }
        
        #tablareportes th, 
        #tablareportes td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            vertical-align: middle !important;
        }
        
        /* Columnas específicas */
        #tablareportes th:nth-child(2),  /* Descripción */
        #ttablareportes td:nth-child(2) {
            min-width: 250px !important;
            white-space: normal !important;
        }
        
        /* Scroll horizontal para pantallas pequeñas */
        .dataTables_scrollBody {
            overflow-x: auto !important;
        }

        /* Scroll horizontal para pantallas pequeñas */
        .dataTables_scrollBody {
            overflow-x: auto !important;
        }

        .loader {
            width: 48px;
            height: 48px;
            border: 3px solid #FFF;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        .loader::after {
            content: '';  
            box-sizing: border-box;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: 3px solid;
            border-color:rgb(10, 2, 77) transparent;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        } 

        .spinner-overlay {
            position: fixed; /* Fijo en la ventana */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Fondo semitransparente */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Asegura que esté por encima de todo */
        }
    </style>
</head>
<body>
<div class="spinner-overlay">
    <span class="loader"></span>
</div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div class="container-fluid">
                <div class="report-container">    
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <p class="error"><?php echo $_SESSION['error_message']; ?></p>
                        <?php endif; ?>
                        
                    <div style="margin-left: 20px; margin-right: 20px">                
                        <div class="table-responsive"> 
                            <div class="search-container">
                                <form method="POST" style="display: inline;">
                                <button type="submit" name="actualizar" class="btn btn-sm btn-dark btn-small">Actualizar</button>
                                <input type="submit" name="descargar_excel" class="btn btn-sm btn-dark btn-small" value="Descargar Excel">
                                </form>
                            </div>           
                            <table id="tablareportes" class="table table-striped table-hover dataTable display">                    
                            <thead>
                                <tr>
                                    <th style="text-align: center">SKU</th>
                                    <th style="text-align: center">Descripción</th>
                                    <th style="text-align: center">LPN Inventario</th>
                                    <th style="text-align: center">Ubicación Origen</th>
                                    <th style="text-align: center">LPN Destino</th>
                                    <th style="text-align: center">Ubicación Destino</th>
                                    <th style="text-align: center">Estado</th>
                                    <th style="text-align: center">lote</th>
                                    <th style="text-align: center">FPC</th>
                                    <th style="text-align: center">Unidades a Reabastecer</th>
                                    <th style="text-align: center">Cajas a Reabastecer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reportes)): ?>
                                    <?php foreach ($reportes as $reporte): ?>
                                        <tr>
                                            <td style="text-align: center"><?php echo $reporte['sku']; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['descripcion'] ?? ''; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['lpn_inventario'] ?? ''; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['localizacion_origen'] ?? ''; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['lpn_max_min'] ?? ''; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['localizacion_destino'] ?? ''; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['estado'] ?? ''; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['lote'] ?? ''; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['fpc'] ?? ''; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['unidades_reabastecer'] ?? 0; ?></td>
                                            <td style="text-align: center"><?php echo $reporte['cajas_reabastecer'] ?? 0; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
                </div>
            </div> 
    </div>

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

        $(document).ready(function() {
                $(".spinner-overlay").hide();
            });

        let table = $("#tablareportes").DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            responsive: true,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            pageLength: 10
            });
    </script>
    
</body>
</html>
