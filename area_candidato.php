<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }

require_once 'includes/header.php';
require_once 'classes/Database.php';

$db = Database::getConnection();
$uid = $_SESSION['usuario_id'];

// 1. Busca dados do currículo, incluindo a FOTO e o status
$stmt = $db->prepare("SELECT c.nome_completo, c.foto_path, c.aprovado, u.precisa_trocar_senha
                      FROM curriculos c
                      JOIN usuarios u ON c.usuario_id = u.id
                      WHERE c.usuario_id = ?");
$stmt->execute([$uid]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Redireciona para troca de senha se necessário
if (isset($dados['precisa_trocar_senha']) && $dados['precisa_trocar_senha'] == 1) {
    header("Location: trocar_senha_obrigatorio.php");
    exit;
}

$nome_exibicao = $dados['nome_completo'] ?? $_SESSION['usuario_login'] ?? 'Candidato';
$foto = (!empty($dados['foto_path']) && file_exists($dados['foto_path'])) ? $dados['foto_path'] : 'assets/img/default-user.png';

$status_texto = ($dados && $dados['aprovado']) ? "Perfil Aprovado ✅" : "Perfil em Análise ⏳";
$status_bg = ($dados && $dados['aprovado']) ? "#d1fae5" : "#fffbeb";
$status_color = ($dados && $dados['aprovado']) ? "#065f46" : "#b45309";
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }

    /* Ajuste para evitar a rolagem/paginação vertical */
    html, body { 
        height: 100%; 
        margin: 0; 
        overflow: hidden; /* Trava a rolagem do navegador */
    }

    .page-wrapper {
        display: flex;
        flex-direction: column;
        height: 100vh; 
        background: linear-gradient(135deg, var(--acip-green), #005a2f);
    }

    .content-grow {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px; /* Reduzido de 40px */
    }

    .card-area {
        background: white;
        border-radius: 30px;
        padding: 25px 40px; /* Reduzido de 40px vertical */
        width: 100%;
        max-width: 500px; /* Reduzido de 550px */
        box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        text-align: center;
    }

    .logo-container {
        margin-bottom: 15px; /* Reduzido de 30px */
        display: block;
    }

    .foto-container {
        margin-bottom: 15px; /* Reduzido de 20px */
        display: block;
    }

    .img-perfil-area {
        width: 110px; /* Reduzido de 140px */
        height: 110px;
        object-fit: cover;
        border-radius: 50%;
        border: 5px solid var(--acip-yellow);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .btn-premium {
        background-color: var(--acip-green);
        color: white;
        font-weight: 800;
        border-radius: 12px;
        padding: 12px 30px;
        text-transform: uppercase;
        border: none;
        transition: 0.3s;
        text-decoration: none;
        display: inline-block;
        width: 100%;
    }

    .btn-premium:hover {
        background-color: var(--acip-yellow);
        color: var(--acip-green);
        transform: translateY(-3px);
    }

    .btn-outline-custom {
        border: 2px solid #e2e8f0;
        color: #475569;
        font-weight: 700;
        border-radius: 12px;
        padding: 10px 30px;
        text-decoration: none;
        transition: 0.3s;
        display: inline-block;
        width: 100%;
        margin-top: 5px;
    }

    .footer-minimal {
        background-color: rgba(0,0,0,0.2);
        color: white;
        padding: 15px 0; /* Reduzido de 25px */
        text-align: center;
        font-size: 0.75rem;
        backdrop-filter: blur(10px);
    }

    .badge-oficial {
        border: 1px solid rgba(255,255,255,0.3);
        padding: 3px 10px;
        border-radius: 8px;
        margin: 0 5px;
        font-weight: bold;
    }

    h1.h3 { font-size: 1.5rem; }
</style>

<div class="page-wrapper">
    <div class="content-grow">
        <div class="card-area">
            <div class="logo-container">
                <img src="assets/img/logo.jpeg" alt="ACIP" style="max-height: 60px;"> </div>

            <?php if (isset($_GET['sucesso_senha'])): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-4 small fw-bold mb-3 p-2">
                    <i class="bi bi-shield-check me-2"></i> Senha alterada com sucesso!
                </div>
            <?php endif; ?>

            <div class="foto-container">
                <img src="<?= $foto ?>" class="img-perfil-area" alt="Foto do Candidato">
            </div>

            <h1 class="fw-bold h3 mb-1" style="color: #1e293b;">Olá, <?= htmlspecialchars(explode(' ', $nome_exibicao)[0]) ?>!</h1>
            <p class="text-muted mb-3 small fw-bold text-uppercase">Painel do Candidato • ACIP Palestina</p>

            <div class="p-2 mb-3 rounded-4 d-flex align-items-center justify-content-center" style="background-color: <?= $status_bg ?>; color: <?= $status_color ?>; border: 1px solid rgba(0,0,0,0.05);">
                <span class="fw-bold fs-6"><?= $status_texto ?></span>
            </div>

            <div class="d-grid gap-2">
                <a href="editar_curriculo.php" class="btn-premium shadow">
                    <i class="bi bi-pencil-square me-2"></i> Atualizar Meu Currículo
                </a>

                <a href="alterar_senha.php" class="btn-outline-custom">
                    <i class="bi bi-key me-2"></i> Alterar Minha Senha
                </a>
            </div>

            <div class="mt-3 pt-3 border-top">
                <a href="logout.php" class="text-danger fw-bold text-decoration-none small">
                    <i class="bi bi-box-arrow-right me-1"></i> Sair do Sistema
                </a>
            </div>

            <div class="mt-3">
                <p class="text-muted fst-italic small mb-0" style="opacity: 0.7; font-size: 0.7rem;">
                    "Entrega o teu caminho ao Senhor; confia nele, e ele tudo fará."<br>Salmos 37:5
                </p>
            </div>
        </div>
    </div>

    <div class="footer-minimal">
        <div class="container">
            <span class="badge-oficial">PLATAFORMA OFICIAL ACIP</span>
            <span>© 2026 ACIP - Palestina</span>
            <span class="badge-oficial">LGPD COMPLIANT</span>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
