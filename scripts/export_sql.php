<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!in_array('--confirm-sensitive-export', $argv ?? [], true)) {
    fwrite(STDERR, "Exportação bloqueada: o arquivo inclui dados pessoais e hashes de senha. Use --confirm-sensitive-export somente em ambiente protegido.\n");
    exit(2);
}

require_once __DIR__ . '/../app/Support/Config.php';

use App\Support\Config;

Config::load(__DIR__ . '/../.env');

$dbHost = Config::get('database.host', '127.0.0.1');
$dbPort = Config::get('database.port', '3306');
$dbName = Config::get('database.database', 'gestao_academia');
$dbUser = Config::get('database.username', 'root');
$dbPass = Config::get('database.password', '');

$pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tables = ['redes', 'unidades', 'usuarios', 'usuario_unidades', 'recuperacao_senha', 'tentativas_acesso', 'conteudos_comunicados', 'rede_conteudos'];

$output = "-- ==========================================================\n";
$output .= "-- SCRIPT COMPLETO DE BANCO DE DADOS: gestao_academia\n";
$output .= "-- Compatível com MySQL 5.7+ / MariaDB / phpMyAdmin (XAMPP)\n";
$output .= "-- ==========================================================\n\n";
$output .= "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$output .= "USE `{$dbName}`;\n\n";
$output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
    $output .= "-- Estrutura da tabela `{$table}`\n";
    $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $output .= $create['Create Table'] . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll();
    if (!empty($rows)) {
        $output .= "-- Dados iniciais da tabela `{$table}`\n";
        foreach ($rows as $row) {
            $cols = array_keys($row);
            $colList = implode('`, `', $cols);
            $valList = array_map(function ($val) use ($pdo) {
                if ($val === null) return 'NULL';
                return $pdo->quote((string)$val);
            }, array_values($row));
            $output .= "INSERT INTO `{$table}` (`{$colList}`) VALUES (" . implode(', ', $valList) . ");\n";
        }
        $output .= "\n";
    }
}

$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/../database/gestao_academia_completo.sql', $output);
echo "Arquivo database/gestao_academia_completo.sql gerado com sucesso! (" . strlen($output) . " bytes)\n";
