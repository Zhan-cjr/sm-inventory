<?php

namespace App\Services;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use DOMDocument;
use DOMXPath;
use DOMElement;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportService
{
    public function export(View $view, string $filename = 'laporan.xlsx'): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'rpt_') . '.xlsx';
        
        $options = new Options();
        $writer = new Writer($options);
        $writer->openToFile($tempPath);

        $viewName = $view->getName();
        $data = $view->getData();

        if ($viewName === 'print.reports.generic' && isset($data['columns']) && isset($data['rows'])) {
            $this->exportGenericData($writer, $data);
        } else {
            $html = $view->render();
            $this->exportHtmlData($writer, $html, $data);
        }

        $writer->close();

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ];

        return response()->download($tempPath, $filename, $headers)->deleteFileAfterSend(true);
    }

    private function getHeaderInfo(): array
    {
        $org = \App\Models\Organization::first();
        $orgName = $org ? strtoupper($org->name) : 'SM INVENTORY';
        $orgAddress = $org ? $org->address : '';

        $filters = request()->input('tableFilters', []);
        $branchId = request()->input('branch_id') ?? ($filters['branch_id']['value'] ?? null);
        if (!$branchId && auth()->check() && auth()->user()?->branch_id) {
            $branchId = auth()->user()->branch_id;
        }

        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;
        if (!$branch && auth()->check() && auth()->user()?->branch_id) {
            $branch = \App\Models\Branch::find(auth()->user()->branch_id);
        }

        $branchName = $branch ? strtoupper($branch->name) : '';
        $headerAddress = $branch ? $branch->address : $orgAddress;

        return [
            'org_name' => $orgName,
            'branch_name' => $branchName,
            'address' => $headerAddress,
        ];
    }

    private function exportGenericData(Writer $writer, array $data): void
    {
        $storeStyle = (new Style())->setFontBold()->setFontSize(13);
        $branchStyle = (new Style())->setFontBold()->setFontSize(11);
        $addressStyle = (new Style())->setFontSize(9);
        $titleStyle = (new Style())->setFontBold()->setFontSize(12);
        $periodStyle = (new Style())->setFontItalic()->setFontSize(10);
        $noteStyle = (new Style())->setFontItalic()->setFontSize(9);
        
        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontSize(10)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('1E293B');

        $dataStyle = (new Style())->setFontSize(10);
        $totalStyle = (new Style())
            ->setFontBold()
            ->setFontSize(10)
            ->setBackgroundColor('E2E8F0');

        $headerInfo = $this->getHeaderInfo();

        // 1. Header Information
        $writer->addRow(Row::fromValues([$headerInfo['org_name']], $storeStyle));
        if (!empty($headerInfo['branch_name'])) {
            $writer->addRow(Row::fromValues([$headerInfo['branch_name']], $branchStyle));
        }
        if (!empty($headerInfo['address'])) {
            $writer->addRow(Row::fromValues([$headerInfo['address']], $addressStyle));
        }
        $writer->addRow(Row::fromValues([]));

        // 2. Report Title & Period
        $title = $data['title'] ?? 'Laporan';
        $period = $data['period'] ?? '';
        $writer->addRow(Row::fromValues([strtoupper($title)], $titleStyle));
        if (!empty($period)) {
            $writer->addRow(Row::fromValues(['Periode : ' . $period], $periodStyle));
        }
        if (!empty($data['note'])) {
            $writer->addRow(Row::fromValues([$data['note']], $noteStyle));
        }
        $writer->addRow(Row::fromValues([]));

        // 3. Table Columns
        $columns = $data['columns'] ?? [];
        $headerCells = [];
        $columnNames = [];
        foreach ($columns as $col) {
            $cleanCol = trim(strip_tags(html_entity_decode((string)$col, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $columnNames[] = $cleanCol;
            $headerCells[] = Cell::fromValue($cleanCol, $headerStyle);
        }
        if (!empty($headerCells)) {
            $writer->addRow(new Row($headerCells));
        }

        // 4. Table Rows
        $rows = $data['rows'] ?? [];
        $rowCount = count($rows);
        foreach ($rows as $index => $row) {
            $isLast = ($index === $rowCount - 1);
            $rowText = implode(' ', array_map('strval', $row));
            $isTotal = $isLast && (str_contains(strtoupper($rowText), 'TOTAL') || str_contains($rowText, '<strong>'));
            if (!$isTotal && str_contains(strtoupper($rowText), 'TOTAL')) {
                $isTotal = true;
            }

            $currentStyle = $isTotal ? $totalStyle : $dataStyle;
            $cells = [];

            $colIdx = 0;
            foreach ($row as $cellValue) {
                $colName = $columnNames[$colIdx] ?? '';
                $val = $this->parseCellValue($cellValue, $colName);
                $cells[] = Cell::fromValue($val, $currentStyle);
                $colIdx++;
            }

            $writer->addRow(new Row($cells));
        }

        // 5. Summary Box (if present)
        if (!empty($data['summaryBox']) && is_array($data['summaryBox'])) {
            $writer->addRow(Row::fromValues([]));
            $summaryHeaderStyle = (new Style())->setFontBold()->setFontSize(10)->setBackgroundColor('F1F5F9');
            $writer->addRow(Row::fromValues(['Rangkuman Total', ''], $summaryHeaderStyle));
            
            $summaryStyle = (new Style())->setFontSize(10);
            foreach ($data['summaryBox'] as $key => $val) {
                $cleanKey = trim(strip_tags(html_entity_decode((string)$key, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                $cleanVal = trim(strip_tags(html_entity_decode((string)$val, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                $writer->addRow(Row::fromValues([$cleanKey, $cleanVal], $summaryStyle));
            }
        }

        // 6. Signatures
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues([]));
        
        $colCount = max(count($columns), 5);
        $sigRow1 = array_fill(0, $colCount, '');
        $sigRow1[0] = 'Mengetahui,';
        $sigRow1[max(1, $colCount - 2)] = 'Dibuat Oleh,';
        $writer->addRow(Row::fromValues($sigRow1));

        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues([]));

        $userName = auth()->check() ? (auth()->user()?->name ?? 'Admin') : 'Admin';
        $sigRow2 = array_fill(0, $colCount, '');
        $sigRow2[0] = 'Staff/SPV';
        $sigRow2[max(1, $colCount - 2)] = $userName;
        $writer->addRow(Row::fromValues($sigRow2));
    }

    private function exportHtmlData(Writer $writer, string $html, array $data): void
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $storeStyle = (new Style())->setFontBold()->setFontSize(13);
        $branchStyle = (new Style())->setFontBold()->setFontSize(11);
        $addressStyle = (new Style())->setFontSize(9);
        $titleStyle = (new Style())->setFontBold()->setFontSize(12);

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontSize(10)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('1E293B');

        $dataStyle = (new Style())->setFontSize(10);
        $totalStyle = (new Style())
            ->setFontBold()
            ->setFontSize(10)
            ->setBackgroundColor('E2E8F0');

        $headerInfo = $this->getHeaderInfo();

        // 1. Header Information
        $writer->addRow(Row::fromValues([$headerInfo['org_name']], $storeStyle));
        if (!empty($headerInfo['branch_name'])) {
            $writer->addRow(Row::fromValues([$headerInfo['branch_name']], $branchStyle));
        }
        if (!empty($headerInfo['address'])) {
            $writer->addRow(Row::fromValues([$headerInfo['address']], $addressStyle));
        }
        $writer->addRow(Row::fromValues([]));

        // 2. Extract Title from HTML
        $titleNode = $xpath->query('//title')->item(0);
        $titleText = $titleNode ? trim($titleNode->textContent) : ($data['title'] ?? 'Laporan');
        if (!empty($titleText)) {
            $writer->addRow(Row::fromValues([strtoupper($titleText)], $titleStyle));
            $writer->addRow(Row::fromValues([]));
        }

        // 3. Extract Tables
        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');
            if ($rows->length === 0) continue;

            $columnNames = [];

            foreach ($rows as $tr) {
                $cells = [];
                $isHeaderRow = false;
                $rowText = trim($tr->textContent);
                $isTotalRow = str_contains(strtoupper($rowText), 'TOTAL') || 
                              str_contains(strtoupper($rowText), 'SALDO') ||
                              str_contains($tr->getAttribute('class'), 'total') ||
                              str_contains($tr->getAttribute('bgcolor'), '#e2e8f0');

                $colIdx = 0;
                foreach ($tr->childNodes as $node) {
                    if (!($node instanceof DOMElement)) continue;
                    $nodeName = strtolower($node->nodeName);
                    if ($nodeName !== 'th' && $nodeName !== 'td') continue;

                    if ($nodeName === 'th') {
                        $isHeaderRow = true;
                    }

                    $rawText = trim(preg_replace('/\s+/', ' ', $node->textContent));
                    $colspan = (int) $node->getAttribute('colspan');
                    if ($colspan < 1) $colspan = 1;

                    if ($isHeaderRow) {
                        $columnNames[$colIdx] = $rawText;
                        $val = $rawText;
                        $cellStyle = $headerStyle;
                    } else {
                        $colName = $columnNames[$colIdx] ?? '';
                        $val = $this->parseCellValue($rawText, $colName);
                        $cellStyle = $isTotalRow ? $totalStyle : $dataStyle;
                    }

                    $cells[] = Cell::fromValue($val, $cellStyle);
                    for ($c = 1; $c < $colspan; $c++) {
                        $cells[] = Cell::fromValue('', $cellStyle);
                        $colIdx++;
                    }
                    $colIdx++;
                }

                if (!empty($cells)) {
                    $writer->addRow(new Row($cells));
                }
            }

            $writer->addRow(Row::fromValues([]));
        }
    }

    public function parseCellValue(mixed $value, string $columnName = ''): mixed
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return '';
        }

        $clean = trim(strip_tags(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($clean === '' || $clean === '-') {
            return $clean;
        }

        $colLower = strtolower($columnName);

        // Keep identifiers as string: barcode, sku, phone/wa, nik, account/rek, id, code/kode, invoice/nota/faktur/struk
        if (preg_match('/(barcode|sku|telepon|phone|hp|wa|nik|rekening|rek|id|kode|code|invoice|faktur|nota|struk)/i', $colLower)) {
            return $clean;
        }

        // If string has leading zero (e.g., '08123', '0123') and is not just '0' or '0.xx' or '0,xx'
        if (preg_match('/^0\d+/', $clean)) {
            return $clean;
        }

        // Check date/time formats like 2026-11-09 or 09/11/2026 or 08:30:00
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $clean) || preg_match('/^\d{2}\/\d{2}\/\d{4}/', $clean) || preg_match('/^\d{2}:\d{2}/', $clean)) {
            return $clean;
        }

        // Currency: "Rp 150.000", "Rp. 1.500.000,50", "Rp -50.000"
        $stripped = preg_replace('/^[Rr][Pp]\.?\s*/u', '', $clean);
        $stripped = trim($stripped);

        // Accounting parentheses negative format: (15.000) -> -15000
        $isNegative = false;
        if (preg_match('/^\((.+)\)$/', $stripped, $matches)) {
            $isNegative = true;
            $stripped = trim($matches[1]);
        }

        // Indonesian thousand separator: 1.500.000 or 1.500.000,50 or 15.000
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $stripped)) {
            $normalized = str_replace('.', '', $stripped);
            $normalized = str_replace(',', '.', $normalized);
            $num = str_contains($normalized, '.') ? (float) $normalized : (int) $normalized;
            return $isNegative ? -$num : $num;
        }

        // English thousand separator: 1,500,000 or 1,500,000.50 or 15,000
        if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $stripped)) {
            $normalized = str_replace(',', '', $stripped);
            $num = str_contains($normalized, '.') ? (float) $normalized : (int) $normalized;
            return $isNegative ? -$num : $num;
        }

        // Plain decimal or integer: 15000, 15000.50, -500
        if (preg_match('/^-?\d+(\.\d+)?$/', $stripped)) {
            $num = str_contains($stripped, '.') ? (float) $stripped : (int) $stripped;
            return $isNegative ? -$num : $num;
        }

        return $clean;
    }
}
