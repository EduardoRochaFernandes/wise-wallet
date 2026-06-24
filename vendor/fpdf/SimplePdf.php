<?php
/**
 * SimplePdf — a tiny, dependency-free PDF writer (core Helvetica font, no GD).
 * Enough for tabular WiseWallet reports: title, meta, table rows, pagination.
 */

declare(strict_types=1);

final class SimplePdf
{
    private array $pages = [];
    private string $buf = '';
    private float $y;
    private float $pageW = 595.28;  // A4 portrait (pt)
    private float $pageH = 841.89;
    private float $margin = 40.0;
    private float $lineH = 16.0;

    public function __construct()
    {
        $this->y = $this->pageH - $this->margin;
    }

    private function esc(string $s): string
    {
        $s = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
        if ($s === false) { $s = ''; }
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $s);
    }

    private function ensureSpace(): void
    {
        if ($this->y < $this->margin + $this->lineH) {
            $this->pages[] = $this->buf;
            $this->buf = '';
            $this->y = $this->pageH - $this->margin;
        }
    }

    public function text(float $x, string $str, float $size = 11, bool $bold = false, string $rgb = '0 0 0'): void
    {
        $font = $bold ? '/F2' : '/F1';
        $this->buf .= "BT $rgb rg $font $size Tf 1 0 0 1 " . round($x, 2) . ' ' . round($this->y, 2) . " Tm (" . $this->esc($str) . ") Tj ET\n";
    }

    public function ln(float $n = 1): void
    {
        $this->y -= $this->lineH * $n;
        $this->ensureSpace();
    }

    /** A row of columns at fixed x positions. */
    public function row(array $cols, array $xs, float $size = 10, bool $bold = false): void
    {
        foreach ($cols as $i => $c) {
            $this->text($xs[$i] ?? $this->margin, (string) $c, $size, $bold);
        }
        $this->ln(1);
    }

    public function rule(): void
    {
        $this->buf .= '0.8 0.8 0.8 RG 0.5 w ' . round($this->margin, 2) . ' ' . round($this->y + 6, 2)
            . ' m ' . round($this->pageW - $this->margin, 2) . ' ' . round($this->y + 6, 2) . " l S\n";
    }

    public function output(): string
    {
        $this->pages[] = $this->buf;
        $obj = [];
        $obj[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        $n = count($this->pages);
        // Page objects start at 4; content streams interleaved after.
        $kids = [];
        $objNum = 4;
        $pageObjs = [];
        $contentObjs = [];
        foreach ($this->pages as $i => $content) {
            $pageNo = $objNum++;
            $contentNo = $objNum++;
            $pageObjs[$pageNo] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->pageW} {$this->pageH}] "
                . "/Resources << /Font << /F1 3 0 R /F2 " . ($objNum) . " 0 R >> >> /Contents $contentNo 0 R >>";
            $stream = $content;
            $contentObjs[$contentNo] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $kids[] = "$pageNo 0 R";
        }
        $boldNo = $objNum++;

        $obj[2] = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count $n >>";
        $obj[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $obj[$boldNo] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        foreach ($pageObjs as $k => $v) { $obj[$k] = $v; }
        foreach ($contentObjs as $k => $v) { $obj[$k] = $v; }

        ksort($obj);
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        $maxObj = max(array_keys($obj));
        for ($i = 1; $i <= $maxObj; $i++) {
            if (!isset($obj[$i])) { continue; }
            $offsets[$i] = strlen($pdf);
            $pdf .= "$i 0 obj\n" . $obj[$i] . "\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxObj + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObj; $i++) {
            $pdf .= isset($offsets[$i]) ? sprintf("%010d 00000 n \n", $offsets[$i]) : "0000000000 00000 f \n";
        }
        $pdf .= "trailer\n<< /Size " . ($maxObj + 1) . " /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";
        return $pdf;
    }
}
