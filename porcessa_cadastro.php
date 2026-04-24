<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: cadastro.php"); exit; }

if (strlen($_POST['senha']) < 6) { header("Location: cadastro.php?erro=senha_curta"); exit; }

$db = Database::getConnection();

try {
    $db->beginTransaction();

    // 1. Criar Usuário
    $stmt_user = $db->prepare("INSERT INTO usuarios (login, senha) VALUES (?, ?)");
    $stmt_user->execute([$_POST['login'], password_hash($_POST['senha'], PASSWORD_DEFAULT)]);
    $usuario_id = $db->lastInsertId();

    // 2. Currículo (19 Campos)
    $data_nasc = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
    $cidade = !empty($_POST['cidade_ibge']) ? $_POST['cidade_ibge'] : null;

    $sql_cv = "INSERT INTO curriculos (
        usuario_id, nome_completo, email, data_nascimento, sexo,
        endereco, cidade_ibge, telefone1, telefone2, consentimento_lgpd
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_cv = $db->prepare($sql_cv);
    $stmt_cv->execute([
        $usuario_id, $_POST['nome_completo'], $_POST['email'], $data_nasc,
        $_POST['sexo'], $_POST['endereco'], $cidade, $_POST['telefone1'],
        $_POST['telefone2'], 1
    ]);
    $curriculo_id = $db->lastInsertId();

    // 3. Formações (Loop 4 Blocos)
    if (!empty($_POST['nivel_id'])) {
        $stmt_f = $db->prepare("INSERT INTO formacoes (curriculo_id, nivel_id, curso_id, instituicao, ano_conclusao) VALUES (?, ?, ?, ?, ?)");
        foreach ($_POST['nivel_id'] as $k => $nivel) {
            if (!empty($nivel)) {
                $ano = !empty($_POST['ano_conclusao'][$k]) ? $_POST['ano_conclusao'][$k] : null;
                $stmt_f->execute([$curriculo_id, $nivel, $_POST['curso_id'][$k], $_POST['instituicao'][$k], $ano]);
            }
        }
    }

    // 4. Conhecimentos (Loop 4 Blocos)
    if (!empty($_POST['conhecimento_desc'])) {
        $stmt_c = $db->prepare("INSERT INTO conhecimentos (curriculo_id, descricao, nivel) VALUES (?, ?, ?)");
        foreach ($_POST['conhecimento_desc'] as $k => $desc) {
            if (!empty($desc)) {
                $stmt_c->execute([$curriculo_id, $desc, $_POST['conhecimento_nivel'][$k]]);
            }
        }
    }

    // 5. Experiências (Loop 4 Blocos)
    if (!empty($_POST['empresa'])) {
        $stmt_e = $db->prepare("INSERT INTO experiencias (curriculo_id, empresa, cargo_id, dt_inicio_experiencia, dt_fim_experiencia, descricao_atividades) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($_POST['empresa'] as $k => $empresa) {
            if (!empty($empresa)) {
                $ini = !empty($_POST['dt_inicio_experiencia'][$k]) ? $_POST['dt_inicio_experiencia'][$k] : null;
                $fim = !empty($_POST['dt_fim_experiencia'][$k]) ? $_POST['dt_fim_experiencia'][$k] : null;
                $desc = !empty($_POST['descricao_atividades'][$k]) ? $_POST['descricao_atividades'][$k] : null;
                $stmt_e->execute([$curriculo_id, $empresa, !empty($_POST['cargo_id'][$k]) ? $_POST['cargo_id'][$k] : null, $ini, $fim, $desc]);
            }
        }
    }

    $db->commit();
    header("Location: login.php?sucesso_cadastro=1");
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    header("Location: cadastro.php?erro=1");
}
