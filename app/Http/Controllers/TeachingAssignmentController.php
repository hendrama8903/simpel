<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Teacher;
use App\Repositories\TeachingAssignmentRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Tugas mengajar per semester & rombel (lembar kerja guru per mapel). */
class TeachingAssignmentController extends Controller
{
    public function __construct(private TeachingAssignmentRepo $teachingAssignmentRepo)
    {
    }

    public function index()
    {
        return view('teaching-assignment.index', [
            'edit' => $this->hasPermission(PermissionCodes::TeachingAssignmentEdit),
            'semesters' => Academic::semesterOptions(),
            'semesterYears' => \App\Models\Semester::query()->pluck('academic_year_id', 'semester_id'),
            'classrooms' => Classroom::query()->orderBy('grade_level')->orderBy('classroom_name')
                ->get(['classroom_id', 'classroom_name', 'academic_year_id']),
            'teachers' => Teacher::query()->where('is_active', true)->orderBy('teacher_name')->get(['teacher_id', 'teacher_name'])
                ->map(fn ($t) => ['TeacherId' => $t->teacher_id, 'TeacherName' => $t->teacher_name]),
        ]);
    }

    public function sheet(Request $request)
    {
        return $this->jsonData(fn () => $this->teachingAssignmentRepo->sheet((int) $request->input('semesterId'), (int) $request->input('classroomId')));
    }

    public function save(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::TeachingAssignmentEdit)) {
            return $this->permissionDenied();
        }
        $rows = json_decode((string) $request->input('rows', '[]'), true);

        return $this->runJson(fn () => $this->teachingAssignmentRepo->save(
            (int) $request->input('semesterId'),
            (int) $request->input('classroomId'),
            is_array($rows) ? $rows : [],
            $this->loginUsername(),
        ));
    }

    public function copyFromSemester(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::TeachingAssignmentEdit)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->teachingAssignmentRepo->copyFromSemester(
            (int) $request->input('sourceSemesterId'),
            (int) $request->input('targetSemesterId'),
            $this->loginUsername(),
        ));
    }

    public function downloadExcel(Request $request)
    {
        $semester = Academic::semester(((int) $request->input('semesterId')) ?: null);

        return $this->exportExcel(
            'Tugas Mengajar',
            'TUGAS_MENGAJAR',
            ['Guru', 'NIP', 'Rombel', 'Mata Pelajaran'],
            $semester === null ? [] : $this->teachingAssignmentRepo->summary($semester->semester_id),
            fn (object $a) => [$a->TeacherName, $a->Nip, $a->ClassroomName, $a->SubjectName],
        );
    }
}
