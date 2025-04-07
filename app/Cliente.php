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

    // Obtener todos los clientes con ciudad (si existe), nodo, depósito, propietario y estado
    public function getAll()
    {
        $sql = "SELECT 
                    c.id, 
                    c.nombre, 
                    c.email, 
                    c.telefono, 
                    ciu.nombre AS ciudad, 
                    c.nodo, 
                    c.deposito, 
                    c.propietario, 
                    c.created_at, 
                    c.estado
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

    // Crear cliente
    public function create($data)
    {
        $sql = "INSERT INTO clientes 
                (nombre, email, telefono, ciudad_id, estado, nodo, deposito, propietario) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['nombre'],
            $data['email'],
            $data['telefono'],
            $data['ciudad_id'],
            $data['estado'],
            $data['nodo'],
            $data['deposito'],
            $data['propietario']
        ]);
    }

    // Actualizar cliente
    public function update($id, $data)
    {
        $sql = "UPDATE clientes SET 
                    nombre = ?, 
                    email = ?, 
                    telefono = ?, 
                    ciudad_id = ?, 
                    estado = ?, 
                    nodo = ?, 
                    deposito = ?, 
                    propietario = ? 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['nombre'],
            $data['email'],
            $data['telefono'],
            $data['ciudad_id'],
            $data['estado'],
            $data['nodo'],
            $data['deposito'],
            $data['propietario'],
            $id
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
