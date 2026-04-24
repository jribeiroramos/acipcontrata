<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'classes/Database.php';

// 1. SEGURANÇA UNIFICADA: Permite Admin e Superadmin processarem
$minha_role = $_SESSION['usuario_role'] ?? 'user';
if (!isset($_SESSION['usuario_id']) || !in_array($minha_role, ['admin', 'superadmin'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: gestao_candidatos.php");
    exit;
}

$db = Database::getConnection();

$usuario_id = $_POST['usuario_id'];
$nova_senha = $_POST['nova_senha'];
$confirma_senha = $_POST['confirma_senha'];

// 2. Validação de Hierarquia (Segurança de Backend)
// Impede que um Admin comum resete a senha de um Superadmin via manipulação de formulário
$stmt_check = $db->prepare("SELECT role FROM usuarios WHERE id = ?");
$stmt_check->execute([$usuario_id]);
$target_role = $stmt_check->fetchColumn();

if ($minha_role === 'admin' && $target_role === 'superadmin') {
    die("Erro fatal: Administradores não podem alterar senhas de Superadministradores.");
}

// 3. Validação de Dados
if ($nova_senha !== $confirma_senha) {
    die("As senhas não coincidem. Volte e tente novamente.");
}

if (strlen($nova_senha) < 6) {
    die("A senha deve ter no mínimo 6 caracteres.");
}

try {
    // 4. Atualização da Senha
    $hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // Definimos 'precisa_trocar_senha' como 1 para que o candidato seja obrigado 
    // a escolher uma senha própria ao logar com essa senha provisória
    $stmt = $db->prepare("UPDATE usuarios SET senha = ?, precisa_trocar_senha = 1 WHERE id = ?");
    $stmt->execute([$hash, $usuario_id]);

    // Redireciona para a gestão com a mensagem de sucesso que corrigimos anteriormente
    header("Location: gestao_candidatos.php?msg=sucesso");
    exit;

} catch (Exception $e) {
    error_log("Erro ao atualizar senha (Admin): " . $e->getMessage());
    die("Erro interno ao processar a solicitação.");
}
