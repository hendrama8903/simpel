<?php

namespace App\Repositories;

use App\Models\UserAccount;
use App\Support\DBResult;
use Illuminate\Database\QueryException;

/**
 * Port dari Models/Repo/UserRepo.cs. Validasi di sini meniru stored procedure asli
 * (SQL/04_Admin_Role_User_Procedures.sql + SQL/31_Request_Procedures.sql usp_User_ValidateSupervisor).
 */
class UserRepo
{
    private const MIN_PASSWORD_LENGTH = 8;

    /** @return object[] */
    public function search(?string $keyword, ?int $roleId, ?bool $isActive): array
    {
        return UserAccount::query()
            ->with(['role', 'supervisor'])
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('username', 'like', "%{$keyword}%")
                ->orWhere('full_name', 'like', "%{$keyword}%")
                ->orWhere('employee_no', 'like', "%{$keyword}%")))
            ->when($roleId, fn ($q) => $q->where('role_id', $roleId))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('username')
            ->get()
            ->map(fn (UserAccount $u) => $this->mapUser($u))
            ->values()
            ->all();
    }

    public function getByKey(int $userId): ?object
    {
        $user = UserAccount::query()->with(['role', 'supervisor'])->find($userId);

        return $user === null ? null : $this->mapUser($user);
    }

    public function insert(array $data, ?string $passwordHash, string $loginUser): DBResult
    {
        $username = mb_strtolower(trim((string) ($data['Username'] ?? '')));
        $fullName = trim((string) ($data['FullName'] ?? ''));

        if ($username === '' || $fullName === '' || empty($data['RoleId'])) {
            return new DBResult(false, 'Username, nama lengkap, dan peran wajib diisi.');
        }

        if (UserAccount::query()->where('username', $username)->exists()) {
            return new DBResult(false, "Username {$username} sudah dipakai.");
        }

        $isAdUser = (bool) ($data['IsAdUser'] ?? false);
        if (!$isAdUser && $passwordHash === null) {
            return new DBResult(false, 'Password wajib diisi untuk akun lokal.');
        }

        $supervisorError = $this->validateSupervisor(null, $data['SupervisorUserId'] ?? null);
        if ($supervisorError !== null) {
            return new DBResult(false, $supervisorError);
        }

        $user = UserAccount::create([
            'username' => $username,
            'full_name' => $fullName,
            'email' => $this->nullIfBlank($data['Email'] ?? null),
            'phone' => $this->nullIfBlank($data['Phone'] ?? null),
            'employee_no' => $this->nullIfBlank($data['EmployeeNo'] ?? null),
            'role_id' => $data['RoleId'],
            'supervisor_user_id' => $data['SupervisorUserId'] ?? null,
            'is_ad_user' => $isAdUser,
            'password_hash' => $isAdUser ? null : $passwordHash,
            'is_active' => $data['IsActive'] ?? true,
            'created_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data berhasil disimpan.', $user->user_id);
    }

    public function update(array $data, ?string $passwordHash, string $loginUser): DBResult
    {
        $fullName = trim((string) ($data['FullName'] ?? ''));
        if ($fullName === '' || empty($data['RoleId'])) {
            return new DBResult(false, 'Nama lengkap dan peran wajib diisi.');
        }

        $user = UserAccount::query()->find($data['UserId']);
        if ($user === null) {
            return new DBResult(false, 'Pengguna tidak ditemukan.');
        }

        $isAdUser = (bool) ($data['IsAdUser'] ?? false);
        if (!$isAdUser && $passwordHash === null && $user->password_hash === null) {
            return new DBResult(false, 'Password wajib diisi saat mengubah user menjadi akun lokal.');
        }

        $supervisorError = $this->validateSupervisor($user->user_id, $data['SupervisorUserId'] ?? null);
        if ($supervisorError !== null) {
            return new DBResult(false, $supervisorError);
        }

        $user->update([
            'full_name' => $fullName,
            'email' => $this->nullIfBlank($data['Email'] ?? null),
            'phone' => $this->nullIfBlank($data['Phone'] ?? null),
            'employee_no' => $this->nullIfBlank($data['EmployeeNo'] ?? null),
            'role_id' => $data['RoleId'],
            'supervisor_user_id' => $data['SupervisorUserId'] ?? null,
            'is_ad_user' => $isAdUser,
            'password_hash' => $isAdUser ? null : ($passwordHash ?? $user->password_hash),
            'is_active' => $data['IsActive'] ?? true,
            'updated_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function resetPassword(int $userId, string $passwordHash, string $loginUser): DBResult
    {
        $user = UserAccount::query()->find($userId);
        if ($user === null) {
            return new DBResult(false, 'Pengguna tidak ditemukan.');
        }

        if ($user->is_ad_user) {
            return new DBResult(false, 'Pengguna Active Directory tidak punya password lokal.');
        }

        $user->update(['password_hash' => $passwordHash, 'updated_by' => $loginUser]);

        return new DBResult(true, 'Password berhasil direset.');
    }

    public function delete(int $userId, string $loginUser): DBResult
    {
        $user = UserAccount::query()->find($userId);
        if ($user === null) {
            return new DBResult(false, 'Pengguna tidak ditemukan.');
        }

        try {
            $user->delete();
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return new DBResult(false, "Pengguna {$user->username} sudah dipakai data lain dan tidak bisa dihapus. Nonaktifkan saja.");
            }

            return new DBResult(false, $e->getMessage());
        }

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    /** Validasi atasan: harus user aktif, bukan diri sendiri, dan tidak membentuk lingkaran (A->B->A). */
    private function validateSupervisor(?int $userId, ?int $supervisorUserId): ?string
    {
        if ($supervisorUserId === null) {
            return null;
        }

        if ($supervisorUserId === $userId) {
            return 'Pengguna tidak bisa menjadi atasannya sendiri.';
        }

        $supervisor = UserAccount::query()->find($supervisorUserId);
        if ($supervisor === null || !$supervisor->is_active) {
            return 'Atasan tidak ditemukan atau nonaktif.';
        }

        if ($userId !== null) {
            $current = $supervisor;
            for ($depth = 0; $depth < 50 && $current !== null; $depth++) {
                if ($current->supervisor_user_id === $userId) {
                    return 'Atasan tidak valid: pengguna ini sudah berada di atas atasan yang dipilih.';
                }
                $current = $current->supervisor_user_id !== null ? UserAccount::query()->find($current->supervisor_user_id) : null;
            }
        }

        return null;
    }

    private function mapUser(UserAccount $u): object
    {
        return (object) [
            'UserId' => $u->user_id,
            'Username' => $u->username,
            'FullName' => $u->full_name,
            'Email' => $u->email,
            'Phone' => $u->phone,
            'EmployeeNo' => $u->employee_no,
            'RoleId' => $u->role_id,
            'RoleName' => $u->role->role_name,
            'SupervisorUserId' => $u->supervisor_user_id,
            'SupervisorName' => $u->supervisor?->full_name,
            'IsAdUser' => $u->is_ad_user,
            'IsActive' => $u->is_active,
            'LastLoginDate' => $u->last_login_at,
            'CreatedBy' => $u->created_by,
            'CreatedDate' => $u->created_at,
            'ModifiedBy' => $u->updated_by,
            'ModifiedDate' => $u->updated_at,
        ];
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
