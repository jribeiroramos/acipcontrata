<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
require_once 'includes/header.php';
require_once 'classes/Database.php';

$db = Database::getConnection();
$uid_sessao = $_SESSION['usuario_id'];
$id_url = isset($_GET['id']) ? $_GET['id'] : null;

// CORREÇÃO: Permissão agora aceita Admin e Superadmin
$minha_role = $_SESSION['usuario_role'] ?? 'user';
$tem_privilegio = in_array($minha_role, ['admin', 'superadmin']);

// Busca principal: JOIN com usuarios para verificar a role do alvo e localidade
$sql_main = "SELECT c.*, ci.municipio, ci.uf, u.role as target_role
             FROM curriculos c
             JOIN usuarios u ON c.usuario_id = u.id
             LEFT JOIN cidades ci ON c.cidade_ibge = ci.ibge
             WHERE " . (($id_url && $tem_privilegio) ? "c.id = ?" : "c.usuario_id = ?");

$stmt = $db->prepare($sql_main);
$stmt->execute([($id_url && $tem_privilegio) ? $id_url : $uid_sessao]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    die("<div class='container mt-5'><div class='alert alert-danger shadow-sm rounded-4'>Currículo não localizado ou acesso negado.</div></div>");
}

// TRAVA DE SEGURANÇA: Admin não pode visualizar Superadmin
if ($minha_role === 'admin' && $dados['target_role'] === 'superadmin') {
    header("Location: gestao_candidatos.php?erro=permissao_negada");
    exit;
}

// Cálculo da Idade
$nascimento = new DateTime($dados['data_nascimento']);
$hoje = new DateTime('2026-02-25'); // Data base do sistema
$idade = $hoje->diff($nascimento)->y;

// Busca de Históricos
$stmt_exp = $db->prepare("SELECT e.*, carg.nome as cargo_nome, ci.municipio as cidade_nome, ci.uf as uf_nome
                            FROM experiencias e
                            JOIN cargos carg ON e.cargo_id = carg.id
                            LEFT JOIN cidades ci ON e.cidade_ibge = ci.ibge
                            WHERE e.curriculo_id = ?
                            ORDER BY e.dt_inicio_experiencia DESC");
$stmt_exp->execute([$dados['id']]);
$experiencias = $stmt_exp->fetchAll();

$stmt_form = $db->prepare("SELECT f.*, n.descricao as nivel_desc, crs.nome as curso_nome
                         FROM formacoes f
                         JOIN niveis_escolaridade n ON f.nivel_id = n.id
                         LEFT JOIN cursos crs ON f.curso_id = crs.id
                         WHERE f.curriculo_id = ?
                         ORDER BY f.ano_conclusao DESC");
$stmt_form->execute([$dados['id']]);
$formacoes = $stmt_form->fetchAll();

$stmt_conh = $db->prepare("SELECT * FROM conhecimentos WHERE curriculo_id = ?");
$stmt_conh->execute([$dados['id']]);
$conhecimentos = $stmt_conh->fetchAll();

function dataBR($data) { return ($data && $data !== '0000-00-00') ? date('d/m/Y', strtotime($data)) : '---'; }
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: #1e293b; }

    .cv-paper {
        background: white;
        width: 210mm;
        min-height: 297mm;
        margin: 30px auto;
        padding: 15mm 20mm;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        border-top: 12px solid var(--acip-green);
    }

    .cv-header { display: flex; align-items: flex-start; gap: 30px; margin-bottom: 20px; }
    .cv-photo-container { width: 130px; height: 160px; overflow: hidden; border: 4px solid var(--acip-yellow); border-radius: 12px; background: #f8fafc; flex-shrink: 0; }
    .cv-photo { width: 100%; height: 100%; object-fit: cover; }
    .cv-name { font-size: 26pt; font-weight: 900; color: var(--acip-green); text-transform: uppercase; margin: 0; line-height: 1.1; }

    .header-divider { border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px; }

    .cv-section-title {
        background-color: #f0fdf4;
        border-left: 6px solid var(--acip-green);
        color: var(--acip-green);
        padding: 8px 15px;
        font-size: 10.5pt;
        font-weight: 800;
        text-transform: uppercase;
        margin: 20px 0 12px 0;
    }

    .info-label { font-weight: 700; color: #64748b; font-size: 8pt; text-transform: uppercase; margin-top: 6px; }

    .info-value {
        font-size: 10pt;
        color: #1e293b;
        margin-bottom: 2px;
        overflow-wrap: anywhere;
        word-break: normal;
        display: block;
    }

    .badge-status-top { font-size: 8pt; padding: 4px 12px; border-radius: 20px; font-weight: 800; display: inline-block; margin-top: 10px; }

    @media print {
        body { background: white; }
        .no-print { display: none !important; }
        .cv-paper { margin: 0; padding: 10mm; width: 100%; border: none; box-shadow: none; }
    }
</style>

<div class="cv-paper">
    <div class="cv-header">
        <div class="cv-photo-container">
            <img src="<?= (!empty($dados['foto_path']) && file_exists($dados['foto_path'])) ? $dados['foto_path'] : 'assets/img/default-user.png'; ?>" class="cv-photo">
        </div>
        <div class="flex-grow-1" style="min-width: 0;"> 
            <h1 class="cv-name"><?= htmlspecialchars($dados['nome_completo'] ?? '') ?></h1>

            <?php if($dados['target_role'] !== 'user'): ?>
                <div class="badge-status-top bg-dark text-warning">PERFIL ADMINISTRATIVO: <?= strtoupper($dados['target_role']) ?></div>
            <?php endif; ?>

            <div class="row mt-3">
                <div class="col-7"> 
                    <div class="info-label">E-mail</div>
                    <div class="info-value"><strong><?= htmlspecialchars($dados['email'] ?? '') ?></strong></div>

                    <div class="info-label">WhatsApp / Tel Principal</div>
                    <div class="info-value"><strong><?= htmlspecialchars($dados['telefone1'] ?? '') ?></strong></div>

                    <div class="info-label">Telefone 2 (Recado)</div>
                    <div class="info-value"><?= htmlspecialchars($dados['telefone2'] ?? '---') ?></div>
                </div>
                <div class="col-5">
                    <div class="info-label">Idade / Sexo</div>
                    <div class="info-value"><?= $idade ?> anos • <?= $dados['sexo'] == 'M' ? 'Masculino' : ($dados['sexo'] == 'F' ? 'Feminino' : 'Outro') ?></div>

                    <div class="info-label">Cidade/UF Atual</div>
                    <div class="info-value"><?= htmlspecialchars($dados['municipio'] ?? '---') ?> - <?= htmlspecialchars($dados['uf'] ?? '---') ?></div>

                    <div class="info-label">Endereço Residencial</div>
                    <div class="info-value"><?= htmlspecialchars($dados['endereco'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="header-divider"></div>

    <?php if(!empty($dados['cnh_numero'])): ?>
    <div class="cv-section-title">Documentação (CNH)</div>
    <div class="row">
        <div class="col-4"><div class="info-label">Número</div><div class="info-value"><?= htmlspecialchars($dados['cnh_numero'] ?? '') ?></div></div>
        <div class="col-4"><div class="info-label">Categoria</div><div class="info-value"><strong><?= htmlspecialchars($dados['cnh_categoria'] ?? '') ?></strong></div></div>
        <div class="col-4"><div class="info-label">Validade</div><div class="info-value"><?= dataBR($dados['cnh_validade']) ?></div></div>
    </div>
    <?php endif; ?>

    <div class="cv-section-title">Objetivo Profissional</div>
    <div style="font-size: 10.5pt; text-align: justify; line-height: 1.5; color: #334155;">
        <?= nl2br(htmlspecialchars($dados['objetivo'] ?: 'Candidato em busca de novas oportunidades profissionais.')) ?>
    </div>

    <div class="cv-section-title">Experiência Profissional</div>
    <?php if($experiencias): foreach($experiencias as $exp): ?>
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="fw-bold" style="font-size: 11pt;"><?= htmlspecialchars($exp['cargo_nome'] ?? '') ?></div>
                <div class="text-muted small fw-bold"><?= dataBR($exp['dt_inicio_experiencia']) ?> — <?= $exp['dt_fim_experiencia'] ? dataBR($exp['dt_fim_experiencia']) : 'Atual' ?></div>
            </div>
            <div class="text-success fw-bold small mb-1">
                <?= htmlspecialchars($exp['empresa'] ?? '') ?>
                <span class="text-muted fw-normal ms-2">| <?= htmlspecialchars($exp['cidade_nome'] ?? '') ?> - <?= htmlspecialchars($exp['uf_nome'] ?? '') ?></span>
            </div>
            <div style="font-size: 10pt; color: #475569; text-align: justify; border-left: 2px solid #e2e8f0; padding-left: 15px; margin-top: 5px;">
                <?= nl2br(htmlspecialchars($exp['descricao_atividades'] ?? '')) ?>
            </div>
        </div>
    <?php endforeach; else: echo "<p class='small text-muted'>Nenhuma experiência registrada.</p>"; endif; ?>

    <div class="cv-section-title">Formação Acadêmica</div>
    <?php if($formacoes): foreach($formacoes as $f): ?>
        <div class="mb-3">
            <div class="fw-bold" style="font-size: 10.5pt;">
                <?= htmlspecialchars($f['nivel_desc'] ?? '') ?> <?= ($f['curso_nome'] != 'Não se aplica') ? ' - ' . htmlspecialchars($f['curso_nome'] ?? '') : '' ?>
            </div>
            <div class="text-muted small"><?= htmlspecialchars($f['instituicao'] ?? '') ?> • Conclusão em <?= htmlspecialchars($f['ano_conclusao'] ?? '') ?></div>
        </div>
    <?php endforeach; else: echo "<p class='small text-muted'>Sem formação registrada.</p>"; endif; ?>

    <?php if($conhecimentos): ?>
    <div class="cv-section-title">Habilidades e Cursos Complementares</div>
    <div class="row g-3">
        <?php foreach($conhecimentos as $c): ?>
            <div class="col-6">
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-2" style="font-size: 7pt; font-weight: 800;"><?= htmlspecialchars($c['nivel'] ?? '') ?></span>
                    <span style="font-size: 10pt; font-weight: 600;"><?= htmlspecialchars($c['descricao'] ?? '') ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="container text-center no-print mt-3 mb-5">
    <div class="d-flex justify-content-center gap-3">
        <a href="<?= $tem_privilegio ? 'gestao_candidatos.php' : 'area_candidato.php' ?>" class="btn btn-outline-dark px-4 fw-bold" style="border-radius: 12px;">
            <i class="bi bi-arrow-left me-2"></i> Voltar
        </a>
        <button onclick="window.print()" class="btn btn-success px-5 shadow fw-bold" style="border-radius: 12px;">
            <i class="bi bi-printer me-2"></i> Imprimir Currículo
        </button>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
