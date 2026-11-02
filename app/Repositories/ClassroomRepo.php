<?php

namespace App\Repositories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Support\Academic;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/** Rombel per tahun ajaran: tingkat (1-6), nama, wali kelas, ruangan. Anggota diatur di menu Anggota Rombel. */
class ClassroomRepo
{
    /** @return object[] */
    public function search(?int $academicYearId): array
    {
        return Classroom::query()->with(['academicYear', 'homeroomTeacher', 'room'])->withCount('members')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->orderBy('grade_level')
            ->orderBy('classroom_name')
            ->get()
            ->map(fn (Classroom $c) => $this->mapClassroom($c))
            ->all();
    }

    public function getByKey(int $classroomId): ?object
    {
        $classroom = Classroom::query()->with(['academicYear', 'homeroomTeacher', 'room'])->withCount('members')->find($classroomId);

        return $classroom === null ? null : $this->mapClassroom($classroom);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($error = $this->validate($fields, null)) {
            return new DBResult(false, $error);
        }

        $classroom = Classroom::create($fields + ['created_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil disimpan.', $classroom->classroom_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $classroom = Classroom::query()->find($data['ClassroomId'] ?? 0);
        if ($classroom === null) {
            return new DBResult(false, 'Rombel tidak ditemukan.');
        }
        $fields = $this->fields($data);
        // Tahun ajaran tidak bisa dipindah: anggota, nilai & presensi terikat ke tahun tsb.
        $fields['academic_year_id'] = $classroom->academic_year_id;
        if ($error = $this->validate($fields, $classroom->classroom_id)) {
            return new DBResult(false, $error);
        }

        $classroom->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function delete(int $classroomId): DBResult
    {
        $classroom = Classroom::query()->find($classroomId);
        if ($classroom === null) {
            return new DBResult(false, 'Rombel tidak ditemukan.');
        }

        $usedIn = match (true) {
            $classroom->members()->exists() => 'anggota siswa',
            DB::table('assessments')->where('classroom_id', $classroomId)->exists() => 'penilaian',
            DB::table('attendances')->where('classroom_id', $classroomId)->exists() => 'presensi',
            DB::table('exam_participants')->where('classroom_id', $classroomId)->exists() => 'peserta ujian',
            default => null,
        };
        if ($usedIn !== null) {
            return new DBResult(false, "Rombel {$classroom->classroom_name} sudah punya {$usedIn} dan tidak bisa dihapus.");
        }

        // Jadwal & tugas mengajar rombel ini ikut terhapus (cascade).
        $classroom->delete();

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    /** Salin rombel (nama, tingkat, wali kelas, ruangan) dari tahun ajaran lain; rombel yang namanya sudah ada dilewati. */
    public function copyFrom(int $sourceYearId, int $targetYearId, string $loginUser): DBResult
    {
        if ($sourceYearId === $targetYearId) {
            return new DBResult(false, 'Tahun ajaran sumber dan tujuan tidak boleh sama.');
        }
        $target = AcademicYear::query()->find($targetYearId);
        if ($target === null || !AcademicYear::query()->whereKey($sourceYearId)->exists()) {
            return new DBResult(false, 'Tahun ajaran tidak ditemukan.');
        }

        $existing = Classroom::query()->where('academic_year_id', $targetYearId)->pluck('classroom_name')->map(fn ($n) => mb_strtoupper($n))->all();
        $copied = 0;
        DB::transaction(function () use ($sourceYearId, $targetYearId, $existing, $loginUser, &$copied) {
            foreach (Classroom::query()->where('academic_year_id', $sourceYearId)->get() as $c) {
                if (in_array(mb_strtoupper($c->classroom_name), $existing, true)) {
                    continue;
                }
                Classroom::create([
                    'academic_year_id' => $targetYearId,
                    'grade_level' => $c->grade_level,
                    'classroom_name' => $c->classroom_name,
                    'homeroom_teacher_id' => $c->homeroom_teacher_id,
                    'room_id' => $c->room_id,
                    'created_by' => $loginUser,
                ]);
                $copied++;
            }
        });

        return $copied === 0
            ? new DBResult(false, 'Tidak ada rombel yang disalin (semua nama rombel sudah ada di tahun tujuan).')
            : new DBResult(true, "{$copied} rombel disalin ke {$target->year_name}. Periksa kembali wali kelasnya.");
    }

    private function fields(array $data): array
    {
        return [
            'academic_year_id' => (int) ($data['AcademicYearId'] ?? 0),
            'grade_level' => (int) ($data['GradeLevel'] ?? 0),
            'classroom_name' => mb_strtoupper(mb_substr(trim((string) ($data['ClassroomName'] ?? '')), 0, 20)),
            'homeroom_teacher_id' => ((int) ($data['HomeroomTeacherId'] ?? 0)) ?: null,
            'room_id' => ((int) ($data['RoomId'] ?? 0)) ?: null,
        ];
    }

    private function validate(array $fields, ?int $classroomId): ?string
    {
        $duplicate = Classroom::query()->where('academic_year_id', $fields['academic_year_id'])
            ->where('classroom_name', $fields['classroom_name'])
            ->when($classroomId, fn ($q) => $q->where('classroom_id', '!=', $classroomId))->exists();

        return match (true) {
            !AcademicYear::query()->whereKey($fields['academic_year_id'])->exists() => 'Tahun ajaran wajib dipilih.',
            $fields['grade_level'] < 1 || $fields['grade_level'] > 6 => 'Tingkat kelas harus 1 sampai 6.',
            $fields['classroom_name'] === '' => 'Nama rombel wajib diisi.',
            $duplicate => "Rombel {$fields['classroom_name']} sudah ada di tahun ajaran ini.",
            default => null,
        };
    }

    private function mapClassroom(Classroom $c): object
    {
        return (object) [
            'ClassroomId' => $c->classroom_id,
            'AcademicYearId' => $c->academic_year_id,
            'YearName' => $c->academicYear?->year_name,
            'GradeLevel' => $c->grade_level,
            'Phase' => Academic::phase($c->grade_level),
            'ClassroomName' => $c->classroom_name,
            'HomeroomTeacherId' => $c->homeroom_teacher_id,
            'HomeroomTeacherName' => $c->homeroomTeacher?->teacher_name,
            'RoomId' => $c->room_id,
            'RoomName' => $c->room?->room_name,
            'StudentCount' => $c->members_count ?? 0,
            'CreatedBy' => $c->created_by,
            'CreatedDate' => $c->created_at,
            'ModifiedBy' => $c->updated_by,
            'ModifiedDate' => $c->updated_at,
        ];
    }
}
