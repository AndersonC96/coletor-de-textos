<?php
    declare(strict_types=1);
    require 'config.php';
    $feedback = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $texto = $_POST['texto'] ?? '';
        $texto = sanitizeInput($texto);
        // Proteção básica contra spam: recusa envios vazios ou repetidos rapidamente
        session_start();
        if (empty($texto)) {
            $feedback = 'Digite algum texto!';
        } elseif (isset($_SESSION['last_post']) && $_SESSION['last_post'] === $texto) {
            $feedback = 'Texto já enviado recentemente.';
        } else {
            $stmt = db()->prepare('INSERT INTO textos (texto) VALUES (:texto)');
            $stmt->execute(['texto' => $texto]);
            $_SESSION['last_post'] = $texto;
            $feedback = 'Enviado com sucesso!';
        }
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Inserir Texto</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                font-family: sans-serif;
                background: #f6f6f6;
                padding: 2em;
            }
            form {
                background: #fff;
                padding: 1em;
                border-radius: 8px;
                max-width: 400px;
            }
            textarea {
                width: 100%;
                height: 100px;
            }
            .feedback {
                margin-top: 1em;
                color: green;
            }
        </style>
    </head>
    <body>
        <h2>Inserir Texto</h2>
        <form method="POST" autocomplete="off">
            <label for="texto">Texto:</label><br>
            <textarea name="texto" id="texto" maxlength="2000" required></textarea><br>
            <button type="submit">Enviar</button>
        </form>
        <?php if ($feedback): ?>
            <div class="feedback"><?= $feedback ?></div>
        <?php endif; ?>
    </body>
</html>