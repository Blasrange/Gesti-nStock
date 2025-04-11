<?php
// app/LogMateriales.php
namespace App;

class MovimientoLogger{
    private $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    public function registrarMovimiento(array $movimiento)
    {
        // Validación básica de campos requeridos
        $required = ['cliente_id', 'usuario_id', 'sku', 'descripcion', 'cantidad', 'tipo_movimiento'];
        foreach ($required as $field) {
            if (!isset($movimiento[$field])) {
                throw new \InvalidArgumentException("Campo requerido faltante: {$field}");
            }
        }

        $sql = "INSERT INTO log_movimientos (
                    cliente_id,
                    cliente_nombre,
                    usuario_id,
                    usuario_nombre,
                    sku,
                    descripcion,
                    lpn_origen,
                    localizacion_origen,
                    lpn_destino,
                    localizacion_destino,
                    cantidad,
                    lote,
                    tipo_movimiento,
                    fecha_movimiento
                ) VALUES (
                    :cliente_id,
                    :cliente_nombre,
                    :usuario_id,
                    :usuario_nombre,
                    :sku,
                    :descripcion,
                    :lpn_origen,
                    :localizacion_origen,
                    :lpn_destino,
                    :localizacion_destino,
                    :cantidad,
                    :lote,
                    :tipo_movimiento,
                    NOW()
                )";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cliente_id' => $movimiento['cliente_id'],
                ':cliente_nombre' => $movimiento['cliente_nombre'] ?? '',
                ':usuario_id' => $movimiento['usuario_id'],
                ':usuario_nombre' => $movimiento['usuario_nombre'] ?? '',
                ':sku' => $movimiento['sku'],
                ':descripcion' => $movimiento['descripcion'],
                ':lpn_origen' => $movimiento['lpn_origen'] ?? null,
                ':localizacion_origen' => $movimiento['localizacion_origen'] ?? null,
                ':lpn_destino' => $movimiento['lpn_destino'] ?? null,
                ':localizacion_destino' => $movimiento['localizacion_destino'] ?? null,
                ':cantidad' => $movimiento['cantidad'],
                ':lote' => $movimiento['lote'] ?? null,
                ':tipo_movimiento' => $movimiento['tipo_movimiento']
            ]);
            
            return true;
        } catch (\PDOException $e) {
            error_log("Error al registrar movimiento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los movimientos filtrados
     * 
     * @param int $clienteId ID del cliente
     * @param string $fechaInicio Fecha en formato YYYY-MM-DD
     * @param string $fechaFin Fecha en formato YYYY-MM-DD
     * @param int|null $usuarioId ID del usuario (opcional)
     * @param string|null $tipoMovimiento Tipo de movimiento (opcional)
     * @return array Array de movimientos
     */
    public function obtenerMovimientos(
        int $clienteId, 
        string $fechaInicio, 
        string $fechaFin, 
        ?int $usuarioId = null, 
        ?string $tipoMovimiento = null
    ): array {
        $sql = "SELECT 
                    id,
                    cliente_id,
                    cliente_nombre,
                    usuario_id,
                    usuario_nombre,
                    sku,
                    descripcion,
                    lpn_origen,
                    localizacion_origen,
                    lpn_destino,
                    localizacion_destino,
                    cantidad,
                    lote,
                    tipo_movimiento,
                    DATE_FORMAT(fecha_movimiento, '%Y-%m-%d %H:%i:%s') as fecha_movimiento
                FROM log_movimientos 
                WHERE cliente_id = :cliente_id 
                AND fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin";
        
        $params = [
            ':cliente_id' => $clienteId,
            ':fecha_inicio' => $fechaInicio . ' 00:00:00',
            ':fecha_fin' => $fechaFin . ' 23:59:59'
        ];
        
        if ($usuarioId !== null) {
            $sql .= " AND usuario_id = :usuario_id";
            $params[':usuario_id'] = $usuarioId;
        }
        
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
            error_log("Error al obtener movimientos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los tipos de movimiento distintos existentes
     * 
     * @return array Array con los tipos de movimiento
     */
    public function obtenerTiposMovimiento(): array
    {
        $sql = "SELECT DISTINCT tipo_movimiento FROM log_movimientos ORDER BY tipo_movimiento";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            error_log("Error al obtener tipos de movimiento: " . $e->getMessage());
            throw $e;
        }
    }
}