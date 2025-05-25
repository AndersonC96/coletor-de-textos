<?php
    require 'config.php';
    $feedback = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $texto = $_POST['texto'] ?? '';
        $texto = sanitizeInput($texto);
        session_start();
        if (empty($texto)) {
            $feedback = ['type' => 'danger', 'msg' => 'Digite algum texto!'];
        } elseif (isset($_SESSION['last_post']) && $_SESSION['last_post'] === $texto) {
            $feedback = ['type' => 'warning', 'msg' => 'Texto já enviado recentemente.'];
        } else {
            $stmt = db()->prepare('INSERT INTO textos (texto) VALUES (:texto)');
            $stmt->execute(['texto' => $texto]);
            $_SESSION['last_post'] = $texto;
            $feedback = ['type' => 'success', 'msg' => 'Texto enviado com sucesso!'];
        }
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Envio de Texto | Simple Pharma</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: #f8f9fa;
                min-height: 100vh;
            }
            .simple-card {
                border-radius: 20px;
                box-shadow: 0 2px 16px rgba(1, 136, 73, 0.08);
            }
            .btn-simple {
                background: #018849;
                color: #fff;
                font-weight: 600;
                transition: background .2s;
            }
            .btn-simple:hover,
            .btn-simple:focus {
                background: #01653a;
                color: #ffe23b;
            }
            .form-label {
                font-weight: 500;
                color: #018849;
            }
            .simple-header {
                color: #018849;
                font-weight: 800;
                letter-spacing: 0.5px;
            }
            textarea.form-control:focus {
                border-color: #018849;
                box-shadow: 0 0 0 0.2rem #01884930;
            }
            .alert-success {
                background: #f0fdf5;
                color: #018849;
                border: 1px solid #01884922;
            }
            .alert-danger {
                background: #fff1f0;
                color: #e74c3c;
                border: 1px solid #e74c3c22;
            }
            .alert-warning {
                background: #fffbe6;
                color: #f39c12;
                border: 1px solid #f39c1222;
            }
            @media (max-width: 500px) {
                .simple-card {
                    padding: 1.5rem !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
            <div class="simple-card bg-white p-5 w-100" style="max-width: 440px;">
                <div class="text-center mb-4">
                    <img src="https://www.simplepharma.com.br/wp-content/uploads/2023/08/Logo_SimplePharma_Cor.png" alt="Simple Pharma" style="max-width:140px; height:auto;">
                </div>
                <h2 class="text-center mb-4 simple-header">Envio de Texto</h2>
                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="texto" class="form-label">Digite seu texto abaixo:</label>
                        <textarea name="texto" id="texto" maxlength="2000" required class="form-control" style="min-height:100px" placeholder="Digite aqui..."><?= htmlspecialchars($_POST['texto'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-simple w-100 py-2 fs-5">Enviar</button>
                </form>
                <?php if ($feedback): ?>
                    <div class="alert alert-<?= $feedback['type'] ?> mt-4 text-center">
                        <?= htmlspecialchars($feedback['msg']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </body>
</html>