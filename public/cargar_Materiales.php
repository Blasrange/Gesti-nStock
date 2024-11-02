<?php
// cargar_Materiales.php

require_once '../app/db.php';
require_once '../app/maestraMateriales.php';
require_once '../vendor/autoload.php'; // Asegúrate de incluir autoload de PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Database;
use App\MaestraMateriales; // Asegúrate de que el nombre de la clase es correcto

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];

    // Cargar el archivo Excel
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Iniciar conexión a la base de datos
        $database = new Database();
        $cliente_id = $_SESSION['cliente_id'];
        $maestraMaterialesObj = new MaestraMateriales($database, $cliente_id); // Crear instancia de MaestraMateriales

        // Preparar inserciones
        foreach ($data as $key => $row) {
            if ($key > 0) { // Omitir la primera fila (encabezados)

                // Asegúrate de validar y limpiar los datos
                $sku = $row[0];
                $lpn = $row[1];
                $localizacion = $row[2];
                $descripcion = $row[3];
                $stock_minimo = intval($row[4]);
                $stock_maximo = intval($row[5]);
                $embalaje = $row[6];

                // Preparar los datos para insertar o actualizar
                $materialData = [
                    'sku' => $sku,
                    'lpn' => $lpn,
                    'localizacion' => $localizacion,
                    'descripcion' => $descripcion,
                    'stock_minimo' => $stock_minimo,
                    'stock_maximo' => $stock_maximo,
                    'embalaje' => $embalaje,
                ];

                // Verificar si el material ya existe o insertarlo
                $result = $maestraMaterialesObj->updateOrInsertMaterial($materialData);
                if (!$result['success']) {
                    // Manejo de errores
                    $_SESSION['error_message'] = 'Error al insertar o actualizar material: ' . $result['error'];
                    header('Location: maestraMaterialesController.php');
                    exit;
                }
            }
        }

        $_SESSION['success_message'] = 'Materiales cargados correctamente.';
        header('Location: maestraMaterialesController.php'); // Redirigir de vuelta
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error al cargar los materiales: ' . $e->getMessage();
        header('Location: maestraMaterialesController.php');
        exit;
    }
}
?>
