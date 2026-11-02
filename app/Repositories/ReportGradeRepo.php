<?php

namespace App\Repositories;

use App\Models\AssessmentScore;
use App\Models\ReportGrade;
use App\Models\Semester;
use App\Models\Student;
use App\Support\Academic;
use App\Support\AuditTrail;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Nilai akhir & deskripsi capaian rapor per siswa per mapel.
 *
 * Nilai akhir = rata-rata berbobot dari komponen yang ada nilainya:
 *   rata-rata Sumatif Lingkup Materi (bobot weight_summative), STS (weight_sts), SAS (weight_sas).
 * Deskripsi dari nilai per TP (rata-rata kolom formatif/sumatif yang terkait TP tsb):
 *   - TP >= KKTP, nilai tertinggi (maks. 2)  -> "menunjukkan penguasaan yang baik dalam ..."
 *   - TP <  KKTP, nilai terendah (maks. 2)   -> "perlu bantuan dalam ..."
 *   - semua TP tuntas & TP terendah belum disebut di kalimat pertama -> "perlu peningkatan dalam <TP terendah>".
 * Baris yang diubah manual (is_manual) tidak ditimpa saat Hitung, kecuali diminta.
 */
class ReportGradeRepo
{
    private const MaxDescribedObjectives = 2;

    public function __construct(private AssessmentRepo $assessmentRepo)
    {
    }

    /** @return object[] */
    public function sheet(Semester $semester, int $classroomId, int $subjectId): array
    {
        $calculated = $this->calculate($semester, $classroomId, $subjectId);
        $saved = ReportGrade::query()->where('semester_id', $semester->semester_id)->where('subject_id', $subjectId)
            ->whereIn('student_id', array_keys($calculated))->get()->keyBy('student_id');

        return array_values(array_map(function (array $c) use ($saved) {
            $g = $saved->get($c['student']->student_id);

            return (object) [
                'StudentId' => $c['student']->student_id,
                'Nis' => $c['student']->nis,
                'StudentName' => $c['student']->student_name,
                'SummativeAvg' => $c['summative'],
                'Sts' => $c['sts'],
                'Sas' => $c['sas'],
                'CalculatedScore' => $c['final'],
                'FinalScore' => $g?->final_score,
                'DescriptionHigh' => $g?->description_high,
                'DescriptionLow' => $g?->description_low,
                'IsManual' => (bool) $g?->is_manual,
                'IsSaved' => $g !== null,
            ];
        }, $calculated));
    }

    public function generate(Semester $semester, int $classroomId, int $subjectId, bool $overwriteManual, string $loginUser): DBResult
    {
        $calculated = $this->calculate($semester, $classroomId, $subjectId);
        $saved = ReportGrade::query()->where('semester_id', $semester->semester_id)->where('subject_id', $subjectId)
            ->whereIn('student_id', array_keys($calculated))->get()->keyBy('student_id');

        $updated = 0;
        $skipped = 0;
        DB::transaction(function () use ($calculated, $saved, $overwriteManual, $semester, $classroomId, $subjectId, $loginUser, &$updated, &$skipped) {
            foreach ($calculated as $studentId => $c) {
                $existing = $saved->get($studentId);
                if ($existing !== null && $existing->is_manual && !$overwriteManual) {
                    $skipped++;
                    continue;
                }
                // Siswa tanpa nilai sama sekali tidak dibuatkan baris rapor kosong.
                if ($existing === null && $c['final'] === null && $c['high'] === null && $c['low'] === null) {
                    continue;
                }
                $values = ['classroom_id' => $classroomId, 'final_score' => $c['final'], 'description_high' => $c['high'],
                    'description_low' => $c['low'], 'is_manual' => false];
                if ($existing === null) {
                    ReportGrade::create($values + ['semester_id' => $semester->semester_id, 'student_id' => $studentId,
                        'subject_id' => $subjectId, 'created_by' => $loginUser]);
                } else {
                    $existing->update($values + ['updated_by' => $loginUser]);
                }
                $updated++;
            }
        });
        AuditTrail::log('UPDATE', 'Nilai Rapor', (string) $classroomId, null, "Hitung nilai rapor rombel #{$classroomId} mapel #{$subjectId}: {$updated} siswa");

        return new DBResult(true, "Nilai rapor {$updated} siswa dihitung." . ($skipped > 0 ? " {$skipped} siswa yang diubah manual tidak ditimpa." : ''));
    }

    /** Simpan perubahan manual (nilai akhir / deskripsi). @param array<int, array<string, mixed>> $rows */
    public function save(Semester $semester, int $classroomId, int $subjectId, array $rows, string $loginUser): DBResult
    {
        $studentIds = $this->assessmentRepo->students($classroomId)->pluck('student_id')->all();
        $errors = [];
        DB::transaction(function () use ($rows, $studentIds, $semester, $classroomId, $subjectId, $loginUser, &$errors) {
            foreach ($rows as $row) {
                $studentId = (int) ($row['StudentId'] ?? 0);
                if (!in_array($studentId, $studentIds, true)) {
                    continue;
                }
                $score = trim((string) ($row['FinalScore'] ?? ''));
                if ($score !== '' && (!is_numeric($score) || $score < 0 || $score > 100)) {
                    $errors[] = "Nilai akhir {$score} tidak valid (harus 0-100).";
                    continue;
                }
                $values = [
                    'classroom_id' => $classroomId,
                    'final_score' => $score === '' ? null : round((float) $score, 2),
                    'description_high' => trim((string) ($row['DescriptionHigh'] ?? '')) ?: null,
                    'description_low' => trim((string) ($row['DescriptionLow'] ?? '')) ?: null,
                    'is_manual' => true,
                ];
                $existing = ReportGrade::query()->where('semester_id', $semester->semester_id)->where('student_id', $studentId)
                    ->where('subject_id', $subjectId)->first();
                if ($existing === null) {
                    ReportGrade::create($values + ['semester_id' => $semester->semester_id, 'student_id' => $studentId,
                        'subject_id' => $subjectId, 'created_by' => $loginUser]);
                } else {
                    $existing->update($values + ['updated_by' => $loginUser]);
                }
            }
        });

        return $errors === []
            ? new DBResult(true, 'Nilai rapor berhasil disimpan.')
            : new DBResult(false, implode("\n", array_unique($errors)));
    }

    /**
     * Hitung nilai akhir & deskripsi untuk semua anggota rombel.
     *
     * @return array<int, array{student: Student, summative: ?float, sts: ?float, sas: ?float, final: ?float, high: ?string, low: ?string}>
     */
    public function calculate(Semester $semester, int $classroomId, int $subjectId): array
    {
        $school = Academic::school();
        $assessments = $this->assessmentRepo->assessments($semester->semester_id, $classroomId, $subjectId);
        $scores = AssessmentScore::query()->whereIn('assessment_id', $assessments->pluck('assessment_id'))->get()->groupBy('student_id');

        $result = [];
        foreach ($this->assessmentRepo->students($classroomId) as $student) {
            $byAssessment = $scores->get($student->student_id, collect())->pluck('score', 'assessment_id');
            $values = fn (string $type) => $assessments->where('assessment_type', $type)
                ->map(fn ($a) => $byAssessment[$a->assessment_id] ?? null)->filter(fn ($v) => $v !== null);

            $summative = $values('SUMATIF');
            $components = [
                [$summative->isEmpty() ? null : $summative->avg(), $school->weight_summative],
                [$values('STS')->first(), $school->weight_sts],
                [$values('SAS')->first(), $school->weight_sas],
            ];
            $weight = 0;
            $total = 0;
            foreach ($components as [$value, $w]) {
                if ($value !== null && $w > 0) {
                    $total += $value * $w;
                    $weight += $w;
                }
            }

            // Nilai per TP: rata-rata semua kolom (formatif/sumatif) yang terkait TP tsb.
            $objectives = [];
            foreach ($assessments->whereNotNull('learning_objective_id') as $a) {
                $score = $byAssessment[$a->assessment_id] ?? null;
                if ($score !== null && $a->learningObjective !== null) {
                    $objectives[$a->learning_objective_id]['description'] = $a->learningObjective->description;
                    $objectives[$a->learning_objective_id]['scores'][] = $score;
                }
            }
            [$high, $low] = $this->describe($student->student_name, $objectives, (int) $school->kktp);

            $result[$student->student_id] = [
                'student' => $student,
                'summative' => $summative->isEmpty() ? null : round($summative->avg(), 2),
                'sts' => $components[1][0],
                'sas' => $components[2][0],
                'final' => $weight === 0 ? null : round($total / $weight),
                'high' => $high,
                'low' => $low,
            ];
        }

        return $result;
    }

    /** @return array{0: ?string, 1: ?string} [deskripsi capaian tertinggi, deskripsi perlu bantuan] */
    public function describe(string $studentName, array $objectives, int $kktp): array
    {
        if ($objectives === []) {
            return [null, null];
        }
        $list = array_map(fn ($o) => ['description' => $o['description'], 'score' => array_sum($o['scores']) / count($o['scores'])], $objectives);
        $achieved = array_values(array_filter($list, fn ($o) => $o['score'] >= $kktp));
        $needHelp = array_values(array_filter($list, fn ($o) => $o['score'] < $kktp));
        usort($achieved, fn ($a, $b) => $b['score'] <=> $a['score']);
        usort($needHelp, fn ($a, $b) => $a['score'] <=> $b['score']);

        $name = 'Ananda ' . $studentName;
        $high = $achieved === [] ? null
            : "{$name} menunjukkan penguasaan yang baik dalam " . $this->join(array_slice($achieved, 0, self::MaxDescribedObjectives)) . '.';
        $low = match (true) {
            $needHelp !== [] => "{$name} perlu bantuan dalam " . $this->join(array_slice($needHelp, 0, self::MaxDescribedObjectives)) . '.',
            count($achieved) > self::MaxDescribedObjectives => "{$name} perlu peningkatan dalam " . $this->join([end($achieved)]) . '.',
            default => null,
        };

        return [$high, $low];
    }

    private function join(array $objectives): string
    {
        $texts = array_map(fn ($o) => rtrim(lcfirst($o['description']), '.'), $objectives);

        return count($texts) <= 1 ? (string) ($texts[0] ?? '') : implode(', ', array_slice($texts, 0, -1)) . ' dan ' . end($texts);
    }
}
