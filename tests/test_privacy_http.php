<?php
declare(strict_types=1);
$base='http://localhost/GFit/public';
$cookie=tempnam(sys_get_temp_dir(),'gfit-cookie-');
$ch=curl_init($base.'/login');
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_FOLLOWLOCATION=>true]);
$login=curl_exec($ch);
preg_match('/name="csrf_token"\s+value="([^"]+)"/',$login,$match);
curl_setopt_array($ch,[CURLOPT_URL=>$base.'/login',CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['csrf_token'=>$match[1]??'','email'=>'aluno@alphafit.com.br','senha'=>'Aluno@123456'])]);
$dashboard=curl_exec($ch);
curl_setopt_array($ch,[CURLOPT_URL=>$base.'/minha-privacidade',CURLOPT_HTTPGET=>true]);
$privacy=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
$checks=[
    'login de aluno'=>str_contains($dashboard,'Seu espaço GFit'),
    'central responde HTTP 200'=>$code===200,
    'consentimento disponível'=>str_contains($privacy,'Avaliações físicas'),
    'exportação disponível'=>str_contains($privacy,'Exportar meus dados'),
    'direitos disponíveis'=>str_contains($privacy,'Exercer um direito'),
];
curl_close($ch);@unlink($cookie);
$failed=0;foreach($checks as $label=>$ok){echo ($ok?'[PASS] ':'[FAIL] ').$label.PHP_EOL;if(!$ok)$failed++;}exit($failed?1:0);
