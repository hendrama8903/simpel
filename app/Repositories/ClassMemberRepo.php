<?php

namespace App\Repositories;

use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Support\AuditTrail;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Anggota rombel. Satu siswa AKTIF hanya boleh di satu rombel per tahun ajaran.
 * Kenaikan kelas = memasukkan anggota rombel tahun lalu ke rombel tingkat berikutnya di tahun baru.
 */
class ClassMemberRepo
{
    /** @return object[] */
    public function members(int $classroomId): array
    {
        return Student::query()
            ->join('classroom_students', 'classroom_students.student_id', '=', 'students.student_id')
            ->where('classroom_students.classroom_id', $classroomId)
            ->orderBy('students.student_name')
            ->get(['students.*'])
            ->map(fn (Student $s) => $this->mapStudent($s))
            ->all();
    }

    /**
     * Siswa AKTIF yang belum punya rombel di tahun ajaran rombel tujuan.
     * $sourceClassroomId: hanya anggota rombel tsb (mis. rombel tahun lalu untuk kenaikan kelas).
     *
     * @return object[]
     */
    public function candidates(int $classroomId, ?int $sourceClassroomId): array
    {
        $classroom = Classroom::query()->find($classroomId);
        if ($classroom === null) {
            return [];
        }

        return Student::query()
            ->where('status', 'AKTIF')
            ->whereNotIn('student_id', $this->placedQuery($classroom->academic_year_id))
            ->when($sourceClassroomId, fn ($q) => $q->whereIn('student_id',
                ClassroomStudent::query()->where('classroom_id', $sourceClassroomId)->select('student_id')))
            ->orderBy('student_name')
            ->get()
            ->map(fn (Student $s) => $this->mapStudent($s))
            ->all();
    }

    public function add(int $classroomId, array $studentIds, string $loginUser): DBResult
    {
        $classroom = Classroom::query()->find($classroomId);
        if ($classroom === null) {
            return new DBResult(false, 'Rombel tidak ditemukan.');
        }

        $placed = ClassroomStudent::query()->whereIn('student_id', $studentIds)
            ->whereIn('classroom_id', Classroom::query()->where('academic_year_id', $classroom->academic_year_id)->select('classroom_id'))
            ->pluck('student_id')->all();
        $students = Student::query()->whereIn('student_id', $studentIds)->get();
        $errors = [];
        $added = 0;

        DB::transaction(function () use ($students, $placed, $classroom, $loginUser, &$errors, &$added) {
            foreach ($students as $s) {
                if ($s->status !== 'AKTIF') {
                    $errors[] = "{$s->student_name}: status siswa bukan Aktif.";
                } elseif (in_array($s->student_id, $placed, true)) {
                    $errors[] = "{$s->student_name}: sudah punya rombel di tahun ajaran ini.";
                } else {
                    ClassroomStudent::create(['classroom_id' => $classroom->classroom_id, 'student_id' => $s->student_id, 'created_by' => $loginUser]);
                    $added++;
                }
            }
        });

        if ($added > 0) {
            AuditTrail::log('UPDATE', 'Anggota Rombel', (string) $classroom->classroom_id, $classroom->classroom_name, "{$added} siswa dimasukkan ke rombel {$classroom->classroom_name}");
        }

        return $errors === []
            ? new DBResult(true, "{$added} siswa dimasukkan ke rombel {$classroom->classroom_name}.")
            : new DBResult($added > 0, "{$added} siswa dimasukkan.\n" . implode("\n", $errors));
    }

    public function remove(int $classroomId, array $studentIds, string $loginUser): DBResult
    {
        $classroom = Classroom::query()->find($classroomId);
        if ($classroom === null) {
            return new DBResult(false, 'Rombel tidak ditemukan.');
        }

        // Siswa yang sudah punya presensi/nilai di rombel ini tidak boleh dikeluarkan (datanya terikat ke rombel).
        $locked = collect(['attendances', 'report_grades', 'exam_participants', 'report_notes'])
            ->flatMap(fn ($table) => DB::table($table)->where('classroom_id', $classroomId)->whereIn('student_id', $studentIds)->pluck('student_id'))
            ->merge(DB::table('assessment_scores')->join('assessments', 'assessments.assessment_id', '=', 'assessment_scores.assessment_id')
                ->where('assessments.classroom_id', $classroomId)->whereIn('assessment_scores.student_id', $studentIds)->pluck('assessment_scores.student_id'))
            ->unique()->all();
        $removable = array_values(array_diff($studentIds, $locked));

        $removed = ClassroomStudent::query()->where('classroom_id', $classroomId)->whereIn('student_id', $removable)->delete();
        if ($removed > 0) {
            AuditTrail::log('UPDATE', 'Anggota Rombel', (string) $classroom->classroom_id, $classroom->classroom_name, "{$removed} siswa dikeluarkan dari rombel {$classroom->classroom_name}");
        }

        if ($locked !== []) {
            $names = Student::query()->whereIn('student_id', $locked)->orderBy('student_name')->pluck('student_name')->implode(', ');

            return new DBResult($removed > 0, "{$removed} siswa dikeluarkan. Tidak bisa dikeluarkan karena sudah punya presensi/nilai di rombel ini: {$names}.");
        }

        return new DBResult(true, "{$removed} siswa dikeluarkan dari rombel {$classroom->classroom_name}.");
    }

    /** Subquery student_id yang sudah punya rombel di tahun ajaran tsb. */
    private function placedQuery(int $academicYearId)
    {
        return ClassroomStudent::query()
            ->whereIn('classroom_id', Classroom::query()->where('academic_year_id', $academicYearId)->select('classroom_id'))
            ->select('student_id');
    }

    private function mapStudent(Student $s): object
    {
        return (object) [
            'StudentId' => $s->student_id,
            'Nis' => $s->nis,
            'Nisn' => $s->nisn,
            'StudentName' => $s->student_name,
            'Gender' => $s->gender,
        ];
    }
}
