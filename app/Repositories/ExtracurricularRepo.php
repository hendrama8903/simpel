<?php

namespace App\Repositories;

use App\Models\Extracurricular;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/** Ekstrakurikuler. Nilai ekskul per siswa diisi wali kelas di menu Wali Kelas. */
class ExtracurricularRepo
{
    /** @return object[] */
    public function search(?string $keyword, ?bool $isActive): array
    {
        return Extracurricular::query()
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('extracurricular_code', 'like', "%{$keyword}%")
                ->orWhere('extracurricular_name', 'like', "%{$keyword}%")))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderByDesc('is_mandatory')
            ->orderBy('extracurricular_name')
            ->get()
            ->map(fn (Extracurricular $e) => $this->mapExtracurricular($e))
            ->all();
    }

    public function getByKey(int $extracurricularId): ?object
    {
        $extracurricular = Extracurricular::query()->find($extracurricularId);

        return $extracurricular === null ? null : $this->mapExtracurricular($extracurricular);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $code = mb_strtoupper(trim((string) ($data['ExtracurricularCode'] ?? '')));
        $fields = $this->fields($data);
        if ($code === '' || $fields['extracurricular_name'] === '') {
            return new DBResult(false, 'Kode dan nama ekstrakurikuler wajib diisi.');
        }
        if (Extracurricular::query()->where('extracurricular_code', $code)->exists()) {
            return new DBResult(false, "Kode ekstrakurikuler {$code} sudah dipakai.");
        }

        $extracurricular = Extracurricular::create($fields + ['extracurricular_code' => $code, 'created_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil disimpan.', $extracurricular->extracurricular_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($fields['extracurricular_name'] === '') {
            return new DBResult(false, 'Nama ekstrakurikuler wajib diisi.');
        }
        $extracurricular = Extracurricular::query()->find($data['ExtracurricularId'] ?? 0);
        if ($extracurricular === null) {
            return new DBResult(false, 'Ekstrakurikuler tidak ditemukan.');
        }

        $extracurricular->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function delete(int $extracurricularId): DBResult
    {
        $extracurricular = Extracurricular::query()->find($extracurricularId);
        if ($extracurricular === null) {
            return new DBResult(false, 'Ekstrakurikuler tidak ditemukan.');
        }
        if (DB::table('student_extracurriculars')->where('extracurricular_id', $extracurricularId)->exists()) {
            return new DBResult(false, "Ekstrakurikuler {$extracurricular->extracurricular_name} sudah punya nilai siswa dan tidak bisa dihapus. Nonaktifkan saja.");
        }

        $extracurricular->delete();

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    private function fields(array $data): array
    {
        return [
            'extracurricular_name' => mb_substr(trim((string) ($data['ExtracurricularName'] ?? '')), 0, 100),
            'coach_name' => mb_substr(trim((string) ($data['CoachName'] ?? '')), 0, 100) ?: null,
            'is_mandatory' => $data['IsMandatory'] ?? false,
            'is_active' => $data['IsActive'] ?? true,
        ];
    }

    private function mapExtracurricular(Extracurricular $e): object
    {
        return (object) [
            'ExtracurricularId' => $e->extracurricular_id,
            'ExtracurricularCode' => $e->extracurricular_code,
            'ExtracurricularName' => $e->extracurricular_name,
            'CoachName' => $e->coach_name,
            'IsMandatory' => $e->is_mandatory,
            'IsActive' => $e->is_active,
            'CreatedBy' => $e->created_by,
            'CreatedDate' => $e->created_at,
            'ModifiedBy' => $e->updated_by,
            'ModifiedDate' => $e->updated_at,
        ];
    }
}
