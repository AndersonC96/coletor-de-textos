<?php
    declare(strict_types=1);
    require 'config.php';
    require 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    // ----------- INTERFACE & PROCESSAMENTO DE PERÍODO ----------- //
    function getDefaultDates(): array {
        $start = (new DateTime('first day of this month'))->format('Y-m-d');
        $end   = (new DateTime('last day of this month'))->format('Y-m-d');
        return [$start, $end];
    }
    $showForm = true;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data_ini'], $_POST['data_fim'])) {
        // Validação básica de datas (yyyy-mm-dd)
        $data_ini = $_POST['data_ini'];
        $data_fim = $_POST['data_fim'];
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_ini) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_fim)) {
            $showForm = false;
        }
    }
    if ($showForm):
        [$data_ini, $data_fim] = getDefaultDates();
        if (isset($_POST['data_ini'])) $data_ini = $_POST['data_ini'];
        if (isset($_POST['data_fim'])) $data_fim = $_POST['data_fim'];
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Ativos | Simple Pharma</title>
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
            .form-control:focus {
                border-color: #53c29d;
                box-shadow: 0 0 0 0.18rem #c6f2e2;
            }
            .form-control {
                border-radius: 12px;
                border: 1.4px solid #b1e2d0;
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
                <div class="text-center mb-3">
                    <img src="https://static.wixstatic.com/media/6e2603_a1df562998b54aa79d9bedb9add87265~mv2.png/v1/crop/x_0,y_4,w_123,h_73/fill/w_140,h_80,al_c,lg_1,q_85,enc_avif,quality_auto/logo.png" alt="Simple Pharma" style="max-width:120px; height:auto;">
                </div>
                <h3 class="text-center mb-4" style="color:#168978; font-weight:700; letter-spacing:.5px;">Relatório de Ativos em falta</h3>
                <form method="POST" class="mb-2" autocomplete="off">
                    <div class="mb-3 row">
                        <div class="col-12 mb-2">
                            <label for="data_ini" class="form-label"><b>Período</b></label>
                            <div class="input-group">
                                <input type="date" class="form-control" name="data_ini" id="data_ini" value="<?= htmlspecialchars($data_ini) ?>" required>
                                <span class="input-group-text bg-white border-0" style="color:#168978;">até</span>
                                <input type="date" class="form-control" name="data_fim" id="data_fim" value="<?= htmlspecialchars($data_fim) ?>" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-simple w-100 py-2 fs-5">
                        <span style="font-size:1.15em;">📥</span> Baixar Relatório
                    </button>
                </form>
            </div>
        </div>
    </body>
</html>
<?php
    exit;
    endif;
    // ----------- FILTRA O PERÍODO PARA GERAR O XLSX ----------- //
    // Recebe datas do POST
    $data_ini = $_POST['data_ini'] . " 00:00:00";
    $data_fim = $_POST['data_fim'] . " 23:59:59";
    // Busca textos do banco filtrando por período
    $stmt = db()->prepare('SELECT texto, criado_em FROM textos WHERE criado_em BETWEEN :ini AND :fim');
    $stmt->execute(['ini' => $data_ini, 'fim' => $data_fim]);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Agrupa ignorando case
    $agrupados = [];
    foreach ($dados as $item) {
        $texto_norm = mb_strtolower(html_entity_decode($item['texto']), 'UTF-8');
        if (!isset($agrupados[$texto_norm])) {
            $agrupados[$texto_norm] = [
                'texto' => mb_convert_case($texto_norm, MB_CASE_TITLE, 'UTF-8'),
                'quantidade' => 0,
                'ultima_data' => $item['criado_em'],
            ];
        }
        $agrupados[$texto_norm]['quantidade']++;
        if ($item['criado_em'] > $agrupados[$texto_norm]['ultima_data']) {
            $agrupados[$texto_norm]['texto'] = mb_convert_case($item['texto'], MB_CASE_TITLE, 'UTF-8');
            $agrupados[$texto_norm]['ultima_data'] = $item['criado_em'];
        }
    }
    // Ordena por quantidade desc
    usort($agrupados, function($a, $b) {
        return $b['quantidade'] <=> $a['quantidade'];
    });
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório Agrupado');
    // Cabeçalhos
    $cabecalhos = ['Texto', 'Quantidade', 'Última Adição'];
    $sheet->fromArray($cabecalhos, null, 'A1');
    // Adiciona dados
    $row = 2;
    foreach ($agrupados as $item) {
        $sheet->setCellValue("A{$row}", $item['texto']);
        $sheet->setCellValue("B{$row}", $item['quantidade']);
        $sheet->setCellValue("C{$row}", \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($item['ultima_data']));
        $row++;
    }
    $lastRow = $row - 1;
    // Estilos de tabela ao estilo Excel
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'b93a36'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ],
    ];
    $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
    $dataStyle = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'f9dddd'],
        ],
        'font' => [
            'color' => ['rgb' => '4b2e2b'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ],
    ];
    $sheet->getStyle('A2:C' . $lastRow)->applyFromArray($dataStyle);
    // Filtros nos cabeçalhos
    $sheet->setAutoFilter('A1:C1');
    // Ajuste de largura automática
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    // Formatação da coluna de data/hora
    $sheet->getStyle('C2:C' . $lastRow)
        ->getNumberFormat()
        ->setFormatCode('dd/mm/yyyy hh:mm');
    // Alinhamento centralizado para quantidade
    $sheet->getStyle('B2:B' . $lastRow)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    // Força download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_agrupado_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;