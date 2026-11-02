<?php

namespace App\Repositories;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Support\Academic;
use App\Support\AuditTrail;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Jadwal pelajaran per rombel & semester, diisi sebagai matriks: baris = jam ke-, kolom = hari (D1..D6 berisi subject_id).
 * Guru tiap sel diambil dari Tugas Mengajar; mapel "guru kelas" tanpa penugasan = wali kelas.
 */
class ScheduleRepo
{
    public const DefaultPeriods = 8;

    /** @return object[] satu baris per jam ke- */
    public function sheet(int $semesterId, int $classroomId): array
    {
        $schedules = Schedule::query()->where('semester_id', $semesterId)->where('classroom_id', $classroomId)->get();
        $maxPeriod = max(self::DefaultPeriods, (int) $schedules->max('period_no'));

        $rows = [];
        for ($p = 1; $p <= $maxPeriod; $p++) {
            $inPeriod = $schedules->where('period_no', $p);
            $first = $inPeriod->first();
            $row = [
                'PeriodNo' => $p,
                'StartTime' => $first === null ? null : substr($first->start_time, 0, 5),
                'EndTime' => $first === null ? null : substr($first->end_time, 0, 5),
            ];
            foreach (Academic::Days as $day => $name) {
                $row["D{$day}"] = $inPeriod->firstWhere('day_of_week', $day)?->subject_id;
            }
            $rows[] = (object) $row;
        }

        return $rows;
    }

    /** Simpan seluruh matriks (jadwal lama rombel tsb diganti). Bentrok guru dilaporkan sebagai peringatan. */
    public function save(int $semesterId, int $classroomId, array $rows, string $loginUser): DBResult
    {
        $semester = Semester::query()->find($semesterId);
        $classroom = Classroom::query()->find($classroomId);
        if ($semester === null || $classroom === null || $classroom->academic_year_id !== $semester->academic_year_id) {
            return new DBResult(false, 'Semester atau rombel tidak valid.');
        }

        $subjects = Subject::query()->pluck('taught_by', 'subject_id');
        $assigned = TeachingAssignment::query()->where('semester_id', $semesterId)->where('classroom_id', $classroomId)->pluck('teacher_id', 'subject_id');
        $records = [];
        foreach ($rows as $row) {
            $period = (int) ($row['PeriodNo'] ?? 0);
            $start = $this->time($row['StartTime'] ?? null);
            $end = $this->time($row['EndTime'] ?? null);
            $hasSubject = false;
            foreach (Academic::Days as $day => $name) {
                $subjectId = (int) ($row["D{$day}"] ?? 0);
                if ($subjectId === 0) {
                    continue;
                }
                if (!isset($subjects[$subjectId])) {
                    return new DBResult(false, "Jam ke-{$period} {$name}: mapel tidak valid.");
                }
                $hasSubject = true;
                $records[] = [
                    'day_of_week' => $day,
                    'period_no' => $period,
                    'start_time' => $start,
                    'end_time' => $end,
                    'subject_id' => $subjectId,
                    'teacher_id' => $assigned[$subjectId] ?? ($subjects[$subjectId] === 'GURU_KELAS' ? $classroom->homeroom_teacher_id : null),
                ];
            }
            if ($hasSubject && ($period < 1 || $start === null || $end === null)) {
                return new DBResult(false, "Jam ke-{$period}: jam mulai dan selesai wajib diisi (format HH:MM).");
            }
            if ($hasSubject && $start >= $end) {
                return new DBResult(false, "Jam ke-{$period}: jam mulai harus sebelum jam selesai.");
            }
        }

        DB::transaction(function () use ($semesterId, $classroomId, $records, $loginUser) {
            Schedule::query()->where('semester_id', $semesterId)->where('classroom_id', $classroomId)->delete();
            foreach ($records as $r) {
                Schedule::query()->insert($r + ['semester_id' => $semesterId, 'classroom_id' => $classroomId, 'created_by' => $loginUser, 'created_at' => now()]);
            }
        });
        AuditTrail::log('UPDATE', 'Jadwal', (string) $classroomId, $classroom->classroom_name,
            "Jadwal {$classroom->classroom_name} " . Academic::semesterLabel($semester) . ' disimpan (' . count($records) . ' jam pelajaran)');

        $conflicts = $this->conflicts($semesterId, $classroomId);

        return new DBResult(true, "Jadwal rombel {$classroom->classroom_name} berhasil disimpan."
            . ($conflicts === [] ? '' : "\n\nPerhatian, guru bentrok:\n" . implode("\n", $conflicts)));
    }

    /** Data cetak: [periods => object[], subjects => [id => name], teachers => [subject_id => nama guru]]. */
    public function printData(int $semesterId, int $classroomId): array
    {
        $schedules = Schedule::query()->with(['subject', 'teacher'])->where('semester_id', $semesterId)->where('classroom_id', $classroomId)->get();

        return [
            'periods' => $this->sheet($semesterId, $classroomId),
            'subjects' => $schedules->mapWithKeys(fn (Schedule $s) => [$s->subject_id => $s->subject->subject_name])->all(),
            'teachers' => $schedules->filter(fn (Schedule $s) => $s->teacher !== null)
                ->mapWithKeys(fn (Schedule $s) => [$s->subject_id => $s->teacher->teacher_name])->all(),
        ];
    }

    /** Guru yang di hari & waktu yang sama juga mengajar di rombel lain. */
    private function conflicts(int $semesterId, int $classroomId): array
    {
        return Schedule::query()->from('schedules AS a')
            ->join('schedules AS b', function ($join) {
                $join->on('a.semester_id', '=', 'b.semester_id')->on('a.teacher_id', '=', 'b.teacher_id')
                    ->on('a.day_of_week', '=', 'b.day_of_week')->on('a.classroom_id', '!=', 'b.classroom_id')
                    ->whereColumn('a.start_time', '<', 'b.end_time')->whereColumn('b.start_time', '<', 'a.end_time');
            })
            ->join('teachers', 'teachers.teacher_id', '=', 'a.teacher_id')
            ->join('classrooms', 'classrooms.classroom_id', '=', 'b.classroom_id')
            ->where('a.semester_id', $semesterId)->where('a.classroom_id', $classroomId)
            ->orderBy('a.day_of_week')->orderBy('a.period_no')
            ->get(['teachers.teacher_name', 'a.day_of_week', 'a.period_no', 'classrooms.classroom_name'])
            ->map(fn ($r) => Academic::Days[$r->day_of_week] . " jam ke-{$r->period_no}: {$r->teacher_name} juga mengajar di {$r->classroom_name}")
            ->unique()->values()->all();
    }

    private function time(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value . ':00' : null;
    }
}
