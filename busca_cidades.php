<?php
require_once 'classes/Database.php';

if (isset($_GET['uf'])) {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT ibge, municipio FROM cidades WHERE uf = ? ORDER BY municipio");
    $stmt->execute([$_GET['uf']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
