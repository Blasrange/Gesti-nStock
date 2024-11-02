<?php
// app/auth.php // Este archivo manejará la lógica de autenticación, incluyendo login y logout.

namespace App;

use App\Database;

class Auth {
    private $db;

    public function __construct(Database $database) {
        $this->db = $database->pdo;
    }

    /**
     * Inicia sesión al usuario si las credenciales son correctas.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
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
?>
