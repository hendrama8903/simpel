<?php

namespace App\Repositories;

use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentExit;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Mutasi keluar & kelulusan. Mencatat siswa keluar mengubah students.status sesuai jenisnya;
 * menghapus catatan mengembalikan status siswa ke AKTIF.
 */
class StudentExitRepo
{
    public const Types = ['MUTASI' => 'Mutasi (pindah sekolah)', 'LULUS' => 'Lulus', 'KELUAR' => 'Keluar / putus sekolah'];

    /** @return object[] */
    public function search(?string $exitType, ?int $year): array
    {
        $exits = StudentExit::query()->with('student')
            ->when($exitType, fn ($q) => $q->where('exit_type', $exitType))
            ->when($year, fn ($q) => $q->whereYear('exit_date', $year))
            ->orderByDesc('exit_date')
            ->get();
        $lastClassrooms = $this->lastClassrooms($exits->pluck('student_id')->all());

        return $exits->map(fn (StudentExit $e) => $this->mapExit($e, $lastClassrooms))->all();
    }

    public function getByKey(int $studentExitId): ?object
    {
        $exit = StudentExit::query()->with('student')->find($studentExitId);

        return $exit === null ? null : $this->mapExit($exit, $this->lastClassrooms([$exit->student_id]));
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($error = $this->validate($fields)) {
            return new DBResult(false, $error);
        }
        $student = Student::query()->find((int) ($data['StudentId'] ?? 0));
        if ($student === null) {
            return new DBResult(false, 'Siswa wajib dipilih.');
        }
        if ($student->status !== 'AKTIF') {
            return new DBResult(false, "{$student->student_name} sudah berstatus " . (StudentRepo::Statuses[$student->status] ?? $student->status) . '.');
        }

        return DB::transaction(function () use ($student, $fields, $loginUser) {
            $exit = StudentExit::create($fields + ['student_id' => $student->student_id, 'created_by' => $loginUser]);
            $student->update(['status' => $fields['exit_type'], 'updated_by' => $loginUser]);

            return new DBResult(true, 'Data berhasil disimpan.', $exit->student_exit_id);
        });
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $exit = StudentExit::query()->with('student')->find($data['StudentExitId'] ?? 0);
        if ($exit === null) {
            return new DBResult(false, 'Data mutasi tidak ditemukan.');
        }
        $fields = $this->fields($data);
        if ($error = $this->validate($fields)) {
            return new DBResult(false, $error);
        }

        // Siswa tidak bisa diganti; hapus lalu buat ulang bila salah pilih siswa.
        return DB::transaction(function () use ($exit, $fields, $loginUser) {
            $exit->update($fields + ['updated_by' => $loginUser]);
            $exit->student->update(['status' => $fields['exit_type'], 'updated_by' => $loginUser]);

            return new DBResult(true, 'Data berhasil diperbarui.');
        });
    }

    /** Batalkan mutasi/kelulusan: status siswa kembali AKTIF. */
    public function delete(int $studentExitId, string $loginUser): DBResult
    {
        $exit = StudentExit::query()->with('student')->find($studentExitId);
        if ($exit === null) {
            return new DBResult(false, 'Data mutasi tidak ditemukan.');
        }

        DB::transaction(function () use ($exit, $loginUser) {
            $exit->student->update(['status' => 'AKTIF', 'updated_by' => $loginUser]);
            $exit->delete();
        });

        return new DBResult(true, 'Data dihapus, status siswa kembali Aktif.');
    }

    /** Luluskan semua siswa AKTIF di satu rombel (kelas 6). */
    public function graduateClassroom(int $classroomId, ?string $exitDate, string $loginUser): DBResult
    {
        $classroom = Classroom::query()->find($classroomId);
        if ($classroom === null) {
            return new DBResult(false, 'Rombel tidak ditemukan.');
        }
        if ($classroom->grade_level !== 6) {
            return new DBResult(false, 'Kelulusan massal hanya untuk rombel kelas 6.');
        }
        if ($exitDate === null || strtotime($exitDate) === false) {
            return new DBResult(false, 'Tanggal lulus wajib diisi.');
        }

        $students = Student::query()->where('status', 'AKTIF')
            ->whereIn('student_id', ClassroomStudent::query()->where('classroom_id', $classroomId)->select('student_id'))->get();
        if ($students->isEmpty()) {
            return new DBResult(false, "Tidak ada siswa aktif di rombel {$classroom->classroom_name}.");
        }

        DB::transaction(function () use ($students, $exitDate, $loginUser) {
            foreach ($students as $student) {
                StudentExit::create(['student_id' => $student->student_id, 'exit_type' => 'LULUS', 'exit_date' => $exitDate, 'created_by' => $loginUser]);
                $student->update(['status' => 'LULUS', 'updated_by' => $loginUser]);
            }
        });

        return new DBResult(true, $students->count() . " siswa rombel {$classroom->classroom_name} dinyatakan lulus.");
    }

    /** Rombel terakhir tiap siswa (tahun ajaran terbaru): [student_id => classroom_name]. */
    private function lastClassrooms(array $studentIds): array
    {
        return ClassroomStudent::query()
            ->join('classrooms', 'classrooms.classroom_id', '=', 'classroom_students.classroom_id')
            ->join('academic_years', 'academic_years.academic_year_id', '=', 'classrooms.academic_year_id')
            ->whereIn('classroom_students.student_id', $studentIds)
            ->orderBy('academic_years.year_name')
            ->get(['classroom_students.student_id', 'classrooms.classroom_name', 'academic_years.year_name'])
            ->mapWithKeys(fn ($r) => [$r->student_id => $r->classroom_name . ' (' . $r->year_name . ')'])
            ->all();
    }

    private function fields(array $data): array
    {
        $text = fn (string $key, int $max) => ($v = trim((string) ($data[$key] ?? ''))) === '' ? null : mb_substr($v, 0, $max);

        return [
            'exit_type' => (string) ($data['ExitType'] ?? ''),
            'exit_date' => $text('ExitDate', 10),
            'destination_school' => $text('DestinationSchool', 150),
            'reason' => $text('Reason', 255),
        ];
    }

    private function validate(array $fields): ?string
    {
        return match (true) {
            !isset(self::Types[$fields['exit_type']]) => 'Jenis wajib dipilih.',
            $fields['exit_date'] === null || strtotime($fields['exit_date']) === false => 'Tanggal wajib diisi.',
            $fields['exit_type'] === 'MUTASI' && $fields['destination_school'] === null => 'Sekolah tujuan wajib diisi untuk mutasi.',
            default => null,
        };
    }

    private function mapExit(StudentExit $e, array $lastClassrooms): object
    {
        return (object) [
            'StudentExitId' => $e->student_exit_id,
            'StudentId' => $e->student_id,
            'Nis' => $e->student?->nis,
            'StudentName' => $e->student?->student_name,
            'StudentText' => $e->student === null ? null : $e->student->nis . ' - ' . $e->student->student_name,
            'LastClassroom' => $lastClassrooms[$e->student_id] ?? null,
            'ExitType' => $e->exit_type,
            'ExitTypeName' => self::Types[$e->exit_type] ?? $e->exit_type,
            'ExitDate' => $e->exit_date?->format('Y-m-d'),
            'DestinationSchool' => $e->destination_school,
            'Reason' => $e->reason,
            'CreatedBy' => $e->created_by,
            'CreatedDate' => $e->created_at,
            'ModifiedBy' => $e->updated_by,
            'ModifiedDate' => $e->updated_at,
        ];
    }
}
