<!-- Banner de Alerta Global -->
<?php if (!empty($institutional['banner_ativo']) && (int)$institutional['banner_ativo'] === 1 && !empty($institutional['banner_aviso'])): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center gap-2 p-3 shadow-sm mb-4">
        <i class="bi bi-megaphone-fill fs-5 text-warning-emphasis"></i>
        <div>
            <strong class="me-1">Aviso da Academia:</strong>
            <span><?= e($institutional['banner_aviso']) ?></span>
        </div>
    </div>
<?php endif; ?>

<!-- Cabeçalho Hero -->
<div class="dashboard-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <span class="badge px-3 py-2 mb-2 text-uppercase fw-semibold" style="background: rgba(6,182,212,0.2); color: #38bdf8; border: 1px solid rgba(6,182,212,0.4); letter-spacing: 0.07em;">
            <i class="bi bi-person-check me-1"></i> Área do Aluno
        </span>
        <h2 class="h3 fw-bold mb-1 text-white">Olá, <?= e($user['nome']) ?> 👋</h2>
        <p class="mb-0" style="color: rgba(255,255,255,0.55);">
            Aluno matriculado &bull; <strong class="text-white"><?= e($network['nome'] ?? 'Rede de Academia') ?></strong>
            <?php if (!empty($institutional['slogan'])): ?>
                &bull; <em><?= e($institutional['slogan']) ?></em>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge px-3 py-2" style="background: rgba(250,204,21,0.15); color: #fef08a; border: 1px solid rgba(250,204,21,0.3);">
            <i class="bi bi-award me-1"></i> Aluno Regular
        </span>
    </div>
</div>

<!-- Navegação por Abas -->
<ul class="nav nav-tabs dashboard-tabs mb-4" id="dashboardStudentTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab" aria-controls="tab-overview" aria-selected="true">
            <i class="bi bi-grid-1x2 me-1"></i><span class="tab-label">Visão Geral</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-profile-btn" data-bs-toggle="tab" data-bs-target="#tab-profile" type="button" role="tab" aria-controls="tab-profile" aria-selected="false">
            <i class="bi bi-person me-1"></i><span class="tab-label">Meu Perfil</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-workouts-btn" data-bs-toggle="tab" data-bs-target="#tab-workouts" type="button" role="tab" aria-controls="tab-workouts" aria-selected="false">
            <i class="bi bi-journal-check me-1"></i><span class="tab-label">Meus Treinos</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-settings-btn" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button" role="tab" aria-controls="tab-settings" aria-selected="false">
            <i class="bi bi-gear me-1"></i><span class="tab-label">Configurações</span>
        </button>
    </li>
</ul>

<!-- Conteúdo das Abas -->
<div class="tab-content dashboard-tab-content" id="dashboardStudentTabContent">

    <!-- ========== ABA: VISÃO GERAL ========== -->
    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview-btn" tabindex="0">
        <div class="row g-4">

            <!-- Mural de Comunicados da Academia para o Aluno -->
            <?php if (!empty($announcements)): ?>
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="bi bi-megaphone me-2 text-primary"></i>Mural de Avisos da Academia</span>
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
            <?php else: ?>
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-chat-left-dots text-muted d-block mb-2" style="font-size: 2.5rem;"></i>
                            <h6 class="fw-bold text-dark">Nenhum aviso no momento</h6>
                            <p class="text-muted small mb-0">Quando a academia publicar comunicados, eles aparecerão aqui.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Resumo rápido: Unidade + Treino -->
            <div class="col-md-5">
                <div class="card h-100 shadow-sm">
                    <div class="card-header">
                        <span class="fw-bold"><i class="bi bi-geo-alt me-2 text-primary"></i>Sua Unidade</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($linkedUnits)): ?>
                            <div class="text-muted small py-3">Nenhuma unidade vinculada ao seu cadastro.</div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($linkedUnits as $unit): ?>
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <span class="fw-medium"><?= e($unit['nome']) ?></span>
                                        <span class="badge bg-success-subtle text-success">Matriculado</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($institutional['horario_funcionamento'])): ?>
                            <div class="mt-3 pt-3 border-top small text-muted">
                                <strong class="d-block text-dark mb-1"><i class="bi bi-clock me-1 text-primary"></i>Horários de Funcionamento:</strong>
                                <?= e($institutional['horario_funcionamento']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($institutional['telefone_contato'])): ?>
                            <div class="mt-2 small text-muted">
                                <strong class="d-block text-dark"><i class="bi bi-whatsapp me-1 text-success"></i>Suporte:</strong>
                                <?= e($institutional['telefone_contato']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card h-100 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-journal-check me-2 text-primary"></i>Seu Plano de Treino</span>
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('/treinos') ?>">Consultar fichas</a>
                    </div>
                    <div class="card-body py-4 text-center">
                        <div class="mb-3">
                            <i class="bi bi-calendar2-heart text-muted" style="font-size: 2.5rem;"></i>
                        </div>
                        <h6 class="fw-bold text-dark">Acompanhe seu plano de treino</h6>
                        <p class="text-muted small mx-auto mb-0" style="max-width: 400px;">
                            Consulte os treinos publicados pela equipe e registre sua frequência ao concluir cada ficha.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Carrossel de Fotos: Comunidade & Estrutura da Academia -->
            <div class="col-12 mt-3">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <span class="fw-bold d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-images text-primary fs-5"></i>
                            <span>Nosso Espaço & Comunidade</span>
                        </span>
                        <span class="badge px-3 py-2" style="background: rgba(8,127,114,0.12); color: #087f72; border: 1px solid rgba(8,127,114,0.3);">
                            <i class="bi bi-building me-1"></i> Infraestrutura GFit
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div id="studentGymCarousel" class="carousel slide carousel-fade gfit-dashboard-carousel" data-bs-ride="carousel" data-bs-interval="5000" aria-label="Galeria da academia">
                            <div class="carousel-indicators">
                                <button type="button" data-bs-target="#studentGymCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Pessoas em Treino"></button>
                                <button type="button" data-bs-target="#studentGymCarousel" data-bs-slide-to="1" aria-label="Fundo da Academia"></button>
                                <button type="button" data-bs-target="#studentGymCarousel" data-bs-slide-to="2" aria-label="Treino Funcional"></button>
                                <button type="button" data-bs-target="#studentGymCarousel" data-bs-slide-to="3" aria-label="Área de Musculação"></button>
                            </div>
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="<?= url('assets/images/carousel/slide1_training.jpg') ?>" class="d-block w-100" alt="Pessoas treinando na academia">
                                    <div class="carousel-caption">
                                        <span class="carousel-tag"><i class="bi bi-people-fill"></i> Pessoas em Treino</span>
                                        <h5>Energia, União & Superação</h5>
                                        <p>Comunidade ativa com profissionais dedicados ao seu desenvolvimento constante.</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="<?= url('assets/images/carousel/slide2_interior.jpg') ?>" class="d-block w-100" alt="Fundo da academia com aparelhos de última geração">
                                    <div class="carousel-caption">
                                        <span class="carousel-tag"><i class="bi bi-building"></i> Fundo da Academia</span>
                                        <h5>Ambiente Moderno & Climatizado</h5>
                                        <p>Infraestrutura de ponta projetada para máxima eficiência, conforto e foco.</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="<?= url('assets/images/carousel/slide3_functional.jpg') ?>" class="d-block w-100" alt="Pessoas praticando treino funcional">
                                    <div class="carousel-caption">
                                        <span class="carousel-tag"><i class="bi bi-lightning-charge-fill"></i> Treino Funcional</span>
                                        <h5>Movimento Livre & Condicionamento</h5>
                                        <p>Área ampla dedicada a circuitos de alta intensidade, mobilidade e saúde integral.</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="<?= url('assets/images/carousel/slide4_weights.jpg') ?>" class="d-block w-100" alt="Área de musculação e pesos livres">
                                    <div class="carousel-caption">
                                        <span class="carousel-tag"><i class="bi bi-trophy-fill"></i> Área de Musculação</span>
                                        <h5>Força, Disciplina & Hipertrofia</h5>
                                        <p>Linha completa de halteres, anilhas calibradas e maquinário biomecânico de elite.</p>
                                    </div>
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#studentGymCarousel" data-bs-slide="prev" aria-label="Slide anterior">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#studentGymCarousel" data-bs-slide="next" aria-label="Próximo slide">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ========== ABA: MEU PERFIL ========== -->
    <div class="tab-pane fade" id="tab-profile" role="tabpanel" aria-labelledby="tab-profile-btn" tabindex="0">
        <div class="row g-4">

            <!-- Dados Pessoais -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <span class="fw-bold"><i class="bi bi-person-vcard me-2 text-primary"></i>Dados Pessoais</span>
                    </div>
                    <div class="card-body">
                        <div class="profile-info-item">
                            <div class="info-icon"><i class="bi bi-person"></i></div>
                            <div>
                                <div class="info-label">Nome completo</div>
                                <div class="info-value"><?= e($user['nome']) ?></div>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-icon"><i class="bi bi-envelope"></i></div>
                            <div>
                                <div class="info-label">E-mail</div>
                                <div class="info-value"><?= e($user['email']) ?></div>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-icon"><i class="bi bi-shield-check"></i></div>
                            <div>
                                <div class="info-label">Perfil</div>
                                <div class="info-value">
                                    <span class="badge bg-primary-subtle text-primary"><?= e(ucfirst($user['perfil'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-icon"><i class="bi bi-circle-fill" style="font-size: 0.6rem; color: <?= ($user['status'] ?? 'ativo') === 'ativo' ? '#16a34a' : '#dc2626' ?>;"></i></div>
                            <div>
                                <div class="info-label">Status da conta</div>
                                <div class="info-value">
                                    <span class="badge <?= ($user['status'] ?? 'ativo') === 'ativo' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                        <?= e(ucfirst($user['status'] ?? 'ativo')) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-icon"><i class="bi bi-building"></i></div>
                            <div>
                                <div class="info-label">Rede</div>
                                <div class="info-value"><?= e($network['nome'] ?? '—') ?></div>
                            </div>
                        </div>
                        <?php if (!empty($user['created_at'])): ?>
                            <div class="profile-info-item">
                                <div class="info-icon"><i class="bi bi-calendar3"></i></div>
                                <div>
                                    <div class="info-label">Membro desde</div>
                                    <div class="info-value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Unidades Vinculadas -->
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <span class="fw-bold"><i class="bi bi-geo-alt me-2 text-primary"></i>Unidades Vinculadas</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($linkedUnits)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-geo text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                <p class="small mb-0">Nenhuma unidade vinculada ao seu cadastro.</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($linkedUnits as $unit): ?>
                                    <li class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium"><?= e($unit['nome']) ?></div>
                                            <span class="text-muted small">ID #<?= (int)$unit['id'] ?></span>
                                        </div>
                                        <span class="badge <?= ($unit['status'] ?? 'ativo') === 'ativo' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                            <?= e(ucfirst($unit['status'] ?? 'ativo')) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="tab-pane fade" id="tab-workouts" role="tabpanel" aria-labelledby="tab-workouts-btn" tabindex="0"><div class="card empty-state"><i class="bi bi-journal-check"></i><h2 class="h4">Seu treino, sua evolução.</h2><p>Consulte suas fichas, registre cada treino concluído e acompanhe suas avaliações e frequência.</p><a class="btn btn-primary align-self-center mt-3" href="<?= url('/treinos') ?>">Abrir treinos e evolução <i class="bi bi-arrow-right ms-2"></i></a></div></div>    <!-- ========== ABA: CONFIGURAÇÕES ========== -->
    <div class="tab-pane fade" id="tab-settings" role="tabpanel" aria-labelledby="tab-settings-btn" tabindex="0">
        <div class="row g-4">

            <!-- Alteração de Senha -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <span class="fw-bold"><i class="bi bi-key me-2 text-primary"></i>Alterar Senha</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Para sua segurança, insira a senha atual antes de definir uma nova senha. Após a alteração, você será redirecionado para o login.
                        </p>
                        <form action="<?= url('/aluno/senha') ?>" method="POST" id="formChangePassword">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label for="senha_atual" class="form-label">Senha Atual</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="senha_atual" name="senha_atual" required autocomplete="current-password">
                                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="senha_atual" aria-label="Exibir senha" aria-pressed="false">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="nova_senha" class="form-label">Nova Senha</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="12" maxlength="128" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="nova_senha" aria-label="Exibir senha" aria-pressed="false">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Mínimo de 6 caracteres.</div>
                            </div>

                            <div class="mb-4">
                                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required minlength="12" maxlength="128" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="confirmar_senha" aria-label="Exibir senha" aria-pressed="false">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle"></i>
                                <span>Salvar Nova Senha</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Informações da Conta -->
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <span class="fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Informações da Conta</span>
                    </div>
                    <div class="card-body">
                        <div class="profile-info-item">
                            <div class="info-icon"><i class="bi bi-at"></i></div>
                            <div>
                                <div class="info-label">E-mail de acesso</div>
                                <div class="info-value"><?= e($user['email']) ?></div>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-icon"><i class="bi bi-person-badge"></i></div>
                            <div>
                                <div class="info-label">Tipo de conta</div>
                                <div class="info-value"><?= e(ucfirst($user['perfil'])) ?></div>
                            </div>
                        </div>
                        <?php if (!empty($user['created_at'])): ?>
                            <div class="profile-info-item">
                                <div class="info-icon"><i class="bi bi-calendar3"></i></div>
                                <div>
                                    <div class="info-label">Cadastrado em</div>
                                    <div class="info-value"><?= date('d/m/Y \à\s H:i', strtotime($user['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($user['updated_at'])): ?>
                            <div class="profile-info-item">
                                <div class="info-icon"><i class="bi bi-arrow-repeat"></i></div>
                                <div>
                                    <div class="info-label">Última atualização</div>
                                    <div class="info-value"><?= date('d/m/Y \à\s H:i', strtotime($user['updated_at'])) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3 pt-3 border-top">
                            <p class="small text-muted mb-0">
                                <i class="bi bi-shield-lock me-1"></i>
                                Para alterações nos dados pessoais como nome ou e-mail, entre em contato com a administração da academia.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
