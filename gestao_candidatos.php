<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
require_once 'classes/Database.php';

// Segurança: Superadmin e Admin acessam a lista, mas as ações divergem abaixo
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_role'], ['superadmin', 'admin'])) {
    header("Location: login.php"); exit;
}

$db = Database::getConnection();
$minha_role = $_SESSION['usuario_role'];
$meu_id_usuario = $_SESSION['usuario_id'];

// --- Lógicas de Ação Direta (EXCLUSIVAS PARA SUPERADMIN) ---
if ($minha_role === 'superadmin') {

    // 1. Tornar Admin
    if (isset($_GET['tornar_admin'])) {
        $cid = $_GET['tornar_admin'];
        $stmt_u = $db->prepare("SELECT usuario_id FROM curriculos WHERE id = ?");
        $stmt_u->execute([$cid]);
        $uid = $stmt_u->fetchColumn();
        if ($uid && $uid != $meu_id_usuario) {
            $db->prepare("UPDATE usuarios SET role = 'admin' WHERE id = ?")->execute([$uid]);
            header("Location: gestao_candidatos.php?msg=role_atualizada"); exit;
        }
    }

    // 2. Tornar Superadmin
    if (isset($_GET['tornar_superadmin'])) {
        $cid = $_GET['tornar_superadmin'];
        $stmt_u = $db->prepare("SELECT usuario_id FROM curriculos WHERE id = ?");
        $stmt_u->execute([$cid]);
        $uid = $stmt_u->fetchColumn();
        if ($uid && $uid != $meu_id_usuario) {
            $db->prepare("UPDATE usuarios SET role = 'superadmin' WHERE id = ?")->execute([$uid]);
            header("Location: gestao_candidatos.php?msg=role_atualizada"); exit;
        }
    }

    // 3. Rebaixar para Usuário Comum
    if (isset($_GET['rebaixar_user'])) {
        $cid = $_GET['rebaixar_user'];
        $stmt_u = $db->prepare("SELECT usuario_id FROM curriculos WHERE id = ?");
        $stmt_u->execute([$cid]);
        $uid = $stmt_u->fetchColumn();
        if ($uid && $uid != $meu_id_usuario) {
            $db->prepare("UPDATE usuarios SET role = 'user' WHERE id = ?")->execute([$uid]);
            header("Location: gestao_candidatos.php?msg=role_atualizada"); exit;
        }
    }

    // 4. Exclusão de Registro
    if (isset($_GET['excluir'])) {
        $cid = $_GET['excluir'];
        $stmt_u = $db->prepare("SELECT usuario_id FROM curriculos WHERE id = ?");
        $stmt_u->execute([$cid]);
        $uid = $stmt_u->fetchColumn();
        if ($uid && $uid != $meu_id_usuario) {
            $db->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$uid]);
            header("Location: gestao_candidatos.php?msg=excluido"); exit;
        }
    }

    // 5. Aprovação de Currículo
    if (isset($_GET['aprovar'])) {
        $db->prepare("UPDATE curriculos SET aprovado = 1 WHERE id = ?")->execute([$_GET['aprovar']]);
        header("Location: gestao_candidatos.php?msg=aprovado"); exit;
    }
}

// --- Filtros de Busca e Travas de Visualização ---
$busca_nome = $_GET['busca_nome'] ?? '';

// REGRA DE ISOLAMENTO: Admin não vê Superadmin na lista
$filtro_hierarquia = "";
if ($minha_role === 'admin') {
    $filtro_hierarquia = " AND u.role != 'superadmin'";
}

$sql = "SELECT c.id, c.nome_completo, c.foto_path, c.aprovado, u.login, u.role, u.id as user_id
        FROM curriculos c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE 1=1 $filtro_hierarquia";

$params = [];
if ($busca_nome) {
    $sql .= " AND c.nome_completo LIKE ?";
    $params[] = "%$busca_nome%";
}

$sql .= " ORDER BY c.data_cadastro DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    html, body { height: 100%; margin: 0; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
    .content-grow { flex: 1; padding: 30px 0; }
    .acip-nav { background-color: white; border-bottom: 3px solid var(--acip-yellow); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .nav-link { color: #475569 !important; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; padding: 15px 20px !important; }
    .nav-link.active { color: var(--acip-green) !important; border-bottom: 3px solid var(--acip-green); }
    .section-title { color: var(--acip-green); font-weight: 800; border-left: 6px solid var(--acip-yellow); padding-left: 15px; margin-bottom: 5px; text-transform: uppercase; }
    .table-container { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0; }
    .img-thumb { width: 45px; height: 45px; object-fit: cover; border-radius: 10px; border: 2px solid var(--acip-yellow); }
    .btn-action { width: 35px; height: 35px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; border: none; font-size: 0.9rem; color: white !important; text-decoration: none; }
    .btn-view { background-color: #64748b; }
    .btn-edit { background-color: #0ea5e9; }
    .btn-key { background-color: #f59e0b; }
    .btn-approve { background-color: #10b981; }
    .btn-admin { background-color: #8b5cf6; }
    .btn-super { background-color: #1e293b; }
    .btn-standard { background-color: #6366f1; }
    .btn-del { background-color: #ef4444; }
    .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .status-aprovado { background-color: #d1fae5; color: #065f46; }
    .status-pendente { background-color: #fef3c7; color: #92400e; }
    .badge-admin { background-color: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
    .badge-super { background-color: #000; color: #fff200; border: 1px solid #fff200; }
    .footer-minimal { background-color: var(--acip-green); color: white; padding: 25px 0; text-align: center; font-size: 0.85rem; margin-top: 50px; }
</style>

<div class="page-wrapper">
    <nav class="navbar navbar-expand-lg acip-nav sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><img src="assets/img/logo.jpeg" style="max-height: 40px;"></a>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="gestao_candidatos.php"><i class="bi bi-people-fill me-1"></i> Candidatos</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_relatorios.php"><i class="bi bi-bar-chart-line-fill me-1"></i> Relatórios</a></li>

                    <?php if($minha_role === 'superadmin'): ?>
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
        <h2 class="section-title">Gerenciamento de Talentos</h2>
        <p class="text-muted fw-bold small mb-4 ms-3">SISTEMA DE HIERARQUIA ACIP</p>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php
                    if($_GET['msg'] == 'sucesso') echo "Dados atualizados com sucesso!";
                    if($_GET['msg'] == 'aprovado') echo "Candidato aprovado com sucesso!";
                    if($_GET['msg'] == 'role_atualizada') echo "Nível de acesso atualizado com sucesso!";
                    if($_GET['msg'] == 'excluido') echo "Registro removido do sistema.";
                ?>
            </div>
        <?php endif; ?>

        <div class="search-bar mb-4 p-3 bg-white rounded-4 shadow-sm border">
            <form method="GET" class="row g-2">
                <div class="col-md-10"><input type="text" name="busca_nome" class="form-control" placeholder="Buscar por nome..." value="<?= htmlspecialchars($busca_nome ?? '') ?>"></div>
                <div class="col-md-2 d-grid"><button type="submit" class="btn btn-success fw-bold">FILTRAR</button></div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Foto</th>
                            <th>Candidato / Usuário</th>
                            <th class="text-center">Status CV</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($candidatos as $can): ?>
                        <tr>
                            <td class="ps-4">
                                <?php $foto_path = (!empty($can['foto_path']) && file_exists($can['foto_path'])) ? $can['foto_path'] : 'foto.png'; ?>
                                <img src="<?= $foto_path ?>" class="img-thumb">
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($can['nome_completo'] ?? '') ?></div>
                                <div class="text-muted small">
                                    <?= htmlspecialchars($can['login'] ?? '') ?>
                                    <?php if($can['role'] == 'superadmin'): ?>
                                        <span class="status-badge badge-super ms-1">Superadmin</span>
                                    <?php elseif($can['role'] == 'admin'): ?>
                                        <span class="status-badge badge-admin ms-1">Admin</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="status-badge <?= $can['aprovado'] ? 'status-aprovado' : 'status-pendente' ?>">
                                    <?= $can['aprovado'] ? 'Aprovado' : 'Pendente' ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="visualizar_meu_curriculo.php?id=<?= $can['id'] ?>" class="btn-action btn-view" title="Ver CV"><i class="bi bi-eye"></i></a>

                                    <?php if($minha_role === 'superadmin'): ?>
                                        <a href="editar_candidato_admin.php?id=<?= $can['id'] ?>" class="btn-action btn-edit" title="Editar"><i class="bi bi-pencil"></i></a>

                                        <?php if(!$can['aprovado']): ?>
                                            <a href="?aprovar=<?= $can['id'] ?>" class="btn-action btn-approve" title="Aprovar"><i class="bi bi-check-lg"></i></a>
                                        <?php endif; ?>

                                        <?php if($can['user_id'] != $meu_id_usuario): ?>
                                            <?php if($can['role'] == 'user'): ?>
                                                <a href="?tornar_admin=<?= $can['id'] ?>" class="btn-action btn-admin" title="Promover a Admin" onclick="return confirm('Promover a Admin?')"><i class="bi bi-person-badge"></i></a>
                                            <?php elseif($can['role'] == 'admin'): ?>
                                                <a href="?tornar_superadmin=<?= $can['id'] ?>" class="btn-action btn-super" title="Promover a Superadmin" onclick="return confirm('Dar poder TOTAL?')"><i class="bi bi-gem"></i></a>
                                                <a href="?rebaixar_user=<?= $can['id'] ?>" class="btn-action btn-standard" title="Tornar Usuário Comum" onclick="return confirm('Remover acesso admin?')"><i class="bi bi-person-dash"></i></a>
                                            <?php elseif($can['role'] == 'superadmin'): ?>
                                                <a href="?tornar_admin=<?= $can['id'] ?>" class="btn-action btn-admin" style="background-color:#f97316;" title="Rebaixar para Admin" onclick="return confirm('Remover poder de Superadmin?')"><i class="bi bi-person-gear"></i></a>
                                                <a href="?rebaixar_user=<?= $can['id'] ?>" class="btn-action btn-standard" title="Rebaixar para Usuário" onclick="return confirm('Remover todo acesso admin?')"><i class="bi bi-person-dash"></i></a>
                                            <?php endif; ?>

                                            <a href="?excluir=<?= $can['id'] ?>" class="btn-action btn-del" onclick="return confirm('Excluir permanentemente?')"><i class="bi bi-trash"></i></a>
                                        <?php endif; ?>

                                        <a href="alterar_senha_admin.php?id=<?= $can['id'] ?>" class="btn-action btn-key" title="Senha"><i class="bi bi-key"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="footer-minimal"><div class="container"><span>© 2026 ACIP - Palestina</span></div></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
