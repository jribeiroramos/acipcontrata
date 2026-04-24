<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/header.php';
require_once 'classes/Database.php';

// 1. Segurança de Acesso: Permitir Admin e Superadmin
$minha_role = $_SESSION['usuario_role'] ?? 'user';
if (!isset($_SESSION['usuario_id']) || !in_array($minha_role, ['superadmin', 'admin'])) {
    header("Location: login.php"); exit;
}

$db = Database::getConnection();
$cid = $_GET['id'] ?? null;

if (!$cid) { die("ID do currículo não fornecido."); }

// 2. Busca dados do usuário alvo incluindo a ROLE para validação de hierarquia
$stmt = $db->prepare("SELECT u.id, u.login, u.role, c.nome_completo
                      FROM usuarios u
                      JOIN curriculos c ON c.usuario_id = u.id
                      WHERE c.id = ?");
$stmt->execute([$cid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { die("Usuário não encontrado."); }

// 3. REGRA DE ISOLAMENTO: Admin não pode alterar senha de Superadmin
if ($minha_role === 'admin' && $user['role'] === 'superadmin') {
    header("Location: gestao_candidatos.php?erro=permissao_negada");
    exit;
}
?>

<div class="page-wrapper" style="background-color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="card p-4 shadow-lg border-0" style="width: 100%; max-width: 400px; border-radius: 25px; background: white;">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="bi bi-shield-lock-fill text-warning" style="font-size: 3rem;"></i>
            </div>
            <h4 class="fw-bold mt-2">Alterar Senha</h4>
            <p class="text-muted small">Candidato: <b class="text-dark"><?= htmlspecialchars($user['nome_completo'] ?? '') ?></b></p>
            <?php if($user['role'] === 'superadmin'): ?>
                <span class="badge bg-dark text-warning mb-2">PERFIL SUPERADMIN</span>
            <?php endif; ?>
        </div>

        <form action="processa_alterar_senha_admin.php" method="POST" onsubmit="return validarSenhas()">
            <input type="hidden" name="usuario_id" value="<?= $user['id'] ?>">
            <input type="hidden" name="curriculo_id" value="<?= $cid ?>">

            <div class="mb-3">
                <label class="small fw-bold text-uppercase text-muted">Nova Senha</label>
                <input type="password" name="nova_senha" id="nova_senha" class="form-control form-control-lg"
                       required minlength="6" placeholder="Mínimo 6 caracteres"
                       style="border-radius: 12px; background-color: #f1f5f9; border: none;">
            </div>

            <div class="mb-4">
                <label class="small fw-bold text-uppercase text-muted">Confirme a Senha</label>
                <input type="password" name="confirma_senha" id="confirma_senha" class="form-control form-control-lg"
                       required style="border-radius: 12px; background-color: #f1f5f9; border: none;">
                <div id="erro-senha" class="text-danger small fw-bold mt-2 d-none">As senhas não coincidem!</div>
            </div>

            <button type="submit" class="btn btn-dark w-100 fw-bold py-3 shadow-sm mb-3" style="border-radius: 15px;">
                <i class="bi bi-check2-circle me-2"></i>ATUALIZAR AGORA
            </button>

            <div class="text-center">
                <a href="gestao_candidatos.php" class="text-decoration-none small fw-bold text-success">
                    <i class="bi bi-arrow-left me-1"></i> Voltar à Listagem
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function validarSenhas() {
    const s1 = document.getElementById('nova_senha').value;
    const s2 = document.getElementById('confirma_senha').value;
    const erro = document.getElementById('erro-senha');
    
    if (s1 !== s2) {
        erro.classList.remove('d-none');
        return false;
    }
    return true;
}
</script>

<?php require_once 'includes/footer.php'; ?>
