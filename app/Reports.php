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

        if (empty($replenishments)) {
            $this->deleteAllReports($cliente_id);
            return [];
        }

        $reportesGenerados = [];
        $existingSKUs = [];

        foreach ($replenishments as $replenishment) {
            $unidadesReabastecer = $replenishment['unidades_reabastecer'] ?? 0;
            $embalaje = $replenishment['embalaje'] ?? 1;

            // Cálculo de cajas
            $cajasReabastecer = $embalaje > 0 ? ceil($unidadesReabastecer / $embalaje) : 0;
            $createdAt = date('Y-m-d H:i:s');

            try {
                $existingSKUs[] = $replenishment['sku'];

                // Guardar el reporte con cajas
                $result = $this->db->execute(
                    "INSERT INTO reportes (sku, descripcion, lpn_inventario, localizacion_origen, lote, lpn_max_min, localizacion_destino, estado, fpc, unidades_reabastecer, cajas_reabastecer, cliente_id, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        descripcion = VALUES(descripcion),
                        localizacion_origen = VALUES(localizacion_origen),
                        lote = VALUES(lote),
                        lpn_max_min = VALUES(lpn_max_min),
                        localizacion_destino = VALUES(localizacion_destino),
                        estado = VALUES(estado),
                        fpc = VALUES(fpc),
                        unidades_reabastecer = VALUES(unidades_reabastecer),
                        cajas_reabastecer = VALUES(cajas_reabastecer),
                        created_at = VALUES(created_at)",
                    [
                        $replenishment['sku'],
                        $replenishment['descripcion'],
                        $replenishment['lpn_inventario'],
                        $replenishment['localizacion_origen'],
                        $replenishment['lote'],
                        $replenishment['lpn_max_min'],
                        $replenishment['localizacion_destino'],
                        $replenishment['estado'],
                        $replenishment['fpc'],
                        $unidadesReabastecer,
                        $cajasReabastecer,
                        $cliente_id,
                        $createdAt
                    ]
                );

                if ($result) {
                    $replenishment['cajas_reabastecer'] = $cajasReabastecer; // Asegurar que lo devuelves con el valor actualizado
                    $reportesGenerados[] = $replenishment;
                    $this->log("Reporte generado / actualizado para SKU: " . $replenishment['sku']);
                }
            } catch (\Exception $e) {
                $this->log("Error al insertar / actualizar reporte para SKU: " . $replenishment['sku'] . ". Error: " . $e->getMessage());
            }
        }

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
                         r.lote,
                         r.lpn_max_min, 
                         r.localizacion_destino, 
                         r.estado, 
                         r.fpc,
                         r.unidades_reabastecer, 
                         r.embalaje, 
                         r.created_at, 
                         r.cliente_id
                  FROM reabastecimientos r
                  WHERE r.cliente_id = ?";
        return $this->db->fetchAll($query, [$cliente_id]);
    }

    private function log($message) {
        error_log($message . "\n", 3, __DIR__ . '/../logs/report.log');
    }
}
