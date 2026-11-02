<?php

namespace App\Http\Controllers;

use App\Repositories\ExtracurricularRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Ekstrakurikuler: add/edit/delete, export Excel. */
class ExtracurricularController extends Controller
{
    public function __construct(private ExtracurricularRepo $repo)
    {
    }

    public function index()
    {
        return view('extracurricular.index', $this->crudPermissions('EXTRACURRICULAR'));
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->repo->search(
            $request->input('keyword'),
            $request->filled('isActive') ? $request->boolean('isActive') : null,
        ));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(fn () => $this->repo->getByKey((int) $request->input('extracurricularId')), 'Ekstrakurikuler tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['ExtracurricularId', 'ExtracurricularCode', 'ExtracurricularName', 'CoachName']);
        $data['IsMandatory'] = $request->boolean('IsMandatory');
        $data['IsActive'] = $request->boolean('IsActive');

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'EXTRACURRICULAR',
            fn () => $this->repo->insert($data, $this->loginUsername()),
            fn () => $this->repo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::ExtracurricularDelete, $ids, fn (int $id) => $this->repo->delete($id));
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Ekstrakurikuler',
            'EKSKUL',
            ['Kode', 'Nama Ekstrakurikuler', 'Pembina', 'Wajib', 'Status'],
            $this->repo->search(null, null),
            fn (object $e) => [$e->ExtracurricularCode, $e->ExtracurricularName, $e->CoachName, $e->IsMandatory ? 'Ya' : 'Tidak', $e->IsActive ? 'Aktif' : 'Nonaktif'],
        );
    }
}
