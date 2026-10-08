<div class="mb-3">
    <h5 class="fw-bold mb-1">Cadastrar Nova Senha</h5>
    <p class="text-muted small">Crie uma nova senha de acesso segura para a conta <strong><?= e($email) ?></strong>.</p>
</div>

<form action="<?= url('/redefinir-senha') ?>" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div class="mb-3">
        <label for="senha" class="form-label">Nova senha (mínimo de 12 caracteres)</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-lock"></i></span>
            <input type="password" 
                   class="form-control" 
                   id="senha" 
                   name="senha" 
                   placeholder="Digite sua nova senha" 
                   required 
                   autocomplete="new-password" 
                   autofocus>
            <button class="btn btn-outline-secondary" 
                    type="button" 
                    data-toggle-password="senha" 
                    aria-label="Exibir senha" 
                    aria-pressed="false">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="mb-3">
        <label for="confirmar_senha" class="form-label">Confirme a nova senha</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-shield-check"></i></span>
            <input type="password" 
                   class="form-control" 
                   id="confirmar_senha" 
                   name="confirmar_senha" 
                   placeholder="Repita a nova senha" 
                   required 
                   autocomplete="new-password">
            <button class="btn btn-outline-secondary" 
                    type="button" 
                    data-toggle-password="confirmar_senha" 
                    aria-label="Exibir confirmação de senha" 
                    aria-pressed="false">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="d-grid mt-4">
        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-check2-circle"></i>
            <span>Redefinir Senha e Entrar</span>
        </button>
    </div>

    <div class="text-center mt-4">
        <a href="<?= url('/login') ?>" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i> Cancelar e voltar ao login
        </a>
    </div>
</form>
