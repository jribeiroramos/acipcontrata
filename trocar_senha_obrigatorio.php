<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Segurança: Se não houver ID de usuário na sessão, manda para o login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/header.php';
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    html, body { height: 100%; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .login-wrapper { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
    .logo-login { max-height: 80px; margin-bottom: 20px; }
    .card-landing { 
        background: white; 
        padding: 40px; 
        border-radius: 25px; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
        width: 100%; 
        max-width: 450px; 
        border-top: 5px solid var(--acip-yellow);
    }
    .btn-acip { 
        background-color: var(--acip-green); 
        color: white; 
        width: 100%; 
        padding: 12px; 
        border-radius: 12px; 
        font-weight: 700; 
        border: none; 
        transition: 0.3s;
    }
    .btn-acip:hover { background-color: #006837; color: white; transform: translateY(-2px); }
</style>

<div class="login-wrapper">
    <div class="text-center mb-4">
        <a href="index.php">
            <img src="assets/img/logo.jpeg" alt="ACIP" class="logo-login">
        </a>
    </div>

    <div class="card-landing">
        <h2 class="h4 mb-3 text-center fw-bold" style="color: var(--acip-green);">Segurança Obrigatória</h2>
        <p class="text-muted text-center small mb-4">
            Você está usando uma senha temporária. Por favor, crie uma senha definitiva para continuar acessando sua área.
        </p>

        <?php if(isset($_GET['erro'])): ?>
            <div class="alert alert-danger border-0 small text-center mb-4" style="background: #fee2e2; color: #dc2626; border-radius: 12px; font-weight: 600;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php
                    if($_GET['erro'] == 'senhas_diferentes') echo "As senhas digitadas não são iguais.";
                    elseif($_GET['erro'] == 'curta') echo "A senha deve ter no mínimo 6 caracteres.";
                    elseif($_GET['erro'] == 'vazio') echo "Preencha todos os campos de senha.";
                    else echo "Ocorreu um erro ao atualizar. Tente novamente.";
                ?>
            </div>
        <?php endif; ?>

        <form action="processa_troca_obrigatoria.php" method="POST" onsubmit="return validarSenhas()">
            <div class="mb-3">
                <label class="small fw-bold mb-1">Nova Senha (Mín. 6 caracteres)</label>
                <input type="password" name="nova_senha" id="nova_senha" class="form-control form-control-lg" style="border-radius: 10px;" required minlength="6">
                <div class="form-text small" style="color: #64748b;">Dica: Misture letras e números.</div>
            </div>

            <div class="mb-4">
                <label class="small fw-bold mb-1">Confirmar Nova Senha</label>
                <input type="password" name="confirma_senha" id="confirma_senha" class="form-control form-control-lg" style="border-radius: 10px;" required>
            </div>

            <button type="submit" class="btn btn-acip shadow-sm">
                ATUALIZAR E ACESSAR PAINEL
            </button>
        </form>
    </div>
</div>

<script>
function validarSenhas() {
    var senha = document.getElementById("nova_senha").value;
    var confirma = document.getElementById("confirma_senha").value;

    if (senha !== confirma) {
        alert("As senhas não coincidem! Por favor, digite novamente.");
        return false;
    }
    
    if (senha.length < 6) {
        alert("A senha deve ter pelo menos 6 caracteres.");
        return false;
    }
    return true;
}
</script>

<?php require_once 'includes/footer.php'; ?>
