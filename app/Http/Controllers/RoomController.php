<?php

namespace App\Http\Controllers;

use App\Repositories\RoomRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Ruangan: add/edit/delete, export Excel. */
class RoomController extends Controller
{
    public function __construct(private RoomRepo $repo)
    {
    }

    public function index()
    {
        return view('room.index', $this->crudPermissions('ROOM'));
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
        return $this->jsonData(fn () => $this->repo->getByKey((int) $request->input('roomId')), 'Ruangan tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['RoomId', 'RoomCode', 'RoomName', 'Capacity']);
        $data['IsActive'] = $request->boolean('IsActive');

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'ROOM',
            fn () => $this->repo->insert($data, $this->loginUsername()),
            fn () => $this->repo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::RoomDelete, $ids, fn (int $id) => $this->repo->delete($id));
    }

    public function downloadExcel()
    {
        return $this->exportExcel(
            'Ruangan',
            'RUANGAN',
            ['Kode', 'Nama Ruangan', 'Kapasitas', 'Status'],
            $this->repo->search(null, null),
            fn (object $r) => [$r->RoomCode, $r->RoomName, $r->Capacity, $r->IsActive ? 'Aktif' : 'Nonaktif'],
        );
    }
}
