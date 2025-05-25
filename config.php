<?php
    declare(strict_types=1);
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'coleta_textos');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    function db(): PDO {
        static $pdo;
        if (!$pdo) {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        }
        return $pdo;
    }
    // Função para limpar e limitar o input (XSS e tamanho)
    function sanitizeInput(string $input, int $maxLength = 2000): string {
        $text = trim($input);
        $text = mb_substr($text, 0, $maxLength); // limita tamanho
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // previne XSS
        return $text;
    }