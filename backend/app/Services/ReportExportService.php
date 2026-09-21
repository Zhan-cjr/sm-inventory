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
        foreach ($columns as $col) {
            $cleanCol = trim(strip_tags(html_entity_decode((string)$col, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
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

            foreach ($row as $cellValue) {
                $cleanVal = trim(strip_tags(html_entity_decode((string)$cellValue, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                $cells[] = Cell::fromValue($cleanVal, $currentStyle);
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

            foreach ($rows as $tr) {
                $cells = [];
                $isHeaderRow = false;
                $rowText = trim($tr->textContent);
                $isTotalRow = str_contains(strtoupper($rowText), 'TOTAL') || 
                              str_contains(strtoupper($rowText), 'SALDO') ||
                              str_contains($tr->getAttribute('class'), 'total') ||
                              str_contains($tr->getAttribute('bgcolor'), '#e2e8f0');

                foreach ($tr->childNodes as $node) {
                    if (!($node instanceof DOMElement)) continue;
                    $nodeName = strtolower($node->nodeName);
                    if ($nodeName !== 'th' && $nodeName !== 'td') continue;

                    if ($nodeName === 'th') {
                        $isHeaderRow = true;
                    }

                    $val = trim(preg_replace('/\s+/', ' ', $node->textContent));
                    $colspan = (int) $node->getAttribute('colspan');
                    if ($colspan < 1) $colspan = 1;

                    $cellStyle = $isHeaderRow ? $headerStyle : ($isTotalRow ? $totalStyle : $dataStyle);

                    $cells[] = Cell::fromValue($val, $cellStyle);
                    for ($c = 1; $c < $colspan; $c++) {
                        $cells[] = Cell::fromValue('', $cellStyle);
                    }
                }

                if (!empty($cells)) {
                    $writer->addRow(new Row($cells));
                }
            }

            $writer->addRow(Row::fromValues([]));
        }
    }
}
