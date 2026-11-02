<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use App\Support\Academic;
use App\Support\AuditTrail;
use App\Support\DBResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Presensi harian siswa per rombel (H/S/I/A). Baris tanpa status = belum dicatat (tidak disimpan).
 * Siswa yang sudah keluar (mutasi/lulus) sebelum tanggal tsb tidak ditampilkan.
 */
class AttendanceRepo
{
    /** @return object[] */
    public function sheet(int $classroomId, string $date): array
    {
        $records = Attendance::query()->where('classroom_id', $classroomId)->where('attendance_date', $date)->get()->keyBy('student_id');

        return $this->members($classroomId, $date)
            ->map(fn (Student $s) => (object) [
                'StudentId' => $s->student_id,
                'Nis' => $s->nis,
                'StudentName' => $s->student_name,
                'Gender' => $s->gender,
                'Status' => $records->get($s->student_id)?->status,
                'Note' => $records->get($s->student_id)?->note,
            ])
            ->values()
            ->all();
    }

    /** @param array<int, array{StudentId: int, Status: ?string, Note: ?string}> $rows */
    public function save(int $classroomId, string $date, array $rows, string $loginUser): DBResult
    {
        $classroom = Classroom::query()->find($classroomId);
        if ($classroom === null) {
            return new DBResult(false, 'Rombel tidak ditemukan.');
        }
        if ($error = $this->validateDate($date)) {
            return new DBResult(false, $error);
        }
        $memberIds = $this->members($classroomId, $date)->pluck('student_id')->all();

        $saved = 0;
        DB::transaction(function () use ($rows, $memberIds, $classroomId, $date, $loginUser, &$saved) {
            foreach ($rows as $row) {
                $studentId = (int) ($row['StudentId'] ?? 0);
                $status = strtoupper(trim((string) ($row['Status'] ?? '')));
                if (!in_array($studentId, $memberIds, true)) {
                    continue;
                }
                $existing = Attendance::query()->where('student_id', $studentId)->where('attendance_date', $date)->first();
                if ($status === '') {
                    $existing?->delete();
                    continue;
                }
                if (!isset(Academic::AttendanceStatuses[$status])) {
                    continue;
                }
                $values = ['classroom_id' => $classroomId, 'status' => $status, 'note' => mb_substr(trim((string) ($row['Note'] ?? '')), 0, 255) ?: null];
                if ($existing === null) {
                    Attendance::create($values + ['student_id' => $studentId, 'attendance_date' => $date, 'created_by' => $loginUser]);
                } else {
                    $existing->update($values + ['updated_by' => $loginUser]);
                }
                $saved++;
            }
        });
        AuditTrail::log('UPDATE', 'Presensi', (string) $classroomId, $classroom->classroom_name,
            "Presensi {$classroom->classroom_name} tanggal {$date} disimpan ({$saved} siswa)");

        return new DBResult(true, "Presensi {$classroom->classroom_name} tanggal " . Academic::dateText($date) . ' berhasil disimpan.');
    }

    /**
     * Rekap satu bulan: satu baris per siswa, kolom D1..D31 berisi status, plus jumlah H/S/I/A.
     *
     * @return array{days: int, rows: object[]}
     */
    public function recap(int $classroomId, string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $records = Attendance::query()->where('classroom_id', $classroomId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])->get()->groupBy('student_id');

        $students = Student::query()
            ->whereIn('student_id', DB::table('classroom_students')->where('classroom_id', $classroomId)->select('student_id'))
            ->orderBy('student_name')->get();

        $rows = $students->map(function (Student $s) use ($records, $start) {
            $row = ['StudentId' => $s->student_id, 'Nis' => $s->nis, 'StudentName' => $s->student_name, 'H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
            foreach ($records->get($s->student_id, collect()) as $a) {
                $row['D' . $a->attendance_date->day] = $a->status;
                $row[$a->status]++;
            }
            $total = $row['H'] + $row['S'] + $row['I'] + $row['A'];
            $row['Percent'] = $total === 0 ? null : round($row['H'] / $total * 100, 1);

            return (object) $row;
        })->all();

        return ['days' => $start->daysInMonth, 'month' => $start->locale('id')->translatedFormat('F Y'), 'rows' => $rows];
    }

    /** Jumlah S/I/A per siswa dalam rentang tanggal (untuk rapor). @return array<int, array{S: int, I: int, A: int}> */
    public function totals(int $classroomId, string $from, string $to): array
    {
        return Attendance::query()->where('classroom_id', $classroomId)->whereBetween('attendance_date', [$from, $to])
            ->selectRaw('student_id, status, COUNT(*) AS total')->groupBy('student_id', 'status')->get()
            ->groupBy('student_id')
            ->map(fn ($g) => ['S' => (int) ($g->firstWhere('status', 'S')?->total ?? 0), 'I' => (int) ($g->firstWhere('status', 'I')?->total ?? 0),
                'A' => (int) ($g->firstWhere('status', 'A')?->total ?? 0)])
            ->all();
    }

    private function validateDate(string $date): ?string
    {
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);

        return match (true) {
            $parsed === false || $parsed->format('Y-m-d') !== $date => 'Tanggal tidak valid.',
            $date > now()->toDateString() => 'Presensi tidak bisa diisi untuk tanggal yang akan datang.',
            default => null,
        };
    }

    /** Anggota rombel yang masih aktif pada tanggal tsb. */
    private function members(int $classroomId, string $date)
    {
        return Student::query()
            ->whereIn('student_id', DB::table('classroom_students')->where('classroom_id', $classroomId)->select('student_id'))
            ->whereNotIn('student_id', DB::table('student_exits')->where('exit_date', '<=', $date)->select('student_id'))
            ->orderBy('student_name')
            ->get();
    }
}
