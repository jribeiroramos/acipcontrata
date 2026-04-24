<?php
// 1. ATIVAÇÃO DE LOGS PARA DEPURAÇÃO
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'classes/Database.php';

// SEGURANÇA: Restrito ao Superadmin para salvar alterações
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_role'] !== 'superadmin') {
    header("Location: login.php"); exit;
}

$db = Database::getConnection();
$curriculo_id = $_POST['curriculo_id'];
$minha_role = $_SESSION['usuario_role'];

try {
    // 1. Verificação de Hierarquia (Proteção extra para o Banco)
    $stmt_check = $db->prepare("SELECT u.role, c.usuario_id FROM usuarios u JOIN curriculos c ON c.usuario_id = u.id WHERE c.id = ?");
    $stmt_check->execute([$curriculo_id]);
    $res_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$res_check) { die("Erro: Currículo não encontrado."); }

    $target_role = $res_check['role'];

    // Impede que um admin (se por acaso acessasse) editasse um Superadmin
    if ($minha_role === 'admin' && $target_role === 'superadmin') {
        die("Erro: Você não tem permissão para editar um Superadmin.");
    }

    $db->beginTransaction();

    // 2. Update Dados Básicos, Localidade e CNH
    $sql_cv = "UPDATE curriculos SET
                nome_completo = ?, data_nascimento = ?, sexo = ?, email = ?,
                telefone1 = ?, telefone2 = ?, endereco = ?, cidade_ibge = ?,
                estado_civil_id = ?, aprovado = ?, objetivo = ?,
                cnh_numero = ?, cnh_categoria = ?, cnh_validade = ?
               WHERE id = ?";

    $db->prepare($sql_cv)->execute([
        $_POST['nome_completo'],
        $_POST['data_nascimento'] ?: null,
        $_POST['sexo'],
        $_POST['email'],
        $_POST['telefone1'],
        $_POST['telefone2'] ?: 'Não informado',
        $_POST['endereco'],
        $_POST['cidade_ibge'],
        $_POST['estado_civil_id'],
        $_POST['aprovado'],
        $_POST['objetivo'],
        $_POST['cnh_numero'],
        $_POST['cnh_categoria'],
        $_POST['cnh_validade'] ?: null,
        $curriculo_id
    ]);

    // 3. Processamento de Alteração de Foto (Suporte ao Recorte/Crop e Upload Simples)
    $diretorio = 'uploads/fotos/';
    if (!is_dir($diretorio)) { mkdir($diretorio, 0755, true); }

    // PRIORIDADE 1: Imagem Recortada (Base64 enviada pelo Cropper.js)
    if (!empty($_POST['cropped_image'])) {
        $data = $_POST['cropped_image'];

        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, etc
            $data = base64_decode($data);

            if ($data !== false) {
                $novo_nome = "perfil_admin_crop_" . $curriculo_id . "_" . time() . "." . $type;
                $caminho_final = $diretorio . $novo_nome;

                if (file_put_contents($caminho_final, $data)) {
                    // Limpeza da foto antiga
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
    // PRIORIDADE 2: Upload Tradicional (Fallback se não houver recorte)
    elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $novo_nome = "perfil_admin_" . $curriculo_id . "_" . time() . "." . $extensao;
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

    // 4. FORMAÇÕES: Update das existentes
    if (!empty($_POST['update_form_nivel'])) {
        $stmt_up_f = $db->prepare("UPDATE formacoes SET nivel_id = ?, curso_id = ?, instituicao = ?, ano_conclusao = ? WHERE id = ? AND curriculo_id = ?");
        foreach ($_POST['update_form_nivel'] as $fid => $nivel) {
            $stmt_up_f->execute([$nivel, $_POST['update_form_curso'][$fid], $_POST['update_form_instituicao'][$fid], $_POST['update_form_ano'][$fid] ?: null, $fid, $curriculo_id]);
        }
    }
    // 4.1 FORMAÇÕES: Insert das novas
    if (!empty($_POST['nivel_id'])) {
        $stmt_in_f = $db->prepare("INSERT INTO formacoes (curriculo_id, nivel_id, curso_id, instituicao, ano_conclusao) VALUES (?, ?, ?, ?, ?)");
        foreach ($_POST['nivel_id'] as $k => $nivel) {
            if (!empty($nivel) && !empty($_POST['instituicao'][$k])) {
                $stmt_in_f->execute([$curriculo_id, $nivel, $_POST['curso_id'][$k], $_POST['instituicao'][$k], $_POST['ano_conclusao'][$k] ?: null]);
            }
        }
    }

    // 5. CONHECIMENTOS: Update das existentes (Suporta Básico, Intermediário, Avançado, Ativo, Concluído)
    if (!empty($_POST['update_conh_desc'])) {
        $stmt_up_c = $db->prepare("UPDATE conhecimentos SET descricao = ?, nivel = ? WHERE id = ? AND curriculo_id = ?");
        foreach ($_POST['update_conh_desc'] as $cid => $desc) {
            $stmt_up_c->execute([$desc, $_POST['update_conh_nivel'][$cid], $cid, $curriculo_id]);
        }
    }
    // 5.1 CONHECIMENTOS: Insert dos novos
    if (!empty($_POST['conhecimento_desc'])) {
        $stmt_in_c = $db->prepare("INSERT INTO conhecimentos (curriculo_id, descricao, nivel) VALUES (?, ?, ?)");
        foreach ($_POST['conhecimento_desc'] as $k => $desc) {
            if (!empty($desc)) {
                $stmt_in_c->execute([$curriculo_id, $desc, $_POST['conhecimento_nivel'][$k]]);
            }
        }
    }

    // 6. EXPERIÊNCIAS: Update das existentes (Suporte a Localidade)
    if (!empty($_POST['update_exp_empresa'])) {
        $stmt_up_e = $db->prepare("UPDATE experiencias SET empresa = ?, cargo_id = ?, cidade_ibge = ?, dt_inicio_experiencia = ?, dt_fim_experiencia = ?, descricao_atividades = ? WHERE id = ? AND curriculo_id = ?");
        foreach ($_POST['update_exp_empresa'] as $exid => $empresa) {
            $stmt_up_e->execute([
                $empresa,
                $_POST['update_exp_cargo'][$exid],
                $_POST['update_exp_cidade'][$exid] ?: null,
                $_POST['update_exp_inicio'][$exid],
                $_POST['update_exp_fim'][$exid] ?: null,
                $_POST['update_exp_atividades'][$exid],
                $exid,
                $curriculo_id
            ]);
        }
    }
    // 6.1 EXPERIÊNCIAS: Insert das novas
    if (!empty($_POST['empresa'])) {
        $stmt_in_e = $db->prepare("INSERT INTO experiencias (curriculo_id, empresa, cargo_id, cidade_ibge, dt_inicio_experiencia, dt_fim_experiencia, descricao_atividades) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($_POST['empresa'] as $k => $empresa) {
            if (!empty($empresa)) {
                $stmt_in_e->execute([
                    $curriculo_id,
                    $empresa,
                    $_POST['cargo_id'][$k],
                    $_POST['exp_cidade_ibge'][$k] ?: null,
                    $_POST['dt_inicio_experiencia'][$k],
                    $_POST['dt_fim_experiencia'][$k] ?: null,
                    $_POST['descricao_atividades'][$k]
                ]);
            }
        }
    }

    $db->commit();
    header("Location: editar_candidato_admin.php?id=$curriculo_id&msg=sucesso");
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    die("Erro ao salvar: " . $e->getMessage());
}
