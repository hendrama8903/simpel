<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Repositories\LearningObjectiveRepo;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;

/** Tujuan Pembelajaran per mapel, tingkat & semester: add/edit/delete, export Excel. */
class LearningObjectiveController extends Controller
{
    public function __construct(private LearningObjectiveRepo $learningObjectiveRepo)
    {
    }

    public function index()
    {
        return view('learning-objective.index', $this->crudPermissions('LEARNINGOBJECTIVE') + [
            'subjects' => Subject::query()->where('is_active', true)->orderBy('report_order')->orderBy('subject_name')->get(['subject_id', 'subject_name']),
        ]);
    }

    public function search(Request $request)
    {
        return $this->jsonData(fn () => $this->learningObjectiveRepo->search(...$this->filters($request)));
    }

    public function getByKey(Request $request)
    {
        return $this->jsonData(fn () => $this->learningObjectiveRepo->getByKey((int) $request->input('learningObjectiveId')), 'Tujuan pembelajaran tidak ditemukan.');
    }

    public function addEdit(Request $request)
    {
        $data = $request->only(['LearningObjectiveId', 'SubjectId', 'GradeLevel', 'SemesterNo', 'ObjectiveCode', 'Description']);
        $data['IsActive'] = $request->boolean('IsActive');

        return $this->saveByMode(
            (string) $request->input('screenMode'),
            'LEARNINGOBJECTIVE',
            fn () => $this->learningObjectiveRepo->insert($data, $this->loginUsername()),
            fn () => $this->learningObjectiveRepo->update($data, $this->loginUsername()),
        );
    }

    public function deleteMultiple(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('selectedIds', []));

        return $this->deleteMany(PermissionCodes::LearningObjectiveDelete, $ids, fn (int $id) => $this->learningObjectiveRepo->delete($id));
    }

    public function downloadExcel(Request $request)
    {
        return $this->exportExcel(
            'Tujuan Pembelajaran',
            'TP',
            ['Mata Pelajaran', 'Kelas', 'Fase', 'Semester', 'Kode', 'Deskripsi', 'Status'],
            $this->learningObjectiveRepo->search(...$this->filters($request)),
            fn (object $o) => [$o->SubjectName, $o->GradeLevel, $o->Phase, $o->SemesterName, $o->ObjectiveCode, $o->Description, $o->IsActive ? 'Aktif' : 'Nonaktif'],
        );
    }

    private function filters(Request $request): array
    {
        return [
            ((int) $request->input('subjectId')) ?: null,
            ((int) $request->input('gradeLevel')) ?: null,
            ((int) $request->input('semesterNo')) ?: null,
        ];
    }
}
