<?php
// cargar_inventario.php

require_once '../app/db.php';
require_once '../app/inventarios.php';
require_once '../vendor/autoload.php'; // Asegúrate de incluir autoload de PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Database;
use App\Inventarios;

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
        $inventariosObj = new Inventarios($database, $_SESSION['cliente_id']);

        // Array para almacenar los códigos del inventario cargado desde el Excel
        $newItems = [];

        // Preparar inserciones o actualizaciones
        foreach ($data as $key => $row) {
            if ($key > 0) { // Ignorar la primera fila (encabezados)
                // Validar y limpiar los datos
                $codigo = $row[0] ?? '';
                $newItems[] = $codigo;

                $lpn = $row[1] ?? '';
                $localizacion = $row[2] ?? '';
                $area_picking = $row[3] ?? '';
                $sku = $row[4] ?? '';
                $sku2 = $row[5] ?? '';
                $descripcion = $row[6] ?? '';
                $precio = !empty($row[7]) ? floatval(str_replace(',', '.', $row[7])) : 0;
                $tipo_material = $row[8] ?? '';
                $categoria_material = $row[9] ?? '';
                $unidades = !empty($row[10]) ? intval($row[10]) : 0;
                $cajas = !empty($row[11]) ? intval($row[11]) : 0;
                $reserva = !empty($row[12]) ? intval($row[12]) : 0;
                $disponible = !empty($row[13]) ? intval($row[13]) : 0;
                $udm = $row[14] ?? '';
                $embalaje = $row[15] ?? '';

                // Manejar fechas
                $fecha_entrada = !empty($row[16]) ? \DateTime::createFromFormat('d/m/Y', $row[16])->format('Y-m-d') : null;
                $estado = $row[17] ?? '';
                $lote = $row[18] ?? '';
                $fecha_fabricacion = !empty($row[19]) ? \DateTime::createFromFormat('d/m/Y', $row[19])->format('Y-m-d') : null;
                $fecha_vencimiento = !empty($row[20]) ? \DateTime::createFromFormat('d/m/Y', $row[20])->format('Y-m-d') : null;
                $fpc = !empty($row[21]) ? intval($row[21]) : 0;
                $peso = !empty($row[22]) ? floatval(str_replace(',', '.', $row[22])) : 0;
                $serial = $row[23] ?? '';
                $cliente_id = $_SESSION['cliente_id'];

                // Utilizar updateOrInsertInventory para insertar o actualizar
                $inventariosObj->updateOrInsertInventory([
                    'codigo' => $codigo,
                    'lpn' => $lpn,
                    'localizacion' => $localizacion,
                    'area_picking' => $area_picking,
                    'sku' => $sku,
                    'sku2' => $sku2,
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'tipo_material' => $tipo_material,
                    'categoria_material' => $categoria_material,
                    'unidades' => $unidades,
                    'cajas' => $cajas,
                    'reserva' => $reserva,
                    'disponible' => $disponible,
                    'udm' => $udm,
                    'embalaje' => $embalaje,
                    'fecha_entrada' => $fecha_entrada,
                    'estado' => $estado,
                    'lote' => $lote,
                    'fecha_fabricacion' => $fecha_fabricacion,
                    'fecha_vencimiento' => $fecha_vencimiento,
                    'fpc' => $fpc,
                    'peso' => $peso,
                    'serial' => $serial,
                    'cliente_id' => $cliente_id,
                ]);
            }
        }

        // Llamar a deleteMissingItems para eliminar elementos que no están en el Excel cargado
        $inventariosObj->deleteMissingItems($newItems);

        $_SESSION['success_message'] = 'Inventario cargado correctamente.';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error al cargar el inventario: ' . $e->getMessage();
    }

    header('Location: inventarioscontrolle.php'); // Redirigir de vuelta
    exit;
}
?>
