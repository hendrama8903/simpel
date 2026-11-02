<?php

namespace App\Http\Controllers;

use App\Repositories\ReportGradeRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Nilai rapor per rombel x mapel: hitung otomatis dari penilaian, lalu boleh diubah manual. */
class ReportGradeController extends Controller
{
    private const Prefix = 'REPORTGRADE';

    public function __construct(private ReportGradeRepo $reportGradeRepo)
    {
    }

    public function index()
    {
        return view('report-grade.index', [
            'edit' => $this->hasPermission(PermissionCodes::ReportGradeEdit),
            'semesters' => Academic::semesterOptions(),
            'kktp' => Academic::school()->kktp,
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
            'status' => true, 'data' => $this->reportGradeRepo->sheet($semester, $classroomId, $subjectId),
        ]));
    }

    public function generate(Request $request)
    {
        return $this->withAccess($request, fn ($semester, $classroomId, $subjectId) => $this->runJson(fn () => $this->reportGradeRepo->generate(
            $semester, $classroomId, $subjectId, $request->boolean('overwriteManual'), $this->loginUsername(),
        )), true);
    }

    public function save(Request $request)
    {
        return $this->withAccess($request, function ($semester, $classroomId, $subjectId) use ($request) {
            $rows = json_decode((string) $request->input('rows', '[]'), true);

            return $this->runJson(fn () => $this->reportGradeRepo->save($semester, $classroomId, $subjectId, is_array($rows) ? $rows : [], $this->loginUsername()));
        }, true);
    }

    private function withAccess(Request $request, callable $action, bool $isWrite = false): JsonResponse
    {
        if ($isWrite && !$this->hasPermission(PermissionCodes::ReportGradeEdit)) {
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
