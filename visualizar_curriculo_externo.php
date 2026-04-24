<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'includes/header.php';
require_once 'classes/Database.php';

// 1. SEGURANÇA: Verifica se está logado e se é ADMIN conforme sua coluna 'role'
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_role'] !== 'admin') {
    header("Location: login.php?erro=acesso_negado");
    exit;
}

$db = Database::getConnection();

// 2. RECUPERAÇÃO DO ID: Obtém o ID do currículo via URL (?id=X)
$id_curriculo = $_GET['id'] ?? null;

if (!$id_curriculo) {
    die("<div class='container mt-5 alert alert-warning'>ID do currículo não fornecido.</div>");
}

try {
    // 3. BUSCA DADOS: Mapeado exatamente com seu 'desc curriculos'
    $stmt = $db->prepare("SELECT c.*, ci.municipio, ci.uf 
                          FROM curriculos c 
                          LEFT JOIN cidades ci ON c.cidade_ibge = ci.ibge 
                          WHERE c.id = ?");
    $stmt->execute([$id_curriculo]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        die("<div class='container mt-5 alert alert-danger'>Currículo ID #$id_curriculo não encontrado no sistema.</div>");
    }

    // 4. BUSCA DEPENDÊNCIAS (Formações, Experiências, Conhecimentos)
    $experiencias = $db->query("SELECT e.*, carg.nome as cargo_nome FROM experiencias e JOIN cargos carg ON e.cargo_id = carg.id WHERE e.curriculo_id = {$dados['id']} ORDER BY e.id DESC")->fetchAll();
    $formacoes = $db->query("SELECT f.*, n.descricao as nivel, crs.nome as curso_nome FROM formacoes f JOIN niveis_escolaridade n ON f.nivel_id = n.id LEFT JOIN cursos crs ON f.curso_id = crs.id WHERE f.curriculo_id = {$dados['id']} ORDER BY f.ano_conclusao DESC")->fetchAll();
    $conhecimentos = $db->query("SELECT * FROM conhecimentos WHERE curriculo_id = {$dados['id']}")->fetchAll();

} catch (Exception $e) {
    die("Erro técnico: " . $e->getMessage());
}

function dataBR($data) { return ($data && $data !== '0000-00-00') ? date('d/m/Y', strtotime($data)) : '---'; }
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #1e293b; }
    
    .cv-paper { background: white; width: 210mm; min-height: 297mm; margin: 30px auto; padding: 25mm; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top: 10px solid var(--acip-green); position: relative; }
    
    .cv-header { display: flex; align-items: flex-start; margin-bottom: 35px; gap: 30px; }
    .cv-photo-container { width: 130px; height: 160px; overflow: hidden; border: 4px solid var(--acip-yellow); border-radius: 12px; flex-shrink: 0; background: #f1f5f9; }
    .cv-photo { width: 100%; height: 100%; object-fit: cover; }
    
    .cv-name { font-size: 24pt; font-weight: 800; color: var(--acip-green); text-transform: uppercase; margin: 0; line-height: 1.1; }
    .cv-section-title { background-color: #f0fdf4; border-left: 5px solid var(--acip-green); color: var(--acip-green); padding: 8px 15px; font-size: 11pt; font-weight: 800; text-transform: uppercase; margin: 30px 0 15px 0; }
    
    .cv-item-desc { font-size: 10pt; color: #475569; text-align: justify; line-height: 1.4; }
    .badge-nivel { background: var(--acip-yellow); color: #854d0e; padding: 2px 8px; border-radius: 4px; font-size: 8pt; font-weight: 800; margin-right: 5px; }

    @media print { 
        body { background: white; }
        .no-print { display: none !important; } 
        .cv-paper { margin: 0; padding: 15mm; width: 100%; border: none; box-shadow: none; }
    }
</style>

<div class="cv-paper">
    <div class="cv-header">
        <div class="cv-photo-container">
            <?php 
                $foto = (!empty($dados['foto_path']) && file_exists($dados['foto_path'])) ? $dados['foto_path'] : 'assets/img/default-user.png'; 
            ?>
            <img src="<?= $foto ?>" class="cv-photo" alt="Foto">
        </div>
        <div class="cv-info-header">
            <h1 class="cv-name"><?= htmlspecialchars($dados['nome_completo']) ?></h1>
            <p class="text-muted fw-bold mb-2">
                <?= $dados['sexo'] == 'M' ? 'Masculino' : ($dados['sexo'] == 'F' ? 'Feminino' : 'Outro') ?> • 
                Nascimento: <?= dataBR($dados['data_nascimento']) ?>
            </p>
            <div style="font-size: 9pt; color: #475569;">
                <strong>E-mail:</strong> <?= htmlspecialchars($dados['email']) ?><br>
                <strong>Telefone:</strong> <?= $dados['telefone1'] ?> / <?= $dados['telefone2'] ?><br>
                <strong>Endereço:</strong> <?= htmlspecialchars($dados['endereco']) ?><br>
                <strong>Cidade:</strong> <?= htmlspecialchars($dados['municipio']) ?> - <?= $dados['uf'] ?>
            </div>
        </div>
    </div>

    <?php if(!empty($dados['cnh_numero'])): ?>
    <div class="cv-section-title">Documentação (CNH)</div>
    <div class="cv-item-desc">
        <strong>Número:</strong> <?= htmlspecialchars($dados['cnh_numero']) ?> | 
        <strong>Categoria:</strong> <?= htmlspecialchars($dados['cnh_categoria']) ?> | 
        <strong>Validade:</strong> <?= dataBR($dados['cnh_validade']) ?>
    </div>
    <?php endif; ?>

    <?php if(!empty($dados['objetivo'])): ?>
    <div class="cv-section-title">Objetivo Profissional</div>
    <div class="cv-item-desc"><?= nl2br(htmlspecialchars($dados['objetivo'])) ?></div>
    <?php endif; ?>

    <div class="cv-section-title">Experiência Profissional</div>
    <?php if($experiencias): foreach($experiencias as $exp): ?>
        <div class="mb-3">
            <div class="fw-bold text-dark" style="font-size: 11pt;"><?= htmlspecialchars($exp['cargo_nome']) ?></div>
            <div class="text-success fw-bold small uppercase"><?= htmlspecialchars($exp['empresa']) ?></div>
            <div class="cv-item-desc mt-1"><?= nl2br(htmlspecialchars($exp['descricao_atividades'] ?? '')) ?></div>
        </div>
    <?php endforeach; else: echo "<p class='small text-muted'>Nenhuma experiência registrada.</p>"; endif; ?>

    <div class="cv-section-title">Formação Acadêmica</div>
    <?php foreach($formacoes as $f): ?>
        <div class="mb-2">
            <div class="fw-bold"><?= $f['nivel'] ?> <?= ($f['curso_nome'] !== 'Não se aplica') ? 'em ' . htmlspecialchars($f['curso_nome']) : '' ?></div>
            <div class="text-muted small"><?= htmlspecialchars($f['instituicao']) ?> (Conclusão: <?= $f['ano_conclusao'] ?>)</div>
        </div>
    <?php endforeach; ?>

    <?php if($conhecimentos): ?>
    <div class="cv-section-title">Conhecimentos e Habilidades</div>
    <div class="row g-2">
        <?php foreach($conhecimentos as $c): ?>
            <div class="col-6 small">
                <span class="badge-nivel"><?= $c['nivel'] ?></span> <strong><?= htmlspecialchars($c['descricao']) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="container text-center no-print mt-4 mb-5">
    <button onclick="window.print()" class="btn btn-success btn-lg px-5 shadow fw-bold" style="border-radius: 12px;">
        <i class="bi bi-printer me-2"></i> GERAR PDF / IMPRIMIR
    </button>
    <div class="mt-3">
        <a href="gestao_candidatos.php" class="btn btn-outline-dark fw-bold px-4">Voltar para Listagem</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
