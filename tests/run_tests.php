<?php
/**
 * Suite de Verificação e Testes Automatizados - Etapa 1: Base
 * Executar via CLI: php tests/run_tests.php
 */

declare(strict_types=1);

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/../app/Support/helpers.php';

use App\Repositories\ContentRepository;
use App\Repositories\MfaRepository;
use App\Repositories\NetworkRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\RateLimitRepository;
use App\Repositories\StatsRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\MfaService;
use App\Services\PasswordResetService;
use App\Services\SecurityAuditService;
use App\Support\Config;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\SecurityHeaders;
use App\Support\Session;

Config::load(__DIR__ . '/../.env');


class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function assert(string $description, bool $condition, ?string $errorMessage = null): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$description}\n";
        } else {
            $this->failed++;
            $msg = $errorMessage ?? "Falha na asserção";
            $this->errors[] = "{$description}: {$msg}";
            echo "  [FAIL] {$description} - {$msg}\n";
        }
    }

    public function summary(): void
    {
        echo "\n==============================================\n";
        echo "RESULTADO DOS TESTES: {$this->passed} aprovados, {$this->failed} falhas.\n";
        if ($this->failed > 0) {
            echo "Detalhes das falhas:\n";
            foreach ($this->errors as $err) {
                echo " - {$err}\n";
            }
            exit(1);
        } else {
            echo "TODOS OS TESTES DA ETAPA 1 FORAM APROVADOS COM SUCESSO!\n";
            echo "==============================================\n";
        }
    }
}

$t = new TestRunner();

echo "==============================================\n";
echo "INICIANDO TESTES DA ETAPA 1 — GESTÃO DE ACADEMIA\n";
echo "==============================================\n\n";

// 1. Banco de Dados e Conexão PDO
echo "1. Testes de Infraestrutura e Banco de Dados...\n";
$pdo = Database::getConnection();
$t->assert("Conexão PDO estabelecida com sucesso", $pdo instanceof PDO);
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$expectedTables = ['redes', 'unidades', 'usuarios', 'usuario_unidades', 'recuperacao_senha', 'tentativas_acesso'];
foreach ($expectedTables as $tbl) {
    $t->assert("Tabela '{$tbl}' presente no banco", in_array($tbl, $tables, true));
}

// 2. Repositórios e Isolamento entre Redes
echo "\n2. Testes de Repositórios e Isolamento por Rede...\n";
$userRepo = new UserRepository($pdo);
$netRepo = new NetworkRepository($pdo);
$unitRepo = new UnitRepository($pdo);

$adminAlpha = $userRepo->findByEmail('admin@alphafit.com.br');
$adminBeta = $userRepo->findByEmail('admin@betasaude.com.br');

$t->assert("Usuário Admin da Rede Alpha existe", $adminAlpha !== null && $adminAlpha['perfil'] === 'admin');
$t->assert("Usuário Admin da Rede Beta existe", $adminBeta !== null && $adminBeta['perfil'] === 'admin');
$t->assert("Redes possuem IDs distintos", (int)$adminAlpha['rede_id'] !== (int)$adminBeta['rede_id']);

$alphaUnits = $unitRepo->findByNetwork((int)$adminAlpha['rede_id']);
$betaUnits = $unitRepo->findByNetwork((int)$adminBeta['rede_id']);
$t->assert("Rede Alpha possui unidades isoladas", count($alphaUnits) >= 2);
$t->assert("Rede Beta possui unidades isoladas", count($betaUnits) >= 1);

// Garantir que unidades de Beta não aparecem na consulta da rede Alpha
$leakFound = false;
foreach ($alphaUnits as $u) {
    if ($u['nome'] === 'Beta Norte') {
        $leakFound = true;
    }
}
$t->assert("Isolamento estrito: Unidades de Beta não vazam para Alpha", !$leakFound);

// 3. Autenticação e Perfis
echo "\n3. Testes de Autenticação, Perfis e Rejeições Seguras...\n";
$rateRepoInstance = new RateLimitRepository($pdo);
$authService = new AuthService($userRepo, $rateRepoInstance);
$testIp = '192.168.1.100';
$pdo->prepare("DELETE FROM tentativas_acesso WHERE chave_identificadora LIKE :ip")->execute(['ip' => "%{$testIp}%"]);

// Teste 3 perfis válidos
$loginAdmin = $authService->attemptLogin('admin@alphafit.com.br', 'Admin@123456', $testIp);
$t->assert("Login de Administrador com credenciais válidas", $loginAdmin['success'] && $loginAdmin['user']['perfil'] === 'admin');

$loginProf = $authService->attemptLogin('personal@alphafit.com.br', 'Profissional@123456', $testIp);
$t->assert("Login de Profissional com credenciais válidas", $loginProf['success'] && $loginProf['user']['perfil'] === 'profissional');

$loginAluno = $authService->attemptLogin('aluno@alphafit.com.br', 'Aluno@123456', $testIp);
$t->assert("Login de Aluno com credenciais válidas", $loginAluno['success'] && $loginAluno['user']['perfil'] === 'aluno');

// Teste credenciais incorretas e resposta genérica
$loginWrongPass = $authService->attemptLogin('admin@alphafit.com.br', 'SenhaErrada123', $testIp);
$t->assert("Senha incorreta rejeitada", !$loginWrongPass['success']);
$t->assert("Mensagem genérica para senha incorreta", $loginWrongPass['error'] === 'E-mail ou senha incorretos.');

$loginWrongEmail = $authService->attemptLogin('inexistente@alphafit.com.br', 'SenhaQualquer', $testIp);
$t->assert("Conta inexistente rejeitada", !$loginWrongEmail['success']);
$t->assert("Mensagem genérica idêntica para conta inexistente", $loginWrongEmail['error'] === 'E-mail ou senha incorretos.');

// Teste conta inativa
$loginInactive = $authService->attemptLogin('inativo@alphafit.com.br', 'Inativo@123456', $testIp);
$t->assert("Conta de usuário inativo é bloqueada", !$loginInactive['success']);

// Teste rede inativa
$loginInactiveNet = $authService->attemptLogin('bloqueado@deltainativa.com.br', 'Bloqueado@123456', $testIp);
$t->assert("Conta de rede inativa é bloqueada", !$loginInactiveNet['success']);

// 4. Rate Limiting e Proteção contra Força Bruta
echo "\n4. Testes de Rate Limiting (Bloqueio de Força Bruta)...\n";
$rateRepo = new RateLimitRepository($pdo);
$bruteIp = '10.0.0.99';
$bruteKey = "login:{$bruteIp}:ataque@teste.com";
$rateRepo->reset($bruteKey, 'login');
$rateRepo->reset("login_ip:{$bruteIp}", 'login_ip');
$rateRepo->reset("login_acc:ataque@teste.com", 'login_acc');

for ($i = 1; $i <= 5; $i++) {
    $authService->attemptLogin('ataque@teste.com', 'tentativa' . $i, $bruteIp);
}
$isLocked = $rateRepo->isLocked($bruteKey, 'login');
$t->assert("Bloqueio de IP/usuário acionado após 5 tentativas incorretas", $isLocked);

$lockedAttempt = $authService->attemptLogin('ataque@teste.com', 'tentativa6', $bruteIp);
$t->assert("Tentativa durante bloqueio retorna aviso de limite excedido", !$lockedAttempt['success'] && str_contains($lockedAttempt['error'], 'Limite de tentativas excedido'));

// Reset de rate limit para limpar ambiente
$rateRepo->reset($bruteKey, 'login');
$rateRepo->reset("login_ip:{$bruteIp}", 'login_ip');
$rateRepo->reset("login_acc:ataque@teste.com", 'login_acc');
$t->assert("Liberação/reset de rate limit funcional", !$rateRepo->isLocked($bruteKey, 'login'));


// 5. Proteção CSRF
echo "\n5. Testes de Segurança CSRF...\n";
Session::start();
$token = Csrf::getToken();
$t->assert("Geração de token CSRF com comprimento seguro", strlen($token) === 64);
$t->assert("Validação positiva com token CSRF correto", Csrf::validate($token));
$t->assert("Rejeição estrita com token CSRF incorreto", !Csrf::validate('token_falso_invalido'));
$t->assert("Rejeição com token nulo ou vazio", !Csrf::validate(null) && !Csrf::validate(''));

// 6. Recuperação de Senha, Hash de Tokens e Revogação de Sessão
echo "\n6. Testes de Recuperação de Senha e Revogação de Sessões...\n";
$resetRepo = new PasswordResetRepository($pdo);
$resetService = new PasswordResetService($userRepo, $resetRepo, $rateRepo);

// Limpar rate limit prévio para o IP de teste
$rateRepo->reset("recovery:{$testIp}:aluno@alphafit.com.br", 'recuperacao');
$rateRepo->reset("recovery:{$testIp}:fantasma@inexistente.com", 'recuperacao');

// Solicitação de recuperação (não enumeração)
$recoveryReq = $resetService->requestReset('aluno@alphafit.com.br', $testIp);
$t->assert("Solicitação de recuperação para e-mail existente retorna mensagem genérica", $recoveryReq['success']);

$recoveryReqFake = $resetService->requestReset('fantasma@inexistente.com', $testIp);
$t->assert("Solicitação para e-mail inexistente retorna a exata mesma mensagem genérica", $recoveryReqFake['success'] && $recoveryReqFake['message'] === $recoveryReq['message']);

// Verificar token salvo com hash no banco
$rawToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $rawToken);
$expires = date('Y-m-d H:i:s', time() + 1800);
$alunoId = (int)$adminAlpha['id']; // usando ID de teste
$resetRepo->create($alunoId, $tokenHash, $expires);

// Validar busca do token pelo hash
$validRecord = $resetService->validateToken($rawToken);
$t->assert("Token válido é localizado pelo seu hash SHA-256", $validRecord !== null);

// Token inválido
$invalidRecord = $resetService->validateToken('token_completamente_invalido');
$t->assert("Token inexistente é rejeitado", $invalidRecord === null);

// Redefinição de senha com validação de confirmação
$failMismatch = $resetService->resetPassword($rawToken, 'NovaSenha@123', 'SenhaDiferente@123');
$t->assert("Rejeita redefinição quando confirmação de senha diverge", !$failMismatch['success']);

// Redefinição de senha com senha curta
$failShort = $resetService->resetPassword($rawToken, '12345', '12345');
$t->assert("Rejeita redefinição com senha inferior a 8 caracteres", !$failShort['success']);

// Redefinição bem-sucedida
$vBefore = $userRepo->getSessionVersion($alunoId);
$successReset = $resetService->resetPassword($rawToken, 'NovaSenha@123456', 'NovaSenha@123456');
$t->assert("Redefinição bem-sucedida de senha com token válido", $successReset['success']);

// Tentativa de reuso do mesmo token (deve falhar por ser atômico e descartável)
$reuseAttempt = $resetService->resetPassword($rawToken, 'OutraSenha@123456', 'OutraSenha@123456');
$t->assert("Reuso do mesmo token de recuperação é estritamente recusado (consumo atômico)", !$reuseAttempt['success']);

// Verificar se session_version foi incrementada para invalidar sessões ativas
$vAfter = $userRepo->getSessionVersion($alunoId);
$t->assert("Versão da sessão (session_version) incrementada após troca de senha", $vAfter === ($vBefore + 1));

// Verificar que a nova senha funciona e a antiga não
$loginOldPass = $authService->attemptLogin($adminAlpha['email'], 'Admin@123456', $testIp);
$t->assert("Senha antiga deixou de funcionar", !$loginOldPass['success']);

$loginNewPass = $authService->attemptLogin($adminAlpha['email'], 'NovaSenha@123456', $testIp);
$t->assert("Nova senha autentica com sucesso", $loginNewPass['success']);

// Restaurar senha padrão do admin para manter o ambiente de dev previsível
$userRepo->updatePasswordAndRevokeSessions($alunoId, password_hash('Admin@123456', PASSWORD_DEFAULT));

// 7. Teste de Outbox de E-mail
echo "\n7. Testes de Outbox de E-mail Local...\n";
$outboxFiles = glob(__DIR__ . '/../storage/mail/mail_*.html');
$t->assert("E-mails de recuperação são gravados no diretório seguro storage/mail/", count($outboxFiles) > 0);

// 8. Testes de Gestão de Conteúdo (CMS) e Isolamento
echo "\n8. Testes de Gestão de Conteúdo (CMS) e Isolamento por Rede...\n";
$contentRepo = new ContentRepository($pdo);

// Criar comunicado de teste para Alpha
$newCommId = $contentRepo->create([
    'rede_id' => 1,
    'unidade_id' => 1,
    'titulo' => 'Treino Especial de Sábado',
    'categoria' => 'Eventos',
    'publico_alvo' => 'alunos',
    'conteudo' => 'Neste sábado teremos aulão funcional às 10h.',
    'fixado' => 1,
    'status' => 'ativo',
    'autor_id' => 1,
]);
$t->assert("Criação de novo comunicado com sucesso", $newCommId > 0);

// Buscar comunicado criado
$foundComm = $contentRepo->findById($newCommId, 1);
$t->assert("Busca de comunicado por ID e Rede retorna os dados corretos", $foundComm !== null && $foundComm['titulo'] === 'Treino Especial de Sábado');

// Isolamento estrito: Rede 2 (Beta) não pode acessar comunicado da Rede 1 (Alpha)
$leakComm = $contentRepo->findById($newCommId, 2);
$t->assert("Isolamento estrito: Rede Beta não acessa comunicado da Rede Alpha", $leakComm === null);

// Atualização de comunicado
$updated = $contentRepo->update($newCommId, 1, [
    'unidade_id' => 1,
    'titulo' => 'Treino Especial de Sábado (Horário Atualizado)',
    'categoria' => 'Eventos',
    'publico_alvo' => 'alunos',
    'conteudo' => 'Novo horário: 11h.',
    'fixado' => 1,
    'status' => 'ativo',
]);
$t->assert("Atualização de comunicado existente", $updated);

// Alternar status (toggle status)
$contentRepo->toggleStatus($newCommId, 1);
$commInativo = $contentRepo->findById($newCommId, 1);
$t->assert("Alternar status inativa o comunicado", $commInativo['status'] === 'inativo');

$contentRepo->toggleStatus($newCommId, 1);
$commAtivo = $contentRepo->findById($newCommId, 1);
$t->assert("Alternar status novamente reativa o comunicado", $commAtivo['status'] === 'ativo');

// Conteúdo institucional
$contentRepo->saveInstitutionalContent(1, [
    'slogan' => 'Sua melhor versão começa aqui.',
    'descricao' => 'Academia de alta performance.',
    'horario_funcionamento' => '06h às 23h',
    'telefone_contato' => '11999999999',
    'email_contato' => 'contato@alphafit.com.br',
    'banner_aviso' => 'Feriado com horário especial',
    'banner_ativo' => 1,
    'regras_gerais' => 'Uso de toalha obrigatório',
]);
$instData = $contentRepo->getInstitutionalContent(1);
$t->assert("Salvar e recuperar conteúdo institucional e banner de aviso", $instData !== null && $instData['banner_aviso'] === 'Feriado com horário especial');

// Exclusão de comunicado
$deleted = $contentRepo->delete($newCommId, 1);
$t->assert("Exclusão de comunicado com sucesso", $deleted);
$t->assert("Comunicado excluído não existe mais na rede", $contentRepo->findById($newCommId, 1) === null);

// 9. Testes de Estatísticas e Métricas
echo "\n9. Testes de Estatísticas e Métricas da Rede...\n";
$statsRepo = new StatsRepository($pdo);

// Resumo geral da Rede Alpha (ID: 1)
$statsAlpha = $statsRepo->getSummary(1);
$t->assert("Estatísticas calculam total de usuários na rede", $statsAlpha['total_usuarios'] >= 4);
$t->assert("Estatísticas separam alunos ativos e inativos", isset($statsAlpha['alunos_ativos']) && isset($statsAlpha['alunos_inativos']));
$t->assert("Cálculo correto da taxa de atividade percentual", $statsAlpha['taxa_atividade_alunos'] >= 0 && $statsAlpha['taxa_atividade_alunos'] <= 100);
$t->assert("Contagem de unidades da rede Alpha", $statsAlpha['total_unidades'] === 2);

// Resumo da Rede Beta (ID: 2) — Isolamento de métricas
$statsBeta = $statsRepo->getSummary(2);
$t->assert("Isolamento de métricas: Rede Beta possui dados distintos da Rede Alpha", $statsBeta['total_usuarios'] !== $statsAlpha['total_usuarios'] || $statsBeta['total_unidades'] === 1);

// Distribuição de unidades
$unitsDist = $statsRepo->getUnitsDistribution(1);
$t->assert("Distribuição retorna lista de unidades com alunos vinculados", count($unitsDist) === 2);

// Tentativas de segurança isoladas por rede
$secStats = $statsRepo->getAccessSecurityStats();
$t->assert("Métricas de segurança retornam dados de tentativas de acesso", isset($secStats['total_tentativas']));
$secStatsAlpha = $statsRepo->getAccessSecurityStats(1);
$t->assert("Isolamento multi-tenant: Métricas de segurança podem ser filtradas por rede", isset($secStatsAlpha['total_tentativas']));

// 10. Testes de Autenticação Multifatorial (MFA) e Criptografia AES-256-GCM
echo "\n10. Testes de MFA (TOTP) e Criptografia Autenticada AES-256-GCM...\n";
$mfaService = new MfaService();
$mfaRepo = new MfaRepository($pdo);

// 10.1 Tabelas no banco
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$t->assert("Tabela 'mfa_recovery_codes' presente no banco", in_array('mfa_recovery_codes', $tables, true));
$t->assert("Tabela 'mfa_used_codes' presente no banco", in_array('mfa_used_codes', $tables, true));

// 10.2 Criptografia autenticada
$rawSecret = 'JBSWY3DPEHPK3PXP';
$encryptedSecret = $mfaService->encryptSecret($rawSecret);
$t->assert("Segredo TOTP criptografado com sucesso", is_string($encryptedSecret) && strlen($encryptedSecret) > 28);
$decryptedSecret = $mfaService->decryptSecret($encryptedSecret);
$t->assert("Segredo TOTP decriptado confere com o original", $decryptedSecret === $rawSecret);

// 10.3 Rejeição estrita de adulteração (Integrity / Authenticated Encryption Tampering)
$rawDec = base64_decode($encryptedSecret);
$rawDec[strlen($rawDec) - 1] = chr(ord($rawDec[strlen($rawDec) - 1]) ^ 0xFF);
$tampered = base64_encode($rawDec);
$decryptedTampered = $mfaService->decryptSecret($tampered);
$t->assert("Adulteração de ciphertext/tag rejeitada imediatamente pelo AES-256-GCM", $decryptedTampered === null);

// 10.4 Geração e validação de TOTP
$setupInfo = $mfaService->initializeSetup(['email' => 'admin@alphafit.com.br']);
$t->assert("Inicialização de MFA gera segredo e QR Code SVG local", !empty($setupInfo['secret']) && str_contains($setupInfo['qr_code_svg'], '<svg'));

// Ativação com primeiro código válido
$totpEngine = \OTPHP\TOTP::createFromSecret($setupInfo['secret']);
$validCode = $totpEngine->now();
$activationResult = $mfaService->confirmActivation(1, $setupInfo['secret'], $validCode);
$t->assert("Confirmação de primeiro código ativa o MFA com sucesso", $activationResult['success'] === true);
$t->assert("Ativação de MFA gera 8 códigos de recuperação", count($activationResult['recovery_codes']) === 8);

// Verificar usuário no banco com MFA ativo
$userAdmin = $userRepo->findById(1);
$t->assert("Coluna mfa_enabled atualizada para 1 no banco", (int)$userAdmin['mfa_enabled'] === 1);
$t->assert("Coluna mfa_secret gravada com criptografia", !empty($userAdmin['mfa_secret']));

// 10.5 Validação de desafio no login e proteção anti-replay
$pdo->exec("DELETE FROM mfa_used_codes WHERE usuario_id = 1");
$newValidCode = $totpEngine->now();
$challengeResult = $mfaService->verifyChallenge(1, $newValidCode, '127.0.0.1');
$t->assert("Código TOTP válido aprovado no desafio MFA", $challengeResult['success'] === true);

// 10.6 Proteção Estrita Anti-Replay (Reutilização de código aceito é rejeitada)
$replayResult = $mfaService->verifyChallenge(1, $newValidCode, '127.0.0.1');
$t->assert("Reutilização de código TOTP já aceito é estritamente rejeitada (Anti-Replay)", $replayResult['success'] === false);


// 10.7 Rejeição de código incorreto
$invalidResult = $mfaService->verifyChallenge(1, '000000', '127.0.0.1');
$t->assert("Código TOTP incorreto é rejeitado", $invalidResult['success'] === false);

// 11. Testes de Códigos de Recuperação de Uso Único
echo "\n11. Testes de Códigos de Recuperação de Uso Único...\n";
$recoveryCodes = $activationResult['recovery_codes'];
$testCode = $recoveryCodes[0];

// Verificar contagem inicial
$countInitial = $mfaRepo->countRemainingRecoveryCodes(1);
$t->assert("Usuário possui 8 códigos de recuperação disponíveis", $countInitial === 8);

// Uso do código
$recResult = $mfaService->verifyRecoveryCode(1, $testCode, '127.0.0.1');
$t->assert("Código de recuperação válido autentica a conta", $recResult['success'] === true);
$t->assert("Contador de códigos restantes decresceu para 7", $recResult['remaining_codes'] === 7);

// Tentativa de reuso do mesmo código de recuperação
$reuseRecResult = $mfaService->verifyRecoveryCode(1, $testCode, '127.0.0.1');
$t->assert("Reuso do mesmo código de recuperação é estritamente bloqueado (Uso Único)", $reuseRecResult['success'] === false);

// Código inexistente
$fakeRecResult = $mfaService->verifyRecoveryCode(1, 'ZZZZ-9999', '127.0.0.1');
$t->assert("Código de recuperação inexistente é recusado", $fakeRecResult['success'] === false);

// 12. Testes de Segregação de Sessão Intermediária (MFA Pending)
echo "\n12. Testes de Segregação de Sessão Intermediária (mfa_pending)...\n";
Session::start();
Session::setMfaPending($userAdmin, 5);
$t->assert("Sessão em mfa_pending NÃO é considerada autenticada (isLoggedIn = false)", Session::isLoggedIn() === false);
$t->assert("Sessão mfa_pending não é aceita por validateAuthenticatedSession", $authService->validateAuthenticatedSession() === false);

// Concluir MFA
Session::completeMfa($userAdmin);
$t->assert("Após completeMfa(), a sessão torna-se plenamente autenticada (isLoggedIn = true)", Session::isLoggedIn() === true);
$t->assert("validateAuthenticatedSession aceita a sessão após MFA concluído", $authService->validateAuthenticatedSession() === true);
Session::logout();

// 13. Testes de Independência entre Redefinição de Senha e MFA
echo "\n13. Testes de Redefinição de Senha vs MFA (ASVS V2.8)...\n";
// Redefinir senha do usuário 1
$newPassHash = password_hash('NovaSenha@123456', PASSWORD_DEFAULT);
$userRepo->updatePasswordAndRevokeSessions(1, $newPassHash);
$userAfterReset = $userRepo->findById(1);
$t->assert("Troca de senha revoga sessões anteriores (session_version incrementado)", (int)$userAfterReset['session_version'] > (int)$userAdmin['session_version']);
$t->assert("Redefinição de senha NUNCA desativa o MFA (mfa_enabled continua 1)", (int)$userAfterReset['mfa_enabled'] === 1);
$t->assert("Segredo do MFA permanece intacto após troca de senha", !empty($userAfterReset['mfa_secret']));

// Restaurar senha original
$origHash = password_hash('Admin@123456', PASSWORD_DEFAULT);
$userRepo->updatePasswordAndRevokeSessions(1, $origHash);

// 14. Testes de Desativação Segura de MFA
echo "\n14. Testes de Desativação Segura de MFA...\n";
// Tentativa de desativação com senha incorreta
$disFail = $mfaService->disableMfa(1, 'SenhaErrada', $recoveryCodes[1]);
$t->assert("Desativação de MFA rejeitada com senha incorreta", $disFail['success'] === false);

// Tentativa de desativação com código incorreto
$disCodeFail = $mfaService->disableMfa(1, 'Admin@123456', 'CODIGO_INVALIDO');
$t->assert("Desativação de MFA rejeitada com código inválido", $disCodeFail['success'] === false);

// Desativação válida usando código de recuperação restante
$disSuccess = $mfaService->disableMfa(1, 'Admin@123456', $recoveryCodes[1]);
$t->assert("Desativação com senha + código válido é bem-sucedida", $disSuccess['success'] === true);
$userDisabled = $userRepo->findById(1);
$t->assert("MFA desativado no banco (mfa_enabled = 0 e segredo limpo)", (int)$userDisabled['mfa_enabled'] === 0 && empty($userDisabled['mfa_secret']));

// 15. Testes de Prevenção de Host Header Poisoning e Cabeçalhos
echo "\n15. Testes de Prevenção de Host Header Poisoning e Cabeçalhos...\n";
$_SERVER['HTTP_HOST'] = 'malicious-attacker.com';
$generatedUrl = url('/login');
$t->assert("Host header não autorizado é rejeitado e url() recorre à URL canônica segura", !str_contains($generatedUrl, 'malicious-attacker.com'));
unset($_SERVER['HTTP_HOST']);

$t->summary();

