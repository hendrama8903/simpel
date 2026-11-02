<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Subject;
use App\Repositories\AssessmentRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Input nilai per rombel x mapel. Akses rombel & mapel dibatasi lewat Academic (permission ASSESSMENT-ALL,
 * wali kelas, atau tugas mengajar).
 */
class AssessmentController extends Controller
{
    private const Prefix = 'ASSESSMENT';

    public function __construct(private AssessmentRepo $assessmentRepo)
    {
    }

    public function index()
    {
        return view('assessment.index', [
            'edit' => $this->hasPermission(PermissionCodes::AssessmentEdit),
            'semesters' => Academic::semesterOptions(),
            'types' => AssessmentRepo::Types,
        ]);
    }

    public function classrooms(Request $request)
    {
        return $this->jsonData(fn () => Academic::classrooms(self::Prefix, Academic::semester((int) $request->input('semesterId'))));
    }

    public function subjects(Request $request)
    {
        return $this->jsonData(fn () => Academic::subjects(self::Prefix, Academic::semester((int) $request->input('semesterId')), (int) $request->input('classroomId')));
    }

    public function sheet(Request $request)
    {
        return $this->withAccess($request, fn ($semester, $classroomId, $subjectId) => response()->json([
            'status' => true, 'data' => $this->assessmentRepo->sheet($semester, $classroomId, $subjectId),
        ]));
    }

    public function saveScores(Request $request)
    {
        return $this->withAccess($request, function ($semester, $classroomId, $subjectId) use ($request) {
            $rows = json_decode((string) $request->input('rows', '[]'), true);

            return $this->runJson(fn () => $this->assessmentRepo->saveScores($semester, $classroomId, $subjectId, is_array($rows) ? $rows : [], $this->loginUsername()));
        }, true);
    }

    public function saveColumn(Request $request)
    {
        return $this->withAccess($request, fn ($semester, $classroomId, $subjectId) => $this->runJson(fn () => $this->assessmentRepo->saveColumn(
            $semester, $classroomId, $subjectId,
            $request->only(['AssessmentId', 'AssessmentType', 'LearningObjectiveId', 'AssessmentName', 'AssessmentDate']),
            $this->loginUsername(),
        )), true);
    }

    public function deleteColumn(Request $request)
    {
        return $this->withAccess($request, fn ($semester, $classroomId, $subjectId) => $this->runJson(fn () => $this->assessmentRepo->deleteColumn(
            $semester, $classroomId, $subjectId, (int) $request->input('assessmentId'),
        )), true);
    }

    public function downloadExcel(Request $request)
    {
        $semester = Academic::semester((int) $request->input('semesterId'));
        [$classroomId, $subjectId] = [(int) $request->input('classroomId'), (int) $request->input('subjectId')];
        abort_unless(Academic::canAccessSubject(self::Prefix, $semester, $classroomId, $subjectId), 403);

        $sheet = $this->assessmentRepo->sheet($semester, $classroomId, $subjectId);
        $classroom = Classroom::query()->find($classroomId);
        $subject = Subject::query()->find($subjectId);

        return $this->exportExcel(
            'Nilai',
            "NILAI_{$classroom->classroom_name}_{$subject->subject_code}",
            array_merge(['NIS', 'Nama'], array_map(fn ($c) => $c->AssessmentName, $sheet['columns'])),
            $sheet['rows'],
            fn (object $r) => array_merge([$r->Nis, $r->StudentName], array_map(fn ($c) => $r->{'A' . $c->AssessmentId} ?? null, $sheet['columns'])),
        );
    }

    /** Jalankan aksi hanya bila user boleh mengakses rombel & mapel tsb (dan, untuk perubahan, punya ASSESSMENT-EDIT). */
    private function withAccess(Request $request, callable $action, bool $isWrite = false): JsonResponse
    {
        if ($isWrite && !$this->hasPermission(PermissionCodes::AssessmentEdit)) {
            return $this->permissionDenied();
        }
        $semester = Academic::semester((int) $request->input('semesterId'));
        [$classroomId, $subjectId] = [(int) $request->input('classroomId'), (int) $request->input('subjectId')];
        if (!Academic::canAccessSubject(self::Prefix, $semester, $classroomId, $subjectId)) {
            return response()->json(['status' => false, 'message' => 'Rombel/mapel tidak ditemukan atau Anda tidak punya akses.']);
        }

        return $action($semester, $classroomId, $subjectId);
    }
}
