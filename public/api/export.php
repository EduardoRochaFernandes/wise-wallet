<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$u = Auth::user();

$format = strtolower((string) ($_GET['format'] ?? 'csv'));
$rows = Finance::transactions($uid, [], 1000, 0);

if ($format === 'pdf') {
    require WW_ROOT . '/vendor/fpdf/SimplePdf.php';
    $pdf = new SimplePdf();
    $xs = [40, 95, 250, 360, 470];

    $pdf->text(40, 'WiseWallet — Relatorio de Transacoes', 18, true, '0.39 0.4 0.95');
    $pdf->ln(1.6);
    $pdf->text(40, 'Utilizador: ' . ($u['name'] ?? ''), 10);
    $pdf->ln(1);
    $pdf->text(40, 'Gerado em: ' . date('d/m/Y H:i'), 10);
    $pdf->ln(1);
    $pdf->text(40, 'Total de movimentos: ' . count($rows), 10);
    $pdf->ln(2);

    $pdf->row(['Data', 'Descricao', 'Categoria', 'Conta', 'Valor (EUR)'], $xs, 10, true);
    $pdf->rule();
    $pdf->ln(0.4);

    $totalIn = 0.0; $totalOut = 0.0;
    foreach ($rows as $t) {
        $sign = $t['type'] === 'expense' ? '-' : ($t['type'] === 'income' ? '+' : '');
        if ($t['type'] === 'income') { $totalIn += (float) $t['amount']; }
        if ($t['type'] === 'expense') { $totalOut += (float) $t['amount']; }
        $pdf->row([
            date('d/m/y', strtotime($t['occurred_on'])),
            mb_substr($t['description'] ?: '-', 0, 26),
            mb_substr($t['category_name'] ?? '-', 0, 16),
            mb_substr($t['account_name'] ?? '-', 0, 16),
            $sign . number_format((float) $t['amount'], 2, ',', ' '),
        ], $xs, 9);
    }
    $pdf->ln(1);
    $pdf->rule();
    $pdf->ln(0.6);
    $pdf->text(40, 'Total receitas: ' . number_format($totalIn, 2, ',', ' ') . ' EUR', 10, true);
    $pdf->ln(1);
    $pdf->text(40, 'Total despesas: ' . number_format($totalOut, 2, ',', ' ') . ' EUR', 10, true);
    $pdf->ln(1);
    $pdf->text(40, 'Saldo do periodo: ' . number_format($totalIn - $totalOut, 2, ',', ' ') . ' EUR', 11, true, '0.13 0.77 0.37');

    $out = $pdf->output();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="wisewallet-transacoes-' . date('Ymd') . '.pdf"');
    header('Content-Length: ' . strlen($out));
    echo $out;
    exit;
}

// Default: CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="wisewallet-transacoes-' . date('Ymd') . '.csv"');
$fp = fopen('php://output', 'w');
fprintf($fp, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
fputcsv($fp, ['Data', 'Tipo', 'Descrição', 'Categoria', 'Conta', 'Valor', 'Notas']);
foreach ($rows as $t) {
    fputcsv($fp, [
        $t['occurred_on'], $t['type'], $t['description'], $t['category_name'] ?? '',
        $t['account_name'] ?? '', number_format((float) $t['amount'], 2, '.', ''), $t['notes'] ?? '',
    ]);
}
fclose($fp);
exit;
