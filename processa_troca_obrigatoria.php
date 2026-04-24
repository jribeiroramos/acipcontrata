<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['usuario_id'])) {
    $nova_senha = $_POST['nova_senha'];
    $confirma = $_POST['confirma_senha'];
    $uid = $_SESSION['usuario_id'];

    // 1. Obriga o preenchimento de ambos
    if (empty($nova_senha) || empty($confirma)) {
        header("Location: trocar_senha_obrigatorio.php?erro=vazio");
        exit;
    }

    // 2. Validação de tamanho mínimo
    if (strlen($nova_senha) < 6) {
        header("Location: trocar_senha_obrigatorio.php?erro=curta");
        exit;
    }

    // 3. Validação de IGUALDADE (Crucial)
    if ($nova_senha === $confirma) {
        $db = Database::getConnection();
        
        // Gravação segura com Hash
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        // 4. Atualiza a senha e DESATIVA a flag de troca obrigatória
        $stmt = $db->prepare("UPDATE usuarios SET senha = ?, precisa_trocar_senha = 0 WHERE id = ?");
        $stmt->execute([$hash, $uid]);

        // 5. Destrói sessão para forçar novo login com a nova senha definitiva
        session_destroy();

        // 6. Redireciona para o login informando o sucesso
        header("Location: login.php?troca=sucesso");
    } else {
        // Se as senhas forem diferentes, volta com erro
        header("Location: trocar_senha_obrigatorio.php?erro=senhas_diferentes");
    }
    exit;
}
