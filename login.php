<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
?>

<div class="login-wrapper">
    <a href="index.php">
        <img src="assets/img/logo.jpeg" alt="ACIP Palestina" class="logo-login">
    </a>

    <div class="card-landing">
        <h2 class="h4 mb-4 text-center fw-bold" style="color: #0f172a;">Acesso ao Painel</h2>

        <?php if(isset($_GET['erro']) && $_GET['erro'] == '1'): ?>
            <div class="alert alert-danger mb-4 py-3 text-center small border-0" 
                 style="background: #fee2e2; color: #dc2626; border-radius: 12px; font-weight: 600;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-exclamation-octagon-fill me-2" viewBox="0 0 16 16">
                    <path d="M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353L11.46.146zM8 4c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995A.905.905 0 0 1 8 4zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                Usuário ou senha incorretos.
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['troca']) && $_GET['troca'] == 'sucesso'): ?>
            <div class="alert alert-success mb-4 py-3 text-center small border-0" 
                 style="background: #dcfce7; color: #166534; border-radius: 12px; font-weight: 600;">
                ✅ Senha atualizada com sucesso!<br>
                Faça login com seus novos dados.
            </div>
        <?php endif; ?>

        <form action="processa_login.php" method="POST">
            <div class="mb-3">
                <label class="small fw-bold">Login de Usuário</label>
                <input type="text" name="user" class="form-control form-control-lg" required placeholder="Seu usuário">
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between">
                    <label class="small fw-bold">Sua Senha</label>
                    <a href="esqueci_senha.php" class="text-decoration-none fw-bold" style="color: var(--acip-green); font-size: 0.8rem;">Esqueceu a senha?</a>
                </div>
                <input type="password" name="pass" class="form-control form-control-lg" required placeholder="••••••••">
            </div>

            <button type="submit" name="login_btn" class="btn btn-success w-100 py-3 fw-bold shadow-sm" style="border-radius: 12px;">
                ENTRAR NO SISTEMA
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center">
            <p class="small text-muted mb-1">Ainda não tem cadastro?</p>
            <a href="cadastro.php" class="fw-bold text-decoration-none" style="color: var(--acip-green);">Crie seu currículo aqui</a>
        </div>
    </div>

    <a href="index.php" class="link-voltar mt-4 d-block text-center text-decoration-none text-muted small">
        <i class="bi bi-arrow-left me-1"></i> Voltar para a página inicial
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>
