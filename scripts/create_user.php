<?php
/**
 * Script CLI seguro para criação de usuários.
 * Uso interativo: php scripts/create_user.php
 * Uso via argumentos: php scripts/create_user.php --name="Nome" --email="email@exemplo.com" --role=admin --network=1 --password="SenhaSegura@123" [--unit=1]
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

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

use App\Repositories\NetworkRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Support\Config;

Config::load(__DIR__ . '/../.env');

echo "=== Criação Segura de Usuário (CLI) ===\n\n";

$options = getopt('', ['name:', 'email:', 'role:', 'network:', 'unit::', 'password:']);

$name = $options['name'] ?? null;
$email = $options['email'] ?? null;
$role = $options['role'] ?? null;
$networkId = isset($options['network']) ? (int)$options['network'] : null;
$unitId = isset($options['unit']) ? (int)$options['unit'] : null;
$password = $options['password'] ?? null;

// Modo interativo se não fornecido via argumentos
if (!$name) {
    echo "Nome completo: ";
    $name = trim((string)fgets(STDIN));
}

if (!$email) {
    echo "E-mail: ";
    $email = trim((string)fgets(STDIN));
}

if (!$role) {
    echo "Perfil (admin, profissional, aluno): ";
    $role = strtolower(trim((string)fgets(STDIN)));
}

if (!$networkId) {
    echo "ID da Rede: ";
    $networkId = (int)trim((string)fgets(STDIN));
}

if (!$password) {
    echo "Senha de acesso (mínimo 12 caracteres): ";
    $password = trim((string)fgets(STDIN));
}

// Validações
if (empty($name) || empty($email) || empty($password)) {
    echo "ERRO: Nome, e-mail e senha são obrigatórios.\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "ERRO: Endereço de e-mail inválido.\n";
    exit(1);
}

if (!in_array($role, ['admin', 'profissional', 'aluno'], true)) {
    echo "ERRO: Perfil inválido. Escolha: admin, profissional ou aluno.\n";
    exit(1);
}

if (strlen($password) < 12) {
    echo "ERRO: A senha deve possuir pelo menos 12 caracteres.\n";
    exit(1);
}

$userRepo = new UserRepository();
$networkRepo = new NetworkRepository();
$unitRepo = new UnitRepository();

// Verificar se rede existe
$network = $networkRepo->findById($networkId);
if (!$network) {
    echo "ERRO: Rede de ID {$networkId} não encontrada no banco de dados.\n";
    exit(1);
}

// Verificar duplicidade de e-mail
if ($userRepo->findByEmail($email)) {
    echo "ERRO: Já existe um usuário cadastrado com o e-mail '{$email}'.\n";
    exit(1);
}

// Criar usuário com hash seguro
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $userId = $userRepo->create([
        'rede_id' => $networkId,
        'nome' => $name,
        'email' => $email,
        'senha_hash' => $hash,
        'perfil' => $role,
        'status' => 'ativo',
    ]);

    // Vincular unidade se informada
    if ($unitId) {
        $unit = $unitRepo->findById($unitId);
        if ($unit && (int)$unit['rede_id'] === $networkId) {
            $userRepo->linkUnit($userId, $unitId);
            echo "-> Vínculo com a unidade '{$unit['nome']}' realizado.\n";
        } else {
            echo "AVISO: Unidade {$unitId} não pertence à rede {$networkId} ou não existe. Vínculo ignorado.\n";
        }
    }

    echo "\nSucesso! Usuário criado com identificador ID #{$userId}.\n";
    echo "Nome:   {$name}\n";
    echo "E-mail: {$email}\n";
    echo "Perfil: {$role}\n";
    echo "Rede:   {$network['nome']} (ID: {$networkId})\n";
    echo "Senha:  [DEFINIDA COM SUCESSO - HASH ARMAZENADO]\n";

} catch (Throwable $e) {
    echo "ERRO ao criar usuário: " . $e->getMessage() . "\n";
    exit(1);
}
