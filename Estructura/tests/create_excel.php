<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear nuevo objeto de hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Hello World !');

// Guardar el archivo
$writer = new Xlsx($spreadsheet);
$writer->save('hello_world.xlsx');

echo "Archivo hello_world.xlsx creado correctamente.";
