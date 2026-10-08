<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? e(str_replace(' - Gestão de Academia', '', $title)) . ' · ' : '' ?>GFit</title>
    <link href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=20261008">
</head>

<body class="auth-body">
    <a class="skip-link" href="#main-content">Pular para o formulário</a>
    <main class="auth-container" id="main-content">
        <section class="auth-story" aria-label="GFit">
            <a class="brand" href="<?= url('/login') ?>"><span class="brand-icon"><i class="bi bi-activity"></i></span><span>GFit<small>GESTÃO & MOVIMENTO</small></span></a>
            <div class="auth-story-content">
                <span class="eyebrow">SUA ROTINA, EM MOVIMENTO</span>
                <h2>Organize hoje.<br><span>Evolua sempre.</span></h2>
                <p>Uma experiência mais simples para cuidar da sua academia e acompanhar cada conquista.</p>

                <!-- Carrossel de Imagens GFit: Pessoas em treino e Fundo de Academia -->
                <div id="gfitGymCarousel" class="carousel slide carousel-fade gfit-gym-carousel" data-bs-ride="carousel" data-bs-interval="4500" aria-label="Galeria da academia e comunidade">
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#gfitGymCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Pessoas em Treino"></button>
                        <button type="button" data-bs-target="#gfitGymCarousel" data-bs-slide-to="1" aria-label="Estrutura e Fundo"></button>
                        <button type="button" data-bs-target="#gfitGymCarousel" data-bs-slide-to="2" aria-label="Treino Funcional"></button>
                        <button type="button" data-bs-target="#gfitGymCarousel" data-bs-slide-to="3" aria-label="Área de Musculação"></button>
                    </div>
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="<?= url('assets/images/carousel/slide1_training.jpg') ?>" class="d-block w-100" alt="Pessoas treinando juntas na academia com acompanhamento">
                            <div class="carousel-caption">
                                <span class="carousel-tag"><i class="bi bi-people-fill"></i> Pessoas & Treino</span>
                                <h6>Comunidade & Evolução</h6>
                                <p>Treinos com orientação e foco em resultados diários.</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img src="<?= url('assets/images/carousel/slide2_interior.jpg') ?>" class="d-block w-100" alt="Fundo e ambiente moderno de academia com aparelhos de ponta">
                            <div class="carousel-caption">
                                <span class="carousel-tag"><i class="bi bi-building"></i> Fundo da Academia</span>
                                <h6>Estrutura de Alta Performance</h6>
                                <p>Espaço moderno e climatizado para o seu melhor treino.</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img src="<?= url('assets/images/carousel/slide3_functional.jpg') ?>" class="d-block w-100" alt="Alunos em treino funcional com kettlebell e cordas">
                            <div class="carousel-caption">
                                <span class="carousel-tag"><i class="bi bi-lightning-charge-fill"></i> Treino Funcional</span>
                                <h6>Movimento & Saúde</h6>
                                <p>Dinamismo e condicionamento com exercícios funcionais.</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img src="<?= url('assets/images/carousel/slide4_weights.jpg') ?>" class="d-block w-100" alt="Área de musculação com pesos livres e barras olímpicas">
                            <div class="carousel-caption">
                                <span class="carousel-tag"><i class="bi bi-trophy-fill"></i> Área de Musculação</span>
                                <h6>Força & Disciplina</h6>
                                <p>Equipamentos de musculação e pesos livres completos.</p>
                            </div>
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#gfitGymCarousel" data-bs-slide="prev" aria-label="Imagem anterior">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#gfitGymCarousel" data-bs-slide="next" aria-label="Próxima imagem">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                <div class="auth-benefits"><div><i class="bi bi-journal-check"></i>Treinos e evolução no mesmo lugar</div><div><i class="bi bi-people"></i>Alunos e profissionais conectados</div><div><i class="bi bi-shield-check"></i>Acesso individual e seguro</div></div>
            </div>
            <div class="auth-story-foot">GFit · Feito para acompanhar seu próximo passo. · <a href="<?= url('/privacidade') ?>">Privacidade</a></div>
        </section>
        <section class="auth-form-side" aria-label="Acesso à conta"><div class="auth-card">
            <div class="auth-header"><span class="eyebrow">BEM-VINDO AO GFIT</span><?php if(\App\Support\Router::resolveRequestPath()==='/login'): ?><h1>Bom ter você aqui.</h1><p class="auth-subtitle">Entre para acompanhar sua rotina.</p><?php else: ?><div class="auth-subtitle">Seu espaço para uma rotina mais organizada.</div><?php endif; ?></div>            <!-- Mensagens Flash -->
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
                <div class="alert <?= $alertClass ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="bi <?= $iconClass ?> fs-5"></i>
                    <div class="small fw-semibold"><?= e($message) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </div></section>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= url('assets/js/app.js') ?>"></script>

</body>

</html>
