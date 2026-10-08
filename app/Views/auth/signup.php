<div class="text-center mb-4">
    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-2 shadow-sm" style="width: 56px; height: 56px;">
        <i class="bi bi-person-plus-fill fs-3"></i>
    </div>
    <h1 class="h4 fw-bold mb-1">Crie sua Conta</h1>
    <p class="text-light small mb-0">Cadastre-se para acessar seus treinos, evolução e informações da academia</p>
</div>

<form action="<?= url('/cadastro') ?>" method="POST" id="signupForm">
    <?= csrf_field() ?>

    <!-- Nome Completo -->
    <div class="mb-3">
        <label for="nome" class="form-label">Nome Completo *</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-person"></i></span>
            <input type="text"
                class="form-control"
                id="nome"
                name="nome"
                value="<?= old('nome') ?>"
                placeholder="Ex: Carlos Eduardo Silva"
                required
                autocomplete="name"
                autofocus>
        </div>
    </div>

    <!-- E-mail -->
    <div class="mb-3">
        <label for="email" class="form-label">E-mail *</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-envelope"></i></span>
            <input type="email"
                class="form-control"
                id="email"
                name="email"
                value="<?= old('email') ?>"
                placeholder="seu.email@exemplo.com"
                required
                autocomplete="email">
        </div>
        <div class="form-text text-light small mt-1">Será seu login de acesso ao sistema.</div>
    </div>

    <!-- Unidade de Matrícula -->
    <div class="mb-3">
        <label for="unidade_id" class="form-label">Sua Unidade da Academia *</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-geo-alt"></i></span>
            <select class="form-select" id="unidade_id" name="unidade_id" required>
                <option value="">Selecione uma filial...</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= ((string)old('unidade_id') === (string)$u['id']) ? 'selected' : '' ?>>
                        <?= e($u['unidade_nome']) ?> (<?= e($u['rede_nome']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Tipo de Perfil (Fixo como Aluno no cadastro público) -->
    <input type="hidden" name="perfil" value="aluno">
    <div class="mb-3">
        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-secondary bg-opacity-25 border border-secondary border-opacity-25">
            <span class="small fw-semibold text-light"><i class="bi bi-person-check text-info me-1"></i> Perfil: Aluno da Academia</span>
            <span class="badge bg-info text-dark">Público</span>
        </div>
        <div class="form-text text-light small mt-1">
            Instrutores e administradores são cadastrados internamente pela gestão da rede.
        </div>
    </div>


    <!-- Senha -->
    <div class="mb-3">
        <label for="signup_senha" class="form-label">Senha *</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-lock"></i></span>
            <input type="password"
                class="form-control"
                id="signup_senha"
                name="senha"
                placeholder="Mínimo 12 caracteres"
                minlength="12"
                required
                autocomplete="new-password">
            <button class="btn btn-outline-secondary"
                type="button"
                data-toggle-password="signup_senha"
                aria-label="Exibir senha">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <!-- Confirmar Senha -->
    <div class="mb-4">
        <label for="signup_confirmar_senha" class="form-label">Confirmar Senha *</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="bi bi-shield-lock"></i></span>
            <input type="password"
                class="form-control"
                id="signup_confirmar_senha"
                name="confirmar_senha"
                placeholder="Repita a mesma senha"
                required
                autocomplete="new-password">
            <button class="btn btn-outline-secondary"
                type="button"
                data-toggle-password="signup_confirmar_senha"
                aria-label="Exibir confirmação de senha">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" value="1" id="aceite_privacidade" name="aceite_privacidade" required>
        <label class="form-check-label small" for="aceite_privacidade">Li o <a href="<?= url('/privacidade') ?>" target="_blank" rel="noopener">Aviso de Privacidade</a> e estou ciente do tratamento necessário para criar e administrar minha conta.</label>
    </div>

    <!-- Botão de Cadastro -->
    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-2">
            <i class="bi bi-check-lg fs-5"></i>
            <span class="fw-semibold">Concluir Cadastro</span>
        </button>
    </div>

    <hr class="my-4 text-muted">

    <!-- Redirecionamento para Login -->
    <div class="text-center">
        <span class="text-light small">Já possui uma conta cadastrada?</span>
        <a href="<?= url('/login') ?>" class="small text-decoration-none ms-1 fw-bold text-primary">
            Fazer Login <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</form>
