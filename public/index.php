<?php

declare(strict_types=1);

// Ponto único de entrada da aplicação

// Registrar autoload simples PSR-4
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

// Registrar autoload do Composer para dependências mantidas
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Funções auxiliares globais
require_once __DIR__ . '/../app/Support/helpers.php';

use App\Controllers\AdminContentController;
use App\Controllers\AdminStatsController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\MfaController;
use App\Controllers\PrivacyController;
use App\Controllers\StudentController;
use App\Support\Config;
use App\Support\Router;
use App\Support\SecurityHeaders;
use App\Support\Session;
use App\Support\View;

// Carregar variáveis de ambiente
Config::load(__DIR__ . '/../.env');

// Aplicar cabeçalhos de segurança HTTP modernos (CSP, HSTS, X-Frame-Options, etc.)
SecurityHeaders::apply();


// Configuração de ambiente e tratamento de erros
$isDebug = Config::get('app.debug', false);

if ($isDebug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Tratador global de exceções seguras
set_exception_handler(function (Throwable $e) use ($isDebug) {
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $errorId = bin2hex(random_bytes(8));
    $logMessage = sprintf("[%s] ERROR_ID=%s TYPE=%s LOCATION=%s:%d\n", date('Y-m-d H:i:s'), $errorId, get_class($e), basename($e->getFile()), $e->getLine());
    if ($isDebug) {
        $logMessage .= "MESSAGE=" . str_replace(["\r", "\n"], ' ', $e->getMessage()) . "\n" . $e->getTraceAsString() . "\n";
    }
    file_put_contents($logDir . '/error.log', $logMessage, FILE_APPEND);

    http_response_code(500);
    if ($isDebug) {
        echo "<h1>Erro no Servidor</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        View::render('errors.500', ['title' => 'Erro interno do servidor', 'errorId' => $errorId], 'main');
    }
    exit;
});

// Inicializar Sessão
Session::start();

// Definição das Rotas
Router::get('/', function () {
    if (Session::isLoggedIn()) {
        $user = Session::getUser();
        match ($user['perfil'] ?? '') {
            'admin' => Router::redirect('/admin'),
            'profissional' => Router::redirect('/profissional'),
            'aluno' => Router::redirect('/aluno'),
            default => Router::redirect('/login'),
        };
        return;
    }
    Router::redirect('/login');
});

// Rotas de Autenticação e Recuperação
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'login']);
Router::post('/logout', [AuthController::class, 'logout']);

// Rotas de Cadastro / Signup
Router::get('/cadastro', [AuthController::class, 'showSignup']);
Router::post('/cadastro', [AuthController::class, 'signup']);
Router::get('/signup', [AuthController::class, 'showSignup']);
Router::post('/signup', [AuthController::class, 'signup']);
Router::get('/privacidade', [PrivacyController::class, 'notice']);

// Login Exclusivo de Administrador
Router::get('/admin/login', [AuthController::class, 'showAdminLogin']);
Router::post('/admin/login', [AuthController::class, 'adminLogin']);

// Rotas de Autenticação Multifatorial (MFA / TOTP)
Router::get('/login/mfa', [MfaController::class, 'showChallenge']);
Router::post('/login/mfa', [MfaController::class, 'verifyChallenge']);
Router::get('/login/mfa/recuperar', [MfaController::class, 'showRecovery']);
Router::post('/login/mfa/recuperar', [MfaController::class, 'verifyRecovery']);

// Rotas de Configuração e Gestão de MFA (Área Autenticada)
Router::get('/mfa/configurar', [MfaController::class, 'setup']);
Router::post('/mfa/confirmar', [MfaController::class, 'confirmSetup']);
Router::get('/mfa/gerenciar', [MfaController::class, 'manage']);
Router::post('/mfa/desativar', [MfaController::class, 'disable']);
Router::post('/mfa/codigos-recuperacao', [MfaController::class, 'regenerateRecoveryCodes']);

Router::get('/recuperar-senha', [AuthController::class, 'showForgotPassword']);
Router::post('/recuperar-senha', [AuthController::class, 'sendResetLink']);

Router::get('/redefinir-senha', [AuthController::class, 'showResetPassword']);
Router::post('/redefinir-senha', [AuthController::class, 'resetPassword']);

// Rotas dos Painéis por Perfil
Router::get('/admin', [DashboardController::class, 'admin']);
Router::get('/profissional', [DashboardController::class, 'professional']);
Router::get('/aluno', [DashboardController::class, 'student']);

// Rotas do Aluno — Ações do Dashboard
Router::post('/aluno/senha', [StudentController::class, 'updatePassword']);
Router::get('/minha-privacidade', [PrivacyController::class, 'center']);
Router::post('/minha-privacidade/consentimento', [PrivacyController::class, 'consent']);
Router::post('/minha-privacidade/solicitacao', [PrivacyController::class, 'request']);
Router::post('/minha-privacidade/solicitacao/status', [PrivacyController::class, 'updateRequest']);
Router::get('/minha-privacidade/exportar', [PrivacyController::class, 'export']);

// Rotas Administrativas — Gestão de Conteúdo (CMS)
Router::get('/admin/conteudo', [AdminContentController::class, 'index']);
Router::post('/admin/conteudo/salvar', [AdminContentController::class, 'save']);
Router::post('/admin/conteudo/status', [AdminContentController::class, 'toggleStatus']);
Router::post('/admin/conteudo/excluir', [AdminContentController::class, 'delete']);
Router::post('/admin/conteudo/institucional', [AdminContentController::class, 'saveInstitutional']);

// Rotas Administrativas — Estatísticas e Métricas
Router::get('/admin/estatisticas', [AdminStatsController::class, 'index']);
Router::get('/admin/estatisticas/dados', [AdminStatsController::class, 'apiData']);


// Gestão diária, com autorização por perfil e rede.
Router::get('/treinos', [\App\Controllers\TrainingController::class, 'index']);
Router::post('/treinos/salvar', [\App\Controllers\TrainingController::class, 'save']);
Router::get('/admin/pessoas', [\App\Controllers\PeopleController::class, 'index']);
Router::post('/admin/pessoas/salvar', [\App\Controllers\PeopleController::class, 'save']);
// Despachar requisição
Router::dispatch();
