<?php

namespace App\Repositories;

use App\Models\LearningObjective;
use App\Support\Academic;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Tujuan Pembelajaran (TP) per mapel, tingkat, dan semester. Deskripsinya dipakai apa adanya
 * di deskripsi capaian rapor, jadi tulis sebagai frasa, mis. "membaca teks pendek dengan lancar".
 */
class LearningObjectiveRepo
{
    /** @return object[] */
    public function search(?int $subjectId, ?int $gradeLevel, ?int $semesterNo, ?bool $isActive = null): array
    {
        return LearningObjective::query()->with('subject')
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($gradeLevel, fn ($q) => $q->where('grade_level', $gradeLevel))
            ->when($semesterNo, fn ($q) => $q->where('semester_no', $semesterNo))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('subject_id')->orderBy('grade_level')->orderBy('semester_no')->orderBy('objective_code')
            ->get()
            ->map(fn (LearningObjective $o) => $this->mapObjective($o))
            ->all();
    }

    public function getByKey(int $learningObjectiveId): ?object
    {
        $objective = LearningObjective::query()->with('subject')->find($learningObjectiveId);

        return $objective === null ? null : $this->mapObjective($objective);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($fields['objective_code'] === '') {
            $fields['objective_code'] = $this->nextCode($fields);
        }
        if ($error = $this->validate($fields, null)) {
            return new DBResult(false, $error);
        }

        $objective = LearningObjective::create($fields + ['created_by' => $loginUser]);

        return new DBResult(true, "Data berhasil disimpan ({$objective->objective_code}).", $objective->learning_objective_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $objective = LearningObjective::query()->find($data['LearningObjectiveId'] ?? 0);
        if ($objective === null) {
            return new DBResult(false, 'Tujuan pembelajaran tidak ditemukan.');
        }
        $fields = $this->fields($data);
        if ($error = $this->validate($fields, $objective->learning_objective_id)) {
            return new DBResult(false, $error);
        }

        $objective->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function delete(int $learningObjectiveId): DBResult
    {
        $objective = LearningObjective::query()->find($learningObjectiveId);
        if ($objective === null) {
            return new DBResult(false, 'Tujuan pembelajaran tidak ditemukan.');
        }
        if (DB::table('assessments')->where('learning_objective_id', $learningObjectiveId)->exists()) {
            return new DBResult(false, "{$objective->objective_code} sudah dipakai di penilaian dan tidak bisa dihapus. Nonaktifkan saja.");
        }

        $objective->delete();

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    private function nextCode(array $fields): string
    {
        $codes = LearningObjective::query()->where('subject_id', $fields['subject_id'])->where('grade_level', $fields['grade_level'])
            ->where('semester_no', $fields['semester_no'])->pluck('objective_code');
        $max = $codes->map(fn ($c) => preg_match('/^TP(\d+)$/', $c, $m) ? (int) $m[1] : 0)->max() ?? 0;

        return 'TP' . ($max + 1);
    }

    private function fields(array $data): array
    {
        return [
            'subject_id' => (int) ($data['SubjectId'] ?? 0),
            'grade_level' => (int) ($data['GradeLevel'] ?? 0),
            'semester_no' => (int) ($data['SemesterNo'] ?? 0),
            'objective_code' => mb_strtoupper(mb_substr(trim((string) ($data['ObjectiveCode'] ?? '')), 0, 10)),
            'description' => mb_substr(trim(preg_replace('/\s+/', ' ', (string) ($data['Description'] ?? ''))), 0, 500),
            'is_active' => $data['IsActive'] ?? true,
        ];
    }

    private function validate(array $fields, ?int $id): ?string
    {
        $duplicate = LearningObjective::query()->where('subject_id', $fields['subject_id'])->where('grade_level', $fields['grade_level'])
            ->where('semester_no', $fields['semester_no'])->where('objective_code', $fields['objective_code'])
            ->when($id, fn ($q) => $q->where('learning_objective_id', '!=', $id))->exists();

        return match (true) {
            !DB::table('subjects')->where('subject_id', $fields['subject_id'])->exists() => 'Mata pelajaran wajib dipilih.',
            $fields['grade_level'] < 1 || $fields['grade_level'] > 6 => 'Kelas harus 1 sampai 6.',
            !in_array($fields['semester_no'], [1, 2], true) => 'Semester wajib dipilih.',
            $fields['description'] === '' => 'Deskripsi tujuan pembelajaran wajib diisi.',
            $duplicate => "Kode {$fields['objective_code']} sudah dipakai untuk mapel, kelas & semester ini.",
            default => null,
        };
    }

    private function mapObjective(LearningObjective $o): object
    {
        return (object) [
            'LearningObjectiveId' => $o->learning_objective_id,
            'SubjectId' => $o->subject_id,
            'SubjectName' => $o->subject?->subject_name,
            'GradeLevel' => $o->grade_level,
            'Phase' => Academic::phase($o->grade_level),
            'SemesterNo' => $o->semester_no,
            'SemesterName' => Academic::semesterName($o->semester_no),
            'ObjectiveCode' => $o->objective_code,
            'Description' => $o->description,
            'IsActive' => $o->is_active,
            'CreatedBy' => $o->created_by,
            'CreatedDate' => $o->created_at,
            'ModifiedBy' => $o->updated_by,
            'ModifiedDate' => $o->updated_at,
        ];
    }
}
