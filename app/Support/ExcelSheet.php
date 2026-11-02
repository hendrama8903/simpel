<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Baca/tulis file Excel import (siswa, guru, TP). Kolom dibaca berdasarkan NAMA HEADER di baris 1
 * (urutan bebas, huruf besar/kecil diabaikan), jadi template boleh ditambah kolom lain.
 */
final class ExcelSheet
{
    public static function validateUploadFile(?UploadedFile $file): ?string
    {
        if ($file === null || !$file->isValid()) {
            return 'Pilih file yang akan diunggah.';
        }
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'xls'], true)) {
            return 'Hanya file Excel (.xlsx / .xls) yang diperbolehkan.';
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            return 'Ukuran file terlalu besar (maks. 10 MB).';
        }

        return null;
    }

    /**
     * Template: header (kolom wajib berwarna kuning) + satu baris contoh.
     *
     * @param string[] $columns
     * @param string[] $required
     */
    public static function template(array $columns, array $required, array $example, array $notes = []): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        foreach ($columns as $i => $column) {
            $cell = $sheet->getCell([$i + 1, 1]);
            $cell->setValue($column);
            $style = $cell->getStyle();
            $style->getFont()->setBold(true);
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(in_array($column, $required, true) ? 'FFE699' : 'D9E1F2');

            // Semua kolom teks supaya NIS/NIP/NIK berawalan nol tidak berubah jadi angka.
            $sheet->getStyle([$i + 1, 2, $i + 1, 1000])->getNumberFormat()->setFormatCode('@');
            if (array_key_exists($column, $example)) {
                $sheet->getCell([$i + 1, 2])->setValueExplicit((string) $example[$column], DataType::TYPE_STRING);
            }
            $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
        }

        if ($notes !== []) {
            $info = $spreadsheet->createSheet();
            $info->setTitle('Petunjuk');
            foreach (array_values($notes) as $i => $note) {
                $info->setCellValue('A' . ($i + 1), $note);
            }
            $info->getColumnDimension('A')->setWidth(120);
            $spreadsheet->setActiveSheetIndex(0);
        }

        return $spreadsheet;
    }

    /**
     * Baca sheet pertama. Baris kosong dilewati.
     *
     * @param string[] $columns kolom yang dikenali
     * @param string[] $required kolom wajib ada di header
     * @return array{rows: array<int, array<string, string>>, error: ?string} key rows = nomor baris Excel
     */
    public static function read(string $filePath, array $columns, array $required): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $data = $reader->load($filePath)->getSheet(0)->toArray(null, false, false, false);

        $header = array_shift($data) ?? [];
        $index = [];
        foreach ($header as $i => $name) {
            $key = mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $name)));
            if ($key !== '' && !isset($index[$key])) {
                $index[$key] = $i;
            }
        }

        $missing = array_values(array_filter($required, fn ($c) => !isset($index[mb_strtoupper($c)])));
        if ($missing !== []) {
            return ['rows' => [], 'error' => 'Kolom wajib tidak ditemukan di baris 1: ' . implode(', ', $missing) . '. Gunakan template yang disediakan.'];
        }

        $rows = [];
        foreach ($data as $n => $values) {
            $row = [];
            $filled = false;
            foreach ($columns as $column) {
                $i = $index[mb_strtoupper($column)] ?? null;
                $value = $i === null ? '' : trim((string) ($values[$i] ?? ''));
                $row[$column] = $value;
                $filled = $filled || $value !== '';
            }
            if ($filled) {
                $rows[$n + 2] = $row;
            }
        }

        return ['rows' => $rows, 'error' => null];
    }

    /** Tanggal dari sel Excel: serial number, YYYY-MM-DD, atau DD-MM-YYYY / DD/MM/YYYY. Null kalau kosong/tidak valid. */
    public static function date(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $format) {
            $date = \DateTime::createFromFormat('!' . $format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
