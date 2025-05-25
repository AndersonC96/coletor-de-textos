<?php
    declare(strict_types=1);
    require 'config.php';
    require 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    // Busca todos os textos do banco
    $stmt = db()->query('SELECT id, texto, criado_em FROM textos ORDER BY criado_em DESC');
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Cria planilha
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório');
    $sheet->fromArray(['ID', 'Texto', 'Data/Hora'], null, 'A1');
    $row = 2;
    foreach ($dados as $item) {
        $sheet->setCellValue("A{$row}", $item['id']);
        $sheet->setCellValue("B{$row}", html_entity_decode($item['texto']));
        $sheet->setCellValue("C{$row}", $item['criado_em']);
        $row++;
    }
    // Força download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;