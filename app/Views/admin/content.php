<div class="row g-4">
    <!-- Cabeçalho -->
    <div class="col-12">
        <div class="card p-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #143d3c 0%, #102e30 100%); color: #ffffff;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2 small">
                            <li class="breadcrumb-item"><a href="<?= url('/admin') ?>" class="text-white-50 text-decoration-none">Painel Geral</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Gestão de Conteúdo</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Gestão de Conteúdo</h2>
                    <p class="mb-0 text-white-50">Edição de comunicados, mural de avisos e informações institucionais da <strong><?= e($network['nome'] ?? 'Rede') ?></strong></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/admin/estatisticas') ?>" class="btn btn-outline-light d-flex align-items-center gap-2">
                        <i class="bi bi-graph-up"></i>
                        <span>Ver Estatísticas</span>
                    </a>
                    <button type="button" class="btn btn-primary d-flex align-items-center gap-2" onclick="openNewAnnouncementModal()">
                        <i class="bi bi-plus-circle"></i>
                        <span>Novo Comunicado</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Abas de Navegação de Conteúdo -->
    <div class="col-12">
        <ul class="nav nav-pills mb-3 gap-2" id="contentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="tab-comunicados-btn" data-bs-toggle="pill" data-bs-target="#tab-comunicados" type="button" role="tab">
                    <i class="bi bi-megaphone me-1"></i> Mural de Comunicados (<?= count($announcements) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-institucional-btn" data-bs-toggle="pill" data-bs-target="#tab-institucional" type="button" role="tab">
                    <i class="bi bi-building-gear me-1"></i> Conteúdo Institucional & Banners
                </button>
            </li>
        </ul>

        <div class="tab-content" id="contentTabsContent">
            <!-- ABA 1: MURAL DE COMUNICADOS -->
            <div class="tab-pane fade show active" id="tab-comunicados" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-chat-square-text me-2 text-primary"></i>Todos os Comunicados Publicados</span>
                        <span class="badge bg-secondary"><?= count($announcements) ?> registro(s)</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($announcements)): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                                <h5 class="fw-bold">Nenhum comunicado criado ainda</h5>
                                <p class="small mb-3">Publique orientações, avisos de feriado, manutenções e novidades para seus alunos e profissionais.</p>
                                <button type="button" class="btn btn-primary btn-sm" onclick="openNewAnnouncementModal()">
                                    <i class="bi bi-plus-circle me-1"></i> Criar Primeiro Comunicado
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th>Título & Conteúdo</th>
                                            <th>Categoria</th>
                                            <th>Público</th>
                                            <th>Unidade</th>
                                            <th>Status</th>
                                            <th>Criado em</th>
                                            <th class="text-end pe-4">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($announcements as $item): ?>
                                            <tr>
                                                <td class="text-center ps-3">
                                                    <?php if ((int)$item['fixado'] === 1): ?>
                                                        <i class="bi bi-pin-angle-fill text-warning fs-5" title="Comunicado fixado no topo"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= e($item['titulo']) ?></div>
                                                    <div class="text-muted small text-truncate" style="max-width: 380px;">
                                                        <?= e(mb_substr($item['conteudo'], 0, 100)) ?><?= mb_strlen($item['conteudo']) > 100 ? '...' : '' ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= e($item['categoria']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                        $audienceBadge = match ($item['publico_alvo']) {
                                                            'alunos' => 'bg-info-subtle text-info-emphasis',
                                                            'profissionais' => 'bg-warning-subtle text-warning-emphasis',
                                                            default => 'bg-primary-subtle text-primary-emphasis',
                                                        };
                                                        $audienceLabel = match ($item['publico_alvo']) {
                                                            'alunos' => 'Alunos',
                                                            'profissionais' => 'Instrutores',
                                                            default => 'Todos',
                                                        };
                                                    ?>
                                                    <span class="badge <?= $audienceBadge ?>">
                                                        <?= $audienceLabel ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="small text-muted">
                                                        <?= !empty($item['unidade_nome']) ? e($item['unidade_nome']) : 'Todas as Unidades' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $item['status'] === 'ativo' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                                        <?= e(ucfirst($item['status'])) ?>
                                                    </span>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="d-inline-flex gap-1">
                                                        <!-- Alternar Status -->
                                                        <form action="<?= url('/admin/conteudo/status') ?>" method="POST" class="m-0 d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= $item['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?>">
                                                                <i class="bi <?= $item['status'] === 'ativo' ? 'bi-toggle-on text-success fs-6' : 'bi-toggle-off text-muted fs-6' ?>"></i>
                                                            </button>
                                                        </form>

                                                        <!-- Editar -->
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-primary" 
                                                                title="Editar Comunicado"
                                                                onclick='openEditAnnouncementModal(<?= json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                            <i class="bi bi-pencil"></i>
                                                        </button>

                                                        <!-- Excluir -->
                                                        <form action="<?= url('/admin/conteudo/excluir') ?>" method="POST" class="m-0 d-inline" onsubmit="return confirm('Deseja realmente remover este comunicado?')">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir Comunicado">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ABA 2: CONTEÚDO INSTITUCIONAL & BANNERS -->
            <div class="tab-pane fade" id="tab-institucional" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <span class="fw-bold"><i class="bi bi-sliders me-2 text-primary"></i>Informações Institucionais e Banner de Avisos</span>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('/admin/conteudo/institucional') ?>" method="POST">
                            <?= csrf_field() ?>

                            <!-- Banner de Aviso Superior -->
                            <div class="p-3 mb-4 rounded border" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold mb-0 text-success-emphasis">
                                        <i class="bi bi-broadcast me-2"></i>Banner de Aviso Global (Topo dos Painéis)
                                    </h6>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="banner_ativo" name="banner_ativo" value="1" <?= (!empty($institutional['banner_ativo']) && (int)$institutional['banner_ativo'] === 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label small fw-semibold" for="banner_ativo">Banner Ativo</label>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">Quando ativo, este alerta aparecerá em destaque no topo de todos os painéis (Alunos, Instrutores e Administradores).</p>
                                <input type="text" 
                                       class="form-control" 
                                       id="banner_aviso" 
                                       name="banner_aviso" 
                                       value="<?= e($institutional['banner_aviso'] ?? '') ?>" 
                                       placeholder="Ex: Atenção: Horário especial de feriado neste próximo fim de semana.">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="slogan" class="form-label">Slogan / Frase da Academia</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="slogan" 
                                           name="slogan" 
                                           value="<?= e($institutional['slogan'] ?? '') ?>" 
                                           placeholder="Ex: Sua melhor versão começa aqui">
                                </div>

                                <div class="col-md-6">
                                    <label for="horario_funcionamento" class="form-label">Horário Geral de Funcionamento</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="horario_funcionamento" 
                                           name="horario_funcionamento" 
                                           value="<?= e($institutional['horario_funcionamento'] ?? '') ?>" 
                                           placeholder="Ex: Seg a Sex: 06:00 às 23:00 | Sáb: 08:00 às 18:00">
                                </div>

                                <div class="col-md-6">
                                    <label for="telefone_contato" class="form-label">Telefone / WhatsApp de Suporte</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="telefone_contato" 
                                           name="telefone_contato" 
                                           value="<?= e($institutional['telefone_contato'] ?? '') ?>" 
                                           placeholder="Ex: (11) 98765-4321">
                                </div>

                                <div class="col-md-6">
                                    <label for="email_contato" class="form-label">E-mail de Contato da Rede</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email_contato" 
                                           name="email_contato" 
                                           value="<?= e($institutional['email_contato'] ?? '') ?>" 
                                           placeholder="Ex: contato@alphafit.com.br">
                                </div>

                                <div class="col-12">
                                    <label for="descricao" class="form-label">Descrição Institucional</label>
                                    <textarea class="form-control" 
                                              id="descricao" 
                                              name="descricao" 
                                              rows="3" 
                                              placeholder="Apresentação institucional da rede de academias..."><?= e($institutional['descricao'] ?? '') ?></textarea>
                                </div>

                                <div class="col-12">
                                    <label for="regras_gerais" class="form-label">Regras e Normas de Convivência na Academia</label>
                                    <textarea class="form-control" 
                                              id="regras_gerais" 
                                              name="regras_gerais" 
                                              rows="3" 
                                              placeholder="Orientações aos alunos (ex: uso de toalha, devolução de pesos..."><?= e($institutional['regras_gerais'] ?? '') ?></textarea>
                                </div>

                                <div class="col-12 text-end mt-4">
                                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Salvar Conteúdo Institucional</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA NOVO/EDITAR COMUNICADO -->
<div class="modal fade" id="modalComunicado" tabindex="-1" aria-labelledby="modalComunicadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= url('/admin/conteudo/salvar') ?>" method="POST" id="formComunicado">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="comunicado_id" value="">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalComunicadoLabel">
                        <i class="bi bi-megaphone me-2 text-primary"></i>Novo Comunicado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="comunicado_titulo" class="form-label">Título do Comunicado *</label>
                            <input type="text" class="form-control" id="comunicado_titulo" name="titulo" required placeholder="Ex: Manutenção programada dos aparelhos">
                        </div>

                        <div class="col-md-4">
                            <label for="comunicado_categoria" class="form-label">Categoria</label>
                            <select class="form-select" id="comunicado_categoria" name="categoria">
                                <option value="Geral">Geral</option>
                                <option value="Novidades">Novidades</option>
                                <option value="Manutenção">Manutenção</option>
                                <option value="Eventos">Eventos</option>
                                <option value="Operacional">Operacional</option>
                                <option value="Horários">Horários</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="comunicado_publico_alvo" class="form-label">Público-Alvo</label>
                            <select class="form-select" id="comunicado_publico_alvo" name="publico_alvo">
                                <option value="todos">Todos (Alunos e Instrutores)</option>
                                <option value="alunos">Somente Alunos</option>
                                <option value="profissionais">Somente Instrutores</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="comunicado_unidade_id" class="form-label">Unidade Específica</label>
                            <select class="form-select" id="comunicado_unidade_id" name="unidade_id">
                                <option value="">Todas as Unidades</option>
                                <?php foreach ($networkUnits as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>"><?= e($u['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="comunicado_conteudo" class="form-label">Mensagem / Conteúdo Completo *</label>
                            <textarea class="form-control" id="comunicado_conteudo" name="conteudo" rows="5" required placeholder="Escreva a mensagem que será exibida para os alunos e profissionais..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="comunicado_fixado" name="fixado">
                                <label class="form-check-label fw-semibold" for="comunicado_fixado">
                                    <i class="bi bi-pin-angle text-warning me-1"></i> Fixar este comunicado no topo do mural
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="comunicado_status" class="form-label">Status da Publicação</label>
                            <select class="form-select" id="comunicado_status" name="status">
                                <option value="ativo">Publicado e Ativo</option>
                                <option value="inativo">Rascunho / Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="bi bi-save"></i>
                        <span id="btnSalvarComunicadoTexto">Salvar Comunicado</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?= e(\App\Support\SecurityHeaders::nonce()) ?>">
let modalInstance = null;

function getModal() {
    if (!modalInstance) {
        modalInstance = new bootstrap.Modal(document.getElementById('modalComunicado'));
    }
    return modalInstance;
}

function openNewAnnouncementModal() {
    document.getElementById('modalComunicadoLabel').innerHTML = '<i class="bi bi-megaphone me-2 text-primary"></i>Novo Comunicado';
    document.getElementById('btnSalvarComunicadoTexto').textContent = 'Publicar Comunicado';
    document.getElementById('comunicado_id').value = '';
    document.getElementById('comunicado_titulo').value = '';
    document.getElementById('comunicado_categoria').value = 'Geral';
    document.getElementById('comunicado_publico_alvo').value = 'todos';
    document.getElementById('comunicado_unidade_id').value = '';
    document.getElementById('comunicado_conteudo').value = '';
    document.getElementById('comunicado_fixado').checked = false;
    document.getElementById('comunicado_status').value = 'ativo';

    getModal().show();
}

function openEditAnnouncementModal(item) {
    document.getElementById('modalComunicadoLabel').innerHTML = '<i class="bi bi-pencil-square me-2 text-primary"></i>Editar Comunicado';
    document.getElementById('btnSalvarComunicadoTexto').textContent = 'Atualizar Comunicado';
    document.getElementById('comunicado_id').value = item.id;
    document.getElementById('comunicado_titulo').value = item.titulo;
    document.getElementById('comunicado_categoria').value = item.categoria || 'Geral';
    document.getElementById('comunicado_publico_alvo').value = item.publico_alvo || 'todos';
    document.getElementById('comunicado_unidade_id').value = item.unidade_id || '';
    document.getElementById('comunicado_conteudo').value = item.conteudo;
    document.getElementById('comunicado_fixado').checked = parseInt(item.fixado) === 1;
    document.getElementById('comunicado_status').value = item.status;

    getModal().show();
}
</script>
