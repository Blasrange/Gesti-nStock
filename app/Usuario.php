<?php
// app/Usuario.php

namespace App;

use PDO;

class Usuario
{
    private $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    public function getAll()
    {
        $query = "
            SELECT users.*, clientes.nombre AS cliente_nombre 
            FROM users 
            LEFT JOIN usuario_clientes ON users.id = usuario_clientes.user_id
            LEFT JOIN clientes ON usuario_clientes.cliente_id = clientes.id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $query = "
            SELECT users.*, usuario_clientes.cliente_id 
            FROM users 
            LEFT JOIN usuario_clientes ON users.id = usuario_clientes.user_id 
            WHERE users.id = :id
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        // Insertar en tabla users (ahora incluyendo estado y created_at)
        $query = "INSERT INTO users (name, username, password, cliente_id, estado, created_at) 
                  VALUES (:name, :username, :password, :cliente_id, :estado, :created_at)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':name' => $data['nombre'],
            ':username' => $data['username'],
            ':password' => $data['password'],
            ':cliente_id' => $data['cliente_id'],
            ':estado' => $data['estado'],
            ':created_at' => $data['created_at']
        ]);

        $userId = $this->db->lastInsertId();

        // Insertar en tabla usuario_clientes (relación)
        $relQuery = "INSERT INTO usuario_clientes (user_id, cliente_id) VALUES (:user_id, :cliente_id)";
        $relStmt = $this->db->prepare($relQuery);
        $relStmt->execute([
            ':user_id' => $userId,
            ':cliente_id' => $data['cliente_id']
        ]);
    }

    public function update($id, $data)
    {
        // Actualizar tabla users (incluyendo estado)
        $query = "UPDATE users SET name = :name, username = :username, password = :password, cliente_id = :cliente_id, estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':name' => $data['nombre'],
            ':username' => $data['username'],
            ':password' => $data['password'],
            ':cliente_id' => $data['cliente_id'],
            ':estado' => $data['estado'],
            ':id' => $id
        ]);

        // Verificar si ya existe la relación con el cliente
        $checkQuery = "SELECT COUNT(*) FROM usuario_clientes WHERE user_id = :user_id AND cliente_id = :cliente_id";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([
            ':user_id' => $id,
            ':cliente_id' => $data['cliente_id']
        ]);
        $exists = $checkStmt->fetchColumn();

        if (!$exists) {
            $insertRel = "INSERT INTO usuario_clientes (user_id, cliente_id) VALUES (:user_id, :cliente_id)";
            $stmtRel = $this->db->prepare($insertRel);
            $stmtRel->execute([
                ':user_id' => $id,
                ':cliente_id' => $data['cliente_id']
            ]);
        }
    }
}
