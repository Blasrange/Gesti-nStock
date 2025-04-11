<?php
// app/Replenishment.php

namespace App;

class Replenishment {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function generateReplenishments($clienteId) {
        $materials = $this->db->fetchAll("SELECT sku, lpn, localizacion, stock_minimo, stock_maximo, descripcion, embalaje FROM maestra_materiales WHERE cliente_id = ?", [$clienteId]);

        if ($materials === false) {
            error_log("Error al obtener la maestra de materiales para el cliente ID: $clienteId.", 3, __DIR__ . '/../logs/replenishment.log');
            return [];
        }

        $replenishments = [];
        $excludePrefixes = ['PS', 'PTF', 'P30', 'P40', 'P50'];

        foreach ($materials as $material) {
            $sku = $material['sku'];
            $lpnMaestra = $material['lpn'];
            $localizacionMaestra = $material['localizacion'];
            $stockMin = $material['stock_minimo'];
            $stockMax = $material['stock_maximo'];
            $descripcion = $material['descripcion'];
            $embalaje = $material['embalaje'];

            $estadoPermitido = $this->db->fetchOne("SELECT estado FROM estado_cliente WHERE cliente_id = ?", [$clienteId]);

            if (!$estadoPermitido) {
                error_log("No se encontró el estado permitido para el cliente ID: $clienteId", 3, __DIR__ . '/../logs/replenishment.log');
                continue;
            }

            $inventory = $this->db->fetchAll(
                "SELECT * FROM inventarios WHERE sku = ? AND localizacion = ? AND estado = ? AND cliente_id = ? ORDER BY fecha_vencimiento ASC", 
                [$sku, $localizacionMaestra, $estadoPermitido, $clienteId]
            );

            if ($inventory === false || count($inventory) === 0) {
                error_log("No se encontraron datos para el SKU: $sku en la localización: $localizacionMaestra con estado permitido: $estadoPermitido para el cliente ID: $clienteId", 3, __DIR__ . '/../logs/replenishment.log');
                continue;
            }

            $totalAvailableMaestra = 0;
            $loteMaestra = null;
            $fechaVencimientoMaestra = null;

            foreach ($inventory as $item) {
                $totalAvailableMaestra += $item['disponible'];

                if ($loteMaestra === null && $item['disponible'] > 0) {
                    $loteMaestra = $item['lote'];
                    $fechaVencimientoMaestra = $item['fecha_vencimiento'];
                    $estadoMaestra = $item['estado'];
                }
            }

            if ($totalAvailableMaestra < $stockMin) {
                $unitsToReplenish = $stockMax - $totalAvailableMaestra;

                $inventoryOtherLocations = $this->db->fetchAll(
                    "SELECT * FROM inventarios WHERE sku = ? AND localizacion != ? AND estado = ? AND cliente_id = ? AND 
                    localizacion NOT LIKE '%10' AND localizacion NOT LIKE '%10-2' 
                    ORDER BY fecha_vencimiento ASC", 
                    [$sku, $localizacionMaestra, $estadoPermitido, $clienteId]
                );

                if ($inventoryOtherLocations === false || count($inventoryOtherLocations) === 0) {
                    error_log("No se encontraron otras ubicaciones para el SKU: $sku con estado permitido: $estadoPermitido para el cliente ID: $clienteId", 3, __DIR__ . '/../logs/replenishment.log');
                    continue;
                }

                foreach ($inventoryOtherLocations as $key => $otherLocationItem) {
                    foreach ($excludePrefixes as $prefix) {
                        if (strpos($otherLocationItem['localizacion'], $prefix) === 0) {
                            unset($inventoryOtherLocations[$key]);
                            break;
                        }
                    }
                }

                $totalTaken = 0;

                foreach ($inventoryOtherLocations as $otherLocationItem) {
                    $availableInOtherLocation = $otherLocationItem['disponible'];
                    $lpnInventario = $otherLocationItem['lpn'];
                    $localizacionOrigen = $otherLocationItem['localizacion'];
                    $lote = $otherLocationItem['lote'];
                    $fechaVencimiento = $otherLocationItem['fecha_vencimiento'];
                    $estado = $otherLocationItem['estado'];
                    $fpc = $otherLocationItem['fpc'];

                    if ($availableInOtherLocation > 0) {
                        $unitsToTake = min($availableInOtherLocation, $unitsToReplenish - $totalTaken);

                        if ($unitsToTake > 10) {
                            $result = $this->db->execute(
                                "INSERT INTO reabastecimientos (sku, descripcion, lpn_inventario, localizacion_origen, unidades_reabastecer, lote, fecha_vencimiento, fpc, lpn_max_min, localizacion_destino, estado, embalaje, cliente_id, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                                ON DUPLICATE KEY UPDATE 
                                unidades_reabastecer = VALUES(unidades_reabastecer), 
                                lote = VALUES(lote), 
                                fecha_vencimiento = VALUES(fecha_vencimiento),
                                fpc = VALUES(fpc),
                                estado = VALUES(estado), 
                                embalaje = VALUES(embalaje)", 
                                [$sku, $descripcion, $lpnInventario, $localizacionOrigen, $unitsToTake, $lote, $fechaVencimiento, $fpc, $lpnMaestra, $localizacionMaestra, $estado, $embalaje, $clienteId]
                            );

                            if (!$result) {
                                error_log("Error al insertar reabastecimiento para SKU: $sku, Unidades: $unitsToTake", 3, __DIR__ . '/../logs/replenishment.log');
                            } else {
                                error_log("Reabastecimiento generado para SKU: $sku con unidades: $unitsToTake para cliente ID: $clienteId", 3, __DIR__ . '/../logs/replenishment.log');

                                $replenishments[] = [
                                    'sku' => $sku,
                                    'descripcion' => $descripcion,
                                    'lpn_inventario' => $lpnInventario,
                                    'localizacion_origen' => $localizacionOrigen,
                                    'unidades_reabastecer' => $unitsToTake,
                                    'lote' => $lote,
                                    'fecha_vencimiento' => $fechaVencimiento,
                                    'fpc' => $fpc,
                                    'lpn_max_min' => $lpnMaestra,
                                    'localizacion_destino' => $localizacionMaestra,
                                    'estado' => $estado,
                                    'embalaje' => $embalaje,
                                    'cliente_id' => $clienteId,
                                    'created_at' => date('Y-m-d H:i:s'),
                                ];

                                $totalTaken += $unitsToTake;
                            }

                            if ($totalTaken >= $unitsToReplenish) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        $this->removeObsoleteReplenishments($clienteId, $replenishments);

        return $replenishments;
    }

    private function removeObsoleteReplenishments($clienteId, $newReplenishments) {
        $existingKeys = [];
        foreach ($newReplenishments as $reabastecimiento) {
            $existingKeys[] = $reabastecimiento['sku'] . '|' . $reabastecimiento['lpn_inventario'];
        }

        $currentReplenishments = $this->db->fetchAll("SELECT * FROM reabastecimientos WHERE cliente_id = ?", [$clienteId]);

        foreach ($currentReplenishments as $current) {
            $key = $current['sku'] . '|' . $current['lpn_inventario'];
            if (!in_array($key, $existingKeys)) {
                $this->db->execute("DELETE FROM reabastecimientos WHERE id = ?", [$current['id']]);
                error_log("Reabastecimiento obsoleto eliminado para SKU: {$current['sku']}", 3, __DIR__ . '/../logs/replenishment.log');
            }
        }
    }
}
