<div class="text-center mb-4">
    <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-warning bg-opacity-10 text-warning mb-3">
        <i class="bi bi-key-fill fs-1"></i>
    </div>
    <h1 class="h4 fw-bold text-white mb-1">Código de Recuperação</h1>
    <p class="text-muted small">
        Informe um dos seus códigos de recuperação de uso único gerados durante a ativação do MFA.
    </p>
</div>

<form action="<?= url('/login/mfa/recuperar') ?>" method="POST" id="mfa-recovery-form">
    <?= csrf_field() ?>

    <div class="mb-4">
        <label for="recovery-code" class="form-label">Código de Recuperação (ex: ABCD-1234)</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
            <input 
                type="text" 
                class="form-control text-uppercase text-center fw-bold" 
                id="recovery-code" 
                name="recovery_code" 
                placeholder="XXXX-XXXX" 
                maxlength="12" 
                autofocus 
                required
                style="letter-spacing: 0.15em; font-family: monospace; font-size: 1.2rem;"
            >
        </div>
        <div class="form-text text-muted small mt-2">
            <i class="bi bi-exclamation-circle me-1"></i> Cada código só pode ser utilizado <strong>uma única vez</strong> e será invalidado imediatamente após o acesso.
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-unlock-fill fs-5"></i>
        <span>Acessar Conta</span>
    </button>
</form>

<hr class="my-4 border-secondary opacity-25">

<div class="d-flex flex-column gap-2 text-center small">
    <div>
        <a href="<?= url('/login/mfa') ?>" class="text-info text-decoration-none fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Voltar para código do aplicativo
        </a>
    </div>
    <div>
        <a href="<?= url('/login') ?>" class="text-muted text-decoration-none">
            <i class="bi bi-box-arrow-in-left me-1"></i> Voltar ao início
        </a>
    </div>
</div>
