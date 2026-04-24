<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
require_once 'classes/Database.php';

// SEGURANÇA UNIFICADA: Atualizada para permitir Admin e Superadmin
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_role'], ['admin', 'superadmin'])) {
    header("Location: login.php"); exit;
}

$db = Database::getConnection();
$tabela = $_GET['t'] ?? 'cargos';
$coluna = ($tabela == 'niveis_escolaridade' || $tabela == 'estados_civis') ? 'descricao' : 'nome';

// 1. Lógica de Inserção
if(isset($_POST['adicionar'])) {
    $valor = trim($_POST['valor_novo']);
    if (!empty($valor)) {
        $db->prepare("INSERT INTO $tabela ($coluna) VALUES (?)")->execute([$valor]);
        header("Location: admin_crud_mestre.php?t=$tabela&msg=adicionado"); exit;
    }
}

// 2. Lógica de Edição (AJAX ou Post simples)
if(isset($_POST['editar_registro'])) {
    $id_edit = $_POST['id_edit'];
    $novo_valor = trim($_POST['valor_editado']);
    $db->prepare("UPDATE $tabela SET $coluna = ? WHERE id = ?")->execute([$novo_valor, $id_edit]);
    header("Location: admin_crud_mestre.php?t=$tabela&msg=editado"); exit;
}

// 3. Lógica de Exclusão
if(isset($_GET['del'])) {
    $db->prepare("DELETE FROM $tabela WHERE id = ?")->execute([$_GET['del']]);
    header("Location: admin_crud_mestre.php?t=$tabela&msg=removido"); exit;
}

// 4. Busca de Dados
$dados = $db->query("SELECT id, $coluna as label FROM $tabela ORDER BY label ASC")->fetchAll();
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    html, body { height: 100%; margin: 0; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
    .content-grow { flex: 1; padding: 40px 0; }
    .acip-nav { background-color: white; border-bottom: 3px solid var(--acip-yellow); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .nav-link { color: #475569 !important; font-weight: 700; text-transform: uppercase; font-size: 0.85rem; padding: 15px 20px !important; transition: 0.3s; }
    .nav-link:hover { color: var(--acip-green) !important; background-color: #f0fdf4; }
    .nav-link.active { color: var(--acip-green) !important; border-bottom: 3px solid var(--acip-green); }
    .section-title { color: var(--acip-green); font-weight: 800; border-left: 6px solid var(--acip-yellow); padding-left: 15px; margin-bottom: 5px; text-transform: uppercase; }
    .form-control:focus { border-color: var(--acip-yellow) !important; box-shadow: 0 0 0 0.25rem rgba(255, 242, 0, 0.25) !important; background-color: #fffdec !important; }
    .card-master { border-radius: 25px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.05); background: white; }
    .table thead th { background-color: #f8fafc; color: #64748b; font-weight: 800; text-transform: uppercase; font-size: 0.75rem; padding: 15px; border: none; }
    .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .btn-action { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; border: none; font-size: 0.85rem; }
    .btn-edit-trigger { background-color: #f1f5f9; color: var(--acip-green); }
    .btn-del { background-color: #fee2e2; color: #dc2626; }
    .footer-minimal { background-color: var(--acip-green); color: white; padding: 25px 0; text-align: center; font-size: 0.85rem; margin-top: 50px; }
</style>

<div class="page-wrapper">
    <nav class="navbar navbar-expand-lg acip-nav sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><img src="assets/img/logo.jpeg" style="max-height: 40px;"></a>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="gestao_candidatos.php">Candidatos</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_relatorios.php">Relatórios</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_manutencao.php">Tabelas de Apoio</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="small fw-bold text-muted me-3"><i class="bi bi-person-circle"></i> <?= strtoupper($_SESSION['usuario_role'] ?? 'ADMIN') ?></span>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger fw-bold" style="border-radius: 8px;">SAIR</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container content-grow">
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h2 class="section-title">Editor Master</h2>
                <p class="text-muted fw-bold small mb-0 ms-3 uppercase"><?= str_replace('_', ' ', $tabela) ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="admin_manutencao.php" class="btn btn-outline-secondary fw-bold shadow-sm" style="border-radius: 12px;"><i class="bi bi-arrow-left me-1"></i>VOLTAR</a>
            </div>
        </div>

        <div class="card card-master p-4 p-md-5">
            <form method="POST" class="row g-3 mb-5 align-items-end p-4 rounded-4" style="background-color: #f0fdf4; border: 1px solid #dcfce7;">
                <div class="col-md-8">
                    <label class="small fw-bold text-success mb-2 uppercase">Adicionar Novo</label>
                    <input type="text" name="valor_novo" class="form-control form-control-lg" placeholder="Digite aqui..." required>
                </div>
                <div class="col-md-4"><button type="submit" name="adicionar" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" style="border-radius: 12px;">SALVAR</button></div>
            </form>

            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="inputBusca" class="form-control border-start-0 ps-0" placeholder="Pesquisar nesta lista..." onkeyup="filtrarTabela()">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tabelaDados">
                    <thead><tr><th style="width: 80px;">ID</th><th>NOME / DESCRIÇÃO</th><th class="text-end">AÇÕES</th></tr></thead>
                    <tbody>
                        <?php foreach($dados as $item): ?>
                        <tr id="row-<?= $item['id'] ?>">
                            <td class="text-muted small">#<?= $item['id'] ?></td>
                            <td class="fw-bold text-dark item-label">
                                <span class="text-display"><?= htmlspecialchars($item['label'] ?? '') ?></span>
                                <form method="POST" class="edit-form d-none mt-1">
                                    <input type="hidden" name="id_edit" value="<?= $item['id'] ?>">
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="valor_editado" class="form-control" value="<?= htmlspecialchars($item['label'] ?? '') ?>" required>
                                        <button type="submit" name="editar_registro" class="btn btn-success"><i class="bi bi-check-lg"></i></button>
                                        <button type="button" class="btn btn-secondary btn-cancel" onclick="toggleEdit(<?= $item['id'] ?>)"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </form>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn-action btn-edit-trigger me-1" onclick="toggleEdit(<?= $item['id'] ?>)"><i class="bi bi-pencil-square"></i></button>
                                <a href="?t=<?= $tabela ?>&del=<?= $item['id'] ?>" class="btn-action btn-del" onclick="return confirm('Excluir permanentemente?')"><i class="bi bi-trash3"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="footer-minimal">
        <div class="container"><span>© 2026 ACIP - Palestina • GESTÃO DE DADOS</span></div>
    </div>
</div>

<script>
function toggleEdit(id) {
    const row = document.getElementById('row-' + id);
    const display = row.querySelector('.text-display');
    const form = row.querySelector('.edit-form');
    display.classList.toggle('d-none');
    form.classList.toggle('d-none');
    if(!form.classList.contains('d-none')) form.querySelector('input').focus();
}

function filtrarTabela() {
    const filter = document.getElementById('inputBusca').value.toUpperCase();
    const rows = document.getElementById('tabelaDados').getElementsByTagName('tr');
    for (let i = 1; i < rows.length; i++) {
        const label = rows[i].querySelector('.item-label .text-display');
        if (label) {
            const text = label.textContent || label.innerText;
            rows[i].style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
        }
    }
}
</script>
<?php require_once 'includes/footer.php'; ?>
