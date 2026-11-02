<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Repositories\ReportCardRepo;
use App\Support\Academic;
use App\Support\AuditTrail;
use App\Support\PermissionCodes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** Cetak rapor PDF per siswa / satu rombel. Tanpa REPORTCARD-ALL hanya rombel yang diwalikan. */
class ReportCardController extends Controller
{
    private const Prefix = 'REPORTCARD';

    public function __construct(private ReportCardRepo $reportCardRepo)
    {
    }

    public function index()
    {
        return view('report-card.index', [
            'print' => $this->hasPermission(PermissionCodes::ReportCardPrint),
            'semesters' => Academic::semesterOptions(),
        ]);
    }

    public function classrooms(Request $request)
    {
        return $this->jsonData(function () use ($request) {
            $classrooms = Academic::classrooms(self::Prefix, Academic::semester((int) $request->input('semesterId')));

            return Academic::canSeeAll(self::Prefix) ? $classrooms : array_values(array_filter($classrooms, fn ($c) => $c->IsHomeroom));
        });
    }

    public function search(Request $request)
    {
        return $this->jsonData(function () use ($request) {
            [$semester, $classroomId] = [Academic::semester((int) $request->input('semesterId')), (int) $request->input('classroomId')];

            return Academic::canManageHomeroom(self::Prefix, $semester, $classroomId) ? $this->reportCardRepo->search($semester, $classroomId) : null;
        }, 'Rombel tidak ditemukan atau Anda bukan wali kelasnya.');
    }

    public function print(Request $request)
    {
        abort_unless($this->hasPermission(PermissionCodes::ReportCardPrint), 403);
        $semester = Academic::semester((int) $request->input('semesterId'));
        $classroomId = (int) $request->input('classroomId');
        abort_unless(Academic::canManageHomeroom(self::Prefix, $semester, $classroomId), 403);

        $studentIds = array_values(array_filter(array_map('intval', explode(',', (string) $request->input('studentIds')))));
        $classroom = Classroom::query()->with(['homeroomTeacher', 'academicYear'])->findOrFail($classroomId);
        $reports = $this->reportCardRepo->printData($semester, $classroomId, $studentIds);
        abort_if($reports === [], 404, 'Tidak ada siswa untuk dicetak.');

        AuditTrail::log('PRINT', 'Cetak Rapor', (string) $classroomId, $classroom->classroom_name,
            'Cetak rapor ' . count($reports) . " siswa rombel {$classroom->classroom_name} " . Academic::semesterLabel($semester));
        set_time_limit(300);

        return Pdf::loadView('report-card.pdf', [
            'reports' => $reports,
            'school' => Academic::school(),
            'classroom' => $classroom,
            'semester' => $semester,
            'withCover' => $request->boolean('cover'),
        ])->setPaper('a4')->stream('Rapor_' . $classroom->classroom_name . '_' . str_replace('/', '-', $classroom->academicYear->year_name) . "_S{$semester->semester_no}.pdf");
    }
}
