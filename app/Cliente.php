<?php
// app/Cliente.php

namespace App;

use PDO;

class Cliente
{
    private $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    // Obtener todos los clientes con ciudad (si existe) y estado
    public function getAll()
    {
        $sql = "SELECT c.id, c.nombre, c.email, c.telefono, ciu.nombre AS ciudad, c.created_at, c.estado
                FROM clientes c
                LEFT JOIN ciudades ciu ON c.ciudad_id = ciu.id
                ORDER BY c.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener cliente por ID
    public function getById($id)
    {
        $sql = "SELECT * FROM clientes WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear cliente (incluyendo estado)
    public function create($data)
    {
        $sql = "INSERT INTO clientes (nombre, email, telefono, ciudad_id, estado) 
                VALUES (:nombre, :email, :telefono, :ciudad_id, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':email' => $data['email'],
            ':telefono' => $data['telefono'] ?? null,
            ':ciudad_id' => $data['ciudad_id'] ?? null,
            ':estado' => $data['estado'] ?? 1 // Por defecto activo
        ]);
    }

    // Actualizar cliente (incluyendo estado)
    public function update($id, $data)
    {
        $sql = "UPDATE clientes 
                SET nombre = :nombre, email = :email, telefono = :telefono, ciudad_id = :ciudad_id, estado = :estado 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':email' => $data['email'],
            ':telefono' => $data['telefono'] ?? null,
            ':ciudad_id' => $data['ciudad_id'] ?? null,
            ':estado' => $data['estado'] ?? 1,
            ':id' => $id
        ]);
    }

    // Eliminar cliente
    public function delete($id)
    {
        $sql = "DELETE FROM clientes WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
    }
}
