<div class="row g-4">
    <!-- Banner de Alerta Global (se ativado nas configurações de conteúdo) -->
    <?php if (!empty($institutional['banner_ativo']) && (int)$institutional['banner_ativo'] === 1 && !empty($institutional['banner_aviso'])): ?>
        <div class="col-12">
            <div class="alert alert-warning border-warning d-flex align-items-center justify-content-between p-3 shadow-sm mb-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-megaphone-fill fs-5 text-warning-emphasis"></i>
                    <div>
                        <strong class="me-1">Aviso da Rede:</strong>
                        <span><?= e($institutional['banner_aviso']) ?></span>
                    </div>
                </div>
                <a href="<?= url('/admin/conteudo') ?>" class="btn btn-sm btn-outline-dark">Editar Aviso</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cabeçalho Hero -->
    <div class="col-12">
        <div class="dashboard-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge px-3 py-2 mb-2 text-uppercase fw-semibold" style="background: rgba(6,182,212,0.2); color: #38bdf8; border: 1px solid rgba(6,182,212,0.4); letter-spacing: 0.07em;">
                    <i class="bi bi-speedometer2 me-1"></i> Painel do Administrador
                </span>
                <h2 class="h3 fw-bold mb-1 text-white">Olá, <?= e($user['nome']) ?> 👋</h2>
                <p class="mb-0" style="color: rgba(255,255,255,0.55);">
                    Gestão centralizada da <strong class="text-white"><?= e($network['nome'] ?? 'Rede de Academia') ?></strong>
                    <?php if (!empty($institutional['slogan'])): ?>
                        &bull; <em><?= e($institutional['slogan']) ?></em>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('/admin/conteudo') ?>" class="btn btn-sm d-flex align-items-center gap-2" style="background: rgba(37,99,235,0.25); color: #93c5fd; border: 1px solid rgba(37,99,235,0.4);">
                    <i class="bi bi-pencil-square"></i>
                    <span>Gestão de Conteúdo</span>
                </a>
                <a href="<?= url('/admin/estatisticas') ?>" class="btn btn-sm d-flex align-items-center gap-2" style="background: rgba(6,182,212,0.15); color: #a5f3fc; border: 1px solid rgba(6,182,212,0.3);">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Ver Estatísticas</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Cards de Métricas em Destaque -->
    <?php if (isset($summary)): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Alunos Cadastrados</span>
                        <h3 class="fw-bold my-1 text-primary"><?= (int)$summary['total_alunos'] ?></h3>
                    </div>
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                </div>
                <div class="small text-muted pt-2 border-top d-flex justify-content-between">
                    <span><?= (int)$summary['alunos_ativos'] ?> ativos</span>
                    <a href="<?= url('/admin/estatisticas') ?>" class="text-decoration-none">Ver detalhes &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Cadastros ativos</span>
                        <h3 class="fw-bold my-1 text-success"><?= number_format((float)$summary['taxa_atividade_alunos'], 1, ',', '.') ?>%</h3>
                    </div>
                    <div class="p-2 bg-success bg-opacity-10 text-success rounded-3">
                        <i class="bi bi-activity fs-4"></i>
                    </div>
                </div>
                <div class="small text-muted pt-2 border-top d-flex justify-content-between">
                    <span>Percentual de alunos com cadastro ativo</span>
                    <a href="<?= url('/admin/estatisticas') ?>" class="text-decoration-none text-success">Gráficos &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Profissionais</span>
                        <h3 class="fw-bold my-1 text-warning-emphasis"><?= (int)$summary['total_profissionais'] ?></h3>
                    </div>
                    <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-3">
                        <i class="bi bi-person-workspace fs-4"></i>
                    </div>
                </div>
                <div class="small text-muted pt-2 border-top d-flex justify-content-between">
                    <span>Equipe técnica</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis">Instrutores</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Comunicados Ativos</span>
                        <h3 class="fw-bold my-1 text-info-emphasis"><?= (int)($summary['total_comunicados'] ?? 0) ?></h3>
                    </div>
                    <div class="p-2 bg-info bg-opacity-10 text-info rounded-3">
                        <i class="bi bi-megaphone-fill fs-4"></i>
                    </div>
                </div>
                <div class="small text-muted pt-2 border-top d-flex justify-content-between">
                    <span>Publicações no mural</span>
                    <a href="<?= url('/admin/conteudo') ?>" class="text-decoration-none text-info-emphasis">Gerenciar &rarr;</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mural de Avisos Recentes (com botão para Gestão de Conteúdo) -->
    <div class="col-lg-7">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-megaphone me-2 text-primary"></i>Mural de Comunicados Recentes</span>
                <a href="<?= url('/admin/conteudo') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i> Publicar / Editar
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($announcements)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-chat-left-dots fs-2 d-block mb-2 text-secondary"></i>
                        <p class="small mb-2">Nenhum comunicado publicado no momento.</p>
                        <a href="<?= url('/admin/conteudo') ?>" class="btn btn-sm btn-primary">Criar Comunicado</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($announcements as $item): ?>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ((int)$item['fixado'] === 1): ?>
                                            <i class="bi bi-pin-angle-fill text-warning" title="Fixado"></i>
                                        <?php endif; ?>
                                        <h6 class="mb-0 fw-bold text-dark"><?= e($item['titulo']) ?></h6>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-dark border"><?= e($item['categoria']) ?></span>
                                        <span class="badge <?= $item['status'] === 'ativo' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                            <?= e(ucfirst($item['status'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2 text-truncate" style="max-width: 90%;">
                                    <?= e($item['conteudo']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center small text-muted">
                                    <span>
                                        <i class="bi bi-people me-1"></i>Público: <strong><?= e(ucfirst($item['publico_alvo'])) ?></strong>
                                        &bull; <?= !empty($item['unidade_nome']) ? e($item['unidade_nome']) : 'Todas as Unidades' ?>
                                    </span>
                                    <span><?= date('d/m/Y', strtotime($item['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Informações da Rede e Unidades -->
    <div class="col-lg-5">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-building me-2 text-primary"></i>Unidades da Rede</span>
                <span class="badge bg-secondary"><?= count($networkUnits) ?> cadastrada(s)</span>
            </div>
            <div class="card-body">
                <p class="text-muted small">Unidades pertencentes à sua rede autorizada:</p>
                <div class="list-group list-group-flush">
                    <?php if (empty($networkUnits)): ?>
                        <div class="text-muted small py-3 text-center">Nenhuma unidade cadastrada nesta rede.</div>
                    <?php else: ?>
                        <?php foreach ($networkUnits as $unit): ?>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-semibold"><?= e($unit['nome']) ?></h6>
                                    <span class="text-muted small">ID: #<?= (int)$unit['id'] ?></span>
                                </div>
                                <span class="badge <?= $unit['status'] === 'ativo' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                    <?= e(ucfirst($unit['status'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($institutional['horario_funcionamento'])): ?>
                    <div class="mt-3 pt-3 border-top small text-muted">
                        <i class="bi bi-clock me-1 text-primary"></i> <strong>Horário da Rede:</strong>
                        <div><?= e($institutional['horario_funcionamento']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>