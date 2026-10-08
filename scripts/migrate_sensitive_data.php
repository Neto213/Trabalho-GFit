<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require_once $file;
});
use App\Services\DataProtectionService;
use App\Support\Config;
use App\Support\Database;
Config::load(__DIR__ . '/../.env');
$pdo = Database::getConnection();
$service = new DataProtectionService();
$rows = $pdo->query('SELECT id,aluno_id,peso,altura,observacoes FROM gfit_avaliacoes WHERE dados_protegidos IS NULL')->fetchAll();
$pdo->beginTransaction();
try {
    $update = $pdo->prepare('UPDATE gfit_avaliacoes SET dados_protegidos=?,versao_criptografia=1,peso=NULL,altura=NULL,observacoes=NULL WHERE id=? AND dados_protegidos IS NULL');
    foreach ($rows as $row) {
        $payload = $service->encrypt(['peso'=>$row['peso']!==null?(float)$row['peso']:null,'altura'=>$row['altura']!==null?(float)$row['altura']:null,'observacoes'=>(string)($row['observacoes']??'')], 'gfit:assessment:'.$row['id'].':'.$row['aluno_id']);
        $update->execute([$payload,$row['id']]);
    }
    $pdo->commit();
    echo count($rows)." avaliação(ões) migrada(s) para criptografia autenticada.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR,"Falha na migração: {$e->getMessage()}\n");
    exit(1);
}
