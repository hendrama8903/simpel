<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\Teacher;
use App\Models\UserAccount;
use App\Support\AuditTrail;
use App\Support\DBResult;
use App\Support\ExcelSheet;
use App\Support\PasswordHasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Guru & tenaga kependidikan. Akun login guru dihubungkan lewat teachers.user_id: dibuat dari tombol
 * "Buat Akun Login" (createAccounts) atau dipilih dari user yang sudah ada di form.
 */
class TeacherRepo
{
    public const Positions = ['KEPSEK' => 'Kepala Sekolah', 'GURU_KELAS' => 'Guru Kelas', 'GURU_MAPEL' => 'Guru Mapel', 'TENDIK' => 'Tenaga Kependidikan'];

    public const EmploymentStatuses = ['PNS', 'PPPK', 'GTY', 'GTT', 'HONORER'];

    public const Genders = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

    /** Role akun yang dibuat otomatis per jabatan (role_code). TENDIK tidak dibuatkan akun. */
    private const PositionRoles = ['KEPSEK' => 'KEPSEK', 'GURU_KELAS' => 'GURU_KELAS', 'GURU_MAPEL' => 'GURU_MAPEL'];

    private const ImportColumns = ['NIP', 'NUPTK', 'NAMA', 'JK', 'TEMPAT LAHIR', 'TANGGAL LAHIR', 'JABATAN', 'STATUS KEPEGAWAIAN', 'NO HP', 'EMAIL', 'ALAMAT'];

    private const ImportRequired = ['NAMA', 'JK'];

    /** @return object[] */
    public function search(?string $keyword, ?bool $isActive): array
    {
        return Teacher::query()->with('user')
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('teacher_name', 'like', "%{$keyword}%")
                ->orWhere('nip', 'like', "%{$keyword}%")
                ->orWhere('nuptk', 'like', "%{$keyword}%")))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('teacher_name')
            ->get()
            ->map(fn (Teacher $t) => $this->mapTeacher($t))
            ->all();
    }

    public function getByKey(int $teacherId): ?object
    {
        $teacher = Teacher::query()->with('user')->find($teacherId);

        return $teacher === null ? null : $this->mapTeacher($teacher);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($error = $this->validate($fields, null)) {
            return new DBResult(false, $error);
        }

        $teacher = Teacher::create($fields + ['created_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil disimpan.', $teacher->teacher_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $teacher = Teacher::query()->find($data['TeacherId'] ?? 0);
        if ($teacher === null) {
            return new DBResult(false, 'Guru tidak ditemukan.');
        }
        $fields = $this->fields($data);
        if ($error = $this->validate($fields, $teacher->teacher_id)) {
            return new DBResult(false, $error);
        }

        $teacher->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function delete(int $teacherId): DBResult
    {
        $teacher = Teacher::query()->find($teacherId);
        if ($teacher === null) {
            return new DBResult(false, 'Guru tidak ditemukan.');
        }

        $usedIn = match (true) {
            DB::table('teaching_assignments')->where('teacher_id', $teacherId)->exists() => 'tugas mengajar',
            DB::table('classrooms')->where('homeroom_teacher_id', $teacherId)->exists() => 'wali kelas rombel',
            default => null,
        };
        if ($usedIn !== null) {
            return new DBResult(false, "{$teacher->teacher_name} masih tercatat di {$usedIn} dan tidak bisa dihapus. Nonaktifkan saja.");
        }

        // Akun login tidak ikut terhapus; hapus lewat menu User bila perlu.
        $teacher->delete();

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    /**
     * Buat akun login untuk guru terpilih yang belum punya akun.
     * Username = NIP, atau NUPTK, atau nama (huruf kecil, titik); password awal = $defaultPassword.
     */
    public function createAccounts(array $teacherIds, string $defaultPassword, string $loginUser): DBResult
    {
        $roles = Role::query()->whereIn('role_code', array_values(self::PositionRoles))->pluck('role_id', 'role_code');
        $created = [];
        $skipped = [];

        DB::transaction(function () use ($teacherIds, $defaultPassword, $loginUser, $roles, &$created, &$skipped) {
            foreach (Teacher::query()->whereIn('teacher_id', $teacherIds)->orderBy('teacher_name')->get() as $teacher) {
                if ($teacher->user_id !== null) {
                    $skipped[] = "{$teacher->teacher_name}: sudah punya akun.";
                    continue;
                }
                $roleId = $roles[self::PositionRoles[$teacher->position] ?? ''] ?? null;
                if ($roleId === null) {
                    $skipped[] = "{$teacher->teacher_name}: jabatan " . (self::Positions[$teacher->position] ?? $teacher->position) . ' tidak dibuatkan akun.';
                    continue;
                }

                $user = UserAccount::create([
                    'username' => $this->uniqueUsername($teacher),
                    'full_name' => $teacher->teacher_name,
                    'email' => $teacher->email,
                    'phone' => $teacher->phone,
                    'employee_no' => $teacher->nip,
                    'role_id' => $roleId,
                    'is_ad_user' => false,
                    'password_hash' => PasswordHasher::hash($defaultPassword),
                    'is_active' => true,
                    'created_by' => $loginUser,
                ]);
                $teacher->update(['user_id' => $user->user_id, 'updated_by' => $loginUser]);
                $created[] = "{$teacher->teacher_name}: {$user->username}";
            }
        });

        if ($created === []) {
            return new DBResult(false, "Tidak ada akun yang dibuat.\n" . implode("\n", $skipped));
        }

        return new DBResult(true, count($created) . " akun dibuat (password awal: {$defaultPassword}, wajib diganti setelah login):\n"
            . implode("\n", $created) . ($skipped === [] ? '' : "\n\nDilewati:\n" . implode("\n", $skipped)));
    }

    public function template(): Spreadsheet
    {
        return ExcelSheet::template(self::ImportColumns, self::ImportRequired, [
            'NIP' => '198501012010011001', 'NUPTK' => '1234567890123456', 'NAMA' => 'Siti Aminah, S.Pd.', 'JK' => 'P',
            'TEMPAT LAHIR' => 'Bandung', 'TANGGAL LAHIR' => '1985-01-01', 'JABATAN' => 'GURU_KELAS',
            'STATUS KEPEGAWAIAN' => 'PNS', 'NO HP' => '081234567890', 'EMAIL' => 'siti@example.com', 'ALAMAT' => 'Jl. Merdeka No. 1',
        ], [
            'Kolom kuning wajib diisi. Hapus baris contoh sebelum upload.',
            'JK: L atau P. TANGGAL LAHIR: YYYY-MM-DD atau DD-MM-YYYY.',
            'JABATAN: ' . implode(', ', array_keys(self::Positions)) . ' (kosong = GURU_KELAS).',
            'STATUS KEPEGAWAIAN: ' . implode(', ', self::EmploymentStatuses) . '.',
            'Guru dicocokkan berdasarkan NIP, lalu NUPTK: kalau sudah ada datanya diperbarui, kalau belum ditambahkan.',
        ]);
    }

    /** @return array{status: bool, message: string, warnings: string[]} */
    public function import(string $filePath, string $loginUser): array
    {
        $sheet = ExcelSheet::read($filePath, self::ImportColumns, self::ImportRequired);
        if ($sheet['error'] !== null) {
            return ['status' => false, 'message' => $sheet['error'], 'warnings' => []];
        }

        $inserted = 0;
        $updated = 0;
        $warnings = [];
        DB::transaction(function () use ($sheet, $loginUser, &$inserted, &$updated, &$warnings) {
            foreach ($sheet['rows'] as $line => $r) {
                $fields = $this->fields([
                    'Nip' => $r['NIP'], 'Nuptk' => $r['NUPTK'], 'TeacherName' => $r['NAMA'], 'Gender' => mb_strtoupper($r['JK']),
                    'BirthPlace' => $r['TEMPAT LAHIR'], 'BirthDate' => ExcelSheet::date($r['TANGGAL LAHIR']),
                    'Position' => mb_strtoupper($r['JABATAN']) ?: 'GURU_KELAS', 'EmploymentStatus' => mb_strtoupper($r['STATUS KEPEGAWAIAN']),
                    'Phone' => $r['NO HP'], 'Email' => $r['EMAIL'], 'Address' => $r['ALAMAT'], 'IsActive' => true,
                ]);
                if ($r['TANGGAL LAHIR'] !== '' && $fields['birth_date'] === null) {
                    $warnings[] = "Baris {$line}: tanggal lahir {$r['TANGGAL LAHIR']} tidak dikenali, dikosongkan.";
                }

                $existing = match (true) {
                    $fields['nip'] !== null => Teacher::query()->where('nip', $fields['nip'])->first(),
                    $fields['nuptk'] !== null => Teacher::query()->where('nuptk', $fields['nuptk'])->first(),
                    default => null,
                };
                if ($error = $this->validate($fields, $existing?->teacher_id)) {
                    $warnings[] = "Baris {$line} ({$r['NAMA']}): {$error} Dilewati.";
                    continue;
                }

                if ($existing === null) {
                    Teacher::create($fields + ['created_by' => $loginUser]);
                    $inserted++;
                } else {
                    // Status aktif tidak diubah oleh import.
                    unset($fields['is_active']);
                    $existing->update($fields + ['updated_by' => $loginUser]);
                    $updated++;
                }
            }
        });

        AuditTrail::log('UPLOAD', 'Guru', null, null, "Upload guru: {$inserted} ditambah, {$updated} diperbarui, " . count($warnings) . ' peringatan');

        return [
            'status' => true,
            'message' => "Unggah selesai: {$inserted} guru ditambah, {$updated} diperbarui" . ($warnings === [] ? '.' : ', ' . count($warnings) . ' peringatan.'),
            'warnings' => $warnings,
        ];
    }

    private function uniqueUsername(Teacher $teacher): string
    {
        $base = $teacher->nip ?: ($teacher->nuptk ?: Str::of($teacher->teacher_name)->before(',')->ascii()->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->limit(40, '')->toString());
        $base = $base === '' ? 'guru' : $base;
        $username = $base;
        for ($i = 2; UserAccount::query()->where('username', $username)->exists(); $i++) {
            $username = $base . $i;
        }

        return $username;
    }

    private function fields(array $data): array
    {
        $text = fn (string $key, int $max) => ($v = trim((string) ($data[$key] ?? ''))) === '' ? null : mb_substr($v, 0, $max);
        $userId = (int) ($data['UserId'] ?? 0);

        $fields = [
            'nip' => $text('Nip', 30),
            'nuptk' => $text('Nuptk', 30),
            'teacher_name' => (string) $text('TeacherName', 100),
            'gender' => (string) ($data['Gender'] ?? ''),
            'birth_place' => $text('BirthPlace', 50),
            'birth_date' => $text('BirthDate', 10),
            'position' => (string) ($data['Position'] ?? 'GURU_KELAS'),
            'employment_status' => $text('EmploymentStatus', 20),
            'phone' => $text('Phone', 20),
            'email' => $text('Email', 100),
            'address' => $text('Address', 255),
            'is_active' => $data['IsActive'] ?? true,
        ];
        // UserId hanya dikirim dari form (import tidak mengubah akun).
        if (array_key_exists('UserId', $data)) {
            $fields['user_id'] = $userId > 0 ? $userId : null;
        }

        return $fields;
    }

    private function validate(array $fields, ?int $teacherId): ?string
    {
        $exists = fn (string $column, ?string $value) => $value !== null && Teacher::query()->where($column, $value)
            ->when($teacherId, fn ($q) => $q->where('teacher_id', '!=', $teacherId))->exists();

        return match (true) {
            $fields['teacher_name'] === '' => 'Nama guru wajib diisi.',
            !isset(self::Genders[$fields['gender']]) => 'Jenis kelamin harus L atau P.',
            !isset(self::Positions[$fields['position']]) => 'Jabatan tidak valid.',
            $fields['employment_status'] !== null && !in_array($fields['employment_status'], self::EmploymentStatuses, true) => 'Status kepegawaian tidak valid.',
            $fields['birth_date'] !== null && strtotime($fields['birth_date']) === false => 'Tanggal lahir tidak valid.',
            $exists('nip', $fields['nip']) => "NIP {$fields['nip']} sudah dipakai guru lain.",
            $exists('nuptk', $fields['nuptk']) => "NUPTK {$fields['nuptk']} sudah dipakai guru lain.",
            ($fields['user_id'] ?? null) !== null && $exists('user_id', (string) $fields['user_id']) => 'Akun login tersebut sudah terhubung ke guru lain.',
            default => null,
        };
    }

    private function mapTeacher(Teacher $t): object
    {
        return (object) [
            'TeacherId' => $t->teacher_id,
            'Nip' => $t->nip,
            'Nuptk' => $t->nuptk,
            'TeacherName' => $t->teacher_name,
            'Gender' => $t->gender,
            'BirthPlace' => $t->birth_place,
            'BirthDate' => $t->birth_date?->format('Y-m-d'),
            'Position' => $t->position,
            'PositionName' => self::Positions[$t->position] ?? $t->position,
            'EmploymentStatus' => $t->employment_status,
            'Phone' => $t->phone,
            'Email' => $t->email,
            'Address' => $t->address,
            'UserId' => $t->user_id,
            'Username' => $t->user?->username,
            'UserText' => $t->user === null ? null : $t->user->full_name . ' (' . $t->user->username . ')',
            'IsActive' => $t->is_active,
            'CreatedBy' => $t->created_by,
            'CreatedDate' => $t->created_at,
            'ModifiedBy' => $t->updated_by,
            'ModifiedDate' => $t->updated_at,
        ];
    }
}
