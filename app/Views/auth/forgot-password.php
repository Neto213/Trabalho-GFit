<div class="mb-3">
    <h5 class="fw-bold mb-1">Recuperar Senha</h5>
    <p class="text-muted small">Informe seu e-mail cadastrado. Se o e-mail existir e estiver ativo, enviaremos um link para você cadastrar uma nova senha.</p>
</div>

<form action="<?= url('/recuperar-senha') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="email" class="form-label">E-mail cadastrado</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-envelope"></i></span>
            <input type="email" 
                   class="form-control" 
                   id="email" 
                   name="email" 
                   value="<?= old('email') ?>" 
                   placeholder="seu.email@exemplo.com" 
                   required 
                   autocomplete="email" 
                   autofocus>
        </div>
    </div>

    <div class="d-grid mt-4">
        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-send"></i>
            <span>Enviar link de recuperação</span>
        </button>
    </div>

    <div class="text-center mt-4">
        <a href="<?= url('/login') ?>" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i> Voltar para a tela de login
        </a>
    </div>
</form>
