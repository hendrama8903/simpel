<?php

namespace App\Repositories;

use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Support\Academic;
use App\Support\AuditTrail;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Tugas mengajar per semester: guru x mapel x rombel. Mapel "guru kelas" tanpa penugasan dipegang wali kelas.
 * Diisi sebagai lembar kerja per rombel: satu baris per mapel aktif.
 */
class TeachingAssignmentRepo
{
    /** @return object[] satu baris per mapel aktif */
    public function sheet(int $semesterId, int $classroomId): array
    {
        $classroom = Classroom::query()->with('homeroomTeacher')->find($classroomId);
        if ($classroom === null) {
            return [];
        }
        $assigned = TeachingAssignment::query()->where('semester_id', $semesterId)->where('classroom_id', $classroomId)
            ->pluck('teacher_id', 'subject_id');

        return Subject::query()->where('is_active', true)->orderBy('report_order')->orderBy('subject_name')->get()
            ->map(fn (Subject $s) => (object) [
                'SubjectId' => $s->subject_id,
                'SubjectCode' => $s->subject_code,
                'SubjectName' => $s->subject_name,
                'TaughtBy' => $s->taught_by,
                'TaughtByName' => SubjectRepo::TaughtBy[$s->taught_by] ?? $s->taught_by,
                'TeacherId' => $assigned[$s->subject_id] ?? null,
                // Ditampilkan bila TeacherId kosong pada mapel guru kelas.
                'DefaultTeacher' => $s->taught_by === 'GURU_KELAS' ? $classroom->homeroomTeacher?->teacher_name : null,
            ])
            ->all();
    }

    /** @param array<int, array{SubjectId: int, TeacherId: ?int}> $rows */
    public function save(int $semesterId, int $classroomId, array $rows, string $loginUser): DBResult
    {
        $semester = Semester::query()->find($semesterId);
        $classroom = Classroom::query()->find($classroomId);
        if ($semester === null || $classroom === null || $classroom->academic_year_id !== $semester->academic_year_id) {
            return new DBResult(false, 'Semester atau rombel tidak valid.');
        }
        $teacherIds = Teacher::query()->pluck('teacher_id')->all();
        $subjectIds = Subject::query()->pluck('subject_id')->all();

        DB::transaction(function () use ($rows, $semesterId, $classroomId, $teacherIds, $subjectIds, $loginUser) {
            foreach ($rows as $row) {
                $subjectId = (int) ($row['SubjectId'] ?? 0);
                $teacherId = (int) ($row['TeacherId'] ?? 0);
                if (!in_array($subjectId, $subjectIds, true)) {
                    continue;
                }
                $existing = TeachingAssignment::query()->where('semester_id', $semesterId)->where('classroom_id', $classroomId)
                    ->where('subject_id', $subjectId)->first();
                if ($teacherId === 0 || !in_array($teacherId, $teacherIds, true)) {
                    $existing?->delete();
                } elseif ($existing === null) {
                    TeachingAssignment::create(['semester_id' => $semesterId, 'classroom_id' => $classroomId, 'subject_id' => $subjectId,
                        'teacher_id' => $teacherId, 'created_by' => $loginUser]);
                } elseif ($existing->teacher_id !== $teacherId) {
                    $existing->update(['teacher_id' => $teacherId, 'updated_by' => $loginUser]);
                }
            }
        });

        return new DBResult(true, "Tugas mengajar rombel {$classroom->classroom_name} berhasil disimpan.");
    }

    /** Salin semua tugas mengajar dari semester lain di tahun ajaran yang sama (mis. Ganjil -> Genap). */
    public function copyFromSemester(int $sourceSemesterId, int $targetSemesterId, string $loginUser): DBResult
    {
        $source = Semester::query()->find($sourceSemesterId);
        $target = Semester::query()->with('academicYear')->find($targetSemesterId);
        if ($source === null || $target === null || $source->semester_id === $target->semester_id) {
            return new DBResult(false, 'Semester sumber dan tujuan tidak valid.');
        }
        if ($source->academic_year_id !== $target->academic_year_id) {
            return new DBResult(false, 'Hanya bisa menyalin antar semester dalam tahun ajaran yang sama (rombelnya sama).');
        }

        $existing = TeachingAssignment::query()->where('semester_id', $targetSemesterId)
            ->get(['classroom_id', 'subject_id'])->map(fn ($a) => $a->classroom_id . '-' . $a->subject_id)->all();
        $copied = 0;
        DB::transaction(function () use ($sourceSemesterId, $targetSemesterId, $existing, $loginUser, &$copied) {
            foreach (TeachingAssignment::query()->where('semester_id', $sourceSemesterId)->get() as $a) {
                if (in_array($a->classroom_id . '-' . $a->subject_id, $existing, true)) {
                    continue;
                }
                TeachingAssignment::create(['semester_id' => $targetSemesterId, 'classroom_id' => $a->classroom_id, 'subject_id' => $a->subject_id,
                    'teacher_id' => $a->teacher_id, 'created_by' => $loginUser]);
                $copied++;
            }
        });
        AuditTrail::log('CREATE', 'Tugas Mengajar', null, Academic::semesterLabel($target), "Salin {$copied} tugas mengajar ke " . Academic::semesterLabel($target));

        return new DBResult(true, "{$copied} tugas mengajar disalin ke " . Academic::semesterLabel($target) . '.');
    }

    /** Semua tugas mengajar di satu semester (untuk export). @return object[] */
    public function summary(int $semesterId): array
    {
        return TeachingAssignment::query()->with(['classroom', 'subject', 'teacher'])->where('semester_id', $semesterId)->get()
            ->sortBy(fn (TeachingAssignment $a) => [$a->teacher->teacher_name, $a->classroom->grade_level, $a->classroom->classroom_name])
            ->map(fn (TeachingAssignment $a) => (object) [
                'TeacherName' => $a->teacher->teacher_name,
                'Nip' => $a->teacher->nip,
                'ClassroomName' => $a->classroom->classroom_name,
                'SubjectName' => $a->subject->subject_name,
            ])
            ->values()
            ->all();
    }
}
