<?php

declare(strict_types=1);

namespace App\Support;

class Router
{
    private static array $routes = [];

    public static function get(string $path, array|callable $handler): void
    {
        self::$routes['GET'][self::normalizePath($path)] = $handler;
    }

    public static function post(string $path, array|callable $handler): void
    {
        self::$routes['POST'][self::normalizePath($path)] = $handler;
    }

    public static function redirect(string $path): void
    {
        $url = url($path);
        header("Location: {$url}");
        exit;
    }

    public static function resolveRequestPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Tratar subdiretório se estiver rodando dentro de subpasta do Apache (ex: /GFit/public)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        if ($scriptDir !== '/' && $scriptDir !== '.' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        return self::normalizePath($path);
    }

    private static function normalizePath(string $path): string
    {
        $trimmed = trim($path, '/');
        return $trimmed === '' ? '/' : '/' . $trimmed;
    }

    public static function dispatch(?string $method = null, ?string $path = null): void
    {
        $method = strtoupper($method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $path ?? self::resolveRequestPath();

        // Verificar se rota existe
        if (!isset(self::$routes[$method][$path])) {
            http_response_code(404);
            View::render('errors.404', ['title' => 'Página não encontrada'], 'main');
            return;
        }

        // Validação obrigatória de CSRF para requisições POST
        if ($method === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Csrf::validate($token)) {
                http_response_code(403);
                Session::setFlash('error', 'Token de segurança CSRF inválido ou expirado. Por favor, tente novamente.');

                View::render('errors.403', ['title' => 'Sessão expirada', 'message' => 'O formulário expirou. Atualize a página de origem e tente novamente.'], 'main');
                return;
            }
        }

        $handler = self::$routes[$method][$path];

        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass();
            $controller->$action();
            return;
        }

        http_response_code(500);
        View::render('errors.500', ['title' => 'Erro interno do servidor'], 'main');
    }
}
