<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Repositories\AttendanceRepo;
use App\Support\Academic;
use App\Support\PermissionCodes;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Presensi harian siswa per rombel + rekap bulanan. */
class AttendanceController extends Controller
{
    private const Prefix = 'ATTENDANCE';

    public function __construct(private AttendanceRepo $attendanceRepo)
    {
    }

    public function index()
    {
        return view('attendance.index', [
            'edit' => $this->hasPermission(PermissionCodes::AttendanceEdit),
            'semesters' => Academic::semesterOptions(),
            'statuses' => Academic::AttendanceStatuses,
        ]);
    }

    public function classrooms(Request $request)
    {
        return $this->jsonData(function () use ($request) {
            $semester = Academic::semester((int) $request->input('semesterId'));

            return $semester === null ? [] : [
                'classrooms' => Academic::classrooms(self::Prefix, $semester),
                'startDate' => $semester->start_date->format('Y-m-d'),
                'endDate' => $semester->end_date->format('Y-m-d'),
            ];
        });
    }

    public function sheet(Request $request)
    {
        return $this->jsonData(function () use ($request) {
            [$semester, $classroomId] = [Academic::semester((int) $request->input('semesterId')), (int) $request->input('classroomId')];

            return Academic::canAccessClassroom(self::Prefix, $semester, $classroomId)
                ? $this->attendanceRepo->sheet($classroomId, (string) $request->input('date'))
                : null;
        }, 'Rombel tidak ditemukan atau Anda tidak punya akses.');
    }

    public function save(Request $request)
    {
        if (!$this->hasPermission(PermissionCodes::AttendanceEdit)) {
            return $this->permissionDenied();
        }
        $semester = Academic::semester((int) $request->input('semesterId'));
        $classroomId = (int) $request->input('classroomId');
        if (!Academic::canAccessClassroom(self::Prefix, $semester, $classroomId)) {
            return $this->permissionDenied();
        }
        $rows = json_decode((string) $request->input('rows', '[]'), true);

        return $this->runJson(fn () => $this->attendanceRepo->save($classroomId, (string) $request->input('date'), is_array($rows) ? $rows : [], $this->loginUsername()));
    }

    public function recap(Request $request)
    {
        return $this->jsonData(function () use ($request) {
            [$semester, $classroomId] = [Academic::semester((int) $request->input('semesterId')), (int) $request->input('classroomId')];
            $month = (string) $request->input('month');
            if (!preg_match('/^\d{4}-\d{2}$/', $month) || !Academic::canAccessClassroom(self::Prefix, $semester, $classroomId)) {
                return null;
            }

            return $this->attendanceRepo->recap($classroomId, $month);
        }, 'Rombel/bulan tidak valid atau Anda tidak punya akses.');
    }

    public function downloadRecap(Request $request)
    {
        $semester = Academic::semester((int) $request->input('semesterId'));
        $classroomId = (int) $request->input('classroomId');
        $month = (string) $request->input('month');
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month) && Academic::canAccessClassroom(self::Prefix, $semester, $classroomId), 403);

        $classroom = Classroom::query()->findOrFail($classroomId);
        $recap = $this->attendanceRepo->recap($classroomId, $month);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap');
        $sheet->setCellValue('A1', "Rekap Presensi {$classroom->classroom_name} - {$recap['month']}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $headers = array_merge(['No', 'NIS', 'Nama'], range(1, $recap['days']), ['H', 'S', 'I', 'A', '% Hadir']);
        foreach ($headers as $i => $h) {
            $sheet->getCell([$i + 1, 3])->setValue($h);
            $sheet->getStyle([$i + 1, 3])->getFont()->setBold(true);
        }
        foreach ($recap['rows'] as $n => $r) {
            $values = [$n + 1, $r->Nis, $r->StudentName];
            for ($d = 1; $d <= $recap['days']; $d++) {
                $values[] = $r->{"D{$d}"} ?? '';
            }
            array_push($values, $r->H, $r->S, $r->I, $r->A, $r->Percent ?? '');
            foreach ($values as $i => $v) {
                $sheet->getCell([$i + 1, $n + 4])->setValueExplicit($v, is_string($v) ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            }
        }
        $sheet->getColumnDimension('C')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), "Presensi_{$classroom->classroom_name}_{$month}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
