<?php
// app/Reports.php
namespace App;

class Reports {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
        // Establecer la zona horaria a Colombia
        date_default_timezone_set('America/Bogota');
    }
    
    // Función para generar los reportes
    public function generateReports($cliente_id) {
        $replenishments = $this->getReplenishmentsByClient($cliente_id);

        // Si no hay reabastecimientos, eliminar reportes existentes
        if (empty($replenishments)) {
            $this->deleteAllReports($cliente_id);
            return [];
        }

        $reportesGenerados = [];
        $existingSKUs = []; // Para mantener el seguimiento de los SKUs existentes

        foreach ($replenishments as $replenishment) {
            $unidadesReabastecer = $replenishment['unidades_reabastecer'] ?? 0;
            $embalaje = $replenishment['embalaje']; // Obtener embalaje desde reabastecimientos
            $cajasReabastecer = $embalaje > 0 ? ceil($unidadesReabastecer / $embalaje) : 0;
            $createdAt = date('Y-m-d H:i:s');

            try {
                // Guardar el SKU existente
                $existingSKUs[] = $replenishment['sku'];

                // Inserción/Actualización de reporte con el valor correcto de cajas_reabastecer
                $result = $this->db->execute(
                    "INSERT INTO reportes (sku, descripcion, lpn_inventario, localizacion_origen, lpn_max_min, localizacion_destino, estado, unidades_reabastecer, cajas_reabastecer, cliente_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    descripcion = VALUES(descripcion),
                    localizacion_origen = VALUES(localizacion_origen),
                    lpn_max_min = VALUES(lpn_max_min),
                    localizacion_destino = VALUES(localizacion_destino),
                    estado = VALUES(estado),
                    unidades_reabastecer = VALUES(unidades_reabastecer),
                    cajas_reabastecer = VALUES(cajas_reabastecer),
                    created_at = VALUES(created_at)",
                    [
                        $replenishment['sku'],
                        $replenishment['descripcion'],
                        $replenishment['lpn_inventario'],
                        $replenishment['localizacion_origen'],
                        $replenishment['lpn_max_min'],
                        $replenishment['localizacion_destino'],
                        $replenishment['estado'],
                        $unidadesReabastecer,
                        $cajasReabastecer,
                        $cliente_id,
                        $createdAt
                    ]
                );

                if ($result) {
                    $this->log("Reporte generado/actualizado para SKU: " . $replenishment['sku']);
                    $reportesGenerados[] = $replenishment;
                }
            } catch (\Exception $e) {
                $this->log("Error al insertar/actualizar reporte para SKU: " . $replenishment['sku'] . ". Error: " . $e->getMessage());
            }
        }

        // Eliminar reportes que no están en la nueva actualización
        $this->deleteObsoleteReports($existingSKUs, $cliente_id);

        return $reportesGenerados;
    }

    private function deleteObsoleteReports(array $existingSKUs, $cliente_id) {
        $placeholders = implode(',', array_fill(0, count($existingSKUs), '?'));
        $query = "DELETE FROM reportes WHERE cliente_id = ? AND sku NOT IN ($placeholders)";
        $this->db->execute($query, array_merge([$cliente_id], $existingSKUs));
    }

    private function deleteAllReports($cliente_id) {
        $query = "DELETE FROM reportes WHERE cliente_id = ?";
        $this->db->execute($query, [$cliente_id]);
    }

    private function getReplenishmentsByClient($cliente_id) {
        $query = "SELECT r.id, 
                         r.sku, 
                         r.descripcion, 
                         r.lpn_inventario, 
                         r.localizacion_origen, 
                         r.lpn_max_min, 
                         r.localizacion_destino, 
                         r.estado, 
                         r.unidades_reabastecer, 
                         r.embalaje, 
                         IFNULL(rep.cajas_reabastecer, 0) AS cajas_reabastecer, 
                         r.created_at, 
                         r.cliente_id
                  FROM reabastecimientos r
                  LEFT JOIN reportes rep ON r.sku = rep.sku
                      AND r.localizacion_origen = rep.localizacion_origen
                      AND r.lpn_inventario = rep.lpn_inventario
                  WHERE r.cliente_id = ?";
        return $this->db->fetchAll($query, [$cliente_id]);
    }

    private function log($message) {
        error_log($message, 3, __DIR__ . '/../logs/report.log');
    }
}
