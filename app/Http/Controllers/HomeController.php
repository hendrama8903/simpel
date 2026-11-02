<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\Academic;
use App\Support\SessionAuth;

/** Dashboard: ringkasan sekolah, presensi hari ini, jadwal mengajar hari ini (untuk guru). */
class HomeController extends Controller
{
    public function index()
    {
        $semester = Academic::activeSemester();
        $yearId = $semester?->academic_year_id;
        $today = now()->toDateString();
        $teacherId = Academic::teacherId();

        $stats = null;
        $attendance = [];
        if (SessionAuth::hasMenu('/Student/Index') || SessionAuth::hasMenu('/Attendance/Index')) {
            $classroomIds = $yearId === null ? collect() : Classroom::query()->where('academic_year_id', $yearId)->pluck('classroom_id');
            $stats = [
                'students' => Student::query()->where('status', 'AKTIF')->count(),
                'teachers' => Teacher::query()->where('is_active', true)->count(),
                'classrooms' => $classroomIds->count(),
                'unplaced' => $yearId === null ? 0 : Student::query()->where('status', 'AKTIF')
                    ->whereNotIn('student_id', ClassroomStudent::query()->whereIn('classroom_id', $classroomIds)->select('student_id'))->count(),
            ];
        }

        // Presensi hari ini per rombel yang boleh dilihat user di menu Presensi.
        if ($semester !== null && SessionAuth::hasMenu('/Attendance/Index')) {
            $classrooms = Academic::classrooms('ATTENDANCE', $semester);
            $ids = array_map(fn ($c) => $c->ClassroomId, $classrooms);
            $members = ClassroomStudent::query()->whereIn('classroom_id', $ids)->selectRaw('classroom_id, COUNT(*) AS total')
                ->groupBy('classroom_id')->pluck('total', 'classroom_id');
            $recorded = Attendance::query()->whereIn('classroom_id', $ids)->where('attendance_date', $today)
                ->selectRaw('classroom_id, status, COUNT(*) AS total')->groupBy('classroom_id', 'status')->get()->groupBy('classroom_id');
            foreach ($classrooms as $c) {
                $counts = ($recorded[$c->ClassroomId] ?? collect())->pluck('total', 'status');
                $attendance[] = (object) [
                    'ClassroomName' => $c->ClassroomName,
                    'Members' => (int) ($members[$c->ClassroomId] ?? 0),
                    'Recorded' => (int) $counts->sum(),
                    'H' => (int) ($counts['H'] ?? 0), 'S' => (int) ($counts['S'] ?? 0), 'I' => (int) ($counts['I'] ?? 0), 'A' => (int) ($counts['A'] ?? 0),
                ];
            }
        }

        // Jadwal mengajar hari ini (guru yang terhubung ke akun login).
        $todaySchedule = [];
        if ($semester !== null && $teacherId !== null && now()->dayOfWeekIso <= 6) {
            $todaySchedule = Schedule::query()->with(['classroom', 'subject'])
                ->where('semester_id', $semester->semester_id)->where('day_of_week', now()->dayOfWeekIso)
                ->where(fn ($q) => $q->where('teacher_id', $teacherId)->orWhere(fn ($q2) => $q2->whereNull('teacher_id')
                    ->whereIn('classroom_id', Classroom::query()->where('homeroom_teacher_id', $teacherId)->select('classroom_id'))))
                ->orderBy('start_time')->get();
        }

        return view('home.index', [
            'semesterLabel' => Academic::semesterLabel($semester),
            'hasSemester' => $semester !== null,
            'stats' => $stats,
            'attendance' => $attendance,
            'todaySchedule' => $todaySchedule,
            'isTeacher' => $teacherId !== null,
        ]);
    }
}
