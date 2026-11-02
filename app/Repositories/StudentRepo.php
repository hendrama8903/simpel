<?php

namespace App\Repositories;

use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Support\Academic;
use App\Support\AuditTrail;
use App\Support\DBResult;
use App\Support\ExcelSheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Data induk siswa. Status (AKTIF/MUTASI/LULUS/KELUAR) tidak diubah dari form ini,
 * melainkan lewat menu Mutasi & Kelulusan (StudentExitRepo).
 */
class StudentRepo
{
    public const Statuses = ['AKTIF' => 'Aktif', 'MUTASI' => 'Mutasi', 'LULUS' => 'Lulus', 'KELUAR' => 'Keluar'];

    public const EntryTypes = ['BARU' => 'Siswa Baru', 'PINDAHAN' => 'Pindahan'];

    private const ImportColumns = ['NIS', 'NISN', 'NIK', 'NAMA', 'JK', 'TEMPAT LAHIR', 'TANGGAL LAHIR', 'AGAMA', 'ALAMAT',
        'NAMA AYAH', 'NAMA IBU', 'NAMA WALI', 'NO HP ORTU', 'JENIS MASUK', 'TANGGAL MASUK', 'DITERIMA DI KELAS', 'SEKOLAH ASAL', 'ROMBEL'];

    private const ImportRequired = ['NIS', 'NAMA', 'JK'];

    /** @return object[] */
    public function search(?string $keyword, ?string $status, ?int $classroomId): array
    {
        $students = Student::query()
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('student_name', 'like', "%{$keyword}%")
                ->orWhere('nis', 'like', "%{$keyword}%")
                ->orWhere('nisn', 'like', "%{$keyword}%")))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($classroomId, fn ($q) => $q->whereIn('student_id', ClassroomStudent::query()->where('classroom_id', $classroomId)->select('student_id')))
            ->orderBy('student_name')
            ->get();
        $classrooms = $this->currentClassrooms($students->pluck('student_id')->all(), Academic::activeSemester()?->academic_year_id);

        return $students->map(fn (Student $s) => $this->mapStudent($s, $classrooms))->all();
    }

    public function getByKey(int $studentId): ?object
    {
        $student = Student::query()->find($studentId);

        return $student === null ? null : $this->mapStudent($student, $this->currentClassrooms([$studentId], Academic::activeSemester()?->academic_year_id));
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($error = $this->validate($fields, null)) {
            return new DBResult(false, $error);
        }

        $student = Student::create($fields + ['status' => 'AKTIF', 'created_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil disimpan.', $student->student_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $student = Student::query()->find($data['StudentId'] ?? 0);
        if ($student === null) {
            return new DBResult(false, 'Siswa tidak ditemukan.');
        }
        $fields = $this->fields($data);
        if ($error = $this->validate($fields, $student->student_id)) {
            return new DBResult(false, $error);
        }

        $student->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function delete(int $studentId): DBResult
    {
        $student = Student::query()->find($studentId);
        if ($student === null) {
            return new DBResult(false, 'Siswa tidak ditemukan.');
        }

        $usedIn = match (true) {
            DB::table('assessment_scores')->where('student_id', $studentId)->exists() => 'nilai',
            DB::table('attendances')->where('student_id', $studentId)->exists() => 'presensi',
            DB::table('report_grades')->where('student_id', $studentId)->exists() => 'nilai rapor',
            DB::table('exam_participants')->where('student_id', $studentId)->exists() => 'peserta ujian',
            default => null,
        };
        if ($usedIn !== null) {
            return new DBResult(false, "{$student->student_name} sudah punya data {$usedIn} dan tidak bisa dihapus. Gunakan menu Mutasi & Kelulusan.");
        }

        $photo = $student->photo_path;
        // Keanggotaan rombel ikut terhapus (cascade).
        $student->delete();
        if ($photo !== null) {
            @unlink(storage_path('app/private/' . $photo));
        }

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    public function savePhoto(int $studentId, ?UploadedFile $file, string $loginUser): DBResult
    {
        $student = Student::query()->find($studentId);
        if ($student === null) {
            return new DBResult(false, 'Siswa tidak ditemukan.');
        }
        if ($error = SchoolRepo::validateImage($file)) {
            return new DBResult(false, $error);
        }

        $old = $student->photo_path;
        $path = $file->storeAs('uploads/students', $student->student_id . '_' . time() . '.' . strtolower($file->getClientOriginalExtension()), 'local');
        $student->update(['photo_path' => $path, 'updated_by' => $loginUser]);
        if ($old !== null) {
            @unlink(storage_path('app/private/' . $old));
        }

        return new DBResult(true, 'Foto berhasil diunggah.');
    }

    public static function photoFile(?string $photoPath): ?string
    {
        return $photoPath !== null && is_file(storage_path('app/private/' . $photoPath)) ? storage_path('app/private/' . $photoPath) : null;
    }

    public function template(): Spreadsheet
    {
        return ExcelSheet::template(self::ImportColumns, self::ImportRequired, [
            'NIS' => '2026001', 'NISN' => '0151234567', 'NIK' => '3273010101190001', 'NAMA' => 'Ahmad Fauzi', 'JK' => 'L',
            'TEMPAT LAHIR' => 'Bandung', 'TANGGAL LAHIR' => '2019-01-01', 'AGAMA' => 'Islam', 'ALAMAT' => 'Jl. Melati No. 5',
            'NAMA AYAH' => 'Budi', 'NAMA IBU' => 'Sari', 'NO HP ORTU' => '081234567890', 'JENIS MASUK' => 'BARU',
            'TANGGAL MASUK' => '2026-07-13', 'DITERIMA DI KELAS' => '1', 'ROMBEL' => '1A',
        ], [
            'Kolom kuning wajib diisi. Hapus baris contoh sebelum upload.',
            'JK: L atau P. Tanggal: YYYY-MM-DD atau DD-MM-YYYY. AGAMA: ' . implode(', ', Academic::Religions) . '.',
            'JENIS MASUK: BARU atau PINDAHAN (kosong = BARU). SEKOLAH ASAL diisi untuk siswa pindahan.',
            'ROMBEL (opsional): nama rombel di tahun ajaran AKTIF, mis. 1A. Siswa langsung dimasukkan ke rombel tsb.',
            'Siswa dicocokkan berdasarkan NIS: kalau sudah ada datanya diperbarui, kalau belum ditambahkan.',
        ]);
    }

    /** @return array{status: bool, message: string, warnings: string[]} */
    public function import(string $filePath, string $loginUser): array
    {
        $sheet = ExcelSheet::read($filePath, self::ImportColumns, self::ImportRequired);
        if ($sheet['error'] !== null) {
            return ['status' => false, 'message' => $sheet['error'], 'warnings' => []];
        }

        $yearId = Academic::activeSemester()?->academic_year_id;
        $classrooms = $yearId === null ? collect() : Classroom::query()->where('academic_year_id', $yearId)->get()
            ->keyBy(fn (Classroom $c) => mb_strtoupper($c->classroom_name));

        $inserted = 0;
        $updated = 0;
        $placed = 0;
        $warnings = [];
        DB::transaction(function () use ($sheet, $loginUser, $classrooms, $yearId, &$inserted, &$updated, &$placed, &$warnings) {
            foreach ($sheet['rows'] as $line => $r) {
                $fields = $this->fields([
                    'Nis' => $r['NIS'], 'Nisn' => $r['NISN'], 'Nik' => $r['NIK'], 'StudentName' => $r['NAMA'], 'Gender' => mb_strtoupper($r['JK']),
                    'BirthPlace' => $r['TEMPAT LAHIR'], 'BirthDate' => ExcelSheet::date($r['TANGGAL LAHIR']), 'Religion' => $this->religion($r['AGAMA']),
                    'Address' => $r['ALAMAT'], 'FatherName' => $r['NAMA AYAH'], 'MotherName' => $r['NAMA IBU'], 'GuardianName' => $r['NAMA WALI'],
                    'ParentPhone' => $r['NO HP ORTU'], 'EntryType' => mb_strtoupper($r['JENIS MASUK']) ?: 'BARU',
                    'EntryDate' => ExcelSheet::date($r['TANGGAL MASUK']), 'EntryGrade' => $r['DITERIMA DI KELAS'] ?: 1, 'PreviousSchool' => $r['SEKOLAH ASAL'],
                ]);
                foreach (['TANGGAL LAHIR' => 'birth_date', 'TANGGAL MASUK' => 'entry_date'] as $column => $field) {
                    if ($r[$column] !== '' && $fields[$field] === null) {
                        $warnings[] = "Baris {$line}: {$column} {$r[$column]} tidak dikenali, dikosongkan.";
                    }
                }
                if ($r['AGAMA'] !== '' && $fields['religion'] === null) {
                    $warnings[] = "Baris {$line}: agama {$r['AGAMA']} tidak dikenali, dikosongkan.";
                }

                $existing = Student::query()->where('nis', $fields['nis'])->first();
                if ($error = $this->validate($fields, $existing?->student_id)) {
                    $warnings[] = "Baris {$line} ({$r['NAMA']}): {$error} Dilewati.";
                    continue;
                }
                if ($existing === null) {
                    $existing = Student::create($fields + ['status' => 'AKTIF', 'created_by' => $loginUser]);
                    $inserted++;
                } else {
                    $existing->update($fields + ['updated_by' => $loginUser]);
                    $updated++;
                }

                if ($r['ROMBEL'] !== '') {
                    $classroom = $classrooms->get(mb_strtoupper($r['ROMBEL']));
                    if ($classroom === null) {
                        $warnings[] = "Baris {$line}: rombel {$r['ROMBEL']} tidak ada di tahun ajaran aktif, siswa tidak dimasukkan ke rombel.";
                    } elseif ($this->currentClassrooms([$existing->student_id], $yearId) === []) {
                        ClassroomStudent::create(['classroom_id' => $classroom->classroom_id, 'student_id' => $existing->student_id, 'created_by' => $loginUser]);
                        $placed++;
                    } elseif (!ClassroomStudent::query()->where('classroom_id', $classroom->classroom_id)->where('student_id', $existing->student_id)->exists()) {
                        $warnings[] = "Baris {$line}: {$r['NAMA']} sudah ada di rombel lain tahun ini, rombel tidak diubah.";
                    }
                }
            }
        });

        AuditTrail::log('UPLOAD', 'Siswa', null, null, "Upload siswa: {$inserted} ditambah, {$updated} diperbarui, {$placed} dimasukkan ke rombel");

        return [
            'status' => true,
            'message' => "Unggah selesai: {$inserted} siswa ditambah, {$updated} diperbarui, {$placed} dimasukkan ke rombel"
                . ($warnings === [] ? '.' : ', ' . count($warnings) . ' peringatan.'),
            'warnings' => $warnings,
        ];
    }

    /** Rombel siswa di tahun ajaran tsb: [student_id => classroom]. */
    public function currentClassrooms(array $studentIds, ?int $academicYearId): array
    {
        if ($academicYearId === null || $studentIds === []) {
            return [];
        }

        return ClassroomStudent::query()->with('classroom')
            ->whereIn('student_id', $studentIds)
            ->whereHas('classroom', fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->get()
            ->mapWithKeys(fn (ClassroomStudent $m) => [$m->student_id => $m->classroom])
            ->all();
    }

    private function religion(string $value): ?string
    {
        foreach (Academic::Religions as $religion) {
            if (strcasecmp($religion, trim($value)) === 0) {
                return $religion;
            }
        }

        return null;
    }

    private function fields(array $data): array
    {
        $text = fn (string $key, int $max) => ($v = trim((string) ($data[$key] ?? ''))) === '' ? null : mb_substr($v, 0, $max);

        return [
            'nis' => (string) $text('Nis', 20),
            'nisn' => $text('Nisn', 10),
            'nik' => $text('Nik', 16),
            'student_name' => (string) $text('StudentName', 100),
            'gender' => (string) ($data['Gender'] ?? ''),
            'birth_place' => $text('BirthPlace', 50),
            'birth_date' => $text('BirthDate', 10),
            'religion' => $text('Religion', 20),
            'address' => $text('Address', 255),
            'father_name' => $text('FatherName', 100),
            'mother_name' => $text('MotherName', 100),
            'guardian_name' => $text('GuardianName', 100),
            'parent_phone' => $text('ParentPhone', 20),
            'entry_type' => (string) ($data['EntryType'] ?? 'BARU'),
            'entry_date' => $text('EntryDate', 10),
            'entry_grade' => (int) ($data['EntryGrade'] ?? 1),
            'previous_school' => $text('PreviousSchool', 150),
        ];
    }

    private function validate(array $fields, ?int $studentId): ?string
    {
        $exists = fn (string $column, ?string $value) => $value !== null && Student::query()->where($column, $value)
            ->when($studentId, fn ($q) => $q->where('student_id', '!=', $studentId))->exists();

        return match (true) {
            $fields['nis'] === '' || $fields['student_name'] === '' => 'NIS dan nama siswa wajib diisi.',
            !isset(TeacherRepo::Genders[$fields['gender']]) => 'Jenis kelamin harus L atau P.',
            $fields['nisn'] !== null && !preg_match('/^\d{10}$/', $fields['nisn']) => 'NISN harus 10 digit angka.',
            $fields['nik'] !== null && !preg_match('/^\d{16}$/', $fields['nik']) => 'NIK harus 16 digit angka.',
            $fields['religion'] !== null && !in_array($fields['religion'], Academic::Religions, true) => 'Agama tidak valid.',
            !isset(self::EntryTypes[$fields['entry_type']]) => 'Jenis masuk harus BARU atau PINDAHAN.',
            $fields['entry_grade'] < 1 || $fields['entry_grade'] > 6 => 'Diterima di kelas harus 1 sampai 6.',
            $fields['birth_date'] !== null && strtotime($fields['birth_date']) === false => 'Tanggal lahir tidak valid.',
            $fields['entry_date'] !== null && strtotime($fields['entry_date']) === false => 'Tanggal masuk tidak valid.',
            $exists('nis', $fields['nis']) => "NIS {$fields['nis']} sudah dipakai siswa lain.",
            $exists('nisn', $fields['nisn']) => "NISN {$fields['nisn']} sudah dipakai siswa lain.",
            default => null,
        };
    }

    private function mapStudent(Student $s, array $classrooms): object
    {
        $classroom = $classrooms[$s->student_id] ?? null;

        return (object) [
            'StudentId' => $s->student_id,
            'Nis' => $s->nis,
            'Nisn' => $s->nisn,
            'Nik' => $s->nik,
            'StudentName' => $s->student_name,
            'Gender' => $s->gender,
            'BirthPlace' => $s->birth_place,
            'BirthDate' => $s->birth_date?->format('Y-m-d'),
            'Religion' => $s->religion,
            'Address' => $s->address,
            'FatherName' => $s->father_name,
            'MotherName' => $s->mother_name,
            'GuardianName' => $s->guardian_name,
            'ParentPhone' => $s->parent_phone,
            'EntryType' => $s->entry_type,
            'EntryDate' => $s->entry_date?->format('Y-m-d'),
            'EntryGrade' => $s->entry_grade,
            'PreviousSchool' => $s->previous_school,
            'HasPhoto' => $s->photo_path !== null,
            'Status' => $s->status,
            'StatusName' => self::Statuses[$s->status] ?? $s->status,
            'ClassroomName' => $classroom?->classroom_name,
            'CreatedBy' => $s->created_by,
            'CreatedDate' => $s->created_at,
            'ModifiedBy' => $s->updated_by,
            'ModifiedDate' => $s->updated_at,
        ];
    }
}
