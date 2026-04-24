<?php
// 1. ATIVAÇÃO DE LOGS PARA DEPURAÇÃO
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    // 1. Antes de deletar, buscamos o curriculo_id e a ROLE do dono do currículo
    // Isso é vital para o redirecionamento e para a segurança de hierarquia
    $stmt_check = $db->prepare("
        SELECT f.curriculo_id, u.role 
        FROM formacoes f 
        JOIN curriculos c ON f.curriculo_id = c.id 
        JOIN usuarios u ON c.usuario_id = u.id 
        WHERE f.id = ?
    ");
    $stmt_check->execute([$id_alvo]);
    $info = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        // Se não achou a formação, volta para a gestão
        header("Location: gestao_candidatos.php?erro=registro_nao_encontrado");
        exit;
    }

    $curriculo_id = $info['curriculo_id'];
    $role_do_dono = $info['role'];

    // 2. REGRA DE HIERARQUIA: Admin não exclui nada de Superadmin
    if ($minha_role === 'admin' && $role_do_dono === 'superadmin') {
        header("Location: gestao_candidatos.php?erro=permissao_negada");
        exit;
    }

    // 3. EXECUÇÃO DA EXCLUSÃO
    if (in_array($minha_role, ['admin', 'superadmin'])) {
        // Gestores podem excluir qualquer formação (respeitada a regra acima)
        $stmt_del = $db->prepare("DELETE FROM formacoes WHERE id = ?");
        $stmt_del->execute([$id_alvo]);
        
        // Redireciona para a edição administrativa do candidato que estava sendo editado
        header("Location: editar_candidato_admin.php?id=$curriculo_id&msg=excluido");
    } else {
        // Usuário comum só exclui se for dele
        $stmt_del = $db->prepare("
            DELETE f FROM formacoes f 
            JOIN curriculos c ON f.curriculo_id = c.id 
            WHERE f.id = ? AND c.usuario_id = ?
        ");
        $stmt_del->execute([$id_alvo, $uid_sessao]);
        
        header("Location: editar_curriculo.php?msg=sucesso_excluir");
    }
    exit;

} catch (Exception $e) {
    error_log("Erro ao excluir formação: " . $e->getMessage());
    die("Erro técnico ao excluir. Informe ao suporte.");
}
