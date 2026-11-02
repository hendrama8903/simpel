<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Repositories\ExamRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Kegiatan ujian + jadwal ujian per tingkat. */
class ExamController extends Controller
{
    public function __construct(private ExamRepo $examRepo)
    {
    }

    public function index()
    {
        return view('exam.index', $this->crudPermissions('EXAM') + [
            'semesters' => Academic::semesterOptions(),
            'subjects' => Subject::query()->where('is_active', true)->orderBy('report_order')->orderBy('subject_name')->get(['subject_id', 'subject_name'])
                ->map(fn ($s) => ['SubjectId' => $s->subject_id, 'SubjectName' => $s->subject_name]),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->examRepo->search(((int) $request->input('semesterId')) ?: null));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(fn () => $this->examRepo->getByKey((int) $request->input('examId')), 'Ujian tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['ExamId', 'SemesterId', 'ExamCode', 'ExamName', 'ExamType', 'StartDate', 'EndDate', 'CardNote']);

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'EXAM',
            fn () => $this->examRepo->insert($data, $this->loginUsername()),
            fn () => $this->examRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::ExamDelete, $ids, fn (int $id) => $this->examRepo->delete($id));
    }

    public function schedules(Request $request)
    {
        return $this->jsonData(fn () => $this->examRepo->schedules((int) $request->input('examId')));
    }

    public function saveSchedules(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::ExamEdit)) {
            return $this->permissionDenied();
        }
        $rows = json_decode((string) $request->input('rows', '[]'), true);

        return $this->runJson(fn () => $this->examRepo->saveSchedules((int) $request->input('examId'), is_array($rows) ? $rows : [], $this->loginUsername()));
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'Ujian',
            'UJIAN',
            ['Semester', 'Kode', 'Nama Ujian', 'Jenis', 'Mulai', 'Selesai', 'Jumlah Mapel', 'Jumlah Peserta'],
            $this->examRepo->search(((int) $request->input('semesterId')) ?: null),
            fn (object $e) => [$e->SemesterLabel, $e->ExamCode, $e->ExamName, $e->ExamTypeName, $e->StartDate, $e->EndDate, $e->ScheduleCount, $e->ParticipantCount],
        );
    }
}
