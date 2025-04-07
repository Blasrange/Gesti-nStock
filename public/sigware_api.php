<?php

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

        // Configura el timeout de la peticion
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

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

// // 🔹 InventoryCatalyst
//  $response1 = callInventoryEndpoint(
//      "SIGWARE.CALSOUTHWESTZONE.CCLCLOUD.CO",
//      "70",
//      "86"
//  );
//  print_r($response1['data']);

// // // 🔹 InventorySabama
// $response2 = callInventoryEndpoint(
//  "SIGWARE.CALCENTRALZONE.CCLCLOUD.CO",
//    "75",
//     "93"
//  );
//  print_r($response2['data']);

// // // 🔹 InventoryMondelezTAT
//  $response3 = callInventoryEndpoint(
//      "SIGWARE.CALNORTHWESTZONE.CCLCLOUD.CO",
//     "78",
//     "105"
//  );
//  print_r($response3['data']);

// // // 🔹 InventoryRecamier
//  $response4 = callInventoryEndpoint(
//   "SIGWARE.RECAMIER.CCLCLOUD.CO",
//  "73",
//  "154"
//  );
// print_r($response4['data']);
