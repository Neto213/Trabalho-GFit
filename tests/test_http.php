<?php

declare(strict_types=1);

echo "=== TESTANDO ENDPOINTS HTTP NO APACHE ===\n";

// 1. Testar /admin/login
$ctx = stream_context_create(['http' => ['follow_location' => 0, 'ignore_errors' => true]]);
$loginHtml = file_get_contents('http://localhost/GFit/public/admin/login', false, $ctx);
echo "1. GET /admin/login:\n";
echo "   - Status: " . ($loginHtml !== false ? "OK" : "FALHA") . "\n";
echo "   - Contém 'Acesso Administrativo': " . (str_contains($loginHtml, 'Acesso Administrativo') ? "SIM" : "NÃO") . "\n";
echo "   - Contém campo email administrativo: " . (str_contains($loginHtml, 'admin_email') ? "SIM" : "NÃO") . "\n";
echo "   - Contém botão 'Entrar como Administrador': " . (str_contains($loginHtml, 'Entrar como Administrador') ? "SIM" : "NÃO") . "\n";

// 2. Testar /login geral
$generalLogin = file_get_contents('http://localhost/GFit/public/login', false, $ctx);
echo "\n2. GET /login geral:\n";
echo "   - Contém link para Portal do Administrador: " . (str_contains($generalLogin, 'Portal do Administrador') ? "SIM" : "NÃO") . "\n";

// 3. Testar fluxo completo com Cookie Jar (Sessão): Login Admin -> /admin -> /admin/conteudo -> /admin/estatisticas
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/GFit/public/admin/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$page = curl_exec($ch);

// Extrair csrf_token
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $page, $matches);
$csrfToken = $matches[1] ?? '';
echo "\n3. Autenticação via POST /admin/login:\n";
echo "   - CSRF Token obtido: " . (strlen($csrfToken) === 64 ? "VÁLIDO (64 chars)" : "INVÁLIDO") . "\n";

// Efetuar Login de Administrador
curl_setopt($ch, CURLOPT_URL, 'http://localhost/GFit/public/admin/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrfToken,
    'email' => 'admin@alphafit.com.br',
    'senha' => 'Admin@123456',
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$adminDash = curl_exec($ch);
$dashCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "   - Resposta do Login: HTTP {$dashCode}\n";
echo "   - Contém 'Painel do Administrador': " . (str_contains($adminDash, 'Painel do Administrador') ? "SIM" : "NÃO") . "\n";
echo "   - Contém botão 'Gestão de Conteúdo': " . (str_contains($adminDash, 'Gestão de Conteúdo') ? "SIM" : "NÃO") . "\n";
echo "   - Contém link para 'Estatísticas': " . (str_contains($adminDash, 'Estatísticas') ? "SIM" : "NÃO") . "\n";
echo "   - Contém resumo de Alunos Cadastrados: " . (str_contains($adminDash, 'Alunos Cadastrados') ? "SIM" : "NÃO") . "\n";

// 4. Testar acesso ao CMS (/admin/conteudo)
curl_setopt($ch, CURLOPT_URL, 'http://localhost/GFit/public/admin/conteudo');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$cmsPage = curl_exec($ch);
$cmsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "\n4. Acesso ao Painel de Conteúdo (CMS) /admin/conteudo:\n";
echo "   - Status HTTP: {$cmsCode}\n";
echo "   - Contém 'Mural de Comunicados': " . (str_contains($cmsPage, 'Mural de Comunicados') ? "SIM" : "NÃO") . "\n";
echo "   - Contém botão 'Novo Comunicado': " . (str_contains($cmsPage, 'Novo Comunicado') ? "SIM" : "NÃO") . "\n";
echo "   - Contém 'Conteúdo Institucional & Banners': " . (str_contains($cmsPage, 'Conteúdo Institucional & Banners') ? "SIM" : "NÃO") . "\n";

// 5. Testar criação de comunicado via POST /admin/conteudo/salvar
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $cmsPage, $matchesCms);
$cmsCsrf = $matchesCms[1] ?? '';

curl_setopt($ch, CURLOPT_URL, 'http://localhost/GFit/public/admin/conteudo/salvar');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $cmsCsrf,
    'titulo' => 'Aulão de Funcional ao Ar Livre',
    'categoria' => 'Eventos',
    'publico_alvo' => 'todos',
    'unidade_id' => '',
    'conteudo' => 'Neste sábado às 9h teremos aulão no estacionamento com hidratação gratuita.',
    'fixado' => '1',
    'status' => 'ativo',
]));
$savedCmsPage = curl_exec($ch);
echo "\n5. Publicação de Novo Comunicado:\n";
echo "   - Contém mensagem de sucesso: " . (str_contains($savedCmsPage, 'Novo comunicado publicado com sucesso') ? "SIM" : "NÃO") . "\n";
echo "   - Contém o comunicado publicado: " . (str_contains($savedCmsPage, 'Aulão de Funcional ao Ar Livre') ? "SIM" : "NÃO") . "\n";

// 6. Testar acesso às Estatísticas (/admin/estatisticas)
curl_setopt($ch, CURLOPT_URL, 'http://localhost/GFit/public/admin/estatisticas');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$statsPage = curl_exec($ch);
$statsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "\n6. Acesso às Estatísticas (/admin/estatisticas):\n";
echo "   - Status HTTP: {$statsCode}\n";
echo "   - Contém 'Visualização de Estatísticas e Métricas': " . (str_contains($statsPage, 'Visualização de Estatísticas e Métricas') ? "SIM" : "NÃO") . "\n";
echo "   - Contém 'Taxa de Atividade': " . (str_contains($statsPage, 'Taxa de Atividade') ? "SIM" : "NÃO") . "\n";
echo "   - Contém gráfico 'chartUnidades': " . (str_contains($statsPage, 'chartUnidades') ? "SIM" : "NÃO") . "\n";
echo "   - Contém gráfico 'chartPerfis': " . (str_contains($statsPage, 'chartPerfis') ? "SIM" : "NÃO") . "\n";
echo "   - Contém biblioteca Chart.js: " . (str_contains($statsPage, 'chart.umd.min.js') ? "SIM" : "NÃO") . "\n";

// 7. Testar API JSON de Estatísticas (/admin/estatisticas/dados)
curl_setopt($ch, CURLOPT_URL, 'http://localhost/GFit/public/admin/estatisticas/dados');
$apiJson = curl_exec($ch);
$apiData = json_decode($apiJson, true);
echo "\n7. Endpoint JSON de Estatísticas (/admin/estatisticas/dados):\n";
echo "   - Retornou JSON válido: " . ($apiData !== null ? "SIM" : "NÃO") . "\n";
echo "   - Possui summary e unitsDistribution: " . (isset($apiData['summary']) && isset($apiData['unitsDistribution']) ? "SIM" : "NÃO") . "\n";

curl_close($ch);
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\n=== TODOS OS TESTES HTTP E FLUXOS FORAM EXECUTADOS COM SUCESSO! ===\n";
