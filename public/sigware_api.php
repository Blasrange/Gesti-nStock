<?php

require_once '../app/db.php';

use App\Database;

class WMSOrds
{
    /**
     * Metodo para consultar el endpoint de inventario de Sigware
     * Esta funcion retorna un Array Asociativo con los datos del inventario
     *
     * @param mixed $dbnode
     * @param mixed $depotid
     * @param mixed $ownerid
     *
     * @return Array|null
     */
    public static function getInventory($dbnode, $depotid, $ownerid){
        // URL base de las Peticiones ORDS de Sigware
        $url = "http://132.145.135.92:8080/ords/api_sigware/cclcatalyst/catalystInventory";

        // Cabeceras de la Peticion
        $headers = [
            "dbnode: $dbnode",
            "depotid: $depotid",
            "ownerid: $ownerid"
        ];

        // Inicializa la sesion cURL
        $ch = curl_init();

        // Configura las opciones de cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Ejecuta la peticion cURL y obtiene la respuesta
        $response = curl_exec($ch);

        // Si hay un error en la peticion, retorna null
        if (curl_errno($ch)) {
            return null;
        }

        // Cierra la sesion cURL
        curl_close($ch);

        // Decodifica la respuesta JSON y retorna el resultado
        return json_decode($response, true);
    }
}

//Validar si se le dio click el boton de actualizar.
if (isset($_POST['actualizar_inventario'])){

    try {

        $cliente_id = intval($_POST['cliente_id']);

        // Extraer de base de datos los valores para consultar Sigware
        $database = new Database();
        $result = $database->fetchAll("SELECT nodo, deposito, propietario FROM clientes WHERE id = :id", [$cliente_id]);

        // Extraer primer posicion de la respuesta.
        $parametrosSigware = $result[0];

        // Consumir ORDS Sigware
        $data = WMSOrds::getInventory($parametrosSigware['nodo'], $parametrosSigware['deposito'], $parametrosSigware['propietario']);
        
        // Actualizar la tabla local.
        // Elimianr todo el contenido relacionado al cliente.
        $database->execute("DELETE FROM inventarios WHERE cliente_id = :id", [$cliente_id]);
        
        // Insertar los datos en la tabla.
        foreach ($data['data'] as $key => $row) {

            $database->execute("INSERT INTO inventarios (
                codigo, lpn, localizacion, area_picking, sku, sku2,
                descripcion, precio, tipo_material, categoria_material, unidades,
                cajas, reserva, disponible, udm, embalaje, fecha_entrada, estado,
                lote, fecha_fabricacion, fecha_vencimiento, fpc, peso, serial, cliente_id
            ) VALUES (
                :codigo, :lpn, :localizacion, :area_picking, :sku, :sku2,
                :descripcion, :precio, :tipo_material, :categoria_material, :unidades,
                :cajas, :reserva, :disponible, :udm, :embalaje, :fecha_entrada, :estado,
                :lote, :fecha_fabricacion, :fecha_vencimiento, :fpc, :peso, :serial, :cliente_id
            )",[
                $row['codigo'],
                $row['lpn'],
                $row['localizacion'],
                $row['area picking'],
                $row['sku'],
                $row['sku2'],
                $row['descripcion'],
                round($row['precio'], 2),
                $row['tipo de material'],
                $row['categoría de material'],
                $row['unidades'],
                $row['cajas'],
                $row['reserva'],
                $row['disponible'],
                $row['udm'],
                $row['embalaje'],
                isset($row['fecha de entrada']) ? date('Y-m-d', strtotime($row['fecha de entrada'])) : null,
                $row['estado'],
                $row['lote'],
                isset($row['fecha de fabricacion']) ? date('Y-m-d', strtotime($row['fecha de fabricacion'])) : null,
                isset($row['fecha de vencimiento']) ? date('Y-m-d', strtotime($row['fecha de vencimiento'])) : null,
                $row['fpc'],
                $row['peso'],
                $row['serial'],
                $cliente_id
            ]);
        }

        http_response_code(200);
        echo True;
        
    } catch (Exception $e) {
        
        http_response_code(400);
        echo "Caught exception: " . $e->getMessage();
    }
}