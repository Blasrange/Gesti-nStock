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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Asegúrate de incluir la clase para formato de números

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
        // Todos los clientes tendrán la misma estructura que cliente_id 1
        $sheet->setCellValue('A1', 'LTLD_LPN_SRC');
        $sheet->setCellValue('B1', 'LTLD_SKU');
        $sheet->setCellValue('C1', 'LTLD_LOT');
        $sheet->setCellValue('D1', 'LTLD_QTY');
        $sheet->setCellValue('E1', 'LTLD_LPN_DST');
        $sheet->setCellValue('F1', 'LTLD_LOCATION_DST');

        // Llenar datos
        $row = 2;
        foreach ($reportes as $reporte) {
            // Asumimos que el "Embalaje" está en el reporte, por ejemplo $reporte['embalaje']
            $embalaje = $reporte['embalaje'] ?? 1; // Valor por defecto 1 si no se encuentra el embalaje
            $cajasReabastecer = $reporte['cajas_reabastecer'] ?? 0;
            $ltldQty = $cajasReabastecer * $embalaje; // Cálculo de LTLD_QTY

            // Asignar valores de celdas
            $sheet->setCellValue('A' . $row, (string)($reporte['lpn_inventario'] ?? ''));
            $sheet->setCellValue('B' . $row, (string)($reporte['sku']));
            $sheet->setCellValue('C' . $row, (string)($reporte['lote'] ?? ''));
            $sheet->setCellValue('D' . $row, (string)($ltldQty)); // Establecer el valor calculado como texto
            $sheet->setCellValue('E' . $row, (string)($reporte['lpn_max_min'] ?? ''));
            $sheet->setCellValue('F' . $row, (string)($reporte['localizacion_destino'] ?? ''));
            $row++;
        }

        // Formato para mejorar la presentación (por ejemplo, ajuste de columnas)
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Establecer estilo de la hoja (borde, negrita, etc.)
        $sheet->getStyle('A1:F1')->getFont()->setBold(true); // Fila de encabezado en negrita
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Centrado de texto

        // Aplicar formato de texto a todas las celdas con datos
        foreach (range('A', 'F') as $col) {
            $sheet->getStyle($col . '2:' . $col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        }

        $fileName = 'reportes_cliente_' . $cliente_id . '.xls'; // Cambiar la extensión a .xls
        header('Content-Type: application/vnd.ms-excel'); // Para Excel 97-2003
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet); // Cambiar a Writer de Excel 97-2003
        $writer->save('php://output');
        exit;
    }
}

$reportes = $_SESSION['reportes'] ?? [];

if (empty($reportes)) {
    unset($_SESSION['reportes']);
}
$titulo = "Reportes";
include '../templates/header.php';
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.12.1/css/all.css" crossorigin="anonymous">
    <style>
        .btn-back {
            display: inline-block;
            background-color: #1e3765            ;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-back:hover {
            background-color: #1e3765;
            transform: scale(1.05);
        }

        .form-upload {
            margin: 0px 0; /* Espaciado */
            border: 1px solid #ccc; /* Borde */
            padding: 1px; /* Espaciado interno */
            border-radius: 5px; /* Bordes redondeados */
            background-color: #f9f9f9; /* Fondo */
        }

        .total {
            font-weight: bold;
            margin-top: 20px;
        }

        /* Estilo para alinear el título y el formulario */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white; /* Fondo blanco */
        }

        .header h1 {
            margin: 0;
        }

        /*.error-message {
            color: red;
            font-weight: bold;
            margin: 10px 0;
            padding: 10px;
            border: 2px solid red;
            background-color: #ffe6e6; /* Fondo claro para destacar el error */
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 98%;
            background-color: white;
            z-index: 1000; /* Asegúrate de que esté sobre otros elementos */
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Añadir sombra para destacar */
        }

        .total {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa;
            text-align: center;
            padding: 3px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000; /* Asegúrate de que esté sobre otros elementos */
        }

        .search-container {
            text-align: right;
        }
        .search-input {
            padding: 8px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 200px;
            margin-left: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            position: relative;
            top: 0;
            left: 0;
            width: 98%;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }       

       
    </style>
</head>
<body>
        <?php if (isset($_SESSION['error_message'])): ?>
            <p class="error"><?php echo $_SESSION['error_message']; ?></p>
        <?php endif; ?>
        
    <div style="margin-left: 20px; margin-right: 20px">                
        <div class="table-responsive"> 
            <div class="search-container">
                <form method="POST" style="display: inline;">
                <button type="submit" name="actualizar" class="btn btn-dark btn-small">Actualizar</button>
                <input type="submit" name="descargar_excel" class="btn btn-dark btn-small" value="Descargar Excel">
                </form>
            </div>           
            <table id="tablareportes" class="table table-striped table-hover dataTable display">                    
            <thead>
                <tr>
                    <th style="text-align: center">SKU</th>
                    <th style="text-align: center">Descripción</th>
                    <th style="text-align: center">LPN Inventario</th>
                    <th style="text-align: center">Localización Origen</th>
                    <th style="text-align: center">LPN Max Min</th>
                    <th style="text-align: center">Localización Destino</th>
                    <th style="text-align: center">Estado</th>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script>
        let table = $("#tablareportes").DataTable({
                "oLanguage": {
                    "sUrl": "assets/js/datatables_es.json"
                },
                responsive: true,
                pagingType: "full_numbers",
            });
    </script>
    
</body>
</html>
