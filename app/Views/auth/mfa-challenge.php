<div class="text-center mb-4">
    <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-info bg-opacity-10 text-info mb-3">
        <i class="bi bi-envelope-open-fill fs-1"></i>
    </div>
    <h1 class="h4 fw-bold text-white mb-1">Verificação em Duas Etapas</h1>
    <p class="text-muted small">
        Olá, <span class="text-info fw-semibold"><?= e($user['nome'] ?? '') ?></span>. Acabamos de enviar um código de 6 dígitos para o seu email.
    </p>
</div>

<form action="<?= url('/login/mfa') ?>" method="POST" id="mfa-challenge-form">
    <?= csrf_field() ?>

    <div class="mb-4">
        <label for="totp-code" class="form-label text-center d-block">Código de Verificação (Email)</label>
        <div class="position-relative">
            <input 
                type="text" 
                inputmode="numeric" 
                pattern="[0-9]*" 
                class="form-control totp-input" 
                id="totp-code" 
                name="code" 
                maxlength="6" 
                placeholder="000000" 
                autocomplete="one-time-code" 
                autofocus 
                required
            >
        </div>
        <div class="form-text text-center text-muted small mt-2">
            <i class="bi bi-clock-history me-1"></i> Verifique sua caixa de entrada ou pasta de spam.
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-check2-circle fs-5"></i>
        <span>Confirmar e Entrar</span>
    </button>
</form>

<hr class="my-4 border-secondary opacity-25">

<div class="d-flex flex-column gap-2 text-center small">
    <div>
        <a href="<?= url('/login/mfa/recuperar') ?>" class="text-info text-decoration-none fw-semibold">
            <i class="bi bi-key me-1"></i> Perdeu o acesso? Usar código de recuperação
        </a>
    </div>
    <div>
        <a href="<?= url('/login') ?>" class="text-muted text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Voltar para a tela de login
        </a>
    </div>
</div>

<script nonce="<?= e(\App\Support\SecurityHeaders::nonce()) ?>">
    // Auto-formatação e foco de digitação fluida
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('totp-code');
        if (input) {
            input.focus();
            input.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                if (this.value.length === 6) {
                    // Auto submissão ao completar 6 dígitos
                    document.getElementById('mfa-challenge-form').submit();
                }
            });
        }
    });
</script>
