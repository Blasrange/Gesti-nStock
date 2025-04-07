<?php
// Iniciar la sesión
session_start();

// Habilitar la visualización de errores (solo para desarrollo; eliminar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir las dependencias necesarias
require_once __DIR__ . '/../vendor/autoload.php'; // Asegúrate de que esta ruta sea correcta

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear un nuevo objeto de hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir los encabezados
$headers = [
    'Código',
    'LPN',
    'Localización',
    'Área Picking',
    'SKU',
    'SKU2',
    'Descripción',
    'Precio',
    'Tipo Material',
    'Categoría Material',
    'Unidades',
    'Cajas',
    'Reserva',
    'Disponible',
    'UDM',
    'Fecha Entrada',
    'Estado',
    'Lote',
    'Fecha Fabricación',
    'Fecha Vencimiento',
    'FPC',
    'Peso',
    'Serial',
];

// Agregar encabezados a la hoja
$sheet->fromArray($headers, NULL, 'A1');

// Ajustar el ancho de las columnas
foreach (range('A', 'V') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Para enviar el archivo como descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="inventarios_template.xlsx"');
header('Cache-Control: max-age=0');

// Guardar el archivo en la salida de PHP
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>