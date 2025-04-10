<?php
// app/db.php

namespace App;

use PDO;
use PDOException;

class Database {
    private $host = '127.0.0.1';
    private $db = 'reabastecimiento';
    private $user = 'root'; 
    private $pass = ''; 
    private $charset = 'utf8mb4';
    public $pdo;

    public function __construct() {
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            error_log($e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            echo 'Error de conexión a la base de datos.';
            exit;
        }
    }

    // Método para obtener la conexión
    public function getConnection() {
        return $this->pdo;
    }

    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en fetchAll: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function fetch($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error en execute: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en fetchOne: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function getClienteNombreById($cliente_id) {
        $stmt = $this->pdo->prepare("SELECT nombre FROM clientes WHERE id = ?");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchColumn();
    }
    
    public function getUsuarioNombreById($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchColumn();
    }

    // Método agregado para compatibilidad con LogMateriales
    public function query($sql, $params = []) {
        return $this->fetchAll($sql, $params);
    }
}
