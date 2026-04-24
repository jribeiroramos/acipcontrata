<?php
require_once 'classes/Database.php';
require_once 'config_email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $db = Database::getConnection();

    // 1. Verifica se o e-mail existe no banco
    $stmt = $db->prepare("SELECT id FROM curriculos WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Gera uma senha temporária
        $nova_senha = substr(md5(uniqid()), 0, 8);
        $hash_senha = password_hash($nova_senha, PASSWORD_DEFAULT);

        // 3. Atualiza no banco vinculado ao usuário e ativa a flag de troca obrigatória
        // Alteração: Adicionado 'precisa_trocar_senha = 1'
        $update = $db->prepare("UPDATE usuarios SET senha = ?, precisa_trocar_senha = 1 WHERE id = (SELECT usuario_id FROM curriculos WHERE email = ?)");
        $update->execute([$hash_senha, $email]);

        // 4. Envia o e-mail
        $corpo = "<h2>Sua nova senha chegou!</h2>
                  <p>Você solicitou uma recuperação de acesso para o portal ACIP Palestina.</p>
                  <p>Sua senha temporária é: <strong>$nova_senha</strong></p>
                  <p>Acesse o painel e mude sua senha imediatamente no primeiro acesso por segurança.</p>";
        
        $enviou = enviarEmail($email, "Recuperação de Senha - ACIP", $corpo);
        
        if ($enviou === true) {
            header("Location: esqueci_senha.php?status=sucesso"); // Redireciona com sucesso
        } else {
            header("Location: esqueci_senha.php?status=erro_envio"); // Erro técnico no SMTP
        }
    } else {
        // E-mail não encontrado
        header("Location: esqueci_senha.php?status=nao_encontrado"); // Redireciona informando que não encontrou
    }
    exit;
}
