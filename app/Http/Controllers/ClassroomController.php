<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Room;
use App\Repositories\ClassroomRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Rombel per tahun ajaran: add/edit/delete, salin dari tahun lain, export Excel. */
class ClassroomController extends Controller
{
    public function __construct(private ClassroomRepo $classroomRepo)
    {
    }

    public function index()
    {
        return view('classroom.index', $this->crudPermissions('CLASSROOM') + [
            'years' => AcademicYear::query()->orderByDesc('year_name')->get(['academic_year_id', 'year_name']),
            'activeYearId' => Academic::activeSemester()?->academic_year_id,
            'rooms' => Room::query()->where('is_active', true)->orderBy('room_code')->get(['room_id', 'room_name']),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->classroomRepo->search(((int) $request->input('academicYearId')) ?: null));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(fn () => $this->classroomRepo->getByKey((int) $request->input('classroomId')), 'Rombel tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['ClassroomId', 'AcademicYearId', 'GradeLevel', 'ClassroomName', 'HomeroomTeacherId', 'RoomId']);

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'CLASSROOM',
            fn () => $this->classroomRepo->insert($data, $this->loginUsername()),
            fn () => $this->classroomRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::ClassroomDelete, $ids, fn (int $id) => $this->classroomRepo->delete($id));
    }

    public function copyFrom(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::ClassroomAdd)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->classroomRepo->copyFrom(
            (int) $request->input('sourceYearId'),
            (int) $request->input('targetYearId'),
            $this->loginUsername(),
        ));
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'Rombel',
            'ROMBEL',
            ['Tahun Ajaran', 'Tingkat', 'Fase', 'Rombel', 'Wali Kelas', 'Ruangan', 'Jumlah Siswa'],
            $this->classroomRepo->search(((int) $request->input('academicYearId')) ?: null),
            fn (object $c) => [$c->YearName, $c->GradeLevel, $c->Phase, $c->ClassroomName, $c->HomeroomTeacherName, $c->RoomName, $c->StudentCount],
        );
    }
}
