<?php
// app/db.php

namespace App;

use PDO;
use PDOException;

class Database {
    private $host = '127.0.0.1'; // Cambiado a 127.0.0.1
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
            // Manejo de errores de conexión
            error_log($e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            echo 'Error de conexión a la base de datos.';
            exit;
        }
    }

    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en fetchAll: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false; // Retorna false en caso de error
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
            return false; // Retorna false en caso de error
        }
    }

    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en fetchOne: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false; // Retorna false en caso de error
        }
    }
}
