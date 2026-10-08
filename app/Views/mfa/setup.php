<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="card border-0 shadow-lg bg-dark text-white rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-gradient bg-primary bg-opacity-25 border-bottom border-secondary border-opacity-25 py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-info fs-4"></i>
                    <h1 class="h5 mb-0 fw-bold text-white">Ativar Autenticação em Duas Etapas (Email)</h1>
                </div>
                <?php if ($isMandatory): ?>
                    <span class="badge bg-danger bg-opacity-75 px-3 py-2 text-uppercase">
                        <i class="bi bi-shield-fill-exclamation me-1"></i> Obrigatório para seu perfil
                    </span>
                <?php else: ?>
                    <span class="badge bg-info bg-opacity-75 px-3 py-2 text-uppercase">
                        <i class="bi bi-shield-check me-1"></i> Recomendado
                    </span>
                <?php endif; ?>
            </div>

            <div class="card-body p-4 p-md-5">
                <p class="text-light text-opacity-75 mb-4 text-center">
                    A Autenticação em Duas Etapas por Email adiciona uma camada extra de proteção. Sempre que você fizer login, enviaremos um código exclusivo para o seu email.
                </p>

                <!-- Confirmação do Código Enviado -->
                <div class="row justify-content-center">
                    <div class="col-md-8 text-center">
                        <h2 class="h5 fw-bold text-info mb-3">
                            <i class="bi bi-envelope-check me-2"></i> Verifique sua Caixa de Entrada
                        </h2>
                        <p class="small text-light text-opacity-75 mb-4">
                            Enviamos um código de verificação de 6 dígitos para o endereço de email <strong><?= e($user['email']) ?></strong>.
                            Digite-o abaixo para confirmar e ativar a Autenticação em Duas Etapas.
                        </p>

                        <form action="<?= url('/mfa/confirmar') ?>" method="POST" class="d-flex flex-column align-items-center gap-3">
                            <?= csrf_field() ?>

                            <div class="w-75">
                                <input
                                    type="text"
                                    name="code"
                                    id="confirm-code"
                                    class="form-control totp-input text-center py-2"
                                    maxlength="6"
                                    placeholder="000000"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    required
                                    autocomplete="off">
                            </div>

                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Ativar Autenticação</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="alert alert-dark border border-secondary border-opacity-25 mt-5 d-flex gap-3 align-items-start">
                    <i class="bi bi-info-circle-fill text-info fs-5 mt-1"></i>
                    <div class="small text-muted">
                        <strong>Não recebeu o código?</strong> Verifique sua pasta de spam ou lixo eletrônico. O código pode levar alguns instantes para chegar.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= e(\App\Support\SecurityHeaders::nonce()) ?>">
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('confirm-code');
        if (input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }
    });
</script>
