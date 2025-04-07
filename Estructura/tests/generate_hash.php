<?php
// generate_hash.php
$password = '1995'; // La contraseña que deseas hashear
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Hash: " . $hash;
?>
