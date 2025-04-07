<?php
// app/Inventarios.php
namespace App;

use PDO;
use PDOException;

class Inventarios {
    private $db;
    private $cliente_id;

    private $campos = [
        'codigo', 'lpn', 'localizacion', 'area_picking', 'sku', 'sku2', 'descripcion', 
        'precio', 'tipo_material', 'categoria_material', 'unidades', 'cajas', 'reserva', 
        'disponible', 'udm', 'embalaje', 'fecha_entrada', 'estado', 'lote', 
        'fecha_fabricacion', 'fecha_vencimiento', 'fpc', 'peso', 'serial'
    ];

    public function __construct(Database $database, $cliente_id) {
        $this->db = $database->pdo;
        $this->cliente_id = $cliente_id;
    }

    public function getAllItems() {
        $stmt = $this->db->prepare('SELECT * FROM inventarios WHERE cliente_id = :cliente_id ORDER BY id');
        $stmt->execute([':cliente_id' => $this->cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaginatedItems($limit = 50, $offset = 0) {
        $stmt = $this->db->prepare('
            SELECT * FROM inventarios 
            WHERE cliente_id = :cliente_id 
            ORDER BY id 
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':cliente_id', $this->cliente_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchItems($term) {
        $stmt = $this->db->prepare('
            SELECT * FROM inventarios 
            WHERE cliente_id = :cliente_id AND (sku LIKE :term OR descripcion LIKE :term)
        ');
        $stmt->execute([
            ':cliente_id' => $this->cliente_id,
            ':term' => '%' . $term . '%'
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addItem($data) {
        if (!$this->validateItemData($data)) {
            $this->log("Datos inválidos para inserción.");
            return false;
        }

        $data['cliente_id'] = $this->cliente_id;
        $camposSQL = implode(', ', $this->campos) . ', cliente_id';
        $placeholders = ':' . implode(', :', $this->campos) . ', :cliente_id';

        $stmt = $this->db->prepare("
            INSERT INTO inventarios ($camposSQL) 
            VALUES ($placeholders)
        ");

        try {
            return $stmt->execute($this->prepareBindings($data));
        } catch (PDOException $e) {
            $this->log("Error al agregar artículo: " . $e->getMessage());
            return false;
        }
    }

    public function updateItem($id, $data) {
        if (!$this->validateItemData($data)) {
            $this->log("Datos inválidos para actualización del ID $id.");
            return false;
        }

        $setFields = implode(', ', array_map(fn($f) => "$f = :$f", $this->campos));
        $stmt = $this->db->prepare("
            UPDATE inventarios 
            SET $setFields 
            WHERE id = :id AND cliente_id = :cliente_id
        ");

        try {
            $bindings = $this->prepareBindings($data);
            $bindings[':id'] = $id;
            $bindings[':cliente_id'] = $this->cliente_id;

            $result = $stmt->execute($bindings);

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
        if (empty($existingItems)) return;

        $placeholders = implode(',', array_fill(0, count($existingItems), '?'));
        $stmt = $this->db->prepare("DELETE FROM inventarios WHERE codigo NOT IN ($placeholders) AND cliente_id = ?");
        $stmt->execute(array_merge($existingItems, [$this->cliente_id]));
    }

    private function validateItemData($data) {
        return !empty($data['codigo']) && !empty($data['descripcion']);
    }

    private function prepareBindings($data) {
        $bindings = [];
        foreach ($this->campos as $campo) {
            $bindings[":$campo"] = $data[$campo] ?? null;
        }
        $bindings[':cliente_id'] = $this->cliente_id;
        return $bindings;
    }

    private function log($message) {
        $logFile = __DIR__ . '/../logs/report.log';
        $time = date('Y-m-d H:i:s');
        error_log("[$time] $message\n", 3, $logFile);
    }
}
?>
