<?php
// app/Usuario.php

namespace App;

require_once 'db.php';

use App\Database;
use PDO;
use PDOException;

class Usuario {
    private $db;

    public function __construct(Database $database) {
        $this->db = $database->getConnection();
    }

    public function obtenerUsuarios() {
        try {
            $stmt = $this->db->query("SELECT id, username FROM users");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error al obtener usuarios: " . $e->getMessage());
        }
    }

    public function obtenerUsuarioPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error al obtener usuario: " . $e->getMessage());
        }
    }
}
