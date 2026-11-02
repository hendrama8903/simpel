<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Repositories\StudentExitRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Mutasi keluar & kelulusan siswa (mengubah status siswa). */
class StudentExitController extends Controller
{
    public function __construct(private StudentExitRepo $studentExitRepo)
    {
    }

    public function index()
    {
        $semester = Academic::activeSemester();

        return view('student-exit.index', $this->crudPermissions('STUDENTEXIT') + [
            'graduationClassrooms' => $semester === null ? collect() : Classroom::query()
                ->where('academic_year_id', $semester->academic_year_id)->where('grade_level', 6)
                ->orderBy('classroom_name')->get(['classroom_id', 'classroom_name']),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->studentExitRepo->search(
            $request->input('exitType') ?: null,
            ((int) $request->input('year')) ?: null,
        ));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(fn () => $this->studentExitRepo->getByKey((int) $request->input('studentExitId')), 'Data mutasi tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['StudentExitId', 'StudentId', 'ExitType', 'ExitDate', 'DestinationSchool', 'Reason']);

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'STUDENTEXIT',
            fn () => $this->studentExitRepo->insert($data, $this->loginUsername()),
            fn () => $this->studentExitRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::StudentExitDelete, $ids, fn (int $id) => $this->studentExitRepo->delete($id, $this->loginUsername()));
    }

    public function graduate(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::StudentExitAdd)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->studentExitRepo->graduateClassroom(
            (int) $request->input('classroomId'),
            $request->input('exitDate') ?: null,
            $this->loginUsername(),
        ));
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'Mutasi & Kelulusan',
            'MUTASI',
            ['NIS', 'Nama', 'Rombel Terakhir', 'Jenis', 'Tanggal', 'Sekolah Tujuan', 'Keterangan'],
            $this->studentExitRepo->search($request->input('exitType') ?: null, ((int) $request->input('year')) ?: null),
            fn (object $e) => [$e->Nis, $e->StudentName, $e->LastClassroom, $e->ExitTypeName, $e->ExitDate, $e->DestinationSchool, $e->Reason],
        );
    }
}
