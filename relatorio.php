<?php
    declare(strict_types=1);
    require 'config.php';
    require 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    // Busca todos os textos do banco
    $stmt = db()->query('SELECT texto, criado_em FROM textos');
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Agrupa ignorando diferença de maiúsculas/minúsculas
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
        // Salva a data mais recente (opcional)
        if ($item['criado_em'] > $agrupados[$texto_norm]['ultima_data']) {
            $agrupados[$texto_norm]['ultima_data'] = $item['criado_em'];
        }
    }
    // Ordena por quantidade decrescente ou por data decrescente
    usort($agrupados, function($a, $b) {
        // Ordena por quantidade
        //return $b['quantidade'] <=> $a['quantidade'];
        // Para ordenar por data:
        return strcmp($b['ultima_data'], $a['ultima_data']);
    });
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório Agrupado');
    // Cabeçalhos
    $sheet->fromArray(['Texto', 'Quantidade', 'Última Data/Hora'], null, 'A1');
    // Preenche dados
    $row = 2;
    foreach ($agrupados as $item) {
        $sheet->setCellValue("A{$row}", $item['texto']);
        $sheet->setCellValue("B{$row}", $item['quantidade']);
        $sheet->setCellValue("C{$row}", $item['ultima_data']);
        $row++;
    }
    // Download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_agrupado_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;