<?php

namespace App\Http\Controllers;

use App\Repositories\RoleRepo;
use App\Repositories\StudentRepo;
use App\Repositories\TeacherRepo;
use App\Repositories\UserRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Port dari Controllers/LookupController.cs — sumber data dropdown Select2.
 * Tambahkan endpoint lookup baru di sini untuk setiap master yang dipakai sebagai dropdown.
 * Format response: { status, items: [{ id, text }] }.
 */
class LookupController extends Controller
{
    public function __construct(
        private RoleRepo $roleRepo,
        private UserRepo $userRepo,
        private TeacherRepo $teacherRepo,
        private StudentRepo $studentRepo,
    ) {
    }

    public function role(Request $request): JsonResponse
    {
        return $this->lookup($request, fn () => array_map(
            static fn ($r) => ['id' => (string) $r->RoleId, 'text' => $r->RoleName],
            $this->roleRepo->search($request->input('q'), true),
        ));
    }

    public function userList(Request $request): JsonResponse
    {
        return $this->lookup($request, fn () => array_map(
            static fn ($u) => ['id' => (string) $u->UserId, 'text' => $u->FullName . ' (' . $u->Username . ')'],
            $this->userRepo->search($request->input('q'), null, true),
        ));
    }

    /** Guru aktif. */
    public function teacher(Request $request): JsonResponse
    {
        return $this->lookup($request, fn () => array_map(
            static fn ($t) => ['id' => (string) $t->TeacherId, 'text' => $t->TeacherName . ($t->Nip ? ' (' . $t->Nip . ')' : '')],
            $this->teacherRepo->search($request->input('q'), true),
        ));
    }

    /** Siswa berstatus Aktif. */
    public function student(Request $request): JsonResponse
    {
        return $this->lookup($request, fn () => array_map(
            static fn ($s) => ['id' => (string) $s->StudentId, 'text' => $s->Nis . ' - ' . $s->StudentName . ($s->ClassroomName ? ' (' . $s->ClassroomName . ')' : '')],
            $this->studentRepo->search($request->input('q'), 'AKTIF', null),
        ));
    }

    private function lookup(Request $request, callable $load): JsonResponse
    {
        try {
            $pageLimit = max(1, min(100, (int) $request->input('pageLimit', 10)));
            $page = max(1, (int) $request->input('page', 1));

            $items = array_slice($load(), ($page - 1) * $pageLimit, $pageLimit);

            return response()->json(['status' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            return $this->jsonError($e);
        }
    }
}
