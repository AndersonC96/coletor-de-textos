<?php
    declare(strict_types=1);
    require 'config.php';
    require 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Color;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    // Busca todos os textos do banco
    $stmt = db()->query('SELECT texto, criado_em FROM textos');
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
            $agrupados[$texto_norm]['texto'] = $item['texto'];
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
    // Cores do tema: vermelho topo (#b93a36), vermelho claro (#f9dddd), borda branca
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'b93a36'], // Vermelho escuro
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
    // Linhas de dados com fundo vermelho claro
    $dataStyle = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'f9dddd'], // Vermelho bem claro
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