<?php
    require 'config.php';
    session_start();
    if (isset($_POST['ajax'])) {
        $texto = $_POST['texto'] ?? '';
        // Divide pelas quebras de linha (\r\n, \n, \r)
        $linhas = preg_split('/\r\n|\r|\n/', $texto);
        $linhas = array_map('sanitizeInput', $linhas);
        $linhas = array_filter($linhas, fn($l) => strlen(trim($l)) > 0);
        if (empty($linhas)) {
            echo json_encode(['type' => 'danger', 'msg' => 'Digite algum texto!']);
        } else {
            $db = db();
            $adicionados = 0;
            foreach ($linhas as $linha) {
                // Evita duplicação imediata
                if (!isset($_SESSION['last_post']) || $_SESSION['last_post'] !== $linha) {
                    $stmt = $db->prepare('INSERT INTO textos (texto) VALUES (:texto)');
                    $stmt->execute(['texto' => $linha]);
                    $_SESSION['last_post'] = $linha;
                    $adicionados++;
                }
            }
            if ($adicionados) {
                $msg = ($adicionados === 1) ? 'Texto enviado com sucesso!' : "$adicionados textos enviados com sucesso!";
                echo json_encode(['type' => 'success', 'msg' => $msg]);
            } else {
                echo json_encode(['type' => 'warning', 'msg' => 'Os textos já haviam sido enviados.']);
            }
        }
        exit;
    }
    // Fallback tradicional (JS desabilitado)
    $feedback = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $texto = $_POST['texto'] ?? '';
        $linhas = preg_split('/\r\n|\r|\n/', $texto);
        $linhas = array_map('sanitizeInput', $linhas);
        $linhas = array_filter($linhas, fn($l) => strlen(trim($l)) > 0);
        if (empty($linhas)) {
            $feedback = ['type' => 'danger', 'msg' => 'Digite algum texto!'];
        } else {
            $db = db();
            $adicionados = 0;
            foreach ($linhas as $linha) {
                if (!isset($_SESSION['last_post']) || $_SESSION['last_post'] !== $linha) {
                    $stmt = $db->prepare('INSERT INTO textos (texto) VALUES (:texto)');
                    $stmt->execute(['texto' => $linha]);
                    $_SESSION['last_post'] = $linha;
                    $adicionados++;
                }
            }
            if ($adicionados) {
                $msg = ($adicionados === 1) ? 'Texto enviado com sucesso!' : "$adicionados textos enviados com sucesso!";
                $feedback = ['type' => 'success', 'msg' => $msg];
            } else {
                $feedback = ['type' => 'warning', 'msg' => 'Os textos já haviam sido enviados.'];
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Ativos em falta | Simple Pharma</title>
        <link rel="icon" type="image/x-icon" href="https://static.wixstatic.com/media/5ede7b_719545c97a084f288b8566db52756425%7Emv2.png/v1/fill/w_32%2Ch_32%2Clg_1%2Cusm_0.66_1.00_0.01/5ede7b_719545c97a084f288b8566db52756425%7Emv2.png">
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
                background: #47aeb2;
                color: #fff;
                font-weight: 600;
                transition: background .2s;
            }
            .btn-simple:hover,
            .btn-simple:focus {
                background: #47aeb2;
                color: #ffe23b;
            }
            .form-label {
                font-weight: 500;
                color: #47aeb2;
            }
            .simple-header {
                color: #47aeb2;
                font-weight: 800;
                letter-spacing: 0.5px;
            }
            textarea.form-control:focus {
                border-color: #47aeb2;
                box-shadow: 0 0 0 0.2rem #01884930;
            }
            .alert-success {
                background: #f0fdf5;
                color: #47aeb2;
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
                    <img src="https://static.wixstatic.com/media/6e2603_a1df562998b54aa79d9bedb9add87265~mv2.png/v1/crop/x_0,y_4,w_123,h_73/fill/w_150,h_89,al_c,lg_1,q_85,enc_avif,quality_auto/logo.png" alt="Simple Pharma" style="max-width:140px; height:auto;">
                </div>
                <form id="form-texto" autocomplete="off">
                    <div class="mb-3">
                        <!--<label for="texto" class="form-label">Ativos em falta</label>-->
                        <textarea name="texto" id="texto" maxlength="2000" required class="form-control" style="min-height:100px" placeholder="Digite o nome do ativo em falta"></textarea>
                        <!--<small class="text-muted">Pressione <b>Enter</b> para enviar, <b>Shift+Enter</b> para pular linha.<br>
                        Cada linha será adicionada como um registro separado.</small>-->
                    </div>
                    <button type="submit" class="btn btn-simple w-100 py-2 fs-5">Enviar</button>
                </form>
                <div id="feedback"></div>
                <noscript>
                <?php if ($feedback): ?>
                    <div class="alert alert-<?= $feedback['type'] ?> mt-4 text-center">
                        <?= htmlspecialchars($feedback['msg']) ?>
                    </div>
                <?php endif; ?>
                </noscript>
            </div>
        </div>
        <script>
            const form = document.getElementById('form-texto');
            const textarea = document.getElementById('texto');
            const feedbackDiv = document.getElementById('feedback');
            // Enter envia, Shift+Enter quebra linha
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const texto = textarea.value.trim();
                if (!texto) {
                    showFeedback('Digite algum texto!', 'danger');
                    return;
                }
                fetch('<?= $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'ajax=1&texto=' + encodeURIComponent(texto)
                })
                .then(res => res.json())
                .then(res => {
                    showFeedback(res.msg, res.type);
                    if (res.type === 'success') {
                        textarea.value = '';
                        textarea.focus();
                    }
                })
                .catch(() => showFeedback('Erro ao enviar. Tente novamente!', 'danger'));
            });
            function showFeedback(msg, type) {
                feedbackDiv.innerHTML = `<div class="alert alert-${type} mt-4 text-center">${msg}</div>`;
            }
        </script>
    </body>
</html>