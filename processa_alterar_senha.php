<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'classes/Database.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$db = Database::getConnection();
$uid = $_SESSION['usuario_id'];
$senha_atual = $_POST['senha_atual'];
$nova_senha = $_POST['nova_senha'];
$confirma_senha = $_POST['confirma_senha'];

// 1. Validação básica
if (strlen($nova_senha) < 6 || $nova_senha !== $confirma_senha) {
    header("Location: alterar_senha.php?erro=validacao");
    exit;
}

try {
    // 2. Verifica se a senha atual está correta
    $stmt = $db->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha_atual, $user['senha'])) {
        // 3. Atualiza para a nova senha hash
        $nova_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE usuarios SET senha = ?, precisa_trocar_senha = 0 WHERE id = ?");
        $update->execute([$nova_hash, $uid]);

        header("Location: area_candidato.php?sucesso_senha=1");
    } else {
        header("Location: alterar_senha.php?erro=senha_atual");
    }
    exit;

} catch (Exception $e) {
    die("Erro ao processar segurança: " . $e->getMessage());
}
