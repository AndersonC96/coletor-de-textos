<?php
    declare(strict_types=1);
    require 'config.php';
    require 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    // 1. Calcula o período do mês anterior
    $inicio = (new DateTime('first day of last month'))->format('Y-m-d') . ' 00:00:00';
    $fim    = (new DateTime('last day of last month'))->format('Y-m-d') . ' 23:59:59';
    // 2. Busca dados do período filtrado
    $stmt = db()->prepare('SELECT texto, criado_em FROM textos WHERE criado_em BETWEEN :ini AND :fim');
    $stmt->execute(['ini' => $inicio, 'fim' => $fim]);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // 3. Agrupamento dos dados (case-insensitive, por texto)
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
    usort($agrupados, function($a, $b) {
        return $b['quantidade'] <=> $a['quantidade'];
    });
    // 4. Gera o XLSX em memória
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório Agrupado');
    $sheet->fromArray(['Texto', 'Quantidade', 'Última Adição'], null, 'A1');
    $row = 2;
    foreach ($agrupados as $item) {
        $sheet->setCellValue("A{$row}", $item['texto']);
        $sheet->setCellValue("B{$row}", $item['quantidade']);
        $sheet->setCellValue("C{$row}", \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($item['ultima_data']));
        $row++;
    }
    $lastRow = $row - 1;
    // Estilização igual ao seu relatório
    $sheet->getStyle('A1:C1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'b93a36']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
    ]);
    $sheet->getStyle('A2:C' . $lastRow)->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f9dddd']],
        'font' => ['color' => ['rgb' => '4b2e2b']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
    ]);
    foreach (range('A', 'C') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
    $sheet->getStyle('C2:C' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
    $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    // Salva arquivo temporário
    $nomeArquivo = 'relatorio_' . date('Ym', strtotime($inicio)) . '.xlsx';
    $tmpFile = sys_get_temp_dir() . '/' . $nomeArquivo;
    $writer = new Xlsx($spreadsheet);
    $writer->save($tmpFile);
    // 5. Envia por e-mail
    $mail = new PHPMailer(true);
    try {
        // --- CONFIGURAÇÕES SMTP --- //
        $mail->isSMTP();
        $mail->Host = 'smtppro.zoho.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'seu@email.com';
        $mail->Password = 'SENHA';
        $mail->SMTPSecure = 'tls'; // Ou 'ssl', conforme provedor
        $mail->Port = 587; // 465 para ssl, 587 para tls normalmente
        $mail->setFrom('seu@email.com', 'Simple Pharma Relatórios');
        $mail->addAddress('destinatario@empresa.com', 'Equipe Simple Pharma');
        $mail->Subject = "Relatório Agrupado - " . ucfirst(strftime('%B de %Y', strtotime($inicio)));
        $mail->Body = "Olá!\n\nSegue em anexo o relatório mensal de ativos (período: ".date('d/m/Y', strtotime($inicio))." até ".date('d/m/Y', strtotime($fim)).").\n\nAtenciosamente,\nEquipe Simple Pharma";
        $mail->addAttachment($tmpFile, $nomeArquivo);
        $mail->send();
        echo "Relatório enviado com sucesso!";
    } catch (Exception $e) {
        echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
    }
    @unlink($tmpFile);