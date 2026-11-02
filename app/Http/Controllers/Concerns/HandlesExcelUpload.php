<?php

namespace App\Http\Controllers\Concerns;

use App\Support\ExcelSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Endpoint Upload & DownloadTemplate untuk master yang bisa diimport dari Excel (Siswa, Guru).
 * Pasangannya di view: resources/views/partials/excel-upload.blade.php.
 * Repository mengembalikan ['status', 'message', 'warnings' => string[]].
 */
trait HandlesExcelUpload
{
    protected function excelTemplate(Spreadsheet $spreadsheet, string $fileName): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** @param callable(string $path): array $import */
    protected function excelUpload(Request $request, string $permissionCode, callable $import): JsonResponse
    {
        try {
            if (!$this->hasPermission($permissionCode)) {
                return $this->permissionDenied();
            }

            $file = $request->file('file');
            if ($error = ExcelSheet::validateUploadFile($file)) {
                return response()->json(['status' => false, 'message' => $error]);
            }

            set_time_limit(300);

            return response()->json($import($file->getRealPath()));
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }
}
