<?php

namespace App\Repositories;

use App\Models\Subject;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Mata pelajaran. taught_by menentukan siapa yang menilai secara default:
 * GURU_KELAS = wali kelas, GURU_MAPEL = guru di Tugas Mengajar. Urutan rapor = report_order.
 */
class SubjectRepo
{
    public const Groups = ['UMUM' => 'Umum', 'MULOK' => 'Muatan Lokal'];

    public const TaughtBy = ['GURU_KELAS' => 'Guru Kelas', 'GURU_MAPEL' => 'Guru Mapel'];

    /** @return object[] */
    public function search(?string $keyword, ?bool $isActive): array
    {
        return Subject::query()
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('subject_code', 'like', "%{$keyword}%")
                ->orWhere('subject_name', 'like', "%{$keyword}%")))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('report_order')
            ->orderBy('subject_name')
            ->get()
            ->map(fn (Subject $s) => $this->mapSubject($s))
            ->all();
    }

    public function getByKey(int $subjectId): ?object
    {
        $subject = Subject::query()->find($subjectId);

        return $subject === null ? null : $this->mapSubject($subject);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $code = mb_strtoupper(trim((string) ($data['SubjectCode'] ?? '')));
        $fields = $this->fields($data);
        if ($error = $this->validate($code, $fields)) {
            return new DBResult(false, $error);
        }
        if (Subject::query()->where('subject_code', $code)->exists()) {
            return new DBResult(false, "Kode mapel {$code} sudah dipakai.");
        }

        $subject = Subject::create($fields + ['subject_code' => $code, 'created_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil disimpan.', $subject->subject_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($error = $this->validate('-', $fields)) {
            return new DBResult(false, $error);
        }
        $subject = Subject::query()->find($data['SubjectId'] ?? 0);
        if ($subject === null) {
            return new DBResult(false, 'Mata pelajaran tidak ditemukan.');
        }

        // Kode tidak ikut di-update: dikunci di form saat EDIT.
        $subject->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function delete(int $subjectId): DBResult
    {
        $subject = Subject::query()->find($subjectId);
        if ($subject === null) {
            return new DBResult(false, 'Mata pelajaran tidak ditemukan.');
        }

        $usedIn = match (true) {
            DB::table('assessments')->where('subject_id', $subjectId)->exists() => 'penilaian',
            DB::table('report_grades')->where('subject_id', $subjectId)->exists() => 'nilai rapor',
            DB::table('schedules')->where('subject_id', $subjectId)->exists() => 'jadwal pelajaran',
            DB::table('teaching_assignments')->where('subject_id', $subjectId)->exists() => 'tugas mengajar',
            DB::table('exam_schedules')->where('subject_id', $subjectId)->exists() => 'jadwal ujian',
            default => null,
        };
        if ($usedIn !== null) {
            return new DBResult(false, "Mapel {$subject->subject_name} sudah dipakai di {$usedIn} dan tidak bisa dihapus. Nonaktifkan saja.");
        }

        // Tujuan pembelajaran mapel ini ikut terhapus (cascade).
        $subject->delete();

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    private function fields(array $data): array
    {
        return [
            'subject_name' => mb_substr(trim((string) ($data['SubjectName'] ?? '')), 0, 100),
            'subject_group' => (string) ($data['SubjectGroup'] ?? 'UMUM'),
            'taught_by' => (string) ($data['TaughtBy'] ?? 'GURU_KELAS'),
            'report_order' => max(0, (int) ($data['ReportOrder'] ?? 0)),
            'is_active' => $data['IsActive'] ?? true,
        ];
    }

    private function validate(string $code, array $fields): ?string
    {
        return match (true) {
            $code === '' || $fields['subject_name'] === '' => 'Kode dan nama mapel wajib diisi.',
            !isset(self::Groups[$fields['subject_group']]) => 'Kelompok mapel tidak valid.',
            !isset(self::TaughtBy[$fields['taught_by']]) => 'Pengajar mapel tidak valid.',
            default => null,
        };
    }

    private function mapSubject(Subject $s): object
    {
        return (object) [
            'SubjectId' => $s->subject_id,
            'SubjectCode' => $s->subject_code,
            'SubjectName' => $s->subject_name,
            'SubjectGroup' => $s->subject_group,
            'SubjectGroupName' => self::Groups[$s->subject_group] ?? $s->subject_group,
            'TaughtBy' => $s->taught_by,
            'TaughtByName' => self::TaughtBy[$s->taught_by] ?? $s->taught_by,
            'ReportOrder' => $s->report_order,
            'IsActive' => $s->is_active,
            'CreatedBy' => $s->created_by,
            'CreatedDate' => $s->created_at,
            'ModifiedBy' => $s->updated_by,
            'ModifiedDate' => $s->updated_at,
        ];
    }
}
