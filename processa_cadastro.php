<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Acesso negado.'); }

/**
 * Persistência de Dados:
 * Salvamos o POST na sessão para que o cadastro.php recupere em caso de erro.
 */
$_POST['uf_cv_hidden'] = $_POST['uf_cv_hidden'] ?? ($_POST['uf_cv'] ?? '');
$_POST['exp_uf_hidden'] = $_POST['exp_uf_hidden'] ?? ($_POST['exp_uf'] ?? []);
$_SESSION['old_form'] = $_POST;

$db = Database::getConnection();

try {
    // --- 0. VALIDAÇÕES PRÉVIAS (E-mail, Login e Formatação) ---

    // Validação de Login: Impede espaços em branco
    if (preg_match('/\s/', $_POST['login'])) {
        header("Location: cadastro.php?erro=login_invalido");
        exit;
    }

    // Validação de E-mail Único
    $stmt_email = $db->prepare("SELECT id FROM curriculos WHERE email = ?");
    $stmt_email->execute([$_POST['email']]);
    if ($stmt_email->fetch()) {
        header("Location: cadastro.php?erro=email_duplicado");
        exit;
    }

    // Validação de Login Único
    $stmt_login = $db->prepare("SELECT id FROM usuarios WHERE login = ?");
    $stmt_login->execute([$_POST['login']]);
    if ($stmt_login->fetch()) {
        header("Location: cadastro.php?erro=login_duplicado");
        exit;
    }

    $db->beginTransaction();

    // 1. Criar Usuário
    $stmt_user = $db->prepare("INSERT INTO usuarios (login, senha) VALUES (?, ?)");
    $stmt_user->execute([
        $_POST['login'],
        password_hash($_POST['senha'], PASSWORD_DEFAULT)
    ]);
    $usuario_id = $db->lastInsertId();

    // 2. Processamento da Foto de Perfil (ALTERADO PARA FOTO PADRÃO)
    $foto_path = 'foto.png'; // Define foto.png como o valor padrão inicial

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $novo_nome = md5(uniqid()) . "." . $extensao;
        $diretorio = "uploads/fotos/";

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $diretorio . $novo_nome)) {
            $foto_path = $diretorio . $novo_nome; // Se houve upload, substitui o padrão pelo caminho novo
        }
    }

    // 3. Currículo Principal - Tratamento de Nulos
    $data_nasc = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
    $cnh_val   = !empty($_POST['cnh_validade']) ? $_POST['cnh_validade'] : null;
    $cidade_cv = !empty($_POST['cidade_ibge']) ? $_POST['cidade_ibge'] : null;

    $sql_cv = "INSERT INTO curriculos (
        usuario_id, nome_completo, email, objetivo, data_nascimento, sexo,
        cnh_numero, cnh_categoria, cnh_validade, endereco, cidade_ibge,
        telefone1, telefone2, foto_path, consentimento_lgpd, aprovado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)";

    $stmt_cv = $db->prepare($sql_cv);
    $stmt_cv->execute([
        $usuario_id,
        $_POST['nome_completo'],
        $_POST['email'],
        $_POST['objetivo'] ?? null,
        $data_nasc,
        $_POST['sexo'],
        $_POST['cnh_numero'] ?? null,
        $_POST['cnh_categoria'] ?? null,
        $cnh_val,
        $_POST['endereco'],
        $cidade_cv,
        $_POST['telefone1'],
        $_POST['telefone2'] ?? null,
        $foto_path // Grava ou 'foto.png' ou o novo caminho do upload
    ]);
    $curriculo_id = $db->lastInsertId();

    // 4. Formações Acadêmicas
    if (!empty($_POST['nivel_id'])) {
        $stmt_f = $db->prepare("INSERT INTO formacoes (curriculo_id, nivel_id, curso_id, instituicao, ano_conclusao) VALUES (?, ?, ?, ?, ?)");
        foreach ($_POST['nivel_id'] as $k => $nivel) {
            if (!empty($nivel)) {
                $ano_conc = !empty($_POST['ano_conclusao'][$k]) ? $_POST['ano_conclusao'][$k] : null;
                $stmt_f->execute([
                    $curriculo_id,
                    $nivel,
                    $_POST['curso_id'][$k],
                    $_POST['instituicao'][$k],
                    $ano_conc
                ]);
            }
        }
    }

    // 5. Conhecimentos e Habilidades
    if (!empty($_POST['conhecimento_desc'])) {
        $stmt_c = $db->prepare("INSERT INTO conhecimentos (curriculo_id, descricao, nivel) VALUES (?, ?, ?)");
        foreach ($_POST['conhecimento_desc'] as $k => $desc) {
            if (!empty($desc)) {
                $stmt_c->execute([
                    $curriculo_id,
                    $desc,
                    $_POST['conhecimento_nivel'][$k]
                ]);
            }
        }
    }

    // 6. Experiências Profissionais - CORREÇÃO DE DATA VAZIA
    if (!empty($_POST['empresa'])) {
        $sql_exp = "INSERT INTO experiencias (
            curriculo_id, empresa, cargo_id, cidade_ibge,
            dt_inicio_experiencia, dt_fim_experiencia, descricao_atividades
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt_e = $db->prepare($sql_exp);
        foreach ($_POST['empresa'] as $k => $empresa) {
            if (!empty($empresa)) {
                $dt_inicio = !empty($_POST['dt_inicio_experiencia'][$k]) ? $_POST['dt_inicio_experiencia'][$k] : null;
                $dt_fim    = !empty($_POST['dt_fim_experiencia'][$k]) ? $_POST['dt_fim_experiencia'][$k] : null;

                $stmt_e->execute([
                    $curriculo_id,
                    $empresa,
                    $_POST['cargo_id'][$k] ?? null,
                    $_POST['exp_cidade_ibge'][$k] ?? null,
                    $dt_inicio,
                    $dt_fim,
                    $_POST['descricao_atividades'][$k] ?? null
                ]);
            }
        }
    }

    $db->commit();

    unset($_SESSION['old_form']);
    unset($_SESSION['erro_detalhado']);

    header("Location: cadastro.php?sucesso=1");
    exit;

} catch (Exception $e) {
    if ($db && $db->inTransaction()) { $db->rollBack(); }

    error_log("Erro no cadastro: " . $e->getMessage());
    $_SESSION['erro_detalhado'] = $e->getMessage();

    header("Location: cadastro.php?erro=1");
    exit;
}
