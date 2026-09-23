<?php
define('AURA_DEV', true); // Em produção, altere para false após configurar o envio de e-mail.

$host = 'localhost';
$db = 'clinica';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die('<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Erro de conexão</title><link rel="icon" type="image/png" sizes="512x512" href="img/favicon-aura.png"><link rel="shortcut icon" href="img/favicon.ico"><body style="font-family:Arial;padding:40px"><h1>Não foi possível conectar ao banco.</h1><p>Verifique se o MySQL está iniciado no XAMPP e se o arquivo <strong>banco.sql</strong> foi importado.</p><script src="js/app.js"></script></body></html>');
}
