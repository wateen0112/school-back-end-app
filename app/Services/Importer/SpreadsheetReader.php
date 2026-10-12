<?php

namespace App\Services\Importer;

use Illuminate\Http\UploadedFile;
use ZipArchive;

class SpreadsheetReader
{
    public static function rowsFromUploadedFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv' => self::readCsv($file),
            'xlsx' => self::readXlsx($file),
            default => throw new \InvalidArgumentException("النوع غير مدعوم: {$extension}"),
        };
    }

    private static function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            throw new \RuntimeException('لا يمكن فتح الملف');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return ['headers' => [], 'rows' => []];
        }

        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            $firstLine = substr($firstLine, 3);
        }

        $delimiter = self::detectDelimiter($firstLine);

        rewind($handle);
        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            fseek($handle, 3);
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false) {
            fclose($handle);
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map('trim', $headers);
        $rows = [];

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($line) === 1 && trim($line[0]) === '') {
                continue;
            }
            $padded = array_pad($line, count($headers), '');
            $rows[] = array_combine($headers, $padded);
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    private static function detectDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t"];
        $counts = [];

        foreach ($delimiters as $d) {
            $counts[$d] = substr_count($line, $d);
        }

        arsort($counts);

        return array_key_first($counts);
    }

    private static function readXlsx(UploadedFile $file): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($file->getPathname(), ZipArchive::OPEN) !== true) {
            throw new \RuntimeException('لا يمكن فتح ملف XLSX');
        }

        $sharedStrings = self::parseSharedStrings($zip);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('لم يتم العثور على ورقة العمل');
        }

        $sheet = simplexml_load_string($xml);
        if ($sheet === false) {
            throw new \RuntimeException('فشل تحليل ورقة العمل');
        }

        $allRows = [];
        foreach ($sheet->sheetData->row as $rowNode) {
            $rowData = [];
            foreach ($rowNode->c as $cell) {
                $ref = (string) $cell['r'];
                $colLetter = preg_replace('/\d+/', '', $ref);
                $colIndex = self::columnToIndex($colLetter);

                $type = isset($cell['t']) ? (string) $cell['t'] : '';
                $value = '';

                if ($type === 's' && isset($cell->v)) {
                    $idx = (int) (string) $cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $value = (string) $cell->is->t;
                } elseif (isset($cell->v)) {
                    $value = (string) $cell->v;
                }

                $rowData[$colIndex] = $value;
            }
            $allRows[] = $rowData;
        }

        if (empty($allRows)) {
            return ['headers' => [], 'rows' => []];
        }

        ksort($allRows[0]);
        $headers = array_values($allRows[0]);
        $rows = [];

        for ($i = 1; $i < count($allRows); $i++) {
            ksort($allRows[$i]);
            $values = array_values($allRows[$i]);
            $padded = array_pad($values, count($headers), '');
            $rows[] = array_combine($headers, $padded);
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private static function parseSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $dom = simplexml_load_string($xml);
        if ($dom === false) {
            return [];
        }

        $strings = [];
        foreach ($dom->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
            } elseif (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) {
                    if (isset($r->t)) {
                        $text .= (string) $r->t;
                    }
                }
                $strings[] = $text;
            } else {
                $strings[] = '';
            }
        }

        return $strings;
    }

    private static function columnToIndex(string $column): int
    {
        $index = 0;
        $column = strtoupper($column);
        $len = strlen($column);

        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($column[$i]) - 64);
        }

        return $index - 1;
    }
}
