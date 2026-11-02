<?php

namespace App\Http\Controllers;

use App\Repositories\HomeroomRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Data rapor dari wali kelas: ketidakhadiran, catatan, kokurikuler, kenaikan kelas, ekstrakurikuler. */
class HomeroomController extends Controller
{
    private const Prefix = 'HOMEROOM';

    public function __construct(private HomeroomRepo $homeroomRepo)
    {
    }

    public function index()
    {
        return view('homeroom.index', [
            'edit' => $this->hasPermission(PermissionCodes::HomeroomEdit),
            'semesters' => Academic::semesterOptions(),
            'decisions' => HomeroomRepo::Decisions,
            'predicates' => HomeroomRepo::Predicates,
        ]);
    }

    /** Tanpa HOMEROOM-ALL hanya rombel yang diwalikan. */
    public function classrooms(Request $request)
    {
        return $this->jsonData(function () use ($request) {
            $semester = Academic::semester((int) $request->input('semesterId'));
            $classrooms = Academic::classrooms(self::Prefix, $semester);

            return [
                'semesterNo' => $semester?->semester_no,
                'classrooms' => Academic::canSeeAll(self::Prefix) ? $classrooms : array_values(array_filter($classrooms, fn ($c) => $c->IsHomeroom)),
            ];
        });
    }

    public function sheet(Request $request)
    {
        return $this->withAccess($request, fn ($semester, $classroomId) => response()->json([
            'status' => true, 'data' => $this->homeroomRepo->sheet($semester, $classroomId),
        ]));
    }

    public function save(Request $request)
    {
        return $this->withAccess($request, function ($semester, $classroomId) use ($request) {
            $rows = json_decode((string) $request->input('rows', '[]'), true);

            return $this->runJson(fn () => $this->homeroomRepo->save($semester, $classroomId, is_array($rows) ? $rows : [], $this->loginUsername()));
        }, true);
    }

    public function fillAttendance(Request $request)
    {
        return $this->withAccess($request, fn ($semester, $classroomId) => response()->json([
            'status' => true, 'data' => (object) $this->homeroomRepo->attendanceTotals($semester, $classroomId),
        ]));
    }

    public function extracurricularSheet(Request $request)
    {
        return $this->withAccess($request, fn ($semester, $classroomId) => response()->json([
            'status' => true, 'data' => $this->homeroomRepo->extracurricularSheet($semester, $classroomId),
        ]));
    }

    public function saveExtracurricular(Request $request)
    {
        return $this->withAccess($request, function ($semester, $classroomId) use ($request) {
            $rows = json_decode((string) $request->input('rows', '[]'), true);

            return $this->runJson(fn () => $this->homeroomRepo->saveExtracurricular($semester, $classroomId, is_array($rows) ? $rows : [], $this->loginUsername()));
        }, true);
    }

    private function withAccess(Request $request, callable $action, bool $isWrite = false): JsonResponse
    {
        if ($isWrite && !$this->hasPermission(PermissionCodes::HomeroomEdit)) {
            return $this->permissionDenied();
        }
        $semester = Academic::semester((int) $request->input('semesterId'));
        $classroomId = (int) $request->input('classroomId');
        if (!Academic::canManageHomeroom(self::Prefix, $semester, $classroomId)) {
            return response()->json(['status' => false, 'message' => 'Rombel tidak ditemukan atau Anda bukan wali kelasnya.']);
        }

        return $action($semester, $classroomId);
    }
}
