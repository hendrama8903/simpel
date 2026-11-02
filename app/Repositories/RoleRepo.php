<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\Role;
use App\Support\AuditTrail;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Port dari Models/Repo/RoleRepo.cs. Validasi di sini meniru stored procedure asli
 * (SQL/04_Admin_Role_User_Procedures.sql: usp_Role_Insert/Update/Delete/SaveAccess).
 */
class RoleRepo
{
    /** @return object[] */
    public function search(?string $keyword, ?bool $isActive): array
    {
        return Role::query()
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('role_code', 'like', "%{$keyword}%")
                ->orWhere('role_name', 'like', "%{$keyword}%")))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('role_name')
            ->get()
            ->map(fn (Role $r) => $this->mapRole($r))
            ->values()
            ->all();
    }

    public function getByKey(int $roleId): ?object
    {
        $role = Role::query()->find($roleId);

        return $role === null ? null : $this->mapRole($role);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $roleCode = mb_strtoupper(trim((string) ($data['RoleCode'] ?? '')));
        $roleName = trim((string) ($data['RoleName'] ?? ''));

        if ($roleCode === '' || $roleName === '') {
            return new DBResult(false, 'Kode dan nama peran wajib diisi.');
        }

        if (Role::query()->where('role_code', $roleCode)->exists()) {
            return new DBResult(false, "Kode peran {$roleCode} sudah dipakai.");
        }

        $role = Role::create([
            'role_code' => $roleCode,
            'role_name' => $roleName,
            'description' => $this->nullIfBlank($data['Description'] ?? null),
            'is_active' => $data['IsActive'] ?? true,
            'created_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data berhasil disimpan.', $role->role_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $roleName = trim((string) ($data['RoleName'] ?? ''));
        if ($roleName === '') {
            return new DBResult(false, 'Nama peran wajib diisi.');
        }

        $role = Role::query()->find($data['RoleId']);
        if ($role === null) {
            return new DBResult(false, 'Peran tidak ditemukan.');
        }

        $isActive = $data['IsActive'] ?? true;
        if ($role->is_system && !$isActive) {
            return new DBResult(false, 'Peran sistem tidak bisa dinonaktifkan.');
        }

        $role->update([
            'role_name' => $roleName,
            'description' => $this->nullIfBlank($data['Description'] ?? null),
            'is_active' => $isActive,
            'updated_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function delete(int $roleId, string $loginUser): DBResult
    {
        $role = Role::query()->find($roleId);
        if ($role === null) {
            return new DBResult(false, 'Peran tidak ditemukan.');
        }

        if ($role->is_system) {
            return new DBResult(false, "Peran {$role->role_name} adalah peran sistem dan tidak bisa dihapus.");
        }

        if ($role->users()->exists()) {
            return new DBResult(false, "Peran {$role->role_name} masih dipakai pengguna dan tidak bisa dihapus.");
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->menus()->detach();
            $role->delete();
        });

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    /**
     * Simpan hak akses role (replace). Parent dari menu yang dipilih otomatis ikut diberikan;
     * permission hanya disimpan kalau menunya diberikan.
     */
    public function saveAccess(int $roleId, array $menuIds, array $permissionIds, string $loginUser): DBResult
    {
        $role = Role::query()->find($roleId);
        if ($role === null) {
            return new DBResult(false, 'Peran tidak ditemukan.');
        }

        $allMenus = Menu::query()->get()->keyBy('menu_id');
        $finalMenuIds = [];
        foreach ($menuIds as $menuId) {
            $current = $allMenus->get((int) $menuId);
            while ($current !== null && !isset($finalMenuIds[$current->menu_id])) {
                $finalMenuIds[$current->menu_id] = true;
                $current = $current->parent_menu_id !== null ? $allMenus->get($current->parent_menu_id) : null;
            }
        }
        $finalMenuIds = array_keys($finalMenuIds);

        // Cegah admin terkunci: role sistem wajib tetap punya menu Role & Access.
        if ($role->is_system) {
            $roleAccessMenuId = $allMenus->firstWhere('menu_url', '/Role/Index')?->menu_id;
            if ($roleAccessMenuId === null || !in_array($roleAccessMenuId, $finalMenuIds, true)) {
                return new DBResult(false, 'Peran sistem harus tetap punya akses ke menu Peran & Akses.');
            }
        }

        $finalPermissionIds = array_values(array_filter(
            array_map('intval', $permissionIds),
            fn ($permissionId) => in_array(
                \App\Models\Permission::query()->find($permissionId)?->menu_id,
                $finalMenuIds,
                true,
            ),
        ));

        DB::transaction(function () use ($role, $finalMenuIds, $finalPermissionIds, $loginUser) {
            // attach/detach tidak memicu event model: catat daftar akses lama & baru ke audit trail.
            $access = fn () => [
                'menus' => $role->menus()->orderBy('menus.menu_name')->pluck('menus.menu_name')->all(),
                'permissions' => $role->permissions()->orderBy('permissions.permission_code')->pluck('permissions.permission_code')->all(),
            ];
            $old = $access();

            $now = now();
            $role->permissions()->detach();
            $role->menus()->detach();

            $role->menus()->attach(array_fill_keys($finalMenuIds, ['created_by' => $loginUser, 'created_at' => $now]));
            $role->permissions()->attach(array_fill_keys($finalPermissionIds, ['created_by' => $loginUser, 'created_at' => $now]));

            $role->update(['updated_by' => $loginUser]);

            $new = $access();
            if ($old !== $new) {
                AuditTrail::log(AuditLog::ActionUpdate, 'Role Access', (string) $role->role_id, $role->role_code,
                    sprintf('Akses peran %s diperbarui: %d menu, %d permission', $role->role_code, count($new['menus']), count($new['permissions'])),
                    $old, $new);
            }
        });

        return new DBResult(true, 'Akses berhasil disimpan. Pengguna dengan peran ini perlu login ulang agar perubahan berlaku.');
    }

    private function mapRole(Role $r): object
    {
        return (object) [
            'RoleId' => $r->role_id,
            'RoleCode' => $r->role_code,
            'RoleName' => $r->role_name,
            'Description' => $r->description,
            'IsSystem' => $r->is_system,
            'IsActive' => $r->is_active,
            'UserCount' => $r->users()->count(),
            'CreatedBy' => $r->created_by,
            'CreatedDate' => $r->created_at,
            'ModifiedBy' => $r->updated_by,
            'ModifiedDate' => $r->updated_at,
        ];
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
