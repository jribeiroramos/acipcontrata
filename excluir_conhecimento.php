<?php
// 1. ATIVAÇÃO DE LOGS PARA DEPURAÇÃO
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'classes/Database.php';

// 2. SEGURANÇA: Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$db = Database::getConnection();
$uid_sessao = $_SESSION['usuario_id'];
// Normalizamos a role para letras minúsculas para evitar erros de comparação
$minha_role = strtolower($_SESSION['usuario_role'] ?? 'user');
$id_alvo = $_GET['id'] ?? null;

if (!$id_alvo) {
    header("Location: dashboard.php");
    exit;
}

try {
    // 3. BUSCA O CURRICULO_ID antes de deletar
    // Precisamos dele para saber para qual página voltar (Admin ou Candidato)
    $stmt_info = $db->prepare("SELECT curriculo_id FROM conhecimentos WHERE id = ?");
    $stmt_info->execute([$id_alvo]);
    $curriculo_id = $stmt_info->fetchColumn();

    // Se o registro não existe mais no banco
    if (!$curriculo_id) {
        header("Location: gestao_candidatos.php?erro=nao_encontrado");
        exit;
    }

    // 4. LÓGICA DE EXCLUSÃO
    if ($minha_role === 'superadmin' || $minha_role === 'admin') {
        // GESTORES: Deletam qualquer conhecimento pelo ID direto
        $stmt = $db->prepare("DELETE FROM conhecimentos WHERE id = ?");
        $stmt->execute([$id_alvo]);

        // Redireciona de volta para a edição do candidato que o admin estava mexendo
        header("Location: editar_candidato_admin.php?id=" . $curriculo_id . "&msg=excluido");
    } else {
        // USUÁRIO COMUM: Só deleta se o conhecimento pertencer ao currículo dele (Segurança extra)
        $stmt = $db->prepare("DELETE kn FROM conhecimentos kn 
                             JOIN curriculos c ON kn.curriculo_id = c.id 
                             WHERE kn.id = ? AND c.usuario_id = ?");
        $stmt->execute([$id_alvo, $uid_sessao]);

        header("Location: editar_curriculo.php?msg=sucesso_excluir");
    }
    exit;

} catch (Exception $e) {
    // Log do erro e mensagem amigável
    error_log("Erro ao excluir conhecimento: " . $e->getMessage());
    die("Erro técnico ao realizar a exclusão. Por favor, tente novamente.");
} // <--- A chave que faltava estava aqui
?>
