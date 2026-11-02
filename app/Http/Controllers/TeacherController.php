<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesExcelUpload;
use App\Repositories\TeacherRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Guru & tendik: add/edit/delete, upload & export Excel, buat akun login. */
class TeacherController extends Controller
{
    use HandlesExcelUpload;

    public function __construct(private TeacherRepo $teacherRepo)
    {
    }

    public function index()
    {
        return view('teacher.index', $this->crudPermissions('TEACHER') + [
            'upload' => $this->hasPermission(PermissionCodes::TeacherUpload),
            'account' => $this->hasPermission(PermissionCodes::TeacherAccount),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->teacherRepo->search(
            $request->input('keyword'),
            $request->filled('isActive') ? $request->boolean('isActive') : null,
        ));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(fn () => $this->teacherRepo->getByKey((int) $request->input('teacherId')), 'Guru tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['TeacherId', 'Nip', 'Nuptk', 'TeacherName', 'Gender', 'BirthPlace', 'BirthDate', 'Position',
            'EmploymentStatus', 'Phone', 'Email', 'Address', 'UserId']);
        $data['IsActive'] = $request->boolean('IsActive');

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'TEACHER',
            fn () => $this->teacherRepo->insert($data, $this->loginUsername()),
            fn () => $this->teacherRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::TeacherDelete, $ids, fn (int $id) => $this->teacherRepo->delete($id));
    }

    public function createAccounts(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::TeacherAccount)) {
            return $this->permissionDenied();
        }
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->runJson(fn () => $this->teacherRepo->createAccounts($ids, (string) config('simpel.default_password'), $this->loginUsername()));
    }

    public function upload(Request $request)
    {
        return $this->excelUpload($request, PermissionCodes::TeacherUpload, fn (string $path) => $this->teacherRepo->import($path, $this->loginUsername()));
    }

    public function downloadTemplate()
    {
        return $this->excelTemplate($this->teacherRepo->template(), 'Template_Guru.xlsx');
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Guru',
            'GURU',
            ['NIP', 'NUPTK', 'Nama', 'JK', 'Tempat Lahir', 'Tanggal Lahir', 'Jabatan', 'Status Kepegawaian', 'No HP', 'Email', 'Alamat', 'Akun Login', 'Status'],
            $this->teacherRepo->search(null, null),
            fn (object $t) => [
                $t->Nip, $t->Nuptk, $t->TeacherName, $t->Gender, $t->BirthPlace, $t->BirthDate, $t->PositionName, $t->EmploymentStatus,
                $t->Phone, $t->Email, $t->Address, $t->Username, $t->IsActive ? 'Aktif' : 'Nonaktif',
            ],
        );
    }
}
