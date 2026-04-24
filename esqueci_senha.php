<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
?>

<div class="login-wrapper">
    <a href="index.php">
        <img src="assets/img/logo.jpeg" alt="ACIP Palestina" class="logo-login">
    </a>

    <div class="card-landing">
        <h2 class="h4 mb-2 text-center fw-bold" style="color: #0f172a;">Recuperar Senha</h2>
        <p class="text-muted text-center small mb-4">Insira seu e-mail para receber uma nova senha temporária.</p>
        
        <?php if(isset($_GET['status'])): ?>
            <?php if($_GET['status'] == 'sucesso'): ?>
                <div class="alert alert-success border-0 small text-center mb-4" style="border-radius: 12px;">
                    ✅ <strong>Sucesso!</strong> Verifique sua caixa de entrada.
                </div>
            <?php elseif($_GET['status'] == 'nao_encontrado'): ?>
                <div class="alert alert-danger border-0 small text-center mb-4" style="border-radius: 12px;">
                    ❌ <strong>Atenção:</strong> E-mail não encontrado na base.
                </div>
            <?php elseif($_GET['status'] == 'erro_envio'): ?>
                <div class="alert alert-warning border-0 small text-center mb-4" style="border-radius: 12px;">
                    ⚠️ <strong>Erro:</strong> Falha ao enviar e-mail. Tente mais tarde.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="processa_recuperacao.php" method="POST">
            <div class="mb-4 text-start">
                <label>E-Mail Cadastrado</label>
                <input type="email" name="email" class="form-control form-control-lg" required placeholder="exemplo@email.com">
            </div>
            
            <button type="submit" class="btn btn-acip shadow-sm">
                RECUPERAR ACESSO
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center">
            <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--acip-green); font-size: 0.9rem;">
                Voltar ao Login
            </a>
        </div>
    </div>

    <a href="index.php" class="link-voltar">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left me-1" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
        Voltar para a página inicial
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>
