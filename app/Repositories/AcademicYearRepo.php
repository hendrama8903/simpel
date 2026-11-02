<?php

namespace App\Repositories;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Tahun ajaran + dua semesternya (Ganjil & Genap) dikelola dalam satu form.
 * Tepat satu semester aktif di seluruh sistem: mengaktifkan satu semester menonaktifkan yang lain.
 */
class AcademicYearRepo
{
    /** @return object[] */
    public function search(): array
    {
        return AcademicYear::query()->with('semesters')
            ->orderByDesc('year_name')
            ->get()
            ->map(fn (AcademicYear $y) => $this->mapYear($y))
            ->all();
    }

    public function getByKey(int $academicYearId): ?object
    {
        $year = AcademicYear::query()->with('semesters')->find($academicYearId);

        return $year === null ? null : $this->mapYear($year);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($error = $this->validate($fields, null)) {
            return new DBResult(false, $error);
        }

        return DB::transaction(function () use ($fields, $loginUser) {
            $year = AcademicYear::create($fields['year'] + ['created_by' => $loginUser]);
            foreach ($fields['semesters'] as $no => $semester) {
                Semester::create($semester + ['academic_year_id' => $year->academic_year_id, 'semester_no' => $no, 'is_active' => false, 'created_by' => $loginUser]);
            }
            $this->activate($year->academic_year_id, $fields['activeSemester'], $loginUser);

            return new DBResult(true, 'Data berhasil disimpan.', $year->academic_year_id);
        });
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $year = AcademicYear::query()->find($data['AcademicYearId'] ?? 0);
        if ($year === null) {
            return new DBResult(false, 'Tahun ajaran tidak ditemukan.');
        }
        $fields = $this->fields($data);
        if ($error = $this->validate($fields, $year->academic_year_id)) {
            return new DBResult(false, $error);
        }

        return DB::transaction(function () use ($year, $fields, $loginUser) {
            $year->update($fields['year'] + ['updated_by' => $loginUser]);
            foreach ($fields['semesters'] as $no => $values) {
                $semester = Semester::query()->firstOrNew(['academic_year_id' => $year->academic_year_id, 'semester_no' => $no]);
                $semester->fill($values + ($semester->exists ? ['updated_by' => $loginUser] : ['created_by' => $loginUser, 'is_active' => false]))->save();
            }
            $this->activate($year->academic_year_id, $fields['activeSemester'], $loginUser);

            return new DBResult(true, 'Data berhasil diperbarui.');
        });
    }

    public function delete(int $academicYearId): DBResult
    {
        $year = AcademicYear::query()->with('semesters')->find($academicYearId);
        if ($year === null) {
            return new DBResult(false, 'Tahun ajaran tidak ditemukan.');
        }
        if ($year->classrooms()->exists()) {
            return new DBResult(false, "Tahun ajaran {$year->year_name} sudah punya rombel dan tidak bisa dihapus.");
        }
        if (DB::table('exams')->whereIn('semester_id', $year->semesters->pluck('semester_id'))->exists()) {
            return new DBResult(false, "Tahun ajaran {$year->year_name} sudah punya kegiatan ujian dan tidak bisa dihapus.");
        }
        if ($year->semesters->contains('is_active', true)) {
            return new DBResult(false, "Tahun ajaran {$year->year_name} sedang aktif dan tidak bisa dihapus.");
        }

        // Semester ikut terhapus (cascade).
        $year->delete();

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    /** $semesterNo 0 = tidak mengubah semester aktif saat ini kecuali yang aktif ada di tahun ini (dinonaktifkan). */
    private function activate(int $academicYearId, int $semesterNo, string $loginUser): void
    {
        if ($semesterNo === 0) {
            Semester::query()->where('academic_year_id', $academicYearId)->where('is_active', true)->get()
                ->each(fn (Semester $s) => $s->update(['is_active' => false, 'updated_by' => $loginUser]));

            return;
        }

        $target = Semester::query()->where('academic_year_id', $academicYearId)->where('semester_no', $semesterNo)->first();
        if ($target === null || $target->is_active) {
            return;
        }
        Semester::query()->where('is_active', true)->get()
            ->each(fn (Semester $s) => $s->update(['is_active' => false, 'updated_by' => $loginUser]));
        $target->update(['is_active' => true, 'updated_by' => $loginUser]);
    }

    private function fields(array $data): array
    {
        $date = fn (string $key) => ($v = trim((string) ($data[$key] ?? ''))) === '' ? null : $v;
        $semesters = [];
        foreach ([1, 2] as $no) {
            $semesters[$no] = [
                'start_date' => $date("Semester{$no}Start"),
                'end_date' => $date("Semester{$no}End"),
                'report_date' => $date("Semester{$no}ReportDate"),
            ];
        }

        return [
            'year' => [
                'year_name' => trim((string) ($data['YearName'] ?? '')),
                'start_date' => $semesters[1]['start_date'],
                'end_date' => $semesters[2]['end_date'],
            ],
            'semesters' => $semesters,
            'activeSemester' => in_array((int) ($data['ActiveSemester'] ?? 0), [1, 2], true) ? (int) $data['ActiveSemester'] : 0,
        ];
    }

    private function validate(array $fields, ?int $academicYearId): ?string
    {
        $name = $fields['year']['year_name'];
        if (!preg_match('/^(\d{4})\/(\d{4})$/', $name, $m) || (int) $m[2] !== (int) $m[1] + 1) {
            return 'Tahun ajaran harus berformat YYYY/YYYY, mis. 2026/2027.';
        }
        if (AcademicYear::query()->where('year_name', $name)->when($academicYearId, fn ($q) => $q->where('academic_year_id', '!=', $academicYearId))->exists()) {
            return "Tahun ajaran {$name} sudah ada.";
        }
        foreach ($fields['semesters'] as $no => $s) {
            $label = $no === 1 ? 'Ganjil' : 'Genap';
            if ($s['start_date'] === null || $s['end_date'] === null) {
                return "Tanggal mulai dan selesai semester {$label} wajib diisi.";
            }
            if ($s['start_date'] > $s['end_date']) {
                return "Tanggal mulai semester {$label} harus sebelum tanggal selesai.";
            }
        }
        if ($fields['semesters'][1]['end_date'] >= $fields['semesters'][2]['start_date']) {
            return 'Semester Genap harus dimulai setelah semester Ganjil selesai.';
        }

        return null;
    }

    private function mapYear(AcademicYear $y): object
    {
        $semesters = $y->semesters->keyBy('semester_no');
        $active = $y->semesters->firstWhere('is_active', true);
        $result = [
            'AcademicYearId' => $y->academic_year_id,
            'YearName' => $y->year_name,
            'StartDate' => $y->start_date?->format('Y-m-d'),
            'EndDate' => $y->end_date?->format('Y-m-d'),
            'ActiveSemester' => $active?->semester_no ?? 0,
            'ActiveSemesterName' => $active === null ? null : ($active->semester_no === 1 ? 'Ganjil' : 'Genap'),
            'IsActive' => $active !== null,
        ];
        foreach ([1, 2] as $no) {
            $s = $semesters->get($no);
            $result["Semester{$no}Start"] = $s?->start_date?->format('Y-m-d');
            $result["Semester{$no}End"] = $s?->end_date?->format('Y-m-d');
            $result["Semester{$no}ReportDate"] = $s?->report_date?->format('Y-m-d');
        }

        return (object) ($result + [
            'CreatedBy' => $y->created_by,
            'CreatedDate' => $y->created_at,
            'ModifiedBy' => $y->updated_by,
            'ModifiedDate' => $y->updated_at,
        ]);
    }
}
