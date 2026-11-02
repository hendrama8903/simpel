<?php

namespace App\Repositories;

use App\Models\Room;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/** Ruangan kelas / ujian. Kapasitas dipakai saat atur ruang ujian otomatis. */
class RoomRepo
{
    /** @return object[] */
    public function search(?string $keyword, ?bool $isActive): array
    {
        return Room::query()
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('room_code', 'like', "%{$keyword}%")
                ->orWhere('room_name', 'like', "%{$keyword}%")))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('room_code')
            ->get()
            ->map(fn (Room $r) => $this->mapRoom($r))
            ->all();
    }

    public function getByKey(int $roomId): ?object
    {
        $room = Room::query()->find($roomId);

        return $room === null ? null : $this->mapRoom($room);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $code = mb_strtoupper(trim((string) ($data['RoomCode'] ?? '')));
        $fields = $this->fields($data);
        if ($code === '' || $fields['room_name'] === '') {
            return new DBResult(false, 'Kode dan nama ruangan wajib diisi.');
        }
        if (Room::query()->where('room_code', $code)->exists()) {
            return new DBResult(false, "Kode ruangan {$code} sudah dipakai.");
        }

        $room = Room::create($fields + ['room_code' => $code, 'created_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil disimpan.', $room->room_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($fields['room_name'] === '') {
            return new DBResult(false, 'Nama ruangan wajib diisi.');
        }
        $room = Room::query()->find($data['RoomId'] ?? 0);
        if ($room === null) {
            return new DBResult(false, 'Ruangan tidak ditemukan.');
        }

        $room->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function delete(int $roomId): DBResult
    {
        $room = Room::query()->find($roomId);
        if ($room === null) {
            return new DBResult(false, 'Ruangan tidak ditemukan.');
        }
        if (DB::table('exam_participants')->where('room_id', $roomId)->exists()) {
            return new DBResult(false, "Ruangan {$room->room_name} sudah dipakai di peserta ujian dan tidak bisa dihapus. Nonaktifkan saja.");
        }

        // Rombel yang memakai ruangan ini menjadi tanpa ruangan (nullOnDelete).
        $room->delete();

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    private function fields(array $data): array
    {
        return [
            'room_name' => mb_substr(trim((string) ($data['RoomName'] ?? '')), 0, 100),
            'capacity' => max(1, min(999, (int) ($data['Capacity'] ?? 28))),
            'is_active' => $data['IsActive'] ?? true,
        ];
    }

    private function mapRoom(Room $r): object
    {
        return (object) [
            'RoomId' => $r->room_id,
            'RoomCode' => $r->room_code,
            'RoomName' => $r->room_name,
            'Capacity' => $r->capacity,
            'IsActive' => $r->is_active,
            'CreatedBy' => $r->created_by,
            'CreatedDate' => $r->created_at,
            'ModifiedBy' => $r->updated_by,
            'ModifiedDate' => $r->updated_at,
        ];
    }
}
