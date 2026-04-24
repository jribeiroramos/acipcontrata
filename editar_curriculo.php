<?php
// 1. ATIVAÇÃO DE LOGS PARA DEPURAÇÃO
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
require_once 'classes/Database.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }

$db = Database::getConnection();
$uid = $_SESSION['usuario_id'];

try {
    // 1. Busca dados do currículo
    $sql_cv = "SELECT c.*, ci.uf as uf_atual, ci.municipio as municipio_nome
               FROM curriculos c
               LEFT JOIN cidades ci ON c.cidade_ibge = ci.ibge
               WHERE c.usuario_id = ?";
    $cv = $db->prepare($sql_cv);
    $cv->execute([$uid]);
    $dados = $cv->fetch(PDO::FETCH_ASSOC);

    if (!$dados) { die("Erro: Currículo não encontrado."); }

    $erro_tecnico = $_SESSION['erro_detalhado'] ?? null;

    // 2. Busca tabelas auxiliares
    $ufs = $db->query("SELECT DISTINCT uf FROM cidades ORDER BY uf")->fetchAll();
    $estados_civis = $db->query("SELECT * FROM estados_civis")->fetchAll();
    $niveis_escolaridade = $db->query("SELECT * FROM niveis_escolaridade ORDER BY id ASC")->fetchAll();
    $cursos = $db->query("SELECT id, nome FROM cursos ORDER BY CASE WHEN nome = 'Não se aplica' THEN 0 ELSE 1 END, nome ASC")->fetchAll();
    $todos_cargos = $db->query("SELECT id, nome FROM cargos ORDER BY nome ASC")->fetchAll();

    // 3. Busca Históricos
    $stmt_f = $db->prepare("SELECT * FROM formacoes WHERE curriculo_id = ?");
    $stmt_f->execute([$dados['id']]);
    $formacoes_salvas = $stmt_f->fetchAll(PDO::FETCH_ASSOC);

    $stmt_e = $db->prepare("SELECT e.*, ci.uf as uf_nome FROM experiencias e LEFT JOIN cidades ci ON e.cidade_ibge = ci.ibge WHERE e.curriculo_id = ?");
    $stmt_e->execute([$dados['id']]);
    $experiencias_salvas = $stmt_e->fetchAll(PDO::FETCH_ASSOC);

    $stmt_c = $db->prepare("SELECT * FROM conhecimentos WHERE curriculo_id = ?");
    $stmt_c->execute([$dados['id']]);
    $conhecimentos_salvos = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("<div class='alert alert-danger'><b>Erro Crítico:</b> " . $e->getMessage() . "</div>");
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    html, body { height: 100%; margin: 0; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
    .content-grow { flex: 1; padding-bottom: 60px; }
    .section-title { color: var(--acip-green); font-weight: 800; border-left: 6px solid var(--acip-yellow); padding-left: 15px; margin-bottom: 25px; margin-top: 40px; text-transform: uppercase; font-size: 1.1rem; }
    .card { border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.08); background: white; }
    .img-perfil { width: 130px; height: 130px; object-fit: cover; border: 4px solid var(--acip-yellow); border-radius: 20px; }
    .container-verde { background-color: #f0fdf4; border: 1px solid #dcfce7; padding: 25px; border-radius: 20px; margin-bottom: 20px; position: relative; }
    .bg-historico { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; border: 1px solid #e2e8f0; position: relative; }
    .btn-excluir { color: #dc2626; text-decoration: none; font-size: 0.85rem; font-weight: bold; position: absolute; top: 15px; right: 15px; border: none; background: none; }
    .btn-excluir:hover { color: #991b1b; }
    .label-edit { font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 3px; display: block; }
    .btn-add-item { background-color: var(--acip-green); color: white; font-weight: bold; border-radius: 10px; border: none; padding: 10px 20px; font-size: 0.85rem; transition: 0.3s; cursor: pointer; }
    .btn-add-item:hover { background-color: #005a2f; transform: translateY(-2px); color: white; }
    .cropper-area { max-height: 500px; overflow: hidden; background: #eee; border-radius: 8px; }
    #image-to-crop { max-width: 100%; display: block; }
</style>

<div class="page-wrapper">
    <div class="container mt-5 content-grow">
        
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 p-3 mb-4 fw-bold animate__animated animate__fadeIn">
                <i class="bi bi-check-circle-fill me-2"></i> Alterações efetuadas com sucesso!
            </div>
        <?php endif; ?>

        <?php if ($erro_tecnico): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 p-3 mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($erro_tecnico) ?>
                <?php unset($_SESSION['erro_detalhado']); ?>
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <a href="area_candidato.php" class="text-success text-decoration-none fw-bold"><i class="bi bi-arrow-left-circle-fill me-2"></i> VOLTAR AO PAINEL</a>
        </div>

        <form action="processa_edicao.php" id="form-curriculo" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Deseja salvar todas as alterações realizadas no seu currículo?');">
            <input type="hidden" name="curriculo_id" value="<?= $dados['id'] ?>">
            <input type="hidden" name="cropped_image" id="cropped_image">

            <div class="card p-4 p-md-5">
                <h2 class="fw-bold mb-5" style="color: var(--acip-green);">ATUALIZAR MEU CURRÍCULO</h2>

                <div class="row align-items-center mb-5 p-3 rounded-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="col-md-auto text-center">
                        <?php $f_img = (!empty($dados['foto_path']) && file_exists($dados['foto_path'])) ? $dados['foto_path'] : 'foto.png'; ?>
                        <img src="<?= $f_img ?>" class="img-perfil mb-2" id="preview-foto">
                    </div>
                    <div class="col-md">
                        <label class="fw-bold mb-1 text-uppercase small text-success">Alterar Foto (Recortar)</label>
                        <input type="file" id="foto-input" accept="image/*" class="form-control">
                    </div>
                </div>

                <h3 class="section-title" style="margin-top:0">Objetivo Profissional</h3>
                <textarea name="objetivo" class="form-control mb-4" rows="3" placeholder="Conte um pouco sobre suas metas profissionais..."><?= htmlspecialchars($dados['objetivo'] ?? '') ?></textarea>

                <h3 class="section-title">Dados Pessoais e Contato</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="small fw-bold">NOME COMPLETO</label><input type="text" name="nome_completo" class="form-control" value="<?= htmlspecialchars($dados['nome_completo'] ?? '') ?>" required></div>
                    <div class="col-md-3"><label class="small fw-bold">DATA NASCIMENTO</label><input type="date" name="data_nascimento" class="form-control" value="<?= $dados['data_nascimento'] ?>" required></div>
                    <div class="col-md-3"><label class="small fw-bold">SEXO</label>
                        <select name="sexo" class="form-select">
                            <option value="M" <?= ($dados['sexo'] ?? '') == 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= ($dados['sexo'] ?? '') == 'F' ? 'selected' : '' ?>>Feminino</option>
                            <option value="Outro" <?= ($dados['sexo'] ?? '') == 'Outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="small fw-bold">E-MAIL</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($dados['email'] ?? '') ?>" required></div>
                    <div class="col-md-4"><label class="small fw-bold">WHATSAPP</label><input type="text" name="telefone1" class="form-control sp_celphones" value="<?= htmlspecialchars($dados['telefone1'] ?? '') ?>" required></div>
                    <div class="col-md-4"><label class="small fw-bold">TEL 2 (RECADO)</label><input type="text" name="telefone2" class="form-control sp_celphones" value="<?= ($dados['telefone2'] == 'Não informado') ? '' : htmlspecialchars($dados['telefone2'] ?? '') ?>"></div>
                    <div class="col-md-12"><label class="small fw-bold">ENDEREÇO</label><input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($dados['endereco'] ?? '') ?>" required></div>
                    <div class="col-md-4">
                        <label class="small fw-bold">UF ATUAL</label>
                        <select id="uf_cv" class="form-select" onchange="carregarCidades(this.value, 'cidade_cv')" required>
                            <option value="">...</option>
                            <?php foreach($ufs as $u): ?>
                                <option value="<?= $u['uf'] ?>" <?= ($dados['uf_atual'] ?? '') == $u['uf'] ? 'selected' : '' ?>><?= $u['uf'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="small fw-bold">CIDADE ATUAL</label>
                        <select id="cidade_cv" name="cidade_ibge" class="form-select" required>
                            <option value="<?= $dados['cidade_ibge'] ?>"><?= htmlspecialchars($dados['municipio_nome'] ?? 'Selecione a UF') ?></option>
                        </select>
                    </div>
                </div>

                <h3 class="section-title">Documentação (CNH)</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><label class="small fw-bold">NÚMERO CNH</label><input type="text" name="cnh_numero" class="form-control" value="<?= htmlspecialchars($dados['cnh_numero'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="small fw-bold">CATEGORIA</label><input type="text" name="cnh_categoria" class="form-control" maxlength="5" value="<?= htmlspecialchars($dados['cnh_categoria'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="small fw-bold">VALIDADE</label><input type="date" name="cnh_validade" class="form-control" value="<?= $dados['cnh_validade'] ?>"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="section-title">Formação Acadêmica</h3>
                    <button type="button" class="btn-add-item" onclick="addFormacao()">+ ADICIONAR FORMAÇÃO</button>
                </div>
                <div id="historico-formacoes">
                    <?php foreach($formacoes_salvas as $f): ?>
                        <div class="bg-historico shadow-sm">
                            <a href="excluir_formacao.php?id=<?= $f['id'] ?>" class="btn-excluir" onclick="return confirm('Deseja realmente excluir esta formação permanentemente?')">Excluir 🗑️</a>
                            <div class="row g-3">
                                <div class="col-md-3"><label class="label-edit">Nível</label><select name="update_form_nivel[<?= $f['id'] ?>]" class="form-select form-select-sm" required><?php foreach($niveis_escolaridade as $n): ?><option value="<?= $n['id'] ?>" <?= $f['nivel_id'] == $n['id'] ? 'selected' : '' ?>><?= $n['descricao'] ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-3"><label class="label-edit">Curso</label><select name="update_form_curso[<?= $f['id'] ?>]" class="form-select form-select-sm" required><?php foreach($cursos as $c): ?><option value="<?= $c['id'] ?>" <?= $f['curso_id'] == $c['id'] ? 'selected' : '' ?>><?= $c['nome'] ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-4"><label class="label-edit">Instituição</label><input type="text" name="update_form_instituicao[<?= $f['id'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($f['instituicao'] ?? '') ?>" required></div>
                                <div class="col-md-2"><label class="label-edit">Ano</label><input type="number" name="update_form_ano[<?= $f['id'] ?>]" class="form-control form-control-sm" value="<?= $f['ano_conclusao'] ?>" required></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="lista-formacoes-novas" style="margin-bottom:20px;"></div>

                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="section-title">Conhecimentos e Habilidades</h3>
                    <button type="button" class="btn-add-item" onclick="addConhecimento()">+ ADICIONAR CONHECIMENTO</button>
                </div>
                <div id="historico-conhecimentos">
                    <?php foreach($conhecimentos_salvos as $c): ?>
                        <div class="bg-historico shadow-sm py-2">
                            <a href="excluir_conhecimento.php?id=<?= $c['id'] ?>" class="btn-excluir" onclick="return confirm('Deseja realmente excluir este conhecimento permanentemente?')">Excluir 🗑️</a>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-8"><label class="label-edit">Descrição</label><input type="text" name="update_conh_desc[<?= $c['id'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($c['descricao'] ?? '') ?>" required></div>
                                <div class="col-md-4"><label class="label-edit">Nível / Status</label>
                                    <select name="update_conh_nivel[<?= $c['id'] ?>]" class="form-select form-select-sm">
                                        <option value="Básico" <?= ($c['nivel'] ?? '') == 'Básico' ? 'selected' : '' ?>>Básico</option>
                                        <option value="Intermediário" <?= ($c['nivel'] ?? '') == 'Intermediário' ? 'selected' : '' ?>>Intermediário</option>
                                        <option value="Avançado" <?= ($c['nivel'] ?? '') == 'Avançado' ? 'selected' : '' ?>>Avançado</option>
                                        <option value="Ativo" <?= ($c['nivel'] ?? '') == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="Concluído" <?= ($c['nivel'] ?? '') == 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="lista-conhecimentos-novos" style="margin-bottom:20px;"></div>

                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="section-title">Experiências Profissionais</h3>
                    <button type="button" class="btn-add-item" onclick="addExperiencia()">+ ADICIONAR EXPERIÊNCIA</button>
                </div>
                <div id="historico-experiencias">
                    <?php foreach($experiencias_salvas as $ex): ?>
                        <div class="bg-historico shadow-sm mb-4">
                            <a href="excluir_experiencia.php?id=<?= $ex['id'] ?>" class="btn-excluir" onclick="return confirm('Deseja realmente excluir esta experiência profissional permanentemente?')">Excluir 🗑️</a>
                            <div class="row g-3 mb-2">
                                <div class="col-md-4"><label class="label-edit">Empresa</label><input type="text" name="update_exp_empresa[<?= $ex['id'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($ex['empresa'] ?? '') ?>"></div>
                                <div class="col-md-4"><label class="label-edit">Cargo</label>
                                    <select name="update_exp_cargo[<?= $ex['id'] ?>]" class="form-select form-select-sm">
                                        <?php foreach($todos_cargos as $tc): ?>
                                            <option value="<?= $tc['id'] ?>" <?= $ex['cargo_id'] == $tc['id'] ? 'selected' : '' ?>><?= $tc['nome'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2"><label class="label-edit">Início</label><input type="date" name="update_exp_inicio[<?= $ex['id'] ?>]" class="form-control form-control-sm" value="<?= $ex['dt_inicio_experiencia'] ?>"></div>
                                <div class="col-md-2"><label class="label-edit">Fim</label><input type="date" name="update_exp_fim[<?= $ex['id'] ?>]" class="form-control form-control-sm" value="<?= $ex['dt_fim_experiencia'] ?>"></div>
                            </div>
                            <div class="row g-3 mb-2">
                                <div class="col-md-3"><label class="label-edit">UF</label><select class="form-select form-select-sm" onchange="carregarCidades(this.value, 'cidade_update_<?= $ex['id'] ?>')"><option value="">...</option><?php foreach($ufs as $u): ?><option value="<?= $u['uf'] ?>" <?= $ex['uf_nome']==$u['uf']?'selected':'' ?>><?= $u['uf'] ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-9"><label class="label-edit">Cidade</label><select name="update_exp_cidade[<?= $ex['id'] ?>]" id="cidade_update_<?= $ex['id'] ?>" class="form-select form-select-sm"><option value="<?= $ex['cidade_ibge'] ?>">Manter Atual</option></select></div>
                            </div>
                            <textarea name="update_exp_atividades[<?= $ex['id'] ?>]" class="form-control form-control-sm mt-2" rows="2"><?= htmlspecialchars($ex['descricao_atividades'] ?? '') ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="lista-experiencias-novas" style="margin-bottom:20px;"></div>

                <div class="text-end mt-5">
                    <button type="submit" class="btn btn-success btn-lg px-5 shadow fw-bold" style="border-radius: 15px;">SALVAR TODAS AS ALTERAÇÕES</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalCrop" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Ajustar Recorte da Foto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 text-center">
        <div class="cropper-area">
          <img id="image-to-crop" src="">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success fw-bold" id="btn-crop-confirm">APLICAR RECORTE</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let expCount = 1;
let cropper = null;
const modalEl = document.getElementById('modalCrop');
const bootstrapModal = new bootstrap.Modal(modalEl);
const imageToCrop = document.getElementById('image-to-crop');

$(document).ready(function(){
    $('.sp_celphones').mask('(00) 00000-0000');
    const ufInic = $('#uf_cv').val();
    if(ufInic) carregarCidades(ufInic, 'cidade_cv', '<?= $dados['cidade_ibge'] ?>');
    <?php foreach($experiencias_salvas as $ex): ?>
        carregarCidades('<?= $ex['uf_nome'] ?>', 'cidade_update_<?= $ex['id'] ?>', '<?= $ex['cidade_ibge'] ?>');
    <?php endforeach; ?>
});

// GESTÃO DE RECORTE DE FOTO
document.getElementById('foto-input').addEventListener('change', function(e){
    const files = e.target.files;
    if (files && files.length > 0) {
        const reader = new FileReader();
        reader.onload = function(event){
            imageToCrop.src = event.target.result;
            bootstrapModal.show();
        };
        reader.readAsDataURL(files[0]);
    }
});

modalEl.addEventListener('shown.bs.modal', function () {
    if (cropper) { cropper.destroy(); }
    cropper = new Cropper(imageToCrop, {
        aspectRatio: 1,
        viewMode: 2,
        autoCropArea: 1,
        responsive: true
    });
});

modalEl.addEventListener('hidden.bs.modal', function () {
    if(cropper) { cropper.destroy(); cropper = null; }
});

document.getElementById('btn-crop-confirm').addEventListener('click', function(){
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
    const dataURL = canvas.toDataURL('image/jpeg', 0.9);
    document.getElementById('preview-foto').src = dataURL;
    document.getElementById('cropped_image').value = dataURL;
    bootstrapModal.hide();
});

// FUNÇÃO PARA REMOVER LINHAS NÃO SALVAS
function removerLinha(btn) {
    if(confirm('Deseja descartar este item antes de salvar?')) {
        $(btn).closest('.container-verde').remove();
    }
}

// LÓGICA DE CIDADES
function carregarCidades(uf, targetId, selectedIbge = null) {
    const select = document.getElementById(targetId);
    if (!uf) return;
    select.disabled = false;
    fetch('busca_cidades.php?uf=' + uf).then(r => r.json()).then(data => {
        select.innerHTML = '<option value="">Cidade</option>';
        data.forEach(c => {
            const isSelected = (selectedIbge && c.ibge == selectedIbge) ? 'selected' : '';
            select.innerHTML += `<option value="${c.ibge}" ${isSelected}>${c.municipio}</option>`;
        });
    });
}

function addFormacao() {
    const html = `
        <div class="bg-white p-3 rounded-3 mb-3 border shadow-sm container-verde">
            <button type="button" class="btn-excluir" onclick="removerLinha(this)">Remover 🗑️</button>
            <div class="row g-3">
                <div class="col-md-3"><label class="small fw-bold">NÍVEL</label><select name="nivel_id[]" class="form-select form-select-sm" required><option value="">Selecione...</option><?php foreach($niveis_escolaridade as $n): ?><option value="<?= $n['id'] ?>"><?= $n['descricao'] ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="small fw-bold">CURSO</label><select name="curso_id[]" class="form-select form-select-sm" required><?php foreach($cursos as $c): ?><option value="<?= $c['id'] ?>"><?= $c['nome'] ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="small fw-bold">INSTITUIÇÃO</label><input type="text" name="instituicao[]" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="small fw-bold">ANO</label><input type="number" name="ano_conclusao[]" class="form-control form-control-sm" required></div>
            </div>
        </div>`;
    $('#lista-formacoes-novas').append(html);
}

function addConhecimento() {
    const html = `
        <div class="bg-white p-3 rounded-3 mb-2 border shadow-sm container-verde">
            <button type="button" class="btn-excluir" onclick="removerLinha(this)">Remover 🗑️</button>
            <div class="row g-3">
                <div class="col-md-8"><label class="small fw-bold">DESCRIÇÃO</label><input type="text" name="conhecimento_desc[]" class="form-control form-control-sm" required></div>
                <div class="col-md-4"><label class="small fw-bold">NÍVEL / STATUS</label>
                    <select name="conhecimento_nivel[]" class="form-select form-select-sm">
                        <option value="Básico">Básico</option>
                        <option value="Intermediário">Intermediário</option>
                        <option value="Avançado">Avançado</option>
                        <option value="Ativo">Ativo</option>
                        <option value="Concluído">Concluído</option>
                    </select>
                </div>
            </div>
        </div>`;
    $('#lista-conhecimentos-novos').append(html);
}

function addExperiencia() {
    const idSufixo = Date.now();
    const html = `
        <div class="bg-white p-3 rounded-3 mb-3 border shadow-sm container-verde">
            <button type="button" class="btn-excluir" onclick="removerLinha(this)">Remover 🗑️</button>
            <div class="row g-3 mb-2">
                <div class="col-md-4"><label class="small fw-bold">EMPRESA</label><input type="text" name="empresa[]" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="small fw-bold">CARGO</label><select name="cargo_id[]" class="form-select form-select-sm"><option value="">Selecionar...</option><?php foreach($todos_cargos as $tc): ?><option value="<?= $tc['id'] ?>"><?= $tc['nome'] ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="small fw-bold">INÍCIO</label><input type="date" name="dt_inicio_experiencia[]" class="form-control form-control-sm"></div>
                <div class="col-md-2"><label class="small fw-bold">FIM</label><input type="date" name="dt_fim_experiencia[]" class="form-control form-control-sm"></div>
            </div>
            <div class="row g-3 mb-2">
                <div class="col-md-3"><label class="small fw-bold">UF</label>
                    <select class="form-select form-select-sm" onchange="carregarCidades(this.value, 'cidade_exp_new_${idSufixo}')">
                        <option value="">...</option>
                        <?php foreach($ufs as $u): ?><option value="<?= $u['uf'] ?>"><?= $u['uf'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-9"><label class="small fw-bold">CIDADE</label>
                    <select name="exp_cidade_ibge[]" id="cidade_exp_new_${idSufixo}" class="form-select form-select-sm">
                        <option value="">Selecione o estado</option>
                    </select>
                </div>
            </div>
            <textarea name="descricao_atividades[]" class="form-control form-control-sm" rows="2" placeholder="Descreva suas principais atividades..."></textarea>
        </div>`;
    $('#lista-experiencias-novas').append(html);
}
</script>

<?php require_once 'includes/footer.php'; ?>
