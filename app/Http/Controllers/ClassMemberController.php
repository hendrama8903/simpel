<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Repositories\ClassMemberRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Anggota rombel: memasukkan / mengeluarkan siswa, termasuk kenaikan kelas dari rombel tahun lalu. */
class ClassMemberController extends Controller
{
    public function __construct(private ClassMemberRepo $classMemberRepo)
    {
    }

    public function index()
    {
        $classrooms = Classroom::query()->with('academicYear')
            ->join('academic_years', 'academic_years.academic_year_id', '=', 'classrooms.academic_year_id')
            ->orderByDesc('academic_years.year_name')->orderBy('classrooms.grade_level')->orderBy('classrooms.classroom_name')
            ->get(['classrooms.*'])
            ->map(fn (Classroom $c) => [
                'ClassroomId' => $c->classroom_id,
                'AcademicYearId' => $c->academic_year_id,
                'YearName' => $c->academicYear->year_name,
                'ClassroomName' => $c->classroom_name,
                'GradeLevel' => $c->grade_level,
            ]);

        return view('class-member.index', [
            'edit' => $this->hasPermission(PermissionCodes::ClassMemberEdit),
            'years' => AcademicYear::query()->orderByDesc('year_name')->get(['academic_year_id', 'year_name']),
            'activeYearId' => Academic::activeSemester()?->academic_year_id,
            'classrooms' => $classrooms,
        ]);
    }

    public function members(Request $request)
    {
        return $this->jsonData(fn () => $this->classMemberRepo->members((int) $request->input('classroomId')));
    }

    public function candidates(Request $request)
    {
        return $this->jsonData(fn () => $this->classMemberRepo->candidates(
            (int) $request->input('classroomId'),
            ((int) $request->input('sourceClassroomId')) ?: null,
        ));
    }

    public function add(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::ClassMemberEdit)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->classMemberRepo->add(
            (int) $request->input('classroomId'),
            array_map('intval', (array) $request->input('studentIds', [])),
            $this->loginUsername(),
        ));
    }

    public function remove(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::ClassMemberEdit)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->classMemberRepo->remove(
            (int) $request->input('classroomId'),
            array_map('intval', (array) $request->input('studentIds', [])),
            $this->loginUsername(),
        ));
    }
}
