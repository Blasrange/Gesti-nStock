<?php
// test_login.php

// Probar la verificación de la contraseña
$username = 'juanp'; // Nombre de usuario
$password = 'password1'; // Contraseña ingresada
$hash = 'Hash: $2y$10$P5/EIuM/Vi5mYXPVMi4ExeYuJX8m9eVcpyapMeV2yD1hY6DbcBLUe'; // Hash desde la base de datos

if (password_verify($password, $hash)) {
    echo "Usuario y contraseña válidos.";
} else {
    echo "Usuario o contraseña incorrectos.";
}
?>
