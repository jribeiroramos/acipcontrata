<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_role'] !== 'admin') {
    exit("Acesso negado.");
}

require_once '../classes/Database.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $db = Database::getConnection();
    // Atualiza o status tanto na tabela usuario quanto curriculo (conforme sua estrutura)
    $stmt = $db->prepare("UPDATE usuarios SET aprovado = 1 WHERE id = ?");
    $stmt->execute([$id]);
    
    $stmt2 = $db->prepare("UPDATE curriculos SET aprovado = 1 WHERE usuario_id = ?");
    $stmt2->execute([$id]);
}

header("Location: dashboard.php");
exit;
