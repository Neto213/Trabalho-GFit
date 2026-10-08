<div class="text-center mb-4">
    <div class="d-inline-flex align-items-center justify-content-center bg-dark text-white rounded-circle p-3 mb-2 shadow-sm" style="width: 56px; height: 56px;">
        <i class="bi bi-shield-lock-fill fs-3 text-primary"></i>
    </div>
    <h1 class="h4 fw-bold mb-1">Acesso Administrativo</h1>
    <p class="text-muted small">Portal de gestão centralizada, conteúdos e métricas da rede</p>
    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1">
        Área Restrita aos Gestores
    </span>
</div>

<form action="<?= url('/admin/login') ?>" method="POST" id="adminLoginForm">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="admin_email" class="form-label">E-mail Administrativo</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-person-badge"></i></span>
            <input type="email" 
                   class="form-control" 
                   id="admin_email" 
                   name="email" 
                   value="<?= old('email') ?>" 
                   placeholder="admin@suarede.com.br" 
                   required 
                   autocomplete="email" 
                   autofocus>
        </div>
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <label for="admin_senha" class="form-label mb-0">Senha de Administrador</label>
            <a href="<?= url('/recuperar-senha') ?>" class="small text-decoration-none text-primary">Esqueci a senha</a>
        </div>
        <div class="input-group mt-1">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-key-fill"></i></span>
            <input type="password" 
                   class="form-control" 
                   id="admin_senha" 
                   name="senha" 
                   
                   placeholder="Sua senha administrativa" 
                   required 
                   autocomplete="current-password">
            <button class="btn btn-outline-secondary" 
                    type="button" 
                    data-toggle-password="admin_senha" 
                    aria-label="Exibir senha" 
                    aria-pressed="false">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="d-grid mt-4 gap-2">
        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-2">
            <i class="bi bi-shield-check"></i>
            <span class="fw-semibold">Entrar como Administrador</span>
        </button>


    </div>

    <hr class="my-4 text-muted">

    <div class="text-center">
        <span class="text-muted small">É aluno ou profissional?</span>
        <a href="<?= url('/login') ?>" class="small text-decoration-none ms-1 fw-semibold text-primary">
            Ir para Login Geral <i class="bi bi-arrow-right"></i>
        </a>
        <div class="mt-2">
            <a href="<?= url('/cadastro') ?>" class="small text-decoration-none text-muted">
                Novo por aqui? Criar conta de aluno
            </a>
        </div>
    </div>
</form>


