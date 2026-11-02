<?php

namespace App\Http\Controllers;

use App\Repositories\SubjectRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Mata pelajaran: add/edit/delete, export Excel. */
class SubjectController extends Controller
{
    public function __construct(private SubjectRepo $subjectRepo)
    {
    }

    public function index()
    {
        return view('subject.index', $this->crudPermissions('SUBJECT'));
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->subjectRepo->search(
            $request->input('keyword'),
            $request->filled('isActive') ? $request->boolean('isActive') : null,
        ));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(fn () => $this->subjectRepo->getByKey((int) $request->input('subjectId')), 'Mata pelajaran tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['SubjectId', 'SubjectCode', 'SubjectName', 'SubjectGroup', 'TaughtBy', 'ReportOrder']);
        $data['IsActive'] = $request->boolean('IsActive');

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'SUBJECT',
            fn () => $this->subjectRepo->insert($data, $this->loginUsername()),
            fn () => $this->subjectRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::SubjectDelete, $ids, fn (int $id) => $this->subjectRepo->delete($id));
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Mata Pelajaran',
            'MAPEL',
            ['Kode', 'Nama Mapel', 'Kelompok', 'Diajar Oleh', 'Urutan Rapor', 'Status'],
            $this->subjectRepo->search(null, null),
            fn (object $s) => [$s->SubjectCode, $s->SubjectName, $s->SubjectGroupName, $s->TaughtByName, $s->ReportOrder, $s->IsActive ? 'Aktif' : 'Nonaktif'],
        );
    }
}
