<?php

namespace App\Repositories;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\Room;
use App\Support\AuditTrail;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Peserta ujian. Nomor peserta = {exam_code}-{tingkat}-{urut 3 digit}, urut per tingkat
 * (diurutkan rombel lalu nama siswa; generate ulang meneruskan nomor terakhir).
 */
class ExamParticipantRepo
{
    /** @return object[] */
    public function search(int $examId, ?int $classroomId = null, ?int $roomId = null, array $ids = []): array
    {
        return ExamParticipant::query()->with(['student', 'classroom', 'room'])
            ->where('exam_id', $examId)
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->when($ids !== [], fn ($q) => $q->whereIn('exam_participant_id', $ids))
            ->orderBy('participant_no')
            ->get()
            ->map(fn (ExamParticipant $p) => $this->mapParticipant($p))
            ->all();
    }

    /** Daftarkan siswa aktif dari rombel tingkat terpilih (tahun ajaran semester ujian) yang belum jadi peserta. */
    public function generate(int $examId, array $gradeLevels, string $loginUser): DBResult
    {
        $exam = Exam::query()->with('semester')->find($examId);
        if ($exam === null) {
            return new DBResult(false, 'Ujian tidak ditemukan.');
        }
        $gradeLevels = array_values(array_intersect(array_map('intval', $gradeLevels), [1, 2, 3, 4, 5, 6]));
        if ($gradeLevels === []) {
            return new DBResult(false, 'Pilih minimal satu tingkat kelas.');
        }

        $existing = ExamParticipant::query()->where('exam_id', $examId)->pluck('student_id')->all();
        $members = DB::table('classroom_students')
            ->join('classrooms', 'classrooms.classroom_id', '=', 'classroom_students.classroom_id')
            ->join('students', 'students.student_id', '=', 'classroom_students.student_id')
            ->where('classrooms.academic_year_id', $exam->semester->academic_year_id)
            ->whereIn('classrooms.grade_level', $gradeLevels)
            ->where('students.status', 'AKTIF')
            ->whereNotIn('students.student_id', $existing)
            ->orderBy('classrooms.grade_level')->orderBy('classrooms.classroom_name')->orderBy('students.student_name')
            ->get(['students.student_id', 'classrooms.classroom_id', 'classrooms.grade_level']);
        if ($members->isEmpty()) {
            return new DBResult(false, 'Tidak ada siswa baru untuk didaftarkan (semua sudah jadi peserta atau rombel kosong).');
        }

        DB::transaction(function () use ($exam, $members, $loginUser) {
            $next = [];
            foreach ($members as $m) {
                $prefix = "{$exam->exam_code}-{$m->grade_level}-";
                $next[$m->grade_level] ??= $this->lastSequence($exam->exam_id, $prefix) + 1;
                ExamParticipant::create([
                    'exam_id' => $exam->exam_id,
                    'student_id' => $m->student_id,
                    'classroom_id' => $m->classroom_id,
                    'participant_no' => $prefix . str_pad((string) $next[$m->grade_level]++, 3, '0', STR_PAD_LEFT),
                    'created_by' => $loginUser,
                ]);
            }
        });
        AuditTrail::log('CREATE', 'Peserta Ujian', (string) $examId, $exam->exam_code, "Generate {$members->count()} peserta ujian {$exam->exam_code}");

        return new DBResult(true, "{$members->count()} peserta didaftarkan.");
    }

    /**
     * Isi ruang & nomor kursi otomatis berurutan nomor peserta, ruang diisi sesuai kapasitas dengan urutan $roomIds.
     * $onlyEmpty: hanya peserta yang belum punya ruang (ruang terisi tetap dihitung kapasitasnya).
     */
    public function assignRooms(int $examId, array $roomIds, bool $onlyEmpty, string $loginUser): DBResult
    {
        $rooms = collect($roomIds)->map(fn ($id) => Room::query()->find((int) $id))->filter()->values();
        if ($rooms->isEmpty()) {
            return new DBResult(false, 'Pilih minimal satu ruangan.');
        }

        $participants = ExamParticipant::query()->where('exam_id', $examId)->orderBy('participant_no')->get();
        $used = [];
        if ($onlyEmpty) {
            foreach ($participants->whereNotNull('room_id') as $p) {
                $used[$p->room_id][] = $p->seat_no;
            }
            $participants = $participants->whereNull('room_id')->values();
        }
        $capacity = $rooms->sum(fn ($r) => max(0, $r->capacity - count($used[$r->room_id] ?? [])));
        if ($participants->count() > $capacity) {
            return new DBResult(false, "Kapasitas ruangan terpilih ({$capacity} kursi) kurang untuk {$participants->count()} peserta.");
        }

        DB::transaction(function () use ($participants, $rooms, $used, $loginUser) {
            $roomIndex = 0;
            $seat = 0;
            foreach ($participants as $p) {
                // Lewati kursi yang sudah terpakai & ruang yang sudah penuh.
                do {
                    $seat++;
                    if ($seat > $rooms[$roomIndex]->capacity) {
                        $roomIndex++;
                        $seat = 1;
                    }
                } while (in_array($seat, $used[$rooms[$roomIndex]->room_id] ?? [], true));
                $p->update(['room_id' => $rooms[$roomIndex]->room_id, 'seat_no' => $seat, 'updated_by' => $loginUser]);
            }
        });

        return new DBResult(true, "{$participants->count()} peserta diatur ke " . $rooms->count() . ' ruangan.');
    }

    /** Simpan ruang & kursi hasil edit di grid. @param array<int, array{ExamParticipantId: int, RoomId: ?int, SeatNo: ?int}> $rows */
    public function save(int $examId, array $rows, string $loginUser): DBResult
    {
        $roomIds = Room::query()->pluck('room_id')->all();
        DB::transaction(function () use ($examId, $rows, $roomIds, $loginUser) {
            foreach ($rows as $row) {
                $p = ExamParticipant::query()->where('exam_id', $examId)->find((int) ($row['ExamParticipantId'] ?? 0));
                $roomId = (int) ($row['RoomId'] ?? 0);
                $p?->update([
                    'room_id' => in_array($roomId, $roomIds, true) ? $roomId : null,
                    'seat_no' => ((int) ($row['SeatNo'] ?? 0)) ?: null,
                    'updated_by' => $loginUser,
                ]);
            }
        });

        $duplicates = ExamParticipant::query()->where('exam_id', $examId)->whereNotNull('room_id')->whereNotNull('seat_no')
            ->selectRaw('room_id, seat_no, COUNT(*) AS total')->groupBy('room_id', 'seat_no')->having('total', '>', 1)->count();

        return new DBResult(true, 'Data peserta berhasil disimpan.' . ($duplicates > 0 ? " Perhatian: ada {$duplicates} kursi yang dipakai lebih dari satu peserta." : ''));
    }

    public function delete(int $examId, int $participantId): DBResult
    {
        $deleted = ExamParticipant::query()->where('exam_id', $examId)->where('exam_participant_id', $participantId)->delete();

        return new DBResult($deleted > 0, $deleted > 0 ? 'Data berhasil dihapus.' : 'Peserta tidak ditemukan.');
    }

    /** Rombel & ruangan yang dipakai peserta ujian (untuk filter cetak). */
    public function filters(int $examId): array
    {
        $participants = ExamParticipant::query()->where('exam_id', $examId);

        return [
            'classrooms' => Classroom::query()->whereIn('classroom_id', (clone $participants)->select('classroom_id'))
                ->orderBy('grade_level')->orderBy('classroom_name')->get(['classroom_id', 'classroom_name']),
            'rooms' => Room::query()->whereIn('room_id', (clone $participants)->whereNotNull('room_id')->select('room_id'))
                ->orderBy('room_code')->get(['room_id', 'room_name']),
        ];
    }

    private function lastSequence(int $examId, string $prefix): int
    {
        return (int) ExamParticipant::query()->where('exam_id', $examId)->where('participant_no', 'like', $prefix . '%')
            ->pluck('participant_no')->map(fn ($no) => (int) substr($no, strlen($prefix)))->max();
    }

    private function mapParticipant(ExamParticipant $p): object
    {
        return (object) [
            'ExamParticipantId' => $p->exam_participant_id,
            'ParticipantNo' => $p->participant_no,
            'StudentId' => $p->student_id,
            'Nis' => $p->student->nis,
            'Nisn' => $p->student->nisn,
            'StudentName' => $p->student->student_name,
            'Gender' => $p->student->gender,
            'PhotoPath' => $p->student->photo_path,
            'HasPhoto' => $p->student->photo_path !== null,
            'ClassroomId' => $p->classroom_id,
            'ClassroomName' => $p->classroom->classroom_name,
            'GradeLevel' => $p->classroom->grade_level,
            'RoomId' => $p->room_id,
            'RoomName' => $p->room?->room_name,
            'SeatNo' => $p->seat_no,
        ];
    }
}
