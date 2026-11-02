<?php

namespace App\Repositories;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classroom;
use App\Models\LearningObjective;
use App\Models\Semester;
use App\Models\Student;
use App\Support\Academic;
use App\Support\AuditTrail;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Input nilai per rombel x mapel x semester. Satu assessment = satu kolom nilai di grid:
 * - FORMATIF : per TP, dipakai untuk deskripsi capaian rapor (tidak masuk nilai akhir);
 * - SUMATIF  : sumatif lingkup materi, boleh dikaitkan ke TP (ikut deskripsi);
 * - STS / SAS: sumatif tengah / akhir semester, maksimal satu kolom per jenis.
 */
class AssessmentRepo
{
    public const Types = ['FORMATIF' => 'Formatif', 'SUMATIF' => 'Sumatif Lingkup Materi', 'STS' => 'Sumatif Tengah Semester', 'SAS' => 'Sumatif Akhir Semester'];

    private const SingleTypes = ['STS', 'SAS'];

    /** @return array{columns: object[], rows: object[], objectives: object[]} */
    public function sheet(Semester $semester, int $classroomId, int $subjectId): array
    {
        $classroom = Classroom::query()->findOrFail($classroomId);
        $assessments = $this->assessments($semester->semester_id, $classroomId, $subjectId);
        $scores = AssessmentScore::query()->whereIn('assessment_id', $assessments->pluck('assessment_id'))->get()->groupBy('student_id');

        $rows = $this->students($classroomId)->map(function (Student $s) use ($scores) {
            $row = ['StudentId' => $s->student_id, 'Nis' => $s->nis, 'StudentName' => $s->student_name];
            foreach ($scores->get($s->student_id, collect()) as $score) {
                $row['A' . $score->assessment_id] = $score->score;
            }

            return (object) $row;
        })->values()->all();

        return [
            'columns' => $assessments->map(fn (Assessment $a) => $this->mapAssessment($a))->values()->all(),
            'rows' => $rows,
            'objectives' => $this->objectives($subjectId, $classroom->grade_level, $semester->semester_no),
        ];
    }

    /**
     * @param array<int, array{StudentId: int, Scores: array<string, mixed>}> $rows Scores: [assessmentId => nilai|""]
     */
    public function saveScores(Semester $semester, int $classroomId, int $subjectId, array $rows, string $loginUser): DBResult
    {
        $assessmentIds = $this->assessments($semester->semester_id, $classroomId, $subjectId)->pluck('assessment_id')->all();
        $studentIds = $this->students($classroomId)->pluck('student_id')->all();

        $errors = [];
        $changes = [];
        foreach ($rows as $row) {
            $studentId = (int) ($row['StudentId'] ?? 0);
            if (!in_array($studentId, $studentIds, true)) {
                continue;
            }
            foreach ((array) ($row['Scores'] ?? []) as $assessmentId => $value) {
                if (!in_array((int) $assessmentId, $assessmentIds, true)) {
                    continue;
                }
                $value = $value === null ? '' : str_replace(',', '.', trim((string) $value));
                if ($value !== '' && (!is_numeric($value) || $value < 0 || $value > 100)) {
                    $errors[] = "Nilai {$value} tidak valid (harus 0-100).";
                    continue;
                }
                $changes[] = [(int) $assessmentId, $studentId, $value === '' ? null : round((float) $value, 2)];
            }
        }
        if ($errors !== []) {
            return new DBResult(false, implode("\n", array_unique($errors)));
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as [$assessmentId, $studentId, $score]) {
                $query = AssessmentScore::query()->where('assessment_id', $assessmentId)->where('student_id', $studentId);
                if ($score === null) {
                    $query->delete();
                } elseif ($query->exists()) {
                    $query->update(['score' => $score]);
                } else {
                    AssessmentScore::query()->insert(['assessment_id' => $assessmentId, 'student_id' => $studentId, 'score' => $score]);
                }
            }
        });

        $classroom = Classroom::query()->find($classroomId);
        AuditTrail::log('UPDATE', 'Input Nilai', (string) $classroomId, $classroom?->classroom_name,
            "Nilai rombel {$classroom?->classroom_name} mapel #{$subjectId} disimpan (" . count($changes) . ' sel)');

        return new DBResult(true, 'Nilai berhasil disimpan.');
    }

    /** Tambah / ubah satu kolom penilaian. */
    public function saveColumn(Semester $semester, int $classroomId, int $subjectId, array $data, string $loginUser): DBResult
    {
        $classroom = Classroom::query()->find($classroomId);
        $type = (string) ($data['AssessmentType'] ?? '');
        $objectiveId = ((int) ($data['LearningObjectiveId'] ?? 0)) ?: null;
        $name = mb_substr(trim((string) ($data['AssessmentName'] ?? '')), 0, 50);
        $date = trim((string) ($data['AssessmentDate'] ?? '')) ?: null;
        $assessmentId = (int) ($data['AssessmentId'] ?? 0);

        $assessment = $assessmentId > 0
            ? Assessment::query()->where('assessment_id', $assessmentId)->where('semester_id', $semester->semester_id)
                ->where('classroom_id', $classroomId)->where('subject_id', $subjectId)->first()
            : null;
        if ($assessmentId > 0 && $assessment === null) {
            return new DBResult(false, 'Kolom penilaian tidak ditemukan.');
        }

        if (in_array($type, self::SingleTypes, true)) {
            $objectiveId = null;
            $name = $name === '' ? $type : $name;
        }
        if ($type === 'FORMATIF' && $objectiveId === null) {
            return new DBResult(false, 'Penilaian formatif wajib dikaitkan ke tujuan pembelajaran.');
        }
        $objective = $objectiveId === null ? null : LearningObjective::query()->find($objectiveId);
        if ($name === '') {
            $name = match ($type) {
                'FORMATIF' => 'F-' . $objective?->objective_code,
                default => 'S-' . ($objective?->objective_code ?? ($this->assessments($semester->semester_id, $classroomId, $subjectId)->where('assessment_type', 'SUMATIF')->count() + 1)),
            };
        }

        $error = match (true) {
            $classroom === null => 'Rombel tidak ditemukan.',
            !isset(self::Types[$type]) => 'Jenis penilaian wajib dipilih.',
            $objectiveId !== null && ($objective === null || $objective->subject_id !== $subjectId || $objective->grade_level !== $classroom->grade_level)
                => 'Tujuan pembelajaran tidak sesuai dengan mapel/kelas.',
            $date !== null && strtotime($date) === false => 'Tanggal tidak valid.',
            in_array($type, self::SingleTypes, true) && Assessment::query()->where('semester_id', $semester->semester_id)
                ->where('classroom_id', $classroomId)->where('subject_id', $subjectId)->where('assessment_type', $type)
                ->when($assessment, fn ($q) => $q->where('assessment_id', '!=', $assessment->assessment_id))->exists()
                => "Kolom {$type} sudah ada untuk mapel ini.",
            default => null,
        };
        if ($error !== null) {
            return new DBResult(false, $error);
        }

        $fields = ['assessment_type' => $type, 'learning_objective_id' => $objectiveId, 'assessment_name' => $name, 'assessment_date' => $date];
        if ($assessment === null) {
            $assessment = Assessment::create($fields + ['semester_id' => $semester->semester_id, 'classroom_id' => $classroomId,
                'subject_id' => $subjectId, 'created_by' => $loginUser]);

            return new DBResult(true, "Kolom {$name} ditambahkan.", $assessment->assessment_id);
        }
        $assessment->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, "Kolom {$name} diperbarui.", $assessment->assessment_id);
    }

    public function deleteColumn(Semester $semester, int $classroomId, int $subjectId, int $assessmentId): DBResult
    {
        $assessment = Assessment::query()->where('assessment_id', $assessmentId)->where('semester_id', $semester->semester_id)
            ->where('classroom_id', $classroomId)->where('subject_id', $subjectId)->first();
        if ($assessment === null) {
            return new DBResult(false, 'Kolom penilaian tidak ditemukan.');
        }

        // Nilai di kolom ini ikut terhapus (cascade).
        $assessment->delete();

        return new DBResult(true, "Kolom {$assessment->assessment_name} dihapus.");
    }

    /** Anggota rombel (termasuk yang sudah keluar, supaya nilai lamanya tetap terlihat). */
    public function students(int $classroomId)
    {
        return Student::query()
            ->whereIn('student_id', DB::table('classroom_students')->where('classroom_id', $classroomId)->select('student_id'))
            ->orderBy('student_name')
            ->get();
    }

    public function assessments(int $semesterId, int $classroomId, int $subjectId)
    {
        $order = array_flip(array_keys(self::Types));

        return Assessment::query()->with('learningObjective')
            ->where('semester_id', $semesterId)->where('classroom_id', $classroomId)->where('subject_id', $subjectId)
            ->get()
            ->sortBy(fn (Assessment $a) => [$order[$a->assessment_type] ?? 9, $a->learningObjective?->objective_code ?? '', $a->assessment_id])
            ->values();
    }

    /** @return object[] TP aktif mapel/kelas/semester */
    public function objectives(int $subjectId, int $gradeLevel, int $semesterNo): array
    {
        return LearningObjective::query()->where('subject_id', $subjectId)->where('grade_level', $gradeLevel)
            ->where('semester_no', $semesterNo)->where('is_active', true)->orderBy('objective_code')->get()
            ->map(fn (LearningObjective $o) => (object) [
                'LearningObjectiveId' => $o->learning_objective_id,
                'ObjectiveCode' => $o->objective_code,
                'Description' => $o->description,
                'Text' => $o->objective_code . ' - ' . $o->description,
            ])
            ->all();
    }

    private function mapAssessment(Assessment $a): object
    {
        return (object) [
            'AssessmentId' => $a->assessment_id,
            'AssessmentType' => $a->assessment_type,
            'AssessmentTypeName' => self::Types[$a->assessment_type] ?? $a->assessment_type,
            'AssessmentName' => $a->assessment_name,
            'AssessmentDate' => $a->assessment_date?->format('Y-m-d'),
            'LearningObjectiveId' => $a->learning_objective_id,
            'ObjectiveCode' => $a->learningObjective?->objective_code,
            'ObjectiveDescription' => $a->learningObjective?->description,
        ];
    }
}
