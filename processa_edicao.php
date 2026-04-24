<?php
// 1. ATIVAÇÃO DE LOGS PARA DEPURAÇÃO
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'classes/Database.php';

// Segurança: Verifica se o usuário está logado e se a requisição é POST
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$db = Database::getConnection();
$uid = $_SESSION['usuario_id'];
$curriculo_id = $_POST['curriculo_id'];

try {
    // 1. VALIDAÇÃO DE E-MAIL ÚNICO
    $novo_email = trim($_POST['email']);
    $stmt_email = $db->prepare("SELECT COUNT(*) FROM curriculos WHERE email = ? AND id != ?");
    $stmt_email->execute([$novo_email, $curriculo_id]);
    if ($stmt_email->fetchColumn() > 0) {
        $_SESSION['erro_detalhado'] = "O e-mail '$novo_email' já está sendo usado por outro cadastro.";
        header("Location: editar_curriculo.php?erro=email_duplicado");
        exit;
    }

    $db->beginTransaction();

    // 2. Atualização dos Dados Básicos, Localidade e CNH
    $objetivo     = $_POST['objetivo'] ?? '';
    $nome         = $_POST['nome_completo'];
    $data_nasc    = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
    $sexo         = $_POST['sexo'];
    $telefone1    = $_POST['telefone1'];
    $telefone2    = (!empty($_POST['telefone2']) && $_POST['telefone2'] !== 'Não informado') ? $_POST['telefone2'] : 'Não informado';
    $endereco     = $_POST['endereco'];
    $cidade_ibge  = !empty($_POST['cidade_ibge']) ? $_POST['cidade_ibge'] : null;
    $estado_civil = $_POST['estado_civil_id'];

    $cnh_num = !empty($_POST['cnh_numero']) ? $_POST['cnh_numero'] : null;
    $cnh_cat = !empty($_POST['cnh_categoria']) ? $_POST['cnh_categoria'] : null;
    $cnh_val = !empty($_POST['cnh_validade']) ? $_POST['cnh_validade'] : null;

    $sql_cv = "UPDATE curriculos SET 
                nome_completo = ?, data_nascimento = ?, sexo = ?, email = ?, 
                objetivo = ?, estado_civil_id = ?, telefone1 = ?, telefone2 = ?, 
                endereco = ?, cidade_ibge = ?, cnh_numero = ?, cnh_categoria = ?, cnh_validade = ?
               WHERE id = ? AND usuario_id = ?";

    $stmt_cv = $db->prepare($sql_cv);
    $stmt_cv->execute([
        $nome, $data_nasc, $sexo, $novo_email,
        $objetivo, $estado_civil, $telefone1, $telefone2,
        $endereco, $cidade_ibge, $cnh_num, $cnh_cat, $cnh_val,
        $curriculo_id, $uid
    ]);

    // 3. Processamento da Foto (Suporte a Recorte/Crop e Upload tradicional)
    $diretorio = 'uploads/fotos/';
    if (!is_dir($diretorio)) { mkdir($diretorio, 0755, true); }

    // PRIORIDADE: Foto recortada vinda do Cropper.js (Base64)
    if (!empty($_POST['cropped_image'])) {
        $data = $_POST['cropped_image'];

        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, etc
            $data = base64_decode($data);

            if ($data !== false) {
                $novo_nome = "perfil_crop_" . $uid . "_" . time() . "." . $type;
                $caminho_final = $diretorio . $novo_nome;

                if (file_put_contents($caminho_final, $data)) {
                    // Deletar foto antiga
                    $stmt_old = $db->prepare("SELECT foto_path FROM curriculos WHERE id = ?");
                    $stmt_old->execute([$curriculo_id]);
                    $foto_antiga = $stmt_old->fetchColumn();

                    if ($foto_antiga && $foto_antiga !== 'foto.png' && file_exists($foto_antiga)) {
                        @unlink($foto_antiga);
                    }
                    $db->prepare("UPDATE curriculos SET foto_path = ? WHERE id = ?")->execute([$caminho_final, $curriculo_id]);
                }
            }
        }
    } 
    // FALLBACK: Upload tradicional caso o JS falhe
    elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $novo_nome = "perfil_" . $uid . "_" . time() . "." . $extensao;
        $caminho_final = $diretorio . $novo_nome;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_final)) {
            $stmt_old = $db->prepare("SELECT foto_path FROM curriculos WHERE id = ?");
            $stmt_old->execute([$curriculo_id]);
            $foto_antiga = $stmt_old->fetchColumn();

            if ($foto_antiga && $foto_antiga !== 'foto.png' && file_exists($foto_antiga)) {
                @unlink($foto_antiga);
            }
            $db->prepare("UPDATE curriculos SET foto_path = ? WHERE id = ?")->execute([$caminho_final, $curriculo_id]);
        }
    }

    // 4. ATUALIZAÇÃO DE FORMAÇÕES EXISTENTES
    if (!empty($_POST['update_form_nivel'])) {
        $stmt_up_f = $db->prepare("UPDATE formacoes SET nivel_id = ?, curso_id = ?, instituicao = ?, ano_conclusao = ? WHERE id = ? AND curriculo_id = ?");
        foreach ($_POST['update_form_nivel'] as $fid => $nivel) {
            $ano = !empty($_POST['update_form_ano'][$fid]) ? $_POST['update_form_ano'][$fid] : null;
            $stmt_up_f->execute([
                $nivel,
                $_POST['update_form_curso'][$fid],
                $_POST['update_form_instituicao'][$fid],
                $ano,
                $fid,
                $curriculo_id
            ]);
        }
    }

    // 5. INSERÇÃO DE NOVAS FORMAÇÕES
    if (!empty($_POST['nivel_id'])) {
        $stmt_f_new = $db->prepare("INSERT INTO formacoes (curriculo_id, nivel_id, curso_id, instituicao, ano_conclusao) VALUES (?, ?, ?, ?, ?)");
        foreach ($_POST['nivel_id'] as $k => $nivel) {
            if (!empty($nivel) && !empty($_POST['instituicao'][$k])) {
                $ano_new = !empty($_POST['ano_conclusao'][$k]) ? $_POST['ano_conclusao'][$k] : null;
                $stmt_f_new->execute([
                    $curriculo_id,
                    $nivel,
                    $_POST['curso_id'][$k],
                    $_POST['instituicao'][$k],
                    $ano_new
                ]);
            }
        }
    }

    // 6. ATUALIZAÇÃO DE CONHECIMENTOS EXISTENTES
    if (!empty($_POST['update_conh_desc'])) {
        $stmt_up_c = $db->prepare("UPDATE conhecimentos SET descricao = ?, nivel = ? WHERE id = ? AND curriculo_id = ?");
        foreach ($_POST['update_conh_desc'] as $cid => $desc) {
            if (!empty($desc)) {
                $stmt_up_c->execute([$desc, $_POST['update_conh_nivel'][$cid], $cid, $curriculo_id]);
            }
        }
    }

    // 7. INSERÇÃO DE NOVOS CONHECIMENTOS
    if (!empty($_POST['conhecimento_desc'])) {
        $stmt_c_new = $db->prepare("INSERT INTO conhecimentos (curriculo_id, descricao, nivel) VALUES (?, ?, ?)");
        foreach ($_POST['conhecimento_desc'] as $k => $desc) {
            if (!empty($desc)) {
                $nivel_val = $_POST['conhecimento_nivel'][$k] ?? 'Básico';
                $stmt_c_new->execute([$curriculo_id, $desc, $nivel_val]);
            }
        }
    }

    // 8. ATUALIZAÇÃO DE EXPERIÊNCIAS EXISTENTES
    if (!empty($_POST['update_exp_empresa'])) {
        $stmt_up_e = $db->prepare("UPDATE experiencias SET empresa = ?, cargo_id = ?, cidade_ibge = ?, dt_inicio_experiencia = ?, dt_fim_experiencia = ?, descricao_atividades = ? WHERE id = ? AND curriculo_id = ?");
        foreach ($_POST['update_exp_empresa'] as $exid => $empresa) {
            $ini = !empty($_POST['update_exp_inicio'][$exid]) ? $_POST['update_exp_inicio'][$exid] : null;
            $fim = !empty($_POST['update_exp_fim'][$exid]) ? $_POST['update_exp_fim'][$exid] : null;
            $exp_cidade = !empty($_POST['update_exp_cidade'][$exid]) ? $_POST['update_exp_cidade'][$exid] : null;
            $stmt_up_e->execute([$empresa, $_POST['update_exp_cargo'][$exid], $exp_cidade, $ini, $fim, $_POST['update_exp_atividades'][$exid], $exid, $curriculo_id]);
        }
    }

    // 9. INSERÇÃO DE NOVAS EXPERIÊNCIAS
    if (!empty($_POST['empresa'])) {
        $stmt_e_new = $db->prepare("INSERT INTO experiencias (curriculo_id, empresa, cargo_id, cidade_ibge, dt_inicio_experiencia, dt_fim_experiencia, descricao_atividades) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($_POST['empresa'] as $k => $empresa) {
            if (!empty($empresa)) {
                $ini_new = !empty($_POST['dt_inicio_experiencia'][$k]) ? $_POST['dt_inicio_experiencia'][$k] : null;
                $fim_new = !empty($_POST['dt_fim_experiencia'][$k]) ? $_POST['dt_fim_experiencia'][$k] : null;
                $exp_cidade_new = !empty($_POST['exp_cidade_ibge'][$k]) ? $_POST['exp_cidade_ibge'][$k] : null;
                $stmt_e_new->execute([$curriculo_id, $empresa, $_POST['cargo_id'][$k], $exp_cidade_new, $ini_new, $fim_new, $_POST['descricao_atividades'][$k]]);
            }
        }
    }

    $db->commit();
    unset($_SESSION['erro_detalhado']);
    header("Location: editar_curriculo.php?sucesso=1");
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    $_SESSION['erro_detalhado'] = "Erro no Banco de Dados: " . $e->getMessage();
    error_log("Erro na edição do currículo (ID $uid): " . $e->getMessage());
    header("Location: editar_curriculo.php?erro=1");
    exit;
}
