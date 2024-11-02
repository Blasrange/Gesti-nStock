<?php
// app/inventarios.php
namespace App;

use PDO;
use PDOException;

class Inventarios {
    private $db;
    private $cliente_id;

    public function __construct(Database $database, $cliente_id) {
        $this->db = $database->pdo;
        $this->cliente_id = $cliente_id;
    }

    public function getAllItems() {
        $stmt = $this->db->prepare('SELECT * FROM inventarios WHERE cliente_id = :cliente_id ORDER BY id');
        $stmt->execute([':cliente_id' => $this->cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addItem($data) {
        $data[':cliente_id'] = $this->cliente_id;

        $stmt = $this->db->prepare('
            INSERT INTO inventarios (codigo, lpn, localizacion, area_picking, sku, sku2, descripcion, precio, tipo_material, categoria_material, unidades, cajas, reserva, disponible, udm, embalaje, fecha_entrada, estado, lote, fecha_fabricacion, fecha_vencimiento, fpc, peso, serial, cliente_id) 
            VALUES (:codigo, :lpn, :localizacion, :area_picking, :sku, :sku2, :descripcion, :precio, :tipo_material, :categoria_material, :unidades, :cajas, :reserva, :disponible, :udm, :embalaje, :fecha_entrada, :estado, :lote, :fecha_fabricacion, :fecha_vencimiento, :fpc, :peso, :serial, :cliente_id)
        ');

        try {
            return $stmt->execute($data);
        } catch (PDOException $e) {
            $this->log("Error al agregar artículo: " . $e->getMessage());
            return false;
        }
    }

    public function updateItem($id, $data) {
        $data[':id'] = $id; 
        $data[':cliente_id'] = $this->cliente_id; 

        $stmt = $this->db->prepare('
            UPDATE inventarios 
            SET codigo = :codigo, lpn = :lpn, localizacion = :localizacion, area_picking = :area_picking, 
                sku = :sku, sku2 = :sku2, descripcion = :descripcion, precio = :precio, 
                tipo_material = :tipo_material, categoria_material = :categoria_material, 
                unidades = :unidades, cajas = :cajas, reserva = :reserva, 
                disponible = :disponible, udm = :udm, embalaje = :embalaje, 
                fecha_entrada = :fecha_entrada, estado = :estado, lote = :lote, 
                fecha_fabricacion = :fecha_fabricacion, fecha_vencimiento = :fecha_vencimiento, 
                fpc = :fpc, peso = :peso, serial = :serial
            WHERE id = :id AND cliente_id = :cliente_id
        ');

        try {
            $result = $stmt->execute([
                ':codigo' => $data['codigo'],
                ':lpn' => $data['lpn'],
                ':localizacion' => $data['localizacion'],
                ':area_picking' => $data['area_picking'],
                ':sku' => $data['sku'],
                ':sku2' => $data['sku2'],
                ':descripcion' => $data['descripcion'],
                ':precio' => $data['precio'],
                ':tipo_material' => $data['tipo_material'],
                ':categoria_material' => $data['categoria_material'],
                ':unidades' => $data['unidades'],
                ':cajas' => $data['cajas'],
                ':reserva' => $data['reserva'],
                ':disponible' => $data['disponible'],
                ':udm' => $data['udm'],
                ':embalaje' => $data['embalaje'],
                ':fecha_entrada' => $data['fecha_entrada'],
                ':estado' => $data['estado'],
                ':lote' => $data['lote'],
                ':fecha_fabricacion' => $data['fecha_fabricacion'],
                ':fecha_vencimiento' => $data['fecha_vencimiento'],
                ':fpc' => $data['fpc'],
                ':peso' => $data['peso'],
                ':serial' => $data['serial'],
                ':id' => $id,
                ':cliente_id' => $this->cliente_id
            ]);

            if (!$result) {
                $this->log("Error al actualizar datos para ID {$id}: " . implode(", ", $stmt->errorInfo()));
            }
            return $result;
        } catch (PDOException $e) {
            $this->log("Error al actualizar artículo con ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function deleteItem($id) {
        $stmt = $this->db->prepare('DELETE FROM inventarios WHERE id = :id AND cliente_id = :cliente_id');
        try {
            return $stmt->execute([
                ':id' => $id,
                ':cliente_id' => $this->cliente_id
            ]);
        } catch (PDOException $e) {
            $this->log("Error al eliminar artículo: " . $e->getMessage());
            return false;
        }
    }

    public function getItem($id) {
        $stmt = $this->db->prepare('SELECT * FROM inventarios WHERE id = :id AND cliente_id = :cliente_id');
        $stmt->execute([
            ':id' => $id,
            ':cliente_id' => $this->cliente_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateOrInsertInventory($data) {
        $stmt = $this->db->prepare('SELECT id FROM inventarios WHERE codigo = :codigo AND cliente_id = :cliente_id');
        $stmt->execute([
            ':codigo' => $data['codigo'],
            ':cliente_id' => $this->cliente_id
        ]);

        $inventarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($inventarioExistente) {
            $this->log("Actualizando artículo con código: {$data['codigo']} para cliente ID: {$this->cliente_id}");
            return $this->updateItem($inventarioExistente['id'], $data);
        } else {
            $this->log("Agregando nuevo artículo con código: {$data['codigo']} para cliente ID: {$this->cliente_id}");
            return $this->addItem($data);
        }
    }

    public function deleteMissingItems($existingItems) {
        $placeholders = implode(',', array_fill(0, count($existingItems), '?'));
        $stmt = $this->db->prepare("DELETE FROM inventarios WHERE codigo NOT IN ($placeholders) AND cliente_id = ?");
        $stmt->execute(array_merge($existingItems, [$this->cliente_id]));
    }

    private function log($message) {
        error_log($message, 3, __DIR__ . '/../logs/report.log');
    }
}
?>
