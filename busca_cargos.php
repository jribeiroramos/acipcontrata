<?php
require_once 'classes/Database.php';
$q = $_GET['q'] ?? '';
$db = Database::getConnection();
$stmt = $db->prepare("SELECT id, nome FROM cargos WHERE nome LIKE ? LIMIT 10");
$stmt->execute(["%$q%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
