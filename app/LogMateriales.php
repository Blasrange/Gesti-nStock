<?php
// app/LogMateriales.php
namespace App;

class LogMateriales
{
    private $db;
    private $clienteId;

    public function __construct(Database $database, int $clienteId)
    {
        $this->db = $database->getConnection();
        $this->clienteId = $clienteId;
    }

    /**
     * Registra un movimiento de materiales en el log
     * 
     * @param array $params Datos del movimiento
     * @return bool
     * @throws \InvalidArgumentException|\PDOException
     */
    public function registrarMovimiento(array $params): bool
    {
        // Validación básica de campos requeridos
        $required = [
            'usuario_id', 'usuario_nombre', 'cliente_nombre',
            'material_id', 'sku', 'descripcion', 'tipo_movimiento'
        ];

        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new \InvalidArgumentException("Falta el campo requerido: {$field}");
            }
        }

        $sql = "INSERT INTO movimientos_materiales_log (
                    usuario_id,
                    usuario_nombre,
                    cliente_id,
                    cliente_nombre,
                    material_id,
                    sku,
                    descripcion,
                    tipo_movimiento,
                    datos_anteriores,
                    datos_nuevos,
                    fecha_movimiento
                ) VALUES (
                    :usuario_id,
                    :usuario_nombre,
                    :cliente_id,
                    :cliente_nombre,
                    :material_id,
                    :sku,
                    :descripcion,
                    :tipo_movimiento,
                    :datos_anteriores,
                    :datos_nuevos,
                    NOW()
                )";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $params['usuario_id'],
                ':usuario_nombre' => $params['usuario_nombre'],
                ':cliente_id' => $this->clienteId,
                ':cliente_nombre' => $params['cliente_nombre'],
                ':material_id' => $params['material_id'],
                ':sku' => $params['sku'],
                ':descripcion' => $params['descripcion'],
                ':tipo_movimiento' => $params['tipo_movimiento'],
                ':datos_anteriores' => $params['datos_anteriores'] ?? null,
                ':datos_nuevos' => $params['datos_nuevos'] ?? null,
            ]);

            return true;
        } catch (\PDOException $e) {
            error_log("Error al registrar movimiento de materiales: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los movimientos de materiales registrados
     * 
     * @param string $fechaInicio Fecha de inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha de fin (YYYY-MM-DD)
     * @param string|null $tipoMovimiento Tipo de movimiento (opcional)
     * @return array
     * @throws \PDOException
     */
    public function obtenerMovimientosMateriales(
        string $fechaInicio,
        string $fechaFin,
        ?string $tipoMovimiento = null
    ): array {
        $sql = "SELECT 
                    id,
                    usuario_id,
                    usuario_nombre,
                    cliente_id,
                    cliente_nombre,
                    material_id,
                    sku,
                    descripcion,
                    tipo_movimiento,
                    datos_anteriores,
                    datos_nuevos,
                    DATE_FORMAT(fecha_movimiento, '%Y-%m-%d %H:%i:%s') as fecha_movimiento
                FROM movimientos_materiales_log
                WHERE cliente_id = :cliente_id
                AND fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin";

        $params = [
            ':cliente_id' => $this->clienteId,
            ':fecha_inicio' => $fechaInicio . ' 00:00:00',
            ':fecha_fin' => $fechaFin . ' 23:59:59'
        ];

        if ($tipoMovimiento !== null) {
            $sql .= " AND tipo_movimiento = :tipo_movimiento";
            $params[':tipo_movimiento'] = $tipoMovimiento;
        }

        $sql .= " ORDER BY fecha_movimiento DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener movimientos de materiales: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los tipos de movimiento distintos registrados
     * 
     * @return array
     * @throws \PDOException
     */
    public function obtenerTiposMovimiento(): array
    {
        $sql = "SELECT DISTINCT tipo_movimiento 
                FROM movimientos_materiales_log 
                WHERE cliente_id = :cliente_id 
                ORDER BY tipo_movimiento";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':cliente_id' => $this->clienteId]);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            error_log("Error al obtener tipos de movimiento de materiales: " . $e->getMessage());
            throw $e;
        }
    }
}
