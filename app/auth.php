<?php
// app/auth.php

namespace App;

use App\Database;

class Auth {
    private $db;

    public function __construct(Database $database) {
        $this->db = $database->pdo;
    }

    /**
     * Inicia sesión al usuario si las credenciales son correctas y está activo.
     *
     * @param string $username
     * @param string $password
     * @return bool|string Retorna true si se loguea, 'inactivo' si está desactivado, o false si es incorrecto
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['estado'] != 1) {
                // Usuario existe pero está inactivo
                return 'inactivo';
            }

            // Iniciar sesión
            session_regenerate_id(true); // Prevenir fijación de sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['cliente_id'] = $user['cliente_id'];
            return true;
        }

        return false;
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout() {
        session_unset();
        session_destroy();
    }

    /**
     * Verifica si el usuario está autenticado.
     *
     * @return bool
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
}
