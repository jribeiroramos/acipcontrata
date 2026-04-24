<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'classes/Database.php';

// SEGURANÇA: Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$db = Database::getConnection();
$uid_sessao = $_SESSION['usuario_id'];
$minha_role = $_SESSION['usuario_role'] ?? 'user';
$id_alvo = $_GET['id'] ?? null;

if (!$id_alvo) {
    header("Location: dashboard.php");
    exit;
}

try {
    // 1. Buscamos o curriculo_id para saber para onde voltar após a exclusão
    $stmt_info = $db->prepare("SELECT curriculo_id FROM experiencias WHERE id = ?");
    $stmt_info->execute([$id_alvo]);
    $curriculo_id = $stmt_info->fetchColumn();

    if (!$curriculo_id) { 
        header("Location: gestao_candidatos.php"); exit; 
    }

    // 2. LÓGICA DE EXCLUSÃO HIERÁRQUICA
    if (in_array($minha_role, ['admin', 'superadmin'])) {
        // Se for gestor, deleta direto o registro
        $stmt = $db->prepare("DELETE FROM experiencias WHERE id = ?");
        $stmt->execute([$id_alvo]);

        // Volta para a edição administrativa
        header("Location: editar_candidato_admin.php?id=$curriculo_id&msg=excluido");
    } else {
        // Se for usuário comum, garante que a experiência é do currículo DELE
        $stmt = $db->prepare("DELETE e FROM experiencias e 
                             JOIN curriculos c ON e.curriculo_id = c.id 
                             WHERE e.id = ? AND c.usuario_id = ?");
        $stmt->execute([$id_alvo, $uid_sessao]);

        header("Location: editar_curriculo.php?msg=sucesso_excluir");
    }
    exit;

} catch (Exception $e) {
    die("Erro ao excluir experiência: " . $e->getMessage());
}
