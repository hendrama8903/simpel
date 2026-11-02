<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Subject;
use App\Repositories\ScheduleRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** Jadwal pelajaran per rombel (matriks jam x hari) + cetak PDF. */
class ScheduleController extends Controller
{
    private const Prefix = 'SCHEDULE';

    public function __construct(private ScheduleRepo $scheduleRepo)
    {
    }

    public function index()
    {
        return view('schedule.index', [
            'edit' => $this->hasPermission(PermissionCodes::ScheduleEdit),
            'semesters' => Academic::semesterOptions(),
            'days' => Academic::Days,
            'subjects' => Subject::query()->where('is_active', true)->orderBy('report_order')->orderBy('subject_name')
                ->get(['subject_id', 'subject_code', 'subject_name'])
                ->map(fn ($s) => ['SubjectId' => $s->subject_id, 'SubjectName' => $s->subject_name, 'SubjectCode' => $s->subject_code]),
        ]);
    }

    public function classrooms(Request $request)
    {
        return $this->jsonData(fn () => Academic::classrooms(self::Prefix, Academic::semester((int) $request->input('semesterId'))));
    }

    public function sheet(Request $request)
    {
        return $this->jsonData(function () use ($request) {
            [$semester, $classroomId] = [Academic::semester((int) $request->input('semesterId')), (int) $request->input('classroomId')];

            return Academic::canAccessClassroom(self::Prefix, $semester, $classroomId) ? $this->scheduleRepo->sheet($semester->semester_id, $classroomId) : null;
        }, 'Rombel tidak ditemukan atau Anda tidak punya akses.');
    }

    public function save(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::ScheduleEdit)) {
            return $this->permissionDenied();
        }
        $semester = Academic::semester((int) $request->input('semesterId'));
        $classroomId = (int) $request->input('classroomId');
        if (!Academic::canAccessClassroom(self::Prefix, $semester, $classroomId)) {
            return $this->permissionDenied();
        }
        $rows = json_decode((string) $request->input('rows', '[]'), true);

        return $this->runJson(fn () => $this->scheduleRepo->save($semester->semester_id, $classroomId, is_array($rows) ? $rows : [], $this->loginUsername()));
    }

    public function print(Request $request)
    {
        $semester = Academic::semester((int) $request->input('semesterId'));
        $classroomId = (int) $request->input('classroomId');
        abort_unless(Academic::canAccessClassroom(self::Prefix, $semester, $classroomId), 403);
        $classroom = Classroom::query()->with('homeroomTeacher')->findOrFail($classroomId);

        return Pdf::loadView('schedule.pdf', $this->scheduleRepo->printData($semester->semester_id, $classroomId) + [
            'school' => Academic::school(),
            'classroom' => $classroom,
            'semesterLabel' => Academic::semesterLabel($semester),
            'days' => Academic::Days,
        ])->setPaper('a4', 'landscape')->stream("Jadwal_{$classroom->classroom_name}.pdf");
    }
}
