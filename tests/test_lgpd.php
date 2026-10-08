<?php
declare(strict_types=1);

spl_autoload_register(function(string $class): void {
    $prefix='App\\'; if(!str_starts_with($class,$prefix)) return;
    $file=__DIR__.'/../app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_file($file)) require_once $file;
});
use App\Services\DataProtectionService;
use App\Support\Config;
use App\Support\Database;

Config::load(__DIR__.'/../.env');
$checks=[];
$assert=function(string $label,bool $condition)use(&$checks):void{$checks[]=[$label,$condition];};
$service=new DataProtectionService();
$payload=$service->encrypt(['peso'=>72.4,'observacoes'=>'dado sensível de teste'],'test:assessment:1');
$plain=$service->decrypt($payload,'test:assessment:1');
$assert('AES-256-GCM round-trip',(float)$plain['peso']===72.4&&$plain['observacoes']==='dado sensível de teste');
$tampered=$payload; $tampered[strlen($tampered)-1]=$tampered[strlen($tampered)-1]==='A'?'B':'A';
try{$service->decrypt($tampered,'test:assessment:1');$rejected=false;}catch(Throwable){$rejected=true;}
$assert('AES-256-GCM rejeita adulteração',$rejected);
$pdo=Database::getConnection();
$tables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach(['lgpd_consentimentos','lgpd_solicitacoes','lgpd_incidentes'] as $table)$assert("Tabela {$table}",in_array($table,$tables,true));
$columns=$pdo->query('SHOW COLUMNS FROM gfit_avaliacoes')->fetchAll(PDO::FETCH_COLUMN);
$assert('Coluna de dados protegidos',in_array('dados_protegidos',$columns,true));
$legacy=(int)$pdo->query('SELECT COUNT(*) FROM gfit_avaliacoes WHERE dados_protegidos IS NULL AND (peso IS NOT NULL OR altura IS NOT NULL OR observacoes IS NOT NULL)')->fetchColumn();
$assert('Nenhuma avaliação legada em texto claro',$legacy===0);
$assert('Arquivo de diagnóstico removido',!is_file(__DIR__.'/../test_db.php'));
$assert('Bibliotecas locais',is_file(__DIR__.'/../public/assets/vendor/bootstrap/bootstrap.min.css')&&is_file(__DIR__.'/../public/assets/vendor/chartjs/chart.umd.min.js'));
$failed=0;foreach($checks as [$label,$ok]){echo ($ok?'[PASS] ':'[FAIL] ').$label.PHP_EOL;if(!$ok)$failed++;}
echo count($checks)-$failed.'/'.count($checks)." verificações aprovadas.\n";exit($failed?1:0);
