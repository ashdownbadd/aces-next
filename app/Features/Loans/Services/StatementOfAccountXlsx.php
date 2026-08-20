<?php

declare(strict_types=1);

namespace App\Features\Loans\Services;

use RuntimeException;

final class StatementOfAccountXlsx
{
    /**
     * @param array<string, mixed> $soa
     */
    public function build(array $soa): string
    {
        $loan = $soa['loan'];
        $rows = $soa['rows'];

        $xmlRows = [];
        $rowNumber = 1;

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'STATEMENT OF ACCOUNT', true),
            $this->cell(
                'B',
                'as of ' . $this->dateLabel((string) $soa['as_of']),
                false,
            ),
        ]);

        $rowNumber++;

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Name', true),
            $this->cell('B', (string) ($loan['member_name'] ?? '')),
            $this->cell('D', 'Principal', true),
            $this->cell(
                'E',
                $this->number((float) ($loan['principal_amount'] ?? 0)),
            ),
        ]);

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Member ID', true),
            $this->cell(
                'B',
                (string) ($loan['member_number'] ?? $loan['member_id'] ?? ''),
            ),
            $this->cell('D', 'Interest', true),
            $this->cell(
                'E',
                $this->number(array_sum(array_column($rows, 'interest'))),
            ),
        ]);

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Term', true),
            $this->cell(
                'B',
                (string) ($loan['terms_months'] ?? '') . ' months',
            ),
            $this->cell('D', 'Service Charges', true),
            $this->cell(
                'E',
                $this->number((float) ($loan['processing_fee'] ?? 0)),
            ),
        ]);

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Payment Frequency', true),
            $this->cell('B', (string) ($loan['payment_frequency'] ?? '')),
            $this->cell('D', 'Non-finance charges', true),
            $this->cell(
                'E',
                $this->number(
                    (float) ($loan['insurance'] ?? 0)
                    + (float) ($loan['notarial_fee'] ?? 0),
                ),
            ),
        ]);

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Interest Rate/annum', true),
            $this->cell(
                'B',
                $this->number((float) ($loan['interest_rate'] ?? 0)) . '%',
            ),
            $this->cell('D', 'Loan Notes Receivable', true),
            $this->cell(
                'E',
                $this->number(
                    (float) ($loan['principal_amount'] ?? 0)
                    + array_sum(array_column($rows, 'interest')),
                ),
            ),
        ]);

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Date Released', true),
            $this->cell('B', (string) ($loan['release_date'] ?? '')),
            $this->cell('D', 'Periodic Amortization', true),
            $this->cell(
                'E',
                $this->number(
                    (float) ($rows[0]['principal'] ?? 0)
                    + (float) ($rows[0]['interest'] ?? 0),
                ),
            ),
        ]);

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Maturity Date', true),
            $this->cell('B', $this->maturityDate($loan, $rows)),
            $this->cell('D', 'Loan ID', true),
            $this->cell('E', (string) ($loan['id'] ?? '')),
        ]);

        $rowNumber += 2;

        $headers = [
            'Due Date',
            'Principal',
            'Interest',
            'Total Amount Due',
            'Payments',
            'Months past due',
            'Principal (overdue portion)',
            'Interest (overdue portion)',
            'Penalty (3%)',
        ];

        $xmlRows[] = $this->rowXml(
            $rowNumber++,
            array_map(
                fn (string $header, int $index) =>
                    $this->cell(
                        $this->columnName($index),
                        $header,
                        true,
                    ),
                $headers,
                array_keys($headers),
            ),
        );

        foreach ($rows as $row) {
            $xmlRows[] = $this->rowXml($rowNumber++, [
                $this->cell('A', $this->dateLabel($row['due_date']), false),
                $this->cell('B', $this->number($row['principal'])),
                $this->cell('C', $this->number($row['interest'])),
                $this->cell('D', $this->number($row['total_amount_due'])),
                $this->cell('E', $this->number($row['payments'])),
                $this->cell('F', (string) $row['months_past_due']),
                $this->cell(
                    'G',
                    $this->number($row['principal_overdue']),
                ),
                $this->cell(
                    'H',
                    $this->number($row['interest_overdue']),
                ),
                $this->cell('I', $this->number($row['penalty'])),
            ]);
        }

        $rowNumber++;

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Total', true),
            $this->cell(
                'B',
                $this->number(array_sum(array_column($rows, 'principal'))),
            ),
            $this->cell(
                'C',
                $this->number(array_sum(array_column($rows, 'interest'))),
            ),
            $this->cell(
                'D',
                $this->number(
                    array_sum(array_column($rows, 'total_amount_due')),
                ),
            ),
            $this->cell(
                'E',
                $this->number(array_sum(array_column($rows, 'payments'))),
            ),
            $this->cell(
                'G',
                $this->number($soa['total_overdue_principal']),
            ),
            $this->cell(
                'H',
                $this->number($soa['total_overdue_interest']),
            ),
            $this->cell(
                'I',
                $this->number($soa['total_penalty']),
            ),
        ]);

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Grand Total', true),
            $this->cell(
                'I',
                $this->number($soa['grand_total_overdue']),
                true,
            ),
        ]);

        $rowNumber += 2;

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Notes', true),
            $this->cell('B', (string) ($loan['notes'] ?? '')),
        ]);

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Total Receivables', true),
            $this->cell('B', $this->number($soa['total_receivables'])),
        ]);

        $xmlRows[] = $this->rowXml($rowNumber++, [
            $this->cell('A', 'Total Outstanding', true),
            $this->cell('B', $this->number($soa['total_outstanding'])),
        ]);

        $worksheet = $this->worksheetXml(implode('', $xmlRows));
        $styles = $this->stylesXml();

        $entries = [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->relsXml(),
            'xl/workbook.xml' => $this->workbookXml(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelsXml(),
            'xl/worksheets/sheet1.xml' => $worksheet,
            'xl/styles.xml' => $styles,
        ];

        return $this->buildZip($entries);
    }

    /**
     * Create a ZIP container using only PHP string/pack operations.
     *
     * All entries are stored without compression. This keeps XLSX export
     * independent of the ZipArchive extension while remaining a valid XLSX.
     *
     * @param array<string, string> $entries
     */
    private function buildZip(array $entries): string
    {
        $local = '';
        $central = '';
        $offset = 0;
        $count = 0;

        foreach ($entries as $name => $content) {
            $nameBytes = $name;
            $data = $content;
            $crc = (int) sprintf('%u', crc32($data));
            $size = strlen($data);
            $nameLength = strlen($nameBytes);

            $local .= pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
            );
            $local .= $nameBytes;
            $local .= $data;

            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset,
            );
            $central .= $nameBytes;

            $offset = strlen($local);
            $count++;
        }

        $centralSize = strlen($central);
        $centralOffset = strlen($local);

        $end = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $count,
            $count,
            $centralSize,
            $centralOffset,
            0,
        );

        return $local . $central . $end;
    }

    private function number(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function dateLabel(string $value): string
    {
        $timestamp = strtotime($value);

        return $timestamp === false
            ? $value
            : date('M d, Y', $timestamp);
    }

    private function maturityDate(
        array $loan,
        array $rows,
    ): string {
        if ($rows !== []) {
            return $this->dateLabel((string) $rows[array_key_last($rows)]['due_date']);
        }

        return '';
    }

    private function columnName(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function cell(
        string $column,
        string $value,
        bool $bold = false,
    ): string {
        $style = $bold ? 1 : 0;
        $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<c r="%s" s="%d" t="inlineStr"><is><t>%s</t></is></c>',
            $column,
            $style,
            $escaped,
        );
    }

    private function rowXml(int $rowNumber, array $cells): string
    {
        return sprintf(
            '<row r="%d">%s</row>',
            $rowNumber,
            implode('', $cells),
        );
    }

    private function worksheetXml(string $rows): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>'
            . $rows
            . '</sheetData>'
            . '</worksheet>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0"/>'
            . '</cellXfs>'
            . '</styleSheet>';
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

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Statement of Account" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }
}
