<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
spl_autoload_register(function (string $class): void {
    $prefix='App\\'; if(!str_starts_with($class,$prefix)) return;
    $file=__DIR__.'/../app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_file($file)) require_once $file;
});
use App\Support\Config;
use App\Support\Database;
Config::load(__DIR__.'/../.env');
$apply=in_array('--apply',$argv??[],true); $pdo=Database::getConnection();
$jobs=[['recuperacao_senha','expires_at < DATE_SUB(NOW(), INTERVAL 90 DAY)'],['tentativas_acesso','updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY)'],['mfa_used_codes','consumed_at < DATE_SUB(NOW(), INTERVAL 2 DAY)']];
echo $apply?"Aplicando política de retenção...\n":"SIMULAÇÃO: use --apply para excluir os registros listados.\n";
foreach($jobs as [$table,$where]){$count=(int)$pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE {$where}")->fetchColumn();echo "{$table}: {$count} registro(s) elegível(is).\n";if($apply&&$count>0)$pdo->exec("DELETE FROM `{$table}` WHERE {$where}");}
$filePolicies=[[__DIR__.'/../storage/logs',(int)Config::get('privacy.retention.security_logs_days',180)],[__DIR__.'/../storage/mail',(int)Config::get('privacy.retention.mail_outbox_days',30)]];
foreach($filePolicies as [$dir,$days]){if(!is_dir($dir))continue;foreach(new DirectoryIterator($dir) as $file){if(!$file->isFile()||$file->getFilename()==='.gitkeep')continue;if($file->getMTime()<time()-($days*86400)){echo "Arquivo elegível: {$file->getPathname()}\n";if($apply)@unlink($file->getPathname());}}}
