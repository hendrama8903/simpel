<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Room;
use App\Repositories\ExamParticipantRepo;
use App\Support\Academic;
use App\Support\AuditTrail;
use App\Support\PermissionCodes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** Peserta ujian: generate, atur ruang, cetak kartu & daftar hadir. */
class ExamParticipantController extends Controller
{
    public function __construct(private ExamParticipantRepo $participantRepo)
    {
    }

    public function index()
    {
        return view('exam-participant.index', [
            'edit' => $this->hasPermission(PermissionCodes::ExamParticipantEdit),
            'print' => $this->hasPermission(PermissionCodes::ExamParticipantPrint),
            'exams' => Exam::query()->with('semester.academicYear')->orderByDesc('start_date')->get()
                ->map(fn (Exam $e) => ['ExamId' => $e->exam_id, 'Label' => "{$e->exam_code} - {$e->exam_name} (" . Academic::semesterLabel($e->semester) . ')']),
            'rooms' => Room::query()->where('is_active', true)->orderBy('room_code')->get(['room_id', 'room_name', 'capacity'])
                ->map(fn ($r) => ['RoomId' => $r->room_id, 'RoomName' => $r->room_name, 'Capacity' => $r->capacity]),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(function () use ($request) {
            $examId = (int) $request->input('examId');

            return ['participants' => $this->participantRepo->search($examId)] + $this->participantRepo->filters($examId);
        });
    }

    public function generate(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::ExamParticipantEdit)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->participantRepo->generate(
            (int) $request->input('examId'),
            (array) $request->input('gradeLevels', []),
            $this->loginUsername(),
        ));
    }

    public function assignRooms(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::ExamParticipantEdit)) {
            return $this->permissionDenied();
        }

        return $this->runJson(fn () => $this->participantRepo->assignRooms(
            (int) $request->input('examId'),
            (array) $request->input('roomIds', []),
            $request->boolean('onlyEmpty'),
            $this->loginUsername(),
        ));
    }

    public function save(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::ExamParticipantEdit)) {
            return $this->permissionDenied();
        }
        $rows = json_decode((string) $request->input('rows', '[]'), true);

        return $this->runJson(fn () => $this->participantRepo->save((int) $request->input('examId'), is_array($rows) ? $rows : [], $this->loginUsername()));
    }

    public function deleteMultiple(Request $request)
    {
        $examId = (int) $request->input('examId');
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::ExamParticipantEdit, $ids, fn (int $id) => $this->participantRepo->delete($examId, $id));
    }

    /** Kartu peserta: 6 kartu per halaman A4. Filter: rombel, ruang, atau id peserta terpilih. */
    public function printCards(Request $request)
    {
        abort_unless($this->hasPermission(PermissionCodes::ExamParticipantPrint), 403);
        [$exam, $participants] = $this->printSelection($request);
        AuditTrail::log('PRINT', 'Peserta Ujian', (string) $exam->exam_id, $exam->exam_code, 'Cetak ' . count($participants) . " kartu ujian {$exam->exam_code}");

        return Pdf::loadView('exam-participant.cards', [
            'exam' => $exam,
            'participants' => $participants,
            'school' => Academic::school(),
        ])->setPaper('a4')->stream("Kartu_Ujian_{$exam->exam_code}.pdf");
    }

    /** Daftar hadir peserta per ruang (satu halaman per ruang). */
    public function printAttendance(Request $request)
    {
        abort_unless($this->hasPermission(PermissionCodes::ExamParticipantPrint), 403);
        [$exam, $participants] = $this->printSelection($request);

        return Pdf::loadView('exam-participant.attendance', [
            'exam' => $exam,
            'groups' => collect($participants)->sortBy('SeatNo')->groupBy(fn ($p) => $p->RoomName ?? 'Belum ada ruang'),
            'school' => Academic::school(),
        ])->setPaper('a4')->stream("Daftar_Hadir_{$exam->exam_code}.pdf");
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'Peserta Ujian',
            'PESERTA_UJIAN',
            ['No Peserta', 'NIS', 'NISN', 'Nama', 'JK', 'Rombel', 'Ruang', 'Kursi'],
            $this->participantRepo->search((int) $request->input('examId')),
            fn (object $p) => [$p->ParticipantNo, $p->Nis, $p->Nisn, $p->StudentName, $p->Gender, $p->ClassroomName, $p->RoomName, $p->SeatNo],
        );
    }

    /** @return array{0: Exam, 1: object[]} */
    private function printSelection(Request $request): array
    {
        $exam = Exam::query()->with('semester.academicYear')->findOrFail((int) $request->input('examId'));
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request->input('ids')))));
        $participants = $this->participantRepo->search(
            $exam->exam_id,
            ((int) $request->input('classroomId')) ?: null,
            ((int) $request->input('roomId')) ?: null,
            $ids,
        );
        abort_if($participants === [], 404, 'Tidak ada peserta untuk dicetak.');
        set_time_limit(300);

        return [$exam, $participants];
    }
}
