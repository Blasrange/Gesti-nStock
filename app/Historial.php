<?php

// app/Historial.php

namespace App;

class Historial {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
        // Establecer la zona horaria a Colombia
        date_default_timezone_set('America/Bogota');
    }

    // Función para generar el historial a partir de los reportes
    public function generateHistorial($cliente_id) {
        // Obtener los reportes por cliente
        $replenishments = $this->getReplenishmentsByClient($cliente_id);

        if (empty($replenishments)) {
            return [];
        }

        // Agrupar los reportes por SKU y turno
        $historialData = [];

        foreach ($replenishments as $replenishment) {
            $sku = $replenishment['sku'];
            $turno = $this->getTurno(); // Obtener el turno basado en la hora actual
            $fecha_hora = date('Y-m-d H:i:s'); // Fecha y hora actual
            $unidadesReabastecer = $replenishment['unidades_reabastecer'] ?? 0;
            $cajasReabastecer = $replenishment['cajas_reabastecer'] ?? 0; // Dato real de cajas

            // Agrupar datos por SKU y turno
            $key = $sku . '|' . $turno;
            if (!isset($historialData[$key])) {
                $historialData[$key] = [
                    'fecha_hora' => $fecha_hora,
                    'sku' => $sku,
                    'unidades' => $unidadesReabastecer,
                    'cajas' => $cajasReabastecer,
                    'turno' => $turno
                ];
            } else {
                // Sumar unidades y cajas si ya existe
                $historialData[$key]['unidades'] += $unidadesReabastecer;
                $historialData[$key]['cajas'] += $cajasReabastecer;
            }
        }

        // Insertar el historial agrupado en la base de datos
        $this->saveHistorial($cliente_id, $historialData);

        return $historialData; // Retornar todos los datos agrupados
    }

    // Método para guardar el historial en la base de datos
    public function saveHistorial($cliente_id, $historialData) {
        foreach ($historialData as $historial) {
            // Comprobar si el registro ya existe en el historial
            $existingEntry = $this->db->fetchOne(
                "SELECT * FROM historial WHERE sku = ? AND turno = ? AND cliente_id = ?",
                [$historial['sku'], $historial['turno'], $cliente_id]
            );

            // Insertar nuevo registro solo si no existe
            if (!$existingEntry) {
                $this->db->execute(
                    "INSERT INTO historial (fecha_hora, sku, unidades, cajas, turno, cliente_id)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $historial['fecha_hora'],
                        $historial['sku'],
                        $historial['unidades'],
                        $historial['cajas'],
                        $historial['turno'],
                        $cliente_id
                    ]
                );
            }
        }
    }

    // Función auxiliar para obtener los datos de reabastecimiento por cliente
    private function getReplenishmentsByClient($cliente_id) {
        $query = "SELECT sku, unidades_reabastecer, cajas_reabastecer FROM reportes WHERE cliente_id = ?";
        return $this->db->fetchAll($query, [$cliente_id]);
    }

    // Función auxiliar para determinar el turno
    private function getTurno() {
        // Obtener la hora actual ajustada a la zona horaria de Colombia
        $hora = (int) date('H'); // Asegúrate de que se obtiene como entero
        // Asignar el turno basado en la hora
        if ($hora >= 6 && $hora < 14) {
            return 1; // Turno 1
        } elseif ($hora >= 14 && $hora < 22) {
            return 2; // Turno 2
        } else {
            return 3; // Turno 3
        }
    }

    public function getHistorial($cliente_id, $fecha_inicio = '', $fecha_fin = '') {
        $query = "SELECT * FROM historial WHERE cliente_id = ?";
        $params = [$cliente_id];
    
        if ($fecha_inicio) {
            $query .= " AND fecha_hora >= ?";
            $params[] = $fecha_inicio . " 00:00:00"; // Iniciar el día
        }
        
        if ($fecha_fin) {
            $query .= " AND fecha_hora <= ?";
            $params[] = $fecha_fin . " 23:59:59"; // Terminar el día
        }
    
        $query .= " ORDER BY fecha_hora DESC";
        return $this->db->fetchAll($query, $params);
    }
    
    

}
