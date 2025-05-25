<?php
    require 'config.php';
    session_start();
    if (isset($_POST['ajax'])) {
        $texto = $_POST['texto'] ?? '';
        $linhas = preg_split('/\r\n|\r|\n/', $texto);
        $linhas = array_map('sanitizeInput', $linhas);
        $linhas = array_filter($linhas, fn($l) => strlen(trim($l)) > 0);
        if (empty($linhas)) {
            echo json_encode(['type' => 'danger', 'msg' => 'Digite algum texto!']);
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
                background: linear-gradient(135deg, #e5f9f6 0%, #f3fcfa 100%);
                min-height: 100vh;
            }
            .simple-card {
                border-radius: 32px;
                box-shadow: 0 8px 32px rgba(83, 194, 157, 0.11), 0 2px 16px rgba(22,137,120,0.06);
                margin-top: 32px;
            }
            .btn-simple {
                background: linear-gradient(90deg, #53c29d 0%, #36b9b1 100%);
                color: #fff;
                font-weight: 700;
                letter-spacing: 0.5px;
                font-size: 1.18em;
                border: none;
                transition: background .2s, transform .13s;
                border-radius: 14px;
            }
            .btn-simple:hover,
            .btn-simple:focus {
                background: linear-gradient(90deg, #38b992 0%, #53c29d 100%);
                color: #fff;
                transform: translateY(-1px) scale(1.04);
                box-shadow: 0 4px 16px #99ecd866;
            }
            textarea.form-control:focus {
                border-color: #53c29d;
                box-shadow: 0 0 0 0.18rem #c6f2e2;
            }
            .form-control {
                border-radius: 12px;
                border: 1.4px solid #b1e2d0;
            }
            .alert-success {
                background: #eafaf5;
                color: #168978;
                border: 1.3px solid #b1e2d0;
            }
            .alert-danger, .alert-warning {
                background: #fff7f2;
                color: #e77a46;
                border: 1.3px solid #f5dccb;
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
                <div class="text-center mb-2">
                    <img src="https://static.wixstatic.com/media/6e2603_a1df562998b54aa79d9bedb9add87265~mv2.png/v1/crop/x_0,y_4,w_123,h_73/fill/w_140,h_80,al_c,lg_1,q_85,enc_avif,quality_auto/logo.png"alt="Simple Pharma" style="max-width:120px; height:auto;">
                </div>
                <div class="text-center mb-2" style="font-size:2.2em;">
                    <span style="color:#53C29D;">&#128138;</span>
                </div>
                <div class="text-center mb-3" style="color:#168978;font-size:1.15em;">
                    Sua informação ajuda a manter o cuidado com todos.  
                    <br>
                    <span style="color:#53C29D;">Conte com nosso time farmacêutico!</span>
                </div>
                <form id="form-texto" autocomplete="off">
                    <div class="mb-3">
                        <textarea name="texto" id="texto" maxlength="2000" required class="form-control" style="min-height:90px; font-size:1.08em;" placeholder="Digite o nome do ativo em falta, ex: Dipirona, Cafeína..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-simple w-100 py-2 fs-5">
                        <span style="font-size:1.25em;">&#128640;</span> Enviar
                    </button>
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
                let emoji = '';
                if(type === 'success') emoji = '✅ ';
                if(type === 'danger') emoji = '❗ ';
                if(type === 'warning') emoji = '⚠️ ';
                feedbackDiv.innerHTML = `<div class="alert alert-${type} mt-4 text-center">${emoji}${msg}</div>`;
                // Remove automaticamente após 10 segundos (somente para sucesso)
                if (type === 'success') {
                    setTimeout(() => {
                        feedbackDiv.innerHTML = '';
                    }, 10000);
                }
            }
        </script>
    </body>
</html>