<?php
// app/maestraMateriales.php
namespace App;

use PDO;
use PDOException;

class MaestraMateriales {
    private $db;
    private $cliente_id;

    public function __construct(Database $database, $cliente_id) {
        $this->db = $database->pdo;
        $this->cliente_id = $cliente_id;
    }

    // Método para buscar material por SKU
    public function fetchBySku($sku) {
        $query = "SELECT * FROM maestra_materiales WHERE sku = :sku AND cliente_id = :cliente_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':sku', $sku);
        $stmt->bindParam(':cliente_id', $this->cliente_id);
        $stmt->execute();
        return $stmt->fetch(); // Devuelve el material encontrado
    }

    // Obtener todos los materiales de la maestra
    public function getAllMaterials() {
        $stmt = $this->db->prepare('SELECT * FROM maestra_materiales WHERE cliente_id = :cliente_id ORDER BY id');
        $stmt->execute([':cliente_id' => $this->cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un material específico por su ID
    public function getMaterialById($id) {
        $stmt = $this->db->prepare('SELECT * FROM maestra_materiales WHERE id = :id AND cliente_id = :cliente_id');
        $stmt->execute([
            ':id' => $id,
            ':cliente_id' => $this->cliente_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Validar datos del material
    private function validateMaterialData($data) {
        $errors = [];
        if (empty(trim($data['sku']))) {
            $errors[] = 'SKU es requerido.';
        }
        if (empty(trim($data['descripcion']))) {
            $errors[] = 'Descripción es requerida.';
        }
        // Agregar más validaciones según sea necesario
        
        return $errors;
    }

    // Agregar un nuevo material a la tabla
    public function addMaterial($data) {
        // Validar datos
        $validationErrors = $this->validateMaterialData($data);
        if (!empty($validationErrors)) {
            return ['success' => false, 'error' => implode(' ', $validationErrors)];
        }

        // Incluir cliente_id en los datos
        $data['cliente_id'] = $this->cliente_id;

        $stmt = $this->db->prepare('
            INSERT INTO maestra_materiales (sku, lpn, localizacion, descripcion, stock_minimo, stock_maximo, embalaje, created_at, cliente_id) 
            VALUES (:sku, :lpn, :localizacion, :descripcion, :stock_minimo, :stock_maximo, :embalaje, :created_at, :cliente_id)
        ');

        try {
            // Loguear los datos recibidos para verificar si están completos y correctos
            error_log("Datos del material: " . print_r($data, true));

            // Intentar ejecutar la inserción
            $success = $stmt->execute([
                ':sku' => $data['sku'],
                ':lpn' => $data['lpn'],
                ':localizacion' => $data['localizacion'],
                ':descripcion' => $data['descripcion'],
                ':stock_minimo' => $data['stock_minimo'],
                ':stock_maximo' => $data['stock_maximo'],
                ':embalaje' => $data['embalaje'],
                ':created_at' => date('Y-m-d H:i:s'), // Para referencia, aunque la base de datos ya manejará la fecha
                ':cliente_id' => $data['cliente_id']
            ]);

            return ['success' => $success];
        } catch (PDOException $e) {
            // Registrar el error específico de la base de datos
            error_log("Error al agregar material: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }

    // Eliminar un material por su ID
    public function deleteMaterial($id) {
        $stmt = $this->db->prepare('DELETE FROM maestra_materiales WHERE id = :id AND cliente_id = :cliente_id');
        try {
            return ['success' => $stmt->execute([
                ':id' => $id,
                ':cliente_id' => $this->cliente_id
            ])];
        } catch (PDOException $e) {
            error_log("Error al eliminar material: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }

    // Editar un material existente
    public function editMaterial($data) {
        // Validar datos
        $validationErrors = $this->validateMaterialData($data);
        if (!empty($validationErrors)) {
            return ['success' => false, 'error' => implode(' ', $validationErrors)];
        }

        // Depurar los datos recibidos
        error_log(print_r($data, true));

        $stmt = $this->db->prepare('
            UPDATE maestra_materiales 
            SET sku = :sku, lpn = :lpn, localizacion = :localizacion, descripcion = :descripcion, 
                stock_minimo = :stock_minimo, stock_maximo = :stock_maximo, embalaje = :embalaje 
            WHERE id = :id AND cliente_id = :cliente_id
        ');

        try {
            $success = $stmt->execute([
                ':sku' => $data['sku'],
                ':lpn' => $data['lpn'],
                ':localizacion' => $data['localizacion'],
                ':descripcion' => $data['descripcion'],
                ':stock_minimo' => $data['stock_minimo'],
                ':stock_maximo' => $data['stock_maximo'],
                ':embalaje' => $data['embalaje'],
                ':id' => $data['id'],
                ':cliente_id' => $this->cliente_id
            ]);

            if (!$success) {
                error_log("Error al actualizar el material. Datos: " . print_r($data, true));
            }

            return ['success' => $success];
        } catch (PDOException $e) {
            error_log("Error al editar material: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()];
        }
    }

    // Verificar si un material ya existe por su SKU y actualizar o insertar
    public function updateOrInsertMaterial($data) {
        $stmt = $this->db->prepare('SELECT id FROM maestra_materiales WHERE sku = :sku AND cliente_id = :cliente_id');
        $stmt->execute([
            ':sku' => $data['sku'],
            ':cliente_id' => $this->cliente_id
        ]);

        $materialExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($materialExistente) {
            // Permitir inserciones múltiples en diferentes localizaciones
            error_log("Material con SKU {$data['sku']} encontrado, pero puede estar en diferentes localizaciones.");
            return $this->addMaterial($data);
        } else {
            return $this->addMaterial($data);
        }
    }
}
?>
