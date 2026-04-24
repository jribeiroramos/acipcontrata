<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
require_once 'classes/Database.php';

// SEGURANÇA UNIFICADA: Permite acesso tanto para Admin quanto para Superadmin
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_role'], ['admin', 'superadmin'])) {
    header("Location: login.php"); exit;
}
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    html, body { height: 100%; margin: 0; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
    .content-grow { flex: 1; padding: 40px 0; }

    /* Navbar Premium Unificada */
    .acip-nav { background-color: white; border-bottom: 3px solid var(--acip-yellow); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .nav-link { color: #475569 !important; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; padding: 15px 20px !important; transition: 0.3s; }
    .nav-link:hover { color: var(--acip-green) !important; background-color: #f0fdf4; }
    .nav-link.active { color: var(--acip-green) !important; border-bottom: 3px solid var(--acip-green); }

    .section-title { color: var(--acip-green); font-weight: 800; border-left: 6px solid var(--acip-yellow); padding-left: 15px; margin-bottom: 5px; text-transform: uppercase; }

    /* Cards de Administração */
    .card-admin {
        transition: all 0.3s ease;
        border-radius: 25px;
        border: 2px solid transparent;
        background: white;
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .card-admin:hover {
        transform: translateY(-8px);
        border-color: var(--acip-yellow);
        box-shadow: 0 15px 35px rgba(0,132,69,0.1);
    }

    .icon-circle {
        width: 70px;
        height: 70px;
        background-color: #f0fdf4;
        color: var(--acip-green);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin: 0 auto 20px auto;
        transition: 0.3s;
    }

    .card-admin:hover .icon-circle {
        background-color: var(--acip-yellow);
        color: #854d0e;
    }

    .footer-minimal { background-color: var(--acip-green); color: white; padding: 25px 0; text-align: center; font-size: 0.85rem; margin-top: 50px; }
    .badge-oficial { border: 1px solid rgba(255,255,255,0.3); padding: 5px 15px; border-radius: 8px; margin: 0 5px; font-weight: bold; }
</style>

<div class="page-wrapper">
    <nav class="navbar navbar-expand-lg acip-nav sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="assets/img/logo.jpeg" alt="ACIP" style="max-height: 40px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="gestao_candidatos.php"><i class="bi bi-people-fill me-1"></i> Candidatos</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_relatorios.php"><i class="bi bi-bar-chart-line-fill me-1"></i> Relatórios</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_manutencao.php"><i class="bi bi-layers-half me-1"></i> Tabelas de Apoio</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="small fw-bold text-muted me-3"><i class="bi bi-person-circle"></i> <?= strtoupper($_SESSION['usuario_role'] ?? 'ADMIN') ?></span>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger fw-bold" style="border-radius: 8px;">SAIR</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container content-grow">
        <div class="row mb-5 align-items-center">
            <div class="col-12">
                <h2 class="section-title">Configurações de Tabelas de Apoio</h2>
                <p class="text-muted fw-bold small mb-0 ms-3 text-uppercase">Gerenciamento de Dados Mestres do Sistema</p>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <?php
            // Lista de tabelas gerenciáveis via CRUD Mestre
            $tabelas = [
                ['nome' => 'Cargos', 'slug' => 'cargos', 'icon' => 'bi-briefcase', 'desc' => 'Lista de cargos profissionais'],
                ['nome' => 'Cursos', 'slug' => 'cursos', 'icon' => 'bi-journal-bookmark', 'desc' => 'Graduações e cursos técnicos'],
                ['nome' => 'Estados Civis', 'slug' => 'estados_civis', 'icon' => 'bi-heart', 'desc' => 'Status de relacionamento'],
                ['nome' => 'Escolaridade', 'slug' => 'niveis_escolaridade', 'icon' => 'bi-mortarboard', 'desc' => 'Níveis de formação acadêmica']
            ];

            foreach($tabelas as $t):
            ?>
                <div class="col-md-6 col-lg-3">
                    <a href="admin_crud_mestre.php?t=<?= $t['slug'] ?>" class="card-admin p-5 text-center">
                        <div class="icon-circle shadow-sm">
                            <i class="bi <?= $t['icon'] ?>"></i>
                        </div>
                        <h5 class="fw-bold mb-2 text-dark"><?= $t['nome'] ?></h5>
                        <p class="small text-muted mb-0"><?= $t['desc'] ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="footer-minimal">
        <div class="container">
            <span class="badge-oficial">ÁREA ADMINISTRATIVA ACIP</span>
            <span>© 2026 ACIP - Palestina</span>
        </div>
    </div>
</div>
