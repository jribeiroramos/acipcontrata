<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
require_once 'classes/Database.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    html, body { height: 100%; margin: 0; }
    .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; background-color: #f8fafc; }
    .content-grow { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }

    .form-control:focus {
        border-color: var(--acip-yellow) !important;
        box-shadow: 0 0 0 0.25rem rgba(255, 242, 0, 0.25) !important;
        background-color: #fffdec !important;
    }

    .card-senha {
        background: white;
        border-radius: 25px;
        padding: 40px;
        width: 100%;
        max-width: 450px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.05);
    }

    .btn-premium {
        background-color: var(--acip-green);
        color: white;
        font-weight: 800;
        border-radius: 12px;
        padding: 12px;
        border: none;
        transition: 0.3s;
        width: 100%;
    }

    .btn-premium:hover { background-color: #006b38; transform: translateY(-2px); }
    
    .footer-minimal { background-color: var(--acip-green); color: white; padding: 25px 0; text-align: center; font-size: 0.85rem; }
</style>

<div class="page-wrapper">
    <div class="container content-grow">
        <div class="card-senha">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock-fill text-success" style="font-size: 3rem;"></i>
                <h2 class="fw-bold mt-2" style="color: var(--acip-green);">Alterar Senha</h2>
                <p class="text-muted small">Mantenha sua conta segura</p>
            </div>

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-danger small border-0 shadow-sm mb-4">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <?= ($_GET['erro'] == 'senha_atual') ? 'A senha atual está incorreta.' : 'As novas senhas não coincidem ou são muito curtas.' ?>
                </div>
            <?php endif; ?>

            <form action="processa_alterar_senha.php" method="POST" id="formSenha">
                <div class="mb-3">
                    <label class="small fw-bold text-muted">SENHA ATUAL</label>
                    <input type="password" name="senha_atual" class="form-control" required>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="small fw-bold text-muted">NOVA SENHA (MÍN. 6 CARACTERES)</label>
                    <input type="password" name="nova_senha" id="nova_senha" class="form-control" required minlength="6">
                </div>
                <div class="mb-4">
                    <label class="small fw-bold text-muted">CONFIRME A NOVA SENHA</label>
                    <input type="password" name="confirma_senha" id="confirma_senha" class="form-control" required>
                </div>

                <button type="submit" class="btn-premium shadow">
                    <i class="bi bi-check2-circle me-2"></i>ATUALIZAR SENHA
                </button>
                
                <div class="text-center mt-3">
                    <a href="area_candidato.php" class="text-decoration-none small fw-bold text-success">Voltar ao Painel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="footer-minimal">
        <div class="container">
            <span>© 2026 ACIP Palestina - Segurança LGPD</span>
        </div>
    </div>
</div>

<script>
document.getElementById('formSenha').onsubmit = function(e) {
    const s1 = document.getElementById('nova_senha').value;
    const s2 = document.getElementById('confirma_senha').value;
    if (s1 !== s2) {
        e.preventDefault();
        alert("As novas senhas não coincidem!");
    }
};
</script>
