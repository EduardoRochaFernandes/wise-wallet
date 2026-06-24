<?php
/**
 * SimpleXlsx — a tiny, dependency-free .xlsx writer (single sheet).
 * Uses PHP's built-in ZipArchive only — no Composer library. Supports a bold
 * header row, a currency-formatted numeric column, and explicit column
 * widths, which is the "real formatting" a plain CSV cannot carry.
 */

declare(strict_types=1);

final class SimpleXlsx
{
    /** @var array<int,array{header:bool,values:array}> */
    private array $rows = [];
    /** Column widths in Excel "character" units, 1-indexed. */
    private array $colWidths = [];
    /** 0-indexed column numbers that should use the currency number format. */
    private array $currencyCols = [];

    public function setColumnWidths(array $widths): void { $this->colWidths = $widths; }
    public function setCurrencyColumns(array $cols): void { $this->currencyCols = $cols; }

    public function addHeaderRow(array $values): void { $this->rows[] = ['header' => true, 'values' => $values]; }
    public function addRow(array $values): void { $this->rows[] = ['header' => false, 'values' => $values]; }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function colLetter(int $i): string
    {
        $letter = '';
        $i++;
        while ($i > 0) {
            $mod = ($i - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $i = intdiv($i - 1, 26);
        }
        return $letter;
    }

    private function sheetXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if ($this->colWidths) {
            $xml .= '<cols>';
            foreach ($this->colWidths as $i => $w) {
                $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach ($this->rows as $rIdx => $row) {
            $r = $rIdx + 1;
            $xml .= '<row r="' . $r . '">';
            foreach ($row['values'] as $cIdx => $val) {
                $ref = $this->colLetter($cIdx) . $r;
                $isNumeric = is_int($val) || is_float($val);
                $style = $row['header'] ? 1 : (in_array($cIdx, $this->currencyCols, true) ? 2 : 0);
                if ($isNumeric) {
                    $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $val . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $this->esc((string) $val) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function stylesXml(): string
    {
        // numFmtId 164 = custom currency format; built-ins stop at 163.
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00&quot; €&quot;"/></numFmts>'
            . '<fonts count="2"><font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><sz val="10"/><name val="Calibri"/><b/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE9F2EC"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs></styleSheet>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Transactions" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    /** Build the .xlsx (a zip archive) and return its raw bytes. */
    public function output(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE | ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());
        $zip->close();
        $bytes = (string) file_get_contents($tmp);
        unlink($tmp);
        return $bytes;
    }
}
