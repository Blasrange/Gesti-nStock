<?php
// app/Rol.php

namespace App;

use PDO;

class Rol
{
    private $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    public function getAll()
    {
        $query = "SELECT * FROM roles";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
