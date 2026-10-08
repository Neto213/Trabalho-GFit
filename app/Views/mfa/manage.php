<div class="row justify-content-center">
    <div class="col-lg-9 col-md-11">
        <!-- Banner de novos códigos gerados -->
        <?php if (!empty($newRecoveryCodes)): ?>
            <div class="card border-warning bg-dark text-white rounded-4 shadow-lg mb-4 overflow-hidden">
                <div class="card-header bg-warning bg-opacity-25 border-warning py-3 px-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 text-warning">
                        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                        <h2 class="h5 mb-0 fw-bold">Códigos de Recuperação — Guarde em Local Seguro!</h2>
                    </div>
                    <span class="badge bg-warning text-dark fw-bold text-uppercase px-3 py-2">Exibição Única</span>
                </div>
                <div class="card-body p-4">
                    <p class="text-light text-opacity-90">
                        Estes são os seus códigos de recuperação de uso único. Caso você perca o acesso ao seu e-mail, cada código permitirá autenticar sua conta uma única vez. <strong>Eles não serão exibidos novamente.</strong>
                    </p>

                    <div class="recovery-codes-grid my-3" id="recovery-codes-container">
                        <?php foreach ($newRecoveryCodes as $code): ?>
                            <div class="recovery-code-item font-monospace">
                                <i class="bi bi-key me-1 text-info"></i><?= e($code) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-2 mt-4 flex-wrap">
                        <button type="button" class="btn btn-outline-info d-flex align-items-center gap-2" onclick="copyAllRecoveryCodes()">
                            <i class="bi bi-clipboard-check" id="copy-codes-icon"></i>
                            <span>Copiar Todos os Códigos</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2 text-white" onclick="window.print()">
                            <i class="bi bi-printer"></i>
                            <span>Imprimir / Salvar em PDF</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Painel Geral de MFA -->
        <div class="card border-0 shadow-lg bg-dark text-white rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-gradient bg-primary bg-opacity-25 border-bottom border-secondary border-opacity-25 py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($user['mfa_enabled'])): ?>
                        <i class="bi bi-shield-check text-success fs-4"></i>
                    <?php else: ?>
                        <i class="bi bi-shield-exclamation text-warning fs-4"></i>
                    <?php endif; ?>
                    <h1 class="h5 mb-0 fw-bold text-white">Segurança da Conta: Autenticação em Duas Etapas</h1>
                </div>
                <?php if (!empty($user['mfa_enabled'])): ?>
                    <span class="badge bg-success bg-opacity-75 px-3 py-2 text-uppercase">
                        <i class="bi bi-check-circle-fill me-1"></i> Ativo &amp; Protegido
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark px-3 py-2 text-uppercase">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Inativo
                    </span>
                <?php endif; ?>
            </div>

            <div class="card-body p-4 p-md-5">
                <?php if (!empty($user['mfa_enabled'])): ?>
                <div class="row g-4 align-items-center mb-4">
                    <div class="col-md-8">
                        <h3 class="h6 fw-bold text-white mb-2"><i class="bi bi-envelope-check me-2 text-info"></i>Autenticação por Email</h3>
                        <p class="text-muted small mb-0">
                            Sua conta está protegida por verificação via email. Cada vez que você fizer login, um código de 6 dígitos será enviado para o seu endereço de email cadastrado.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="<?= url('/mfa/configurar') ?>" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-arrow-repeat me-1"></i> Reconfigurar MFA
                        </a>
                    </div>
                </div>

                <hr class="border-secondary border-opacity-25 my-4">

                <div class="row g-4 align-items-center mb-4">
                    <div class="col-md-8">
                        <h3 class="h6 fw-bold text-white mb-2">Códigos de Recuperação Disponíveis</h3>
                        <p class="text-muted small mb-0">
                            Você possui <strong class="text-info"><?= (int)$remainingCodes ?></strong> código(s) de recuperação não utilizado(s).
                            Se você utilizou a maioria dos seus códigos, gere um novo conjunto imediatamente.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegenerateCodes">
                            <i class="bi bi-plus-circle me-1"></i> Gerar Novos Códigos
                        </button>
                    </div>
                </div>

                <hr class="border-secondary border-opacity-25 my-4">

                <!-- Área de Desativação -->
                <div class="p-4 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h4 class="h6 fw-bold text-danger mb-1">Desativar Autenticação em Duas Etapas</h4>
                            <p class="text-muted small mb-0">
                                A desativação requer sua senha e a validação de um código de recuperação.
                                <?php if ($isMandatory): ?>
                                    <strong class="text-warning">Atenção: Seu perfil exige MFA ativo. Caso desative, será solicitado reativá-lo no próximo acesso.</strong>
                                <?php endif; ?>
                            </p>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalDisableMfa">
                            <i class="bi bi-shield-x me-1"></i> Desativar MFA
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <!-- MFA Inativo: orientar o usuário a ativar -->
                <div class="text-center py-4">
                    <i class="bi bi-envelope-slash fs-1 text-warning mb-3 d-block"></i>
                    <h3 class="h5 fw-bold text-white mb-2">Autenticação por Email não configurada</h3>
                    <p class="text-muted mb-4">
                        Proteja sua conta com uma camada adicional de segurança por email. Cada login exigirá um código enviado para seu endereço registrado.
                        <?php if ($isMandatory): ?>
                            <strong class="d-block text-warning mt-2">Seu perfil exige a ativação do MFA para manter o acesso ao sistema.</strong>
                        <?php endif; ?>
                    </p>
                    <a href="<?= url('/mfa/configurar') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2">
                        <i class="bi bi-envelope-check fs-5"></i>
                        <span class="fw-semibold">Ativar Autenticação por Email</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($user['mfa_enabled'])): ?>
<!-- Modal: Gerar Novos Códigos de Recuperação -->
<div class="modal fade" id="modalRegenerateCodes" tabindex="-1" aria-labelledby="modalRegenerateCodesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="modalRegenerateCodesLabel">Gerar Novos Códigos de Recuperação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= url('/mfa/codigos-recuperacao') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Por segurança, confirme sua senha atual. Os códigos de recuperação anteriores serão automaticamente cancelados e substituídos.
                    </p>
                    <div class="mb-3">
                        <label for="regen-senha" class="form-label text-light">Senha Atual</label>
                        <input type="password" name="senha" id="regen-senha" class="form-control bg-secondary bg-opacity-25 text-white border-secondary" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Gerar e Invalidar Anteriores</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Desativar MFA -->
<div class="modal fade" id="modalDisableMfa" tabindex="-1" aria-labelledby="modalDisableMfaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-danger bg-danger bg-opacity-10">
                <h5 class="modal-title text-danger" id="modalDisableMfaLabel">Confirmar Desativação de MFA</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= url('/mfa/desativar') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Para desativar o segundo fator, informe sua senha e um <strong>código de recuperação</strong> válido (formato ABCD-1234).
                    </p>
                    <div class="mb-3">
                        <label for="dis-senha" class="form-label text-light">Sua Senha</label>
                        <input type="password" name="senha" id="dis-senha" class="form-control bg-secondary bg-opacity-25 text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label for="dis-codigo" class="form-label text-light">Código de Recuperação (ABCD-1234)</label>
                        <input type="text" name="codigo_confirmacao" id="dis-codigo" class="form-control bg-secondary bg-opacity-25 text-white border-secondary text-center font-monospace" placeholder="ABCD-1234" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Desativação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>
<script nonce="<?= e(\App\Support\SecurityHeaders::nonce()) ?>">
    function copyAllRecoveryCodes() {
        const items = document.querySelectorAll('#recovery-codes-container .recovery-code-item');
        const codes = Array.from(items).map(el => el.innerText.trim()).join('\n');
        navigator.clipboard.writeText(codes).then(() => {
            const icon = document.getElementById('copy-codes-icon');
            if (icon) {
                icon.className = 'bi bi-check2 text-success';
                setTimeout(() => {
                    icon.className = 'bi bi-clipboard-check';
                }, 2500);
            }
        });
    }
</script>
