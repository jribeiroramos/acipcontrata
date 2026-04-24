<?php
require_once 'classes/Database.php';
$db = Database::getConnection();

// Geramos o hash exato que o SEU servidor PHP produz para '123456'
$nova_senha = password_hash('123456', PASSWORD_DEFAULT);
$login = 'acip';

$stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE login = ?");
if ($stmt->execute([$nova_senha, $login])) {
    echo "<h3>✅ Senha do usuário 'acip' atualizada com sucesso!</h3>";
    echo "<p>Agora tente logar com a senha: <b>123456</b></p>";
} else {
    echo "<h3>❌ Erro ao atualizar banco de dados.</h3>";
}

// Opcional: Garante que o currículo exista para evitar erro de JOIN
$stmt_check = $db->prepare("SELECT id FROM curriculos WHERE usuario_id = (SELECT id FROM usuarios WHERE login = 'acip')");
$stmt_check->execute();
if (!$stmt_check->fetch()) {
    $db->prepare("INSERT INTO curriculos (usuario_id, nome_completo, email, data_nascimento, sexo, endereco, telefone1, aprovado, foto_path)
                  SELECT id, 'Administrador Master', 'admin@acip.com.br', '1990-01-01', 'M', 'Sede ACIP', '(00) 00000-0000', 1, 'foto.png'
                  FROM usuarios WHERE login = 'acip'")->execute();
    echo "<p>✅ Perfil de currículo criado automaticamente.</p>";
}
?>
