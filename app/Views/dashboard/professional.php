<div class="row g-4">
    <!-- Banner de Alerta Global -->
    <?php if (!empty($institutional['banner_ativo']) && (int)$institutional['banner_ativo'] === 1 && !empty($institutional['banner_aviso'])): ?>
        <div class="col-12">
            <div class="alert alert-warning border-warning d-flex align-items-center gap-2 p-3 shadow-sm mb-0">
                <i class="bi bi-megaphone-fill fs-5 text-warning-emphasis"></i>
                <div>
                    <strong class="me-1">Comunicado Geral:</strong>
                    <span><?= e($institutional['banner_aviso']) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cabeçalho Hero -->
    <div class="col-12">
        <div class="dashboard-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge px-3 py-2 mb-2 text-uppercase fw-semibold" style="background: rgba(6,182,212,0.2); color: #38bdf8; border: 1px solid rgba(6,182,212,0.4); letter-spacing: 0.07em;">
                    <i class="bi bi-person-workspace me-1"></i> Painel do Profissional
                </span>
                <h2 class="h3 fw-bold mb-1 text-white">Olá, <?= e($user['nome']) ?> 👋</h2>
                <p class="mb-0" style="color: rgba(255,255,255,0.55);">Instrutor / Personal Trainer &bull; <strong class="text-white"><?= e($network['nome'] ?? 'Rede de Academia') ?></strong></p>
            </div>
            <div>
                <span class="badge px-3 py-2" style="background: rgba(6,182,212,0.15); color: #a5f3fc; border: 1px solid rgba(6,182,212,0.3);">
                    <i class="bi bi-person-badge me-1"></i> Perfil Profissional
                </span>
            </div>
        </div>
    </div>

    <!-- Mural de Comunicados para Profissionais -->
    <?php if (!empty($announcements)): ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-megaphone me-2 text-primary"></i>Comunicados da Coordenação</span>
                    <span class="badge bg-primary"><?= count($announcements) ?> aviso(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($announcements as $comm): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ((int)$comm['fixado'] === 1): ?>
                                            <i class="bi bi-pin-angle-fill text-warning" title="Fixado"></i>
                                        <?php endif; ?>
                                        <h6 class="mb-0 fw-bold text-dark"><?= e($comm['titulo']) ?></h6>
                                    </div>
                                    <span class="badge bg-light text-secondary border"><?= e($comm['categoria']) ?></span>
                                </div>
                                <p class="text-muted small mb-2"><?= nl2br(e($comm['conteudo'])) ?></p>
                                <span class="small text-muted">
                                    <i class="bi bi-clock me-1"></i><?= date('d/m/Y', strtotime($comm['created_at'])) ?>
                                    <?php if (!empty($comm['unidade_nome'])): ?>
                                        &bull; Unidade: <?= e($comm['unidade_nome']) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Vínculos de Unidade -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header">
                <span class="fw-bold"><i class="bi bi-geo-alt me-2 text-primary"></i>Suas Unidades de Atuação</span>
            </div>
            <div class="card-body">
                <?php if (empty($linkedUnits)): ?>
                    <div class="text-muted small py-3">Nenhuma unidade vinculada ao seu usuário.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($linkedUnits as $unit): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span class="fw-medium"><?= e($unit['nome']) ?></span>
                                <span class="badge bg-light text-dark border">Vinculado</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($institutional['horario_funcionamento'])): ?>
                    <div class="mt-3 pt-3 border-top small text-muted">
                        <strong class="d-block text-dark mb-1"><i class="bi bi-clock me-1 text-primary"></i>Horários da Rede:</strong>
                        <?= e($institutional['horario_funcionamento']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<div class="col-md-8"><div class="card h-100 p-4"><span class="eyebrow">ACOMPANHAMENTO DOS ALUNOS</span><h2 class="h4">O próximo passo começa com um plano.</h2><p class="text-muted">Consulte os alunos das suas unidades, publique fichas de treino e registre avaliações físicas.</p><a class="btn btn-primary align-self-start mt-2" href="<?= url('/treinos') ?>">Gerenciar treinos e avaliações <i class="bi bi-arrow-right ms-2"></i></a></div></div></div>