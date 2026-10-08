<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? e(str_replace(' - Gestão de Academia', '', $title)) . ' · ' : '' ?>GFit</title>
    <link href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <!-- CSS Customizado (carregado APÓS Bootstrap) -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=20261008">
</head>
<body>
    <a class="skip-link" href="#main-content">Pular para o conteúdo</a>
    <?php $loggedUser=\App\Support\Session::getUser(); $currentPath=\App\Support\Router::resolveRequestPath(); ?>
    <div class="sidebar-backdrop" data-close-menu></div>
    <aside class="app-sidebar" id="app-navigation" aria-label="Menu principal">
        <button type="button" class="mobile-menu menu-close btn btn-sm" data-menu-close aria-label="Fechar menu"><i class="bi bi-x-lg"></i></button>
        <a class="brand" href="<?= url('/') ?>"><span class="brand-icon"><i class="bi bi-activity"></i></span><span>GFit<small>GESTÃO & MOVIMENTO</small></span></a>
        <p class="nav-caption">ESPAÇO DE TRABALHO</p>
        <nav>
        <?php if($loggedUser): ?>
        <?php $home=match($loggedUser['perfil']){'admin'=>'/admin','profissional'=>'/profissional',default=>'/aluno'};
        $links=[[$home,'bi-grid-1x2','Visão geral'],['/treinos','bi-journal-check','Treinos e evolução']];
        if($loggedUser['perfil']==='admin') $links=array_merge($links,[['/admin/pessoas','bi-people','Pessoas e unidades'],['/admin/conteudo','bi-megaphone','Comunicados'],['/admin/estatisticas','bi-bar-chart','Estatísticas']]);
        $links[]=['/mfa/gerenciar','bi-shield-check','Segurança da conta'];
        $links[]=['/minha-privacidade','bi-person-lock','Minha privacidade'];
        foreach($links as [$path,$icon,$label]): ?>
        <a class="side-link <?= $currentPath===$path?'active':'' ?>" <?= $currentPath===$path?'aria-current="page"':'' ?> href="<?= url($path) ?>"><i class="bi <?= $icon ?>"></i><?= e($label) ?></a>
        <?php endforeach; ?>
        <?php else: ?><a class="side-link" href="<?= url('/login') ?>">Entrar na minha conta</a><?php endif; ?>
        </nav>
        <div class="sidebar-bottom"><i class="bi bi-activity me-2"></i>Mais organização.<br>Mais espaço para evoluir.</div>
    </aside>
    <div class="app-shell">
    <header class="topbar">
        <div class="d-flex gap-3 align-items-center"><button class="mobile-menu btn btn-outline-secondary" type="button" data-menu-toggle aria-controls="app-navigation" aria-expanded="false" aria-label="Abrir menu"><i class="bi bi-list"></i></button><span class="topbar-title">Seu espaço GFit <span class="mx-2">/</span> <?= e($title??'Visão geral') ?></span></div>
        <?php if($loggedUser): ?><div class="user-chip"><span class="avatar"><?= e(mb_strtoupper(mb_substr($loggedUser['nome'],0,1))) ?></span><div><strong><?= e($loggedUser['nome']) ?></strong><div class="user-email text-muted"><?= e(ucfirst($loggedUser['perfil'])) ?></div></div><form action="<?= url('/logout') ?>" method="post" class="m-0 ms-2"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary" title="Encerrar sessão">Sair</button></form></div><?php endif; ?>
    </header>
    <main class="app-main" id="main-content" tabindex="-1">        <!-- Mensagens Flash -->
        <?php foreach (\App\Support\Session::getAllFlashes() as $type => $message): ?>
            <?php
                $alertClass = match ($type) {
                    'success' => 'alert-success',
                    'error' => 'alert-danger',
                    'warning' => 'alert-warning',
                    default => 'alert-info',
                };
                $iconClass = match ($type) {
                    'success' => 'bi-check-circle-fill',
                    'error' => 'bi-exclamation-triangle-fill',
                    'warning' => 'bi-exclamation-circle-fill',
                    default => 'bi-info-circle-fill',
                };
            ?>
            <div class="alert <?= $alertClass ?> alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi <?= $iconClass ?> fs-5"></i>
                <div><?= e($message) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endforeach; ?>

        <?= $content ?>
    </main>

<footer class="app-footer">GFit · Gestão de academia, pessoas e evolução. · <a href="<?= url('/privacidade') ?>">Privacidade</a></footer></div>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
