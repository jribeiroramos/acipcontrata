<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';
require_once 'classes/Database.php';

if (isset($_POST['login_btn'])) {
    $db = Database::getConnection();
    $user = trim($_POST['user'] ?? '');
    $pass = $_POST['pass'] ?? '';

    $stmt = $db->prepare("SELECT id, login, senha, role, precisa_trocar_senha FROM usuarios WHERE login = ?");
    $stmt->execute([$user]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($pass, $usuario['senha'])) {
        session_regenerate_id(true);

        // --- DEFINIÇÃO DAS VARIÁVEIS DE SESSÃO ---
        $_SESSION['usuario_id']   = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['login'];
        $_SESSION['usuario_tipo'] = $usuario['role'] ?? 'user';
        $_SESSION['usuario_role'] = $usuario['role'] ?? 'user';

        // 1. Verificação de Troca de Senha: Superadmin e Admin ignoram a obrigatoriedade
        $roles_admin = ['admin', 'superadmin'];
        if (!in_array($_SESSION['usuario_role'], $roles_admin) && $usuario['precisa_trocar_senha'] == 1) {
            header("Location: trocar_senha_obrigatorio.php");
            exit;
        }

        // 2. Redirecionamento Baseado na Nova Hierarquia
        // Se for admin ou superadmin, vai para o dashboard administrativo
        if (in_array($_SESSION['usuario_role'], $roles_admin)) {
            header("Location: dashboard.php");
        } else {
            header("Location: area_candidato.php");
        }
        exit;

    } else {
        header("Location: login.php?erro=1");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
