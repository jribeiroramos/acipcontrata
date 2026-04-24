<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
require_once 'classes/Database.php';

// SEGURANÇA UNIFICADA: Admin e Superadmin
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_role'], ['admin', 'superadmin'])) {
    header("Location: login.php"); exit;
}

$db = Database::getConnection();
$minha_role = $_SESSION['usuario_role'];

// --- FILTROS DE RELATÓRIO ---
$f_cargo        = $_GET['f_cargo'] ?? '';
$f_sexo         = $_GET['f_sexo'] ?? '';
$f_escolaridade = $_GET['f_escolaridade'] ?? '';
$f_curso        = $_GET['f_curso'] ?? ''; // Filtro de Curso Preservado
$f_idade_min    = $_GET['f_idade_min'] ?? '';
$f_idade_max    = $_GET['f_idade_max'] ?? '';

// REGRA DE ISOLAMENTO: Admin não vê Superadmin
$filtro_hierarquia = "";
if ($minha_role === 'admin') {
    $filtro_hierarquia = " AND u.role != 'superadmin'";
}

// Query Master
$sql = "SELECT DISTINCT c.id, c.nome_completo, c.email, c.telefone1, c.sexo, c.data_nascimento,
        (YEAR(CURDATE()) - YEAR(c.data_nascimento)) as idade
        FROM curriculos c
        JOIN usuarios u ON c.usuario_id = u.id
        LEFT JOIN experiencias e ON c.id = e.curriculo_id
        LEFT JOIN formacoes f ON c.id = f.curriculo_id
        WHERE 1=1 $filtro_hierarquia";

$params = [];
if ($f_cargo) { $sql .= " AND e.cargo_id = ?"; $params[] = $f_cargo; }
if ($f_sexo) { $sql .= " AND c.sexo = ?"; $params[] = $f_sexo; }
if ($f_escolaridade) { $sql .= " AND f.nivel_id = ?"; $params[] = $f_escolaridade; }
if ($f_curso) { $sql .= " AND f.curso_id = ?"; $params[] = $f_curso; }
if ($f_idade_min) { $sql .= " AND (YEAR(CURDATE()) - YEAR(c.data_nascimento)) >= ?"; $params[] = $f_idade_min; }
if ($f_idade_max) { $sql .= " AND (YEAR(CURDATE()) - YEAR(c.data_nascimento)) <= ?"; $params[] = $f_idade_max; }

$sql .= " ORDER BY c.nome_completo ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Carregamento de dados para os selects
$cargos_opt = $db->query("SELECT id, nome FROM cargos ORDER BY nome ASC")->fetchAll();
$niveis_opt = $db->query("SELECT id, descricao FROM niveis_escolaridade ORDER BY id ASC")->fetchAll();
$cursos_opt = $db->query("SELECT id, nome FROM cursos ORDER BY nome ASC")->fetchAll();

// Nome do cargo e curso para o cabeçalho de impressão
$nome_cargo_filtro = "Todos";
if($f_cargo) {
    foreach($cargos_opt as $co) if($co['id'] == $f_cargo) $nome_cargo_filtro = $co['nome'];
}
$nome_curso_filtro = "Todos";
if($f_curso) {
    foreach($cursos_opt as $cu) if($cu['id'] == $f_curso) $nome_curso_filtro = $cu['nome'];
}
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    html, body { height: 100%; margin: 0; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
    .content-grow { flex: 1; padding-bottom: 60px; }
    .acip-nav { background-color: white; border-bottom: 3px solid var(--acip-yellow); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .nav-link { color: #475569 !important; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; padding: 15px 20px !important; }
    .nav-link.active { color: var(--acip-green) !important; border-bottom: 3px solid var(--acip-green); }
    .section-title { color: var(--acip-green); font-weight: 800; border-left: 6px solid var(--acip-yellow); padding-left: 15px; margin-bottom: 5px; text-transform: uppercase; }

    .filter-box { background: white; border-radius: 20px; padding: 25px; border: 1px solid #edf2f7; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    .table-res { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }

    @media print {
        .no-print { display: none !important; }
        .page-wrapper { padding: 0 !important; }
        body { background: white !important; }
        .table-res { box-shadow: none !important; border: 1px solid #eee; }
        .print-header { display: block !important; margin-bottom: 30px; border-bottom: 2px solid var(--acip-green); padding-bottom: 10px; }
    }
    .print-header { display: none; }
</style>

<div class="page-wrapper">
    <nav class="navbar navbar-expand-lg acip-nav sticky-top no-print">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><img src="assets/img/logo.jpeg" style="max-height: 40px;"></a>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="gestao_candidatos.php"><i class="bi bi-people-fill me-1"></i> Candidatos</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_relatorios.php"><i class="bi bi-bar-chart-line-fill me-1"></i> Relatórios</a></li>
                    
                    <?php if ($_SESSION['usuario_role'] === 'superadmin'): ?>
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

    <div class="container content-grow mt-4">
        <div class="print-header">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 150px;"><img src="assets/img/logo.jpeg" style="max-height: 60px;"></td>
                    <td>
                        <h3 style="margin:0; color: #008445;">RELATÓRIO DE CANDIDATOS - ACIP</h3>
                        <p style="margin:0; font-size: 10pt; color: #666;">
                            Filtro Cargo: <?= $nome_cargo_filtro ?> | Filtro Curso: <?= $nome_curso_filtro ?> | Gerado em: <?= date('d/m/Y H:i') ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h2 class="section-title">Relatórios Analíticos</h2>
            <button onclick="window.print()" class="btn btn-success fw-bold px-4 shadow" style="border-radius: 12px;">
                <i class="bi bi-printer me-2"></i>IMPRIMIR LISTA
            </button>
        </div>

        <div class="filter-box no-print">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="small fw-bold uppercase">Cargo Desejado</label>
                    <select name="f_cargo" class="form-select">
                        <option value="">Todos os Cargos</option>
                        <?php foreach($cargos_opt as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $f_cargo == $c['id'] ? 'selected' : '' ?>><?= $c['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold uppercase">Curso Específico</label>
                    <select name="f_curso" class="form-select">
                        <option value="">Todos os Cursos</option>
                        <?php foreach($cursos_opt as $curso): ?>
                            <option value="<?= $curso['id'] ?>" <?= $f_curso == $curso['id'] ? 'selected' : '' ?>><?= $curso['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold uppercase">Escolaridade</label>
                    <select name="f_escolaridade" class="form-select">
                        <option value="">Todos os Níveis</option>
                        <?php foreach($niveis_opt as $n): ?>
                            <option value="<?= $n['id'] ?>" <?= $f_escolaridade == $n['id'] ? 'selected' : '' ?>><?= $n['descricao'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold uppercase">Sexo</label>
                    <select name="f_sexo" class="form-select">
                        <option value="">Ambos</option>
                        <option value="M" <?= $f_sexo == 'M' ? 'selected' : '' ?>>Masc.</option>
                        <option value="F" <?= $f_sexo == 'F' ? 'selected' : '' ?>>Fem.</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold uppercase">Idade Mín/Máx</label>
                    <div class="input-group">
                        <input type="number" name="f_idade_min" class="form-control" placeholder="Min" value="<?= htmlspecialchars($f_idade_min ?? '') ?>">
                        <input type="number" name="f_idade_max" class="form-control" placeholder="Max" value="<?= htmlspecialchars($f_idade_max ?? '') ?>">
                    </div>
                </div>
                <div class="col-12 text-end">
                    <a href="admin_relatorios.php" class="btn btn-outline-secondary fw-bold me-2">LIMPAR</a>
                    <button type="submit" class="btn btn-dark fw-bold px-5">FILTRAR RESULTADOS</button>
                </div>
            </form>
        </div>

        <div class="table-res">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Candidato</th>
                        <th class="text-center">Idade</th>
                        <th class="text-center">Sexo</th>
                        <th>Contato Principal</th>
                        <th class="text-end pe-4 no-print">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($resultados): foreach($resultados as $r): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($r['nome_completo'] ?? '') ?></td>
                        <td class="text-center"><?= $r['idade'] ?> anos</td>
                        <td class="text-center"><?= $r['sexo'] ?></td>
                        <td>
                            <div class="small"><?= htmlspecialchars($r['email'] ?? '') ?></div>
                            <div class="small fw-bold text-success"><?= htmlspecialchars($r['telefone1'] ?? '') ?></div>
                        </td>
                        <td class="text-end pe-4 no-print">
                            <a href="visualizar_meu_curriculo.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-dark" target="_blank">Ver Currículo</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center p-5 text-muted">Nenhum candidato encontrado com estes filtros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer-minimal no-print">
        <div class="container"><span>© 2026 ACIP - Palestina</span></div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
