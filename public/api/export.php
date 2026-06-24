<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
$uid = Auth::id();
$u = Auth::user();

$format = strtolower((string) ($_GET['format'] ?? 'csv'));
$rows = Finance::transactions($uid, [], 1000, 0);
Audit::log('export', $uid, ['format' => $format]);
Achievements::evaluate($uid);

if ($format === 'pdf') {
    require WW_ROOT . '/vendor/fpdf/SimplePdf.php';
    $pdf = new SimplePdf();
    $xs = [40, 95, 250, 360, 470];

    $pdf->text(40, 'WiseWallet - Transactions Report', 18, true, '0.122 0.353 0.247');
    $pdf->ln(1.6);
    $pdf->text(40, 'Account holder: ' . ($u['name'] ?? ''), 10);
    $pdf->ln(1);
    $pdf->text(40, 'Generated: ' . date('d/m/Y H:i'), 10);
    $pdf->ln(1);
    $pdf->text(40, 'Total entries: ' . count($rows), 10);
    $pdf->ln(2);

    $pdf->row(['Date', 'Description', 'Category', 'Account', 'Amount (EUR)'], $xs, 10, true);
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
            $sign . number_format((float) $t['amount'], 2, '.', ','),
        ], $xs, 9);
    }
    $pdf->ln(1);
    $pdf->rule();
    $pdf->ln(0.6);
    $pdf->text(40, 'Total income: ' . number_format($totalIn, 2, '.', ',') . ' EUR', 10, true);
    $pdf->ln(1);
    $pdf->text(40, 'Total expenses: ' . number_format($totalOut, 2, '.', ',') . ' EUR', 10, true);
    $pdf->ln(1);
    $pdf->text(40, 'Net for period: ' . number_format($totalIn - $totalOut, 2, '.', ',') . ' EUR', 11, true, '0.13 0.77 0.37');

    $out = $pdf->output();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="wisewallet-transactions-' . date('Ymd') . '.pdf"');
    header('Content-Length: ' . strlen($out));
    echo $out;
    exit;
}

if ($format === 'xlsx') {
    require WW_ROOT . '/vendor/xlsx/SimpleXlsx.php';
    $xlsx = new SimpleXlsx();
    $xlsx->setColumnWidths([12, 32, 18, 16, 12, 30]);
    $xlsx->setCurrencyColumns([4]);
    $xlsx->addHeaderRow(['Date', 'Description', 'Category', 'Account', 'Amount (EUR)', 'Notes']);
    foreach ($rows as $t) {
        $signed = (float) $t['amount'] * ($t['type'] === 'expense' ? -1 : 1);
        $xlsx->addRow([
            $t['occurred_on'],
            (string) ($t['description'] ?: ''),
            (string) ($t['category_name'] ?? ($t['type'] === 'transfer' ? 'Transfer' : '')),
            (string) ($t['account_name'] ?? ''),
            round($signed, 2),
            (string) ($t['notes'] ?? ''),
        ]);
    }
    $out = $xlsx->output();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="wisewallet-transactions-' . date('Ymd') . '.xlsx"');
    header('Content-Length: ' . strlen($out));
    echo $out;
    exit;
}

// Default: CSV — neutralize spreadsheet formula injection (=, +, -, @, tab, CR).
$csvSafe = static function ($v): string {
    $v = (string) $v;
    if ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) {
        $v = "'" . $v; // force the cell to be treated as text
    }
    return $v;
};
header('Content-Type: text/csv; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="wisewallet-transactions-' . date('Ymd') . '.csv"');
$fp = fopen('php://output', 'w');
fprintf($fp, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
fputcsv($fp, ['Date', 'Type', 'Description', 'Category', 'Account', 'Amount', 'Notes']);
foreach ($rows as $t) {
    fputcsv($fp, array_map($csvSafe, [
        $t['occurred_on'], $t['type'], $t['description'], $t['category_name'] ?? '',
        $t['account_name'] ?? '', number_format((float) $t['amount'], 2, '.', ''), $t['notes'] ?? '',
    ]));
}
fclose($fp);
exit;
