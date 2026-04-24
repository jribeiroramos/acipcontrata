<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SEGURANÇA UNIFICADA: Permite Admin e Superadmin
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_role'], ['admin', 'superadmin'])) {
    header("Location: login.php"); exit;
}

require_once 'includes/header.php';
require_once 'classes/Database.php';

$db = Database::getConnection();
$minha_role = $_SESSION['usuario_role'];

// 1. Estatísticas Rápidas
$total_cvs = $db->query("SELECT COUNT(*) FROM curriculos")->fetchColumn();
$total_aprovados = $db->query("SELECT COUNT(*) FROM curriculos WHERE aprovado = 1")->fetchColumn();
$total_pendentes = $db->query("SELECT COUNT(*) FROM curriculos WHERE aprovado = 0")->fetchColumn();

// 2. Dados: Perfil por Sexo
$dados_sexo = $db->query("SELECT sexo, COUNT(*) as qtd FROM curriculos GROUP BY sexo")->fetchAll(PDO::FETCH_ASSOC);
$labels_sexo = []; $valores_sexo = [];
foreach($dados_sexo as $d) {
    $labels_sexo[] = $d['sexo'] == 'M' ? 'Masculino' : ($d['sexo'] == 'F' ? 'Feminino' : 'Outro');
    $valores_sexo[] = $d['qtd'];
}

// 3. Dados: Nível de Escolaridade
$dados_esc = $db->query("SELECT n.descricao, COUNT(f.id) as qtd FROM formacoes f JOIN niveis_escolaridade n ON f.nivel_id = n.id GROUP BY n.id ORDER BY qtd DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$labels_esc = []; $valores_esc = [];
foreach($dados_esc as $e) { $labels_esc[] = $e['descricao']; $valores_esc[] = $e['qtd']; }

// 4. Dados: Top Cursos
$dados_cursos = $db->query("SELECT c.nome, COUNT(distinct f.curriculo_id) as qtd FROM formacoes f JOIN cursos c ON f.curso_id = c.id WHERE c.nome != 'Não se aplica' GROUP BY c.id ORDER BY qtd DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$labels_cursos = []; $valores_cursos = [];
foreach($dados_cursos as $dc) { $labels_cursos[] = $dc['nome']; $valores_cursos[] = $dc['qtd']; }

// 5. Dados: Cargos com Mais Candidatos
$dados_cargos = $db->query("SELECT c.nome, COUNT(e.id) as qtd FROM experiencias e JOIN cargos c ON e.cargo_id = c.id GROUP BY c.id ORDER BY qtd DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$labels_cargos = []; $valores_cargos = [];
foreach($dados_cargos as $dg) { $labels_cargos[] = $dg['nome']; $valores_cargos[] = $dg['qtd']; }
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    html, body { height: 100%; margin: 0; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
    .content-grow { flex: 1; padding: 30px 0; }
    .acip-nav { background-color: white; border-bottom: 3px solid var(--acip-yellow); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .nav-link { color: #475569 !important; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; padding: 15px 20px !important; transition: 0.3s; }
    .nav-link:hover { color: var(--acip-green) !important; background-color: #f0fdf4; }
    .nav-link.active { color: var(--acip-green) !important; border-bottom: 3px solid var(--acip-green); }
    .section-title { color: var(--acip-green); font-weight: 800; border-left: 6px solid var(--acip-yellow); padding-left: 15px; margin-bottom: 5px; text-transform: uppercase; }
    .card-stat { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: white; transition: 0.3s; height: 100%; }
    .card-stat:hover { transform: translateY(-5px); }
    .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 15px; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-wrapper">
    <nav class="navbar navbar-expand-lg acip-nav sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><img src="assets/img/logo.jpeg" style="max-height: 40px;"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="gestao_candidatos.php"><i class="bi bi-people-fill me-1"></i> Candidatos</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_relatorios.php"><i class="bi bi-bar-chart-line-fill me-1"></i> Relatórios</a></li>
                    
                    <?php if ($minha_role === 'superadmin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin_manutencao.php"><i class="bi bi-layers-half me-1"></i> Tabelas de Apoio</a></li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="small fw-bold text-muted me-3"><i class="bi bi-person-circle"></i> <?= strtoupper($minha_role) ?></span>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger fw-bold" style="border-radius: 8px;">SAIR</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container content-grow">
        <h2 class="section-title">Painel de Controle Estratégico</h2>
        <p class="text-muted fw-bold small mb-5 ms-3">VISÃO GERAL DO BANCO DE TALENTOS ACIP</p>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card card-stat p-4" style="border-top: 5px solid var(--acip-green);">
                    <div class="icon-box bg-light text-success"><i class="bi bi-file-earmark-person"></i></div>
                    <h6 class="text-muted small fw-bold text-uppercase">Currículos Totais</h6>
                    <h2 class="fw-bold m-0" style="color: var(--acip-green);"><?= $total_cvs ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat p-4" style="border-top: 5px solid #10b981;">
                    <div class="icon-box bg-light text-primary"><i class="bi bi-check-circle-fill"></i></div>
                    <h6 class="text-muted small fw-bold text-uppercase">Currículos Aprovados</h6>
                    <h2 class="fw-bold m-0" style="color: #10b981;"><?= $total_aprovados ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat p-4" style="border-top: 5px solid #f59e0b;">
                    <div class="icon-box bg-light text-warning"><i class="bi bi-clock-history"></i></div>
                    <h6 class="text-muted small fw-bold text-uppercase">Aguardando Aprovação</h6>
                    <h2 class="fw-bold m-0" style="color: #f59e0b;"><?= $total_pendentes ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="card card-stat p-4">
                    <h6 class="fw-bold mb-4 text-uppercase small">Distribuição por Gênero</h6>
                    <div style="height: 250px;"><canvas id="chartSexo"></canvas></div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card card-stat p-4">
                    <h6 class="fw-bold mb-4 text-uppercase small">Escolaridade (TOP 5)</h6>
                    <div style="height: 250px;"><canvas id="chartEscolaridade"></canvas></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card card-stat p-4">
                    <h6 class="fw-bold mb-4 text-uppercase small">Cursos com Mais Candidatos</h6>
                    <div style="height: 300px;"><canvas id="chartCursos"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card card-stat p-4">
                    <h6 class="fw-bold mb-4 text-uppercase small">Áreas de Maior Experiência</h6>
                    <div style="height: 300px;"><canvas id="chartCargos"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
Chart.defaults.font.family = "'Inter', sans-serif";

new Chart(document.getElementById('chartSexo'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels_sexo) ?>,
        datasets: [{
            data: <?= json_encode($valores_sexo) ?>,
            backgroundColor: ['#008445', '#e91e63', '#ff9800', '#64748b'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('chartEscolaridade'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_esc) ?>,
        datasets: [{ label: 'Candidatos', data: <?= json_encode($valores_esc) ?>, backgroundColor: '#008445', borderRadius: 8 }]
    },
    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('chartCursos'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_cursos) ?>,
        datasets: [{ label: 'Candidatos', data: <?= json_encode($valores_cursos) ?>, backgroundColor: '#fff200', borderRadius: 8 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('chartCargos'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_cargos) ?>,
        datasets: [{ label: 'Experiências', data: <?= json_encode($valores_cargos) ?>, backgroundColor: '#1e293b', borderRadius: 8 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});
</script>
