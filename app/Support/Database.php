<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = Config::get('database.host', '127.0.0.1');
            $port = Config::get('database.port', '3306');
            $database = Config::get('database.database', 'gestao_academia');
            $username = Config::get('database.username', 'root');
            $password = Config::get('database.password', '');
            $charset = Config::get('database.charset', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Não expor credenciais ou detalhes de infraestrutura
                error_log('Database connection failure [' . get_class($e) . ']');
                throw new RuntimeException("Falha na conexão com o banco de dados. Verifique a configuração.");
            }
        }

        return self::$instance;
    }

    public static function setConnection(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
