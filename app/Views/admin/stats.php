<div class="row g-4">
    <!-- Cabeçalho de Estatísticas -->
    <div class="col-12">
        <div class="card p-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #143d3c 0%, #102e30 100%); color: #ffffff;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2 small">
                            <li class="breadcrumb-item"><a href="<?= url('/admin') ?>" class="text-white-50 text-decoration-none">Painel Geral</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Estatísticas</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold mb-1"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Visualização de Estatísticas e Métricas</h2>
                    <p class="mb-0 text-white-50">Indicadores de desempenho, alunos, distribuição e engajamento na <strong><?= e($network['nome'] ?? 'Rede') ?></strong></p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Filtro por Unidade -->
                    <form method="GET" action="<?= url('/admin/estatisticas') ?>" class="d-flex align-items-center m-0">
                        <select aria-label="Filtrar por unidade" name="unidade_id" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()">
                            <option value="">Consolidado (Todas as Unidades)</option>
                            <?php foreach ($networkUnits as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= (!empty($selectedUnit) && (int)$selectedUnit === (int)$u['id']) ? 'selected' : '' ?>>
                                    <?= e($u['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <a href="<?= url('/admin/conteudo') ?>" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1">
                        <i class="bi bi-pencil-square"></i>
                        <span>Conteúdos</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Cards de Métricas Principais (KPIs) -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Total de Alunos</span>
                    <h3 class="fw-bold my-1 text-primary"><?= (int)$summary['total_alunos'] ?></h3>
                </div>
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between small text-muted pt-2 border-top">
                <span><i class="bi bi-check-circle text-success me-1"></i><?= (int)$summary['alunos_ativos'] ?> ativos</span>
                <span><i class="bi bi-pause-circle text-secondary me-1"></i><?= (int)$summary['alunos_inativos'] ?> inativos</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Cadastros ativos</span>
                    <h3 class="fw-bold my-1 text-success"><?= number_format((float)$summary['taxa_atividade_alunos'], 1, ',', '.') ?>%</h3>
                </div>
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-3">
                    <i class="bi bi-pie-chart-fill fs-4"></i>
                </div>
            </div>
            <div class="pt-2 border-top">
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, max(0, (float)$summary['taxa_atividade_alunos'])) ?>%"></div>
                </div>
                <div class="small text-muted mt-1 text-end">Percentual de cadastros ativos</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Profissionais / Instrutores</span>
                    <h3 class="fw-bold my-1 text-warning-emphasis"><?= (int)$summary['total_profissionais'] ?></h3>
                </div>
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3">
                    <i class="bi bi-person-workspace fs-4"></i>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between small text-muted pt-2 border-top">
                <span>Prescrição de treinos</span>
                <span class="badge bg-warning-subtle text-warning-emphasis">Equipe Ativa</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Unidades em Operação</span>
                    <h3 class="fw-bold my-1 text-info-emphasis"><?= (int)$summary['total_unidades'] ?></h3>
                </div>
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-3">
                    <i class="bi bi-building fs-4"></i>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between small text-muted pt-2 border-top">
                <span><?= (int)$summary['total_comunicados'] ?> avisos ativos</span>
                <span class="badge bg-info-subtle text-info-emphasis">Filiais</span>
            </div>
        </div>
    </div>

    <!-- Gráficos Visuais -->
    <div class="col-lg-8">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Distribuição de Alunos por Unidade</span>
                <span class="badge bg-light text-secondary border">Comparativo Ativos vs Inativos</span>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="chartUnidades"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pie-chart me-2 text-primary"></i>Composição por Perfil</span>
                <span class="badge bg-light text-secondary border">Total: <?= (int)$summary['total_usuarios'] ?></span>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="chartPerfis"></canvas>
                </div>
                <div class="d-flex justify-content-around w-100 mt-3 pt-2 border-top text-center small">
                    <div>
                        <span class="d-block fw-bold text-primary"><?= (int)$summary['total_alunos'] ?></span>
                        <span class="text-muted">Alunos</span>
                    </div>
                    <div>
                        <span class="d-block fw-bold text-warning-emphasis"><?= (int)$summary['total_profissionais'] ?></span>
                        <span class="text-muted">Instrutores</span>
                    </div>
                    <div>
                        <span class="d-block fw-bold text-dark"><?= (int)$summary['total_admins'] ?></span>
                        <span class="text-muted">Gestores</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela Detalhada das Unidades -->
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-table me-2 text-primary"></i>Detalhamento Operacional por Filial</span>
                <span class="badge bg-secondary"><?= count($unitsDistribution) ?> unidade(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nome da Unidade</th>
                                <th>Status</th>
                                <th class="text-center">Alunos Ativos</th>
                                <th class="text-center">Alunos Inativos</th>
                                <th class="text-center">Instrutores</th>
                                <th class="text-center">Total Vínculos</th>
                                <th>Taxa Ativa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($unitsDistribution)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Nenhuma unidade cadastrada nesta rede.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($unitsDistribution as $row): ?>
                                    <?php
                                        $totAl = (int)$row['total_alunos'];
                                        $atvAl = (int)$row['alunos_ativos'];
                                        $taxa = $totAl > 0 ? round(($atvAl / $totAl) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($row['unidade_nome']) ?></div>
                                            <span class="small text-muted">ID #<?= (int)$row['unidade_id'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $row['unidade_status'] === 'ativo' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                                <?= e(ucfirst($row['unidade_status'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold text-success"><?= $atvAl ?></td>
                                        <td class="text-center text-muted"><?= (int)$row['alunos_inativos'] ?></td>
                                        <td class="text-center fw-semibold"><?= (int)$row['total_profissionais'] ?></td>
                                        <td class="text-center fw-bold"><?= (int)$row['total_usuarios'] ?></td>
                                        <td style="min-width: 120px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar bg-success" style="width: <?= $taxa ?>%"></div>
                                                </div>
                                                <span class="small fw-semibold text-muted"><?= $taxa ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Painel de Segurança e Acessos -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <span class="fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Segurança & Registros de Acesso</span>
            </div>
            <div class="card-body">
                <div class="p-3 mb-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Tentativas de login monitoradas:</span>
                        <span class="fw-bold text-dark"><?= (int)$securityStats['total_tentativas'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">IPs / Chaves registradas:</span>
                        <span class="fw-bold text-dark"><?= (int)$securityStats['total_registros'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Bloqueios ativos por força bruta:</span>
                        <span class="badge <?= (int)$securityStats['bloqueios_ativos'] > 0 ? 'bg-danger' : 'bg-success' ?>">
                            <?= (int)$securityStats['bloqueios_ativos'] ?> bloqueio(s)
                        </span>
                    </div>
                </div>

                <h6 class="fw-bold small text-muted text-uppercase mb-2" style="letter-spacing: 0.05em;">Políticas de Acesso Ativas</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Proteção CSRF estrita em todas as requisições</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Rate Limiting automático (limites por conta e endereço IP)</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Isolamento rígido multitenant por Rede</span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Revogação atômica de sessões e tokens SHA-256</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Carregamento do Chart.js para Renderização dos Gráficos -->
<script src="<?= url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script nonce="<?= e(\App\Support\SecurityHeaders::nonce()) ?>">
document.addEventListener('DOMContentLoaded', () => {
    // 1. Dados do Gráfico de Unidades
    const unitLabels = <?= json_encode(array_column($unitsDistribution, 'unidade_nome')) ?>;
    const unitAlunosAtivos = <?= json_encode(array_map('intval', array_column($unitsDistribution, 'alunos_ativos'))) ?>;
    const unitAlunosInativos = <?= json_encode(array_map('intval', array_column($unitsDistribution, 'alunos_inativos'))) ?>;

    const ctxUnidades = document.getElementById('chartUnidades');
    if (ctxUnidades) {
        new Chart(ctxUnidades, {
            type: 'bar',
            data: {
                labels: unitLabels.length > 0 ? unitLabels : ['Sem unidades'],
                datasets: [
                    {
                        label: 'Alunos Ativos',
                        data: unitAlunosAtivos.length > 0 ? unitAlunosAtivos : [0],
                        backgroundColor: '#087f72',
                        borderRadius: 6,
                    },
                    {
                        label: 'Alunos Inativos',
                        data: unitAlunosInativos.length > 0 ? unitAlunosInativos : [0],
                        backgroundColor: '#cbd5e1',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 14, font: { family: 'Inter', size: 12 } }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // 2. Gráfico Donut de Perfis
    const ctxPerfis = document.getElementById('chartPerfis');
    if (ctxPerfis) {
        new Chart(ctxPerfis, {
            type: 'doughnut',
            data: {
                labels: ['Alunos', 'Instrutores', 'Administradores'],
                datasets: [{
                    data: [
                        <?= (int)$summary['total_alunos'] ?>,
                        <?= (int)$summary['total_profissionais'] ?>,
                        <?= (int)$summary['total_admins'] ?>
                    ],
                    backgroundColor: ['#087f72', '#f59e0b', '#0f172a'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { family: 'Inter', size: 11 } }
                    }
                },
                cutout: '70%'
            }
        });
    }
});
</script>
