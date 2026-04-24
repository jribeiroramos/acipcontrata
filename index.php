<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: editar_curriculo.php");
    exit;
}
?>

<style>
    :root {
        --verde-acip-light: #0B8F3C;
        --verde-acip-dark: #067532;
        --amarelo-premium: #F4E400;
        --glass-white: rgba(255, 255, 255, 0.08);
    }

    /* Trava o corpo para não permitir rolagem */
    html, body {
        height: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        overflow: hidden; 
        background:
            radial-gradient(circle at 50% 0%, rgba(255,255,255,0.06), transparent 60%),
            linear-gradient(135deg, var(--verde-acip-light), var(--verde-acip-dark));
        font-family: 'Inter', sans-serif;
        color: white;
    }

    /* Wrapper principal que ocupa 100% da viewport */
    .main-wrapper {
        height: 100vh;
        width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        padding: 2vh 20px; /* Padding baseado na altura da tela */
        box-sizing: border-box;
    }

    .content-center {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 100%;
        max-width: 1100px;
        gap: 2vh; /* Espaçamento flexível entre elementos */
    }

    .logo-main {
        max-height: 12vh; /* Logo escala conforme a altura da tela */
        background: white;
        padding: 10px 20px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .hero-content h1 {
        font-size: clamp(1.5rem, 4vh, 2.5rem); /* Tamanho de fonte trava na altura */
        font-weight: 800;
        margin: 0;
        letter-spacing: -1px;
        line-height: 1.1;
    }

    .hero-content h1 span { color: var(--amarelo-premium); }

    .hero-content p {
        font-size: clamp(0.9rem, 2vh, 1.1rem);
        opacity: 0.9;
        margin: 1vh 0 0 0;
    }

    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        width: 100%;
        max-width: 900px;
    }

    .benefit-item {
        background: var(--glass-white);
        backdrop-filter: blur(10px);
        padding: 1.5vh 15px;
        border-radius: 15px;
        border: 1px solid rgba(255,255,255,0.1);
        border-top: 3px solid var(--amarelo-premium);
    }

    .benefit-item i { font-size: 1.4rem; color: var(--amarelo-premium); margin-bottom: 5px; display: block; }
    .benefit-item h4 { font-size: 0.85rem; font-weight: 800; margin-bottom: 5px; text-transform: uppercase; }
    .benefit-item p { font-size: 0.75rem; margin: 0; opacity: 0.8; line-height: 1.2; }

    .trust-line {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--amarelo-premium);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 0;
    }

    .btn-cadastro {
        background: var(--amarelo-premium);
        color: var(--verde-acip-dark) !important;
        padding: 12px 40px;
        border-radius: 10px;
        font-weight: 800;
        font-size: 1rem;
        text-transform: uppercase;
        text-decoration: none;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        display: inline-block;
        transition: 0.2s;
    }

    .btn-cadastro:hover { transform: scale(1.05); }

    .link-login {
        display: block;
        margin-top: 1vh;
        color: white;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        opacity: 0.7;
    }

    .footer-minimal {
        font-size: 0.7rem;
        opacity: 0.5;
        display: flex;
        justify-content: center;
        gap: 15px;
        padding-bottom: 10px;
    }

    /* Ajuste para telas muito baixas (Mobile em paisagem ou telas pequenas) */
    @media (max-height: 600px) {
        .benefits-grid { display: none; } /* Esconde o grid se a tela for muito baixa */
        .hero-content h1 { font-size: 1.4rem; }
    }

    @media (max-width: 768px) {
        .benefits-grid { grid-template-columns: 1fr; gap: 10px; }
        .benefit-item { padding: 10px; }
        .main-wrapper { overflow-y: auto; } /* No celular pequeno, o scroll é melhor que cortar */
    }
</style>

<div class="main-wrapper">
    <div class="content-center">
        <img src="assets/img/logo.jpeg" alt="ACIP" class="logo-main">

        <div class="hero-content text-center">
            <h1>Conecte seu talento às<br><span>oportunidades oficiais</span>.</h1>
            <p>Banco de talentos da <strong>ACIP - Palestina</strong>.</p>
        </div>

        <div class="benefits-grid">
            <div class="benefit-item">
                <i class="bi bi-patch-check-fill"></i>
                <h4>Gratuito</h4>
                <p>Perfil visível para empresas associadas.</p>
            </div>
            <div class="benefit-item">
                <i class="bi bi-lightning-fill"></i>
                <h4>Ágil</h4>
                <p>Cadastro completo em poucos minutos.</p>
            </div>
            <div class="benefit-item">
                <i class="bi bi-shield-lock-fill"></i>
                <h4>LGPD</h4>
                <p>Seus dados protegidos e seguros.</p>
            </div>
        </div>

        <div class="text-center">
            <p class="trust-line mb-3">+80 EMPRESAS CONECTADAS</p>
            <a href="cadastro.php" class="btn-cadastro">Começar Agora</a>
            <a href="login.php" class="link-login">Já é cadastrado? Acesse o painel</a>
        </div>
    </div>

    <div class="footer-minimal">
        <span>© 2026 ACIP - Palestina</span>
        <span>|</span>
        <span>Plataforma Oficial de Talentos</span>
    </div>
</div>
</body>
</html>
