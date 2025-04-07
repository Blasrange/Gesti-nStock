<?php
require_once '../app/db.php';
$database = new Database();

if (isset($_GET['cliente_id'])) {
    $cliente_id = $_GET['cliente_id'];
    
    $stmt = $database->pdo->prepare('
        SELECT c.id, c.nombre 
        FROM ciudades c
        JOIN clientes cc ON c.id = cc.ciudad_id
        WHERE cc.id = ?
    ');
    $stmt->execute([$cliente_id]);
    $ciudades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ciudades);
}
?>
