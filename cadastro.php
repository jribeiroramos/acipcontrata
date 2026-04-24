<?php
// 1. ATIVAÇÃO DE LOGS PARA VISUALIZAR O ERRO REAL (Não remova até resolver o problema)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
require_once 'classes/Database.php';

try {
    $db = Database::getConnection();

    // Se o usuário acabou de cadastrar com sucesso, limpamos os dados antigos
    if (isset($_GET['sucesso'])) {
        unset($_SESSION['old_form']);
        unset($_SESSION['erro_detalhado']);
    }

    // Recupera dados salvos na sessão em caso de erro
    $old = $_SESSION['old_form'] ?? [];
    $erro_tecnico = $_SESSION['erro_detalhado'] ?? null;

    // Carregamento de dados para os selects
    $ufs = $db->query("SELECT DISTINCT uf FROM cidades ORDER BY uf")->fetchAll();
    $estados_civis = $db->query("SELECT * FROM estados_civis")->fetchAll();
    $todos_cargos = $db->query("SELECT id, nome FROM cargos ORDER BY nome ASC")->fetchAll();
    $niveis = $db->query("SELECT * FROM niveis_escolaridade ORDER BY id ASC")->fetchAll();
    $cursos = $db->query("SELECT id, nome FROM cursos ORDER BY CASE WHEN nome = 'Não se aplica' THEN 0 ELSE 1 END, nome ASC")->fetchAll();

} catch (Exception $e) {
    die("<div class='alert alert-danger'><b>Erro de Conexão:</b> " . $e->getMessage() . "</div>");
}
?>

<style>
    :root { --acip-green: #008445; --acip-yellow: #fff200; }
    html, body { height: 100%; margin: 0; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .page-wrapper { display: flex; flex-direction: column; min-height: 100vh; }
    .content-grow { flex: 1; padding-bottom: 60px; }
    .form-control:focus, .form-select:focus { border-color: var(--acip-yellow) !important; box-shadow: 0 0 0 0.25rem rgba(255, 242, 0, 0.25) !important; background-color: #fffdec !important; outline: none; }
    .section-title { color: var(--acip-green); font-weight: 800; border-left: 6px solid var(--acip-yellow); padding-left: 15px; margin-bottom: 25px; margin-top: 40px; text-transform: uppercase; font-size: 1.1rem; }
    .card { border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.08); background: white; }
    .badge-number { background: var(--acip-green); color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; margin-right: 8px; }
    .container-verde { background-color: #f0fdf4; border: 1px solid #dcfce7; padding: 25px; border-radius: 20px; }
    .bloco-repeticao { border-bottom: 1px dashed #cbd5e1; padding-bottom: 20px; margin-bottom: 20px; }
    .bloco-repeticao:last-child { border-bottom: none; }
    .img-perfil-preview { width: 120px; height: 120px; object-fit: cover; border: 4px solid var(--acip-yellow); border-radius: 20px; background: #f1f5f9; margin-bottom: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .btn-add { background-color: var(--acip-green); color: white; font-weight: bold; border-radius: 10px; border: none; padding: 10px 20px; transition: 0.3s; cursor: pointer; }
    .footer-minimal { background-color: var(--acip-green); color: white; padding: 30px 0; text-align: center; font-size: 0.85rem; }

    /* Feedback em tempo real */
    .validation-msg { font-size: 0.75rem; font-weight: 800; margin-top: 4px; display: block; }
    .available { color: #10b981; }
    .unavailable { color: #ef4444; }
</style>

<div class="page-wrapper">
    <div class="container mt-5 content-grow">
        <div class="mb-4">
            <a href="index.php" class="text-success text-decoration-none fw-bold">
                <i class="bi bi-arrow-left-circle-fill me-2"></i> VOLTAR PARA A PÁGINA INICIAL
            </a>
        </div>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                <div><h5 class="fw-bold mb-0">Cadastro realizado com sucesso!</h5><p class="mb-0 small text-muted">Seu currículo foi enviado corretamente para o nosso banco.</p></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                    <div>
                        <h5 class="fw-bold mb-0">Houve um problema.</h5>
                        <p class="mb-0 small text-muted">
                            <?php
                                if($_GET['erro'] == 'email_duplicado') echo "Este E-mail já está cadastrado em nosso sistema.";
                                elseif($_GET['erro'] == 'login_duplicado') echo "Este Login já está sendo utilizado.";
                                elseif($_GET['erro'] == 'login_invalido') echo "O Login não pode conter espaços em branco.";
                                else echo "Revise os campos ou tente um login diferente.";
                            ?>
                        </p>
                    </div>
                </div>
                <?php if ($erro_tecnico): ?>
                    <div class="mt-3 p-3 bg-dark text-white rounded-4 small font-monospace" style="opacity: 0.85;">
                        <i class="bi bi-bug-fill me-2 text-warning"></i><b>LOG TÉCNICO:</b><br>
                        <?= htmlspecialchars($erro_tecnico) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="processa_cadastro.php" method="POST" enctype="multipart/form-data" id="formCadastro">
            <div class="card p-4 p-md-5">
                <h2 class="text-center mb-5 fw-bold" style="color: var(--acip-green);">CADASTRO OFICIAL DE CURRÍCULO</h2>

                <div class="row align-items-center mb-5 p-3 rounded-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="col-md-auto text-center">
                        <img src="foto.png" id="preview-foto" class="img-perfil-preview">
                    </div>
                    <div class="col-md">
                        <label class="small fw-bold text-success uppercase">Sua Foto Profissional (Opcional)</label>
                        <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                    </div>
                </div>

                <h3 class="section-title" style="margin-top:0">Acesso ao Painel</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="small fw-bold">LOGIN (USUÁRIO - SEM ESPAÇOS)</label>
                        <input type="text" name="login" id="login_input" class="form-control" value="<?= htmlspecialchars($old['login'] ?? '') ?>" required pattern="^\S+$" title="O login não pode conter espaços em branco.">
                        <span id="login_feedback" class="validation-msg"></span>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">SENHA (MÍNIMO 6 CARACTERES)</label>
                        <input type="password" name="senha" id="senha" class="form-control" required minlength="6">
                        <div id="msgSenha" class="text-danger small fw-bold mt-1" style="display:none;">Mínimo 6 caracteres!</div>
                    </div>
                </div>

                <h3 class="section-title">Objetivo Profissional</h3>
                <textarea name="objetivo" class="form-control mb-4" rows="2" placeholder="Resuma o que você busca..."><?= htmlspecialchars($old['objetivo'] ?? '') ?></textarea>

                <h3 class="section-title">Dados Pessoais e Contato</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="small fw-bold">NOME COMPLETO</label><input type="text" name="nome_completo" class="form-control" value="<?= htmlspecialchars($old['nome_completo'] ?? '') ?>" required></div>
                    <div class="col-md-3"><label class="small fw-bold">DATA NASCIMENTO</label><input type="date" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($old['data_nascimento'] ?? '') ?>" required></div>
                    <div class="col-md-3">
                        <label class="small fw-bold">SEXO</label>
                        <select name="sexo" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="M" <?= (isset($old['sexo']) && $old['sexo'] == 'M') ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= (isset($old['sexo']) && $old['sexo'] == 'F') ? 'selected' : '' ?>>Feminino</option>
                            <option value="Outro" <?= (isset($old['sexo']) && $old['sexo'] == 'Outro') ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">E-MAIL</label>
                        <input type="email" name="email" id="email_input" class="form-control" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                        <span id="email_feedback" class="validation-msg"></span>
                    </div>
                    <div class="col-md-4"><label class="small fw-bold">WHATSAPP (TEL 1)</label><input type="text" name="telefone1" class="form-control sp_celphones" value="<?= htmlspecialchars($old['telefone1'] ?? '') ?>" required></div>
                    <div class="col-md-4"><label class="small fw-bold">TELEFONE 2</label><input type="text" name="telefone2" class="form-control sp_celphones" value="<?= htmlspecialchars($old['telefone2'] ?? '') ?>"></div>
                    <div class="col-md-12"><label class="small fw-bold">ENDEREÇO</label><input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($old['endereco'] ?? '') ?>" required></div>
                    <div class="col-md-4">
                        <label class="small fw-bold">UF ATUAL</label>
                        <select id="uf_cv" class="form-select" onchange="carregarCidades(this.value, 'cidade_cv')" required>
                            <option value="">...</option>
                            <?php foreach($ufs as $u): ?>
                                <option value="<?= $u['uf'] ?>" <?= (isset($old['uf_cv_hidden']) && $old['uf_cv_hidden'] == $u['uf']) ? 'selected' : '' ?>><?= $u['uf'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" id="old_uf_cv" value="<?= htmlspecialchars($old['uf_cv_hidden'] ?? '') ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="small fw-bold">CIDADE ATUAL</label>
                        <select id="cidade_cv" name="cidade_ibge" class="form-select" disabled required><option value="">Selecione o estado</option></select>
                        <input type="hidden" id="old_cidade_cv" value="<?= htmlspecialchars($old['cidade_ibge'] ?? '') ?>">
                    </div>
                </div>

                <h3 class="section-title">Documentação (CNH)</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><label class="small fw-bold">NÚMERO CNH</label><input type="text" name="cnh_numero" class="form-control" value="<?= htmlspecialchars($old['cnh_numero'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="small fw-bold">CATEGORIA</label><input type="text" name="cnh_categoria" class="form-control" maxlength="5" value="<?= htmlspecialchars($old['cnh_categoria'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="small fw-bold">VALIDADE</label><input type="date" name="cnh_validade" class="form-control" value="<?= htmlspecialchars($old['cnh_validade'] ?? '') ?>"></div>
                </div>

                <h3 class="section-title">Formação Acadêmica (Até 4)</h3>
                <div class="container-verde mb-4">
                    <?php for($i=0; $i<4; $i++): ?>
                    <div class="bloco-repeticao row g-3">
                        <div class="col-md-3"><label class="small fw-bold">NÍVEL</label>
                            <select name="nivel_id[]" class="form-select">
                                <option value="">Selecione...</option>
                                <?php foreach($niveis as $n): ?>
                                    <option value="<?= $n['id'] ?>" <?= (isset($old['nivel_id'][$i]) && $old['nivel_id'][$i] == $n['id']) ? 'selected' : '' ?>><?= $n['descricao'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="small fw-bold">CURSO</label>
                            <select name="curso_id[]" class="form-select">
                                <?php foreach($cursos as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (isset($old['curso_id'][$i]) && $old['curso_id'][$i] == $c['id']) ? 'selected' : '' ?>><?= $c['nome'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="small fw-bold">INSTITUIÇÃO</label><input type="text" name="instituicao[]" class="form-control" value="<?= htmlspecialchars($old['instituicao'][$i] ?? '') ?>"></div>
                        <div class="col-md-2"><label class="small fw-bold">ANO CONCL.</label><input type="number" name="ano_conclusao[]" class="form-control" value="<?= htmlspecialchars($old['ano_conclusao'][$i] ?? '') ?>"></div>
                    </div>
                    <?php endfor; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="section-title">Conhecimentos e Habilidades</h3>
                    <button type="button" class="btn-add" onclick="addConhecimento()"><i class="bi bi-plus-lg me-1"></i> ADICIONAR NOVO</button>
                </div>
                <div class="container-verde mb-4" id="lista-conhecimentos">
                    <?php
                    $total_conh = isset($old['conhecimento_desc']) ? count($old['conhecimento_desc']) : 1;
                    for($i=0; $i<$total_conh; $i++):
                    ?>
                    <div class="bloco-repeticao row g-3">
                        <div class="col-md-8"><label class="small fw-bold">HABILIDADE / CURSO</label><input type="text" name="conhecimento_desc[]" class="form-control" placeholder="Ex: Excel, Inglês..." value="<?= htmlspecialchars($old['conhecimento_desc'][$i] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="small fw-bold">NÍVEL</label>
                            <select name="conhecimento_nivel[]" class="form-select">
                                <option value="">Selecione...</option>
                                <option value="Básico" <?= (isset($old['conhecimento_nivel'][$i]) && $old['conhecimento_nivel'][$i] == 'Básico') ? 'selected' : '' ?>>Básico</option>
                                <option value="Intermediário" <?= (isset($old['conhecimento_nivel'][$i]) && $old['conhecimento_nivel'][$i] == 'Intermediário') ? 'selected' : '' ?>>Intermediário</option>
                                <option value="Avançado" <?= (isset($old['conhecimento_nivel'][$i]) && $old['conhecimento_nivel'][$i] == 'Avançado') ? 'selected' : '' ?>>Avançado</option>
                            </select>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <h3 class="section-title">Experiências Profissionais (Até 4)</h3>
                <div class="container-verde mb-4">
                    <?php for($j=1; $j<=4; $j++):
                        $idx = $j - 1;
                    ?>
                    <div class="bloco-repeticao">
                        <div class="mb-2 fw-bold small text-success"><span class="badge-number"><?= $j ?></span> EXPERIÊNCIA</div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-4"><label class="small fw-bold">EMPRESA</label><input type="text" name="empresa[]" class="form-control" value="<?= htmlspecialchars($old['empresa'][$idx] ?? '') ?>"></div>
                            <div class="col-md-4"><label class="small fw-bold">CARGO</label>
                                <select name="cargo_id[]" class="form-select">
                                    <option value="">Selecione...</option>
                                    <?php foreach($todos_cargos as $tc): ?>
                                        <option value="<?= $tc['id'] ?>" <?= (isset($old['cargo_id'][$idx]) && $old['cargo_id'][$idx] == $tc['id']) ? 'selected' : '' ?>><?= $tc['nome'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2"><label class="small fw-bold">INÍCIO</label><input type="date" name="dt_inicio_experiencia[]" class="form-control" value="<?= htmlspecialchars($old['dt_inicio_experiencia'][$idx] ?? '') ?>"></div>
                            <div class="col-md-2"><label class="small fw-bold">FIM</label><input type="date" name="dt_fim_experiencia[]" class="form-control" value="<?= htmlspecialchars($old['dt_fim_experiencia'][$idx] ?? '') ?>"></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="small fw-bold">UF</label>
                                <select class="form-select" onchange="carregarCidades(this.value, 'cidade_exp_<?= $j ?>')">
                                    <option value="">...</option>
                                    <?php foreach($ufs as $u): ?>
                                        <option value="<?= $u['uf'] ?>" <?= (isset($old['exp_uf_hidden'][$idx]) && $old['exp_uf_hidden'][$idx] == $u['uf']) ? 'selected' : '' ?>><?= $u['uf'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" class="old_exp_uf" data-target="cidade_exp_<?= $j ?>" value="<?= htmlspecialchars($old['exp_uf_hidden'][$idx] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">CIDADE</label>
                                <select name="exp_cidade_ibge[]" id="cidade_exp_<?= $j ?>" class="form-select" disabled><option value="">...</option></select>
                                <input type="hidden" id="old_exp_cidade_<?= $j ?>" value="<?= htmlspecialchars($old['exp_cidade_ibge'][$idx] ?? '') ?>">
                            </div>
                            <div class="col-md-7"><label class="small fw-bold">DESCRIÇÃO DAS ATIVIDADES</label><textarea name="descricao_atividades[]" class="form-control" rows="1" placeholder="Breve resumo..."><?= htmlspecialchars($old['descricao_atividades'][$idx] ?? '') ?></textarea></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <div class="form-check mb-5 bg-light p-3 rounded border mx-2">
                    <input class="form-check-input ms-0 me-2" type="checkbox" name="consentimento_lgpd" value="1" required id="lgpd">
                    <label class="form-check-label small" for="lgpd">Aceito os <span class="link-lgpd" data-bs-toggle="modal" data-bs-target="#modalLGPD">Termos de Uso e Política de Privacidade</span> conforme a LGPD.</label>
                </div>

                <div class="text-end"><button type="submit" class="btn btn-success btn-lg px-5 py-3 shadow-lg fw-bold" style="border-radius: 15px;">FINALIZAR CADASTRO</button></div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const oldUfCv = document.getElementById('old_uf_cv').value;
                    const oldCidadeCv = document.getElementById('old_cidade_cv').value;
                    if(oldUfCv) carregarCidades(oldUfCv, 'cidade_cv', oldCidadeCv);

                    document.querySelectorAll('.old_exp_uf').forEach(function(el) {
                        const uf = el.value;
                        const target = el.getAttribute('data-target');
                        const index = target.split('_').pop();
                        const oldCidade = document.getElementById('old_exp_cidade_' + index).value;
                        if(uf) carregarCidades(uf, target, oldCidade);
                    });
                });
            </script>
        </form>
    </div>
    <div class="footer-minimal"><div class="container"><span>© 2026 ACIP - Palestina • LOGS ATIVOS</span></div></div>
</div>

<div class="modal fade" id="modalLGPD" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-success text-white"><h5 class="modal-title fw-bold">Políticas LGPD</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body text-muted"><p>Seus dados serão tratados de forma segura exclusivamente para fins de recrutamento.</p></div></div></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    var behavior = function (val) { return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009'; };
    $('.sp_celphones').mask(behavior, {onKeyPress: function(val, e, field, options) { field.mask(behavior.apply({}, arguments), options); }});

    $('#login_input').on('input', function() {
        $(this).val($(this).val().replace(/\s/g, ''));
    });

    // Verificação de Login em tempo real
    $('#login_input').on('blur', function(){
        const val = $(this).val();
        if(val.length > 2) {
            $.getJSON('check_availability.php', {type: 'login', value: val}, function(data){
                if(data.exists) {
                    $('#login_feedback').html('<i class="bi bi-x-circle"></i> Login já em uso!').removeClass('available').addClass('unavailable');
                } else {
                    $('#login_feedback').html('<i class="bi bi-check-circle"></i> Disponível!').removeClass('unavailable').addClass('available');
                }
            });
        }
    });

    // Verificação de E-mail em tempo real
    $('#email_input').on('blur', function(){
        const val = $(this).val();
        if(val.includes('@')) {
            $.getJSON('check_availability.php', {type: 'email', value: val}, function(data){
                if(data.exists) {
                    $('#email_feedback').html('<i class="bi bi-x-circle"></i> E-mail já cadastrado!').removeClass('available').addClass('unavailable');
                } else {
                    $('#email_feedback').html('<i class="bi bi-check-circle"></i> Disponível!').removeClass('unavailable').addClass('available');
                }
            });
        }
    });

    document.getElementById('foto').addEventListener('change', function(){
        if(this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => document.getElementById('preview-foto').src = e.target.result;
            reader.readAsDataURL(this.files[0]);
        }
    });

    $('#senha').on('input', function() {
        if ($(this).val().length < 6) { $(this).addClass('is-invalid'); $('#msgSenha').show(); }
        else { $(this).removeClass('is-invalid'); $('#msgSenha').hide(); }
    });
});

function addConhecimento() {
    $('#lista-conhecimentos').append('<div class="bloco-repeticao row g-3 mt-2"><div class="col-md-8"><input type="text" name="conhecimento_desc[]" class="form-control" placeholder="Habilidade..."></div><div class="col-md-4"><select name="conhecimento_nivel[]" class="form-select"><option value="Básico">Básico</option><option value="Intermediário">Intermediário</option><option value="Avançado">Avançado</option></select></div></div>');
}

function carregarCidades(uf, targetId, selectedIbge = null) {
    const select = document.getElementById(targetId);
    if (!uf) return;
    select.disabled = false;
    select.innerHTML = '<option>Carregando...</option>';

    fetch('busca_cidades.php?uf=' + uf)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">Cidade</option>';
            data.forEach(c => {
                const isSelected = (selectedIbge && c.ibge == selectedIbge) ? 'selected' : '';
                select.innerHTML += `<option value="${c.ibge}" ${isSelected}>${c.municipio}</option>`;
            });
        })
        .catch(err => {
            console.error("Erro na busca de cidades:", err);
            select.innerHTML = '<option value="">Erro ao carregar</option>';
        });
}
</script>
