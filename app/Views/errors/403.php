<div class="row justify-content-center py-5">
    <div class="col-md-7 text-center">
        <div class="card p-5 border-0 shadow-sm">
            <div class="mb-3 text-danger">
                <i class="bi bi-shield-slash" style="font-size: 3.5rem;"></i>
            </div>
            <h1 class="h3 fw-bold mb-2"><?= e($title ?? 'Acesso não autorizado') ?></h1>
            <p class="text-muted mb-4">
                <?= e($message ?? 'Esta área não está disponível para o seu perfil. Volte ao seu painel para continuar.') ?>
            </p>
            <?php if (isset($user['perfil'])): ?>
                <?php
                    $targetUrl = match ($user['perfil']) {
                        'admin' => url('/admin'),
                        'profissional' => url('/profissional'),
                        'aluno' => url('/aluno'),
                        default => url('/login'),
                    };
                ?>
                <div>
                    <a href="<?= $targetUrl ?>" class="btn btn-primary px-4">
                        <i class="bi bi-arrow-left me-1"></i> Retornar ao meu painel permitido
                    </a>
                </div>
            <?php else: ?>
                <div>
                    <a href="<?= url('/login') ?>" class="btn btn-primary px-4">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Ir para o Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
