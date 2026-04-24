<?php
// check_availability.php
require_once 'classes/Database.php';
$db = Database::getConnection();

$type = $_GET['type'] ?? '';
$value = $_GET['value'] ?? '';

if ($type === 'login') {
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE login = ?");
} elseif ($type === 'email') {
    $stmt = $db->prepare("SELECT id FROM curriculos WHERE email = ?");
} else {
    exit(json_encode(['exists' => false]));
}

$stmt->execute([$value]);
echo json_encode(['exists' => (bool)$stmt->fetch()]);
