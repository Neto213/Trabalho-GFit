<form action="<?= url('/login') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="email" class="form-label">E-mail de acesso</label>
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

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <label for="senha" class="form-label mb-0">Senha</label>
            <a href="<?= url('/recuperar-senha') ?>" class="small text-decoration-none text-primary">Esqueci minha senha</a>
        </div>
        <div class="input-group mt-1">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-lock"></i></span>
            <input type="password"
                class="form-control"
                id="senha"
                name="senha"
                placeholder="Sua senha secreta"
                required
                autocomplete="current-password">
            <button class="btn btn-outline-secondary"
                type="button"
                data-toggle-password="senha"
                aria-label="Exibir senha"
                aria-pressed="false">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="d-grid mt-4">
        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-2">
            <i class="bi bi-box-arrow-in-right"></i>
            <span class="fw-semibold">Entrar</span>
        </button>
    </div>

    <!-- Opção de Cadastro / Signup -->
    <div class="text-center mt-3 pt-2">
        <span class="text-light small">Não possui uma conta?</span>
        <a href="<?= url('/cadastro') ?>" class="small text-decoration-none fw-bold ms-1 text-primary">
            Cadastre-se aqui <i class="bi bi-arrow-right-short"></i>
        </a>
    </div>

    <div class="admin-access-box mt-4 p-3 rounded text-center">
        <div class="d-flex align-items-center justify-content-center gap-2 mb-1 text-light fw-semibold small">
            <i class="bi bi-shield-lock-fill text-primary"></i>
            <span>Portal do Administrador</span>
        </div>
        <p class="text-light small mb-2">Painel de controle, alteração de conteúdos e estatísticas da rede.</p>
        <a href="<?= url('/admin/login') ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
            <span>Acessar como Administrador</span>
            <i class="bi bi-arrow-right-short"></i>
        </a>
    </div>
</form>