<?php

namespace App\Http\Controllers;

use App\Repositories\AcademicYearRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Tahun ajaran + semester Ganjil/Genap dan semester aktif. */
class AcademicYearController extends Controller
{
    public function __construct(private AcademicYearRepo $academicYearRepo)
    {
    }

    public function index()
    {
        return view('academic-year.index', $this->crudPermissions('ACADEMICYEAR'));
    }

    public function search()
    {
        return $this->jsonData(fn () => $this->academicYearRepo->search());
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(fn () => $this->academicYearRepo->getByKey((int) $request->input('academicYearId')), 'Tahun ajaran tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['AcademicYearId', 'YearName', 'ActiveSemester',
            'Semester1Start', 'Semester1End', 'Semester1ReportDate', 'Semester2Start', 'Semester2End', 'Semester2ReportDate']);

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'ACADEMICYEAR',
            fn () => $this->academicYearRepo->insert($data, $this->loginUsername()),
            fn () => $this->academicYearRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::AcademicYearDelete, $ids, fn (int $id) => $this->academicYearRepo->delete($id));
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Tahun Ajaran',
            'TAHUN_AJARAN',
            ['Tahun Ajaran', 'Ganjil Mulai', 'Ganjil Selesai', 'Tanggal Rapor Ganjil', 'Genap Mulai', 'Genap Selesai', 'Tanggal Rapor Genap', 'Semester Aktif'],
            $this->academicYearRepo->search(),
            fn (object $y) => [
                $y->YearName, $y->Semester1Start, $y->Semester1End, $y->Semester1ReportDate,
                $y->Semester2Start, $y->Semester2End, $y->Semester2ReportDate, $y->ActiveSemesterName,
            ],
        );
    }
}
