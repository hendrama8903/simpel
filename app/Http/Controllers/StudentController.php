<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesExcelUpload;
use App\Models\Classroom;
use App\Models\Student;
use App\Repositories\StudentRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Data induk siswa: add/edit/delete, foto, upload & export Excel. */
class StudentController extends Controller
{
    use HandlesExcelUpload;

    public function __construct(private StudentRepo $studentRepo)
    {
    }

    public function index()
    {
        $semester = Academic::activeSemester();

        return view('student.index', $this->crudPermissions('STUDENT') + [
            'upload' => $this->hasPermission(PermissionCodes::StudentUpload),
            'classrooms' => $semester === null ? collect() : Classroom::query()->where('academic_year_id', $semester->academic_year_id)
                ->orderBy('grade_level')->orderBy('classroom_name')->get(['classroom_id', 'classroom_name']),
            'yearName' => $semester?->academicYear->year_name,
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->studentRepo->search(
            $request->input('keyword'),
            $request->input('status') ?: null,
            ((int) $request->input('classroomId')) ?: null,
        ));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(fn () => $this->studentRepo->getByKey((int) $request->input('studentId')), 'Siswa tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['StudentId', 'Nis', 'Nisn', 'Nik', 'StudentName', 'Gender', 'BirthPlace', 'BirthDate', 'Religion', 'Address',
            'FatherName', 'MotherName', 'GuardianName', 'ParentPhone', 'EntryType', 'EntryDate', 'EntryGrade', 'PreviousSchool']);

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'STUDENT',
            fn () => $this->studentRepo->insert($data, $this->loginUsername()),
            fn () => $this->studentRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::StudentDelete, $ids, fn (int $id) => $this->studentRepo->delete($id));
    }

    public function uploadPhoto(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::StudentEdit)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->studentRepo->savePhoto((int) $request->input('studentId'), $request->file('file'), $this->loginUsername()));
    }

    public function photo(Request $request)
    {
        $file = StudentRepo::photoFile(Student::query()->whereKey((int) $request->input('id'))->value('photo_path'));

        return $file === null ? abort(404) : response()->file($file);
    }

    public function upload(Request $request)
    {
        return $this->excelUpload($request, PermissionCodes::StudentUpload, fn (string $path) => $this->studentRepo->import($path, $this->loginUsername()));
    }

    public function downloadTemplate()
    {
        return $this->excelTemplate($this->studentRepo->template(), 'Template_Siswa.xlsx');
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'Siswa',
            'SISWA',
            ['NIS', 'NISN', 'NIK', 'Nama', 'JK', 'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'Alamat', 'Nama Ayah', 'Nama Ibu', 'Nama Wali',
                'No HP Ortu', 'Jenis Masuk', 'Tanggal Masuk', 'Diterima di Kelas', 'Sekolah Asal', 'Rombel', 'Status'],
            $this->studentRepo->search(null, $request->input('status') ?: null, ((int) $request->input('classroomId')) ?: null),
            fn (object $s) => [
                $s->Nis, $s->Nisn, $s->Nik, $s->StudentName, $s->Gender, $s->BirthPlace, $s->BirthDate, $s->Religion, $s->Address,
                $s->FatherName, $s->MotherName, $s->GuardianName, $s->ParentPhone, StudentRepo::EntryTypes[$s->EntryType] ?? $s->EntryType,
                $s->EntryDate, $s->EntryGrade, $s->PreviousSchool, $s->ClassroomName, $s->StatusName,
            ],
        );
    }
}
