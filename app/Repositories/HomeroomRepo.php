<?php

namespace App\Repositories;

use App\Models\Classroom;
use App\Models\Extracurricular;
use App\Models\ReportNote;
use App\Models\Semester;
use App\Models\StudentExtracurricular;
use App\Support\AuditTrail;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Data rapor yang diisi wali kelas: ketidakhadiran (S/I/A), catatan kokurikuler, catatan wali kelas,
 * keputusan kenaikan (semester Genap), dan predikat ekstrakurikuler.
 */
class HomeroomRepo
{
    public const Decisions = ['NAIK' => 'Naik kelas', 'TINGGAL' => 'Tinggal kelas', 'LULUS' => 'Lulus'];

    public const Predicates = ['Sangat Baik', 'Baik', 'Cukup', 'Kurang'];

    public function __construct(private AssessmentRepo $assessmentRepo, private AttendanceRepo $attendanceRepo)
    {
    }

    /** @return object[] */
    public function sheet(Semester $semester, int $classroomId): array
    {
        $students = $this->assessmentRepo->students($classroomId);
        $notes = ReportNote::query()->where('semester_id', $semester->semester_id)->whereIn('student_id', $students->pluck('student_id'))
            ->get()->keyBy('student_id');

        return $students->map(function ($s) use ($notes) {
            $n = $notes->get($s->student_id);

            return (object) [
                'StudentId' => $s->student_id,
                'Nis' => $s->nis,
                'StudentName' => $s->student_name,
                'Sick' => $n?->sick ?? 0,
                'Permit' => $n?->permit ?? 0,
                'Absent' => $n?->absent ?? 0,
                'CocurricularNote' => $n?->cocurricular_note,
                'HomeroomNote' => $n?->homeroom_note,
                'PromotionDecision' => $n?->promotion_decision,
            ];
        })->values()->all();
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function save(Semester $semester, int $classroomId, array $rows, string $loginUser): DBResult
    {
        $classroom = Classroom::query()->find($classroomId);
        $studentIds = $this->assessmentRepo->students($classroomId)->pluck('student_id')->all();
        $count = fn ($v) => max(0, min(999, (int) $v));

        DB::transaction(function () use ($rows, $studentIds, $semester, $classroomId, $loginUser, $count) {
            foreach ($rows as $row) {
                $studentId = (int) ($row['StudentId'] ?? 0);
                if (!in_array($studentId, $studentIds, true)) {
                    continue;
                }
                $decision = (string) ($row['PromotionDecision'] ?? '');
                $values = [
                    'classroom_id' => $classroomId,
                    'sick' => $count($row['Sick'] ?? 0),
                    'permit' => $count($row['Permit'] ?? 0),
                    'absent' => $count($row['Absent'] ?? 0),
                    'cocurricular_note' => trim((string) ($row['CocurricularNote'] ?? '')) ?: null,
                    'homeroom_note' => trim((string) ($row['HomeroomNote'] ?? '')) ?: null,
                    // Keputusan kenaikan hanya di semester Genap.
                    'promotion_decision' => $semester->semester_no === 2 && isset(self::Decisions[$decision]) ? $decision : null,
                ];
                $existing = ReportNote::query()->where('semester_id', $semester->semester_id)->where('student_id', $studentId)->first();
                if ($existing === null) {
                    ReportNote::create($values + ['semester_id' => $semester->semester_id, 'student_id' => $studentId, 'created_by' => $loginUser]);
                } else {
                    $existing->update($values + ['updated_by' => $loginUser]);
                }
            }
        });
        AuditTrail::log('UPDATE', 'Wali Kelas', (string) $classroomId, $classroom?->classroom_name, "Catatan rapor rombel {$classroom?->classroom_name} disimpan");

        return new DBResult(true, 'Data wali kelas berhasil disimpan.');
    }

    /** Jumlah S/I/A dari presensi selama semester (sampai tanggal rapor / akhir semester). */
    public function attendanceTotals(Semester $semester, int $classroomId): array
    {
        $to = ($semester->report_date ?? $semester->end_date)->format('Y-m-d');

        return $this->attendanceRepo->totals($classroomId, $semester->start_date->format('Y-m-d'), $to);
    }

    /** @return array{extracurriculars: object[], rows: object[]} kolom E{id} = predikat */
    public function extracurricularSheet(Semester $semester, int $classroomId): array
    {
        $students = $this->assessmentRepo->students($classroomId);
        $values = StudentExtracurricular::query()->where('semester_id', $semester->semester_id)
            ->whereIn('student_id', $students->pluck('student_id'))->get()->groupBy('student_id');
        $extracurriculars = Extracurricular::query()->where('is_active', true)
            ->orWhereIn('extracurricular_id', StudentExtracurricular::query()->where('semester_id', $semester->semester_id)->select('extracurricular_id'))
            ->orderByDesc('is_mandatory')->orderBy('extracurricular_name')->get();

        return [
            'extracurriculars' => $extracurriculars->map(fn ($e) => (object) [
                'ExtracurricularId' => $e->extracurricular_id, 'ExtracurricularName' => $e->extracurricular_name, 'IsMandatory' => $e->is_mandatory,
            ])->all(),
            'rows' => $students->map(function ($s) use ($values) {
                $row = ['StudentId' => $s->student_id, 'Nis' => $s->nis, 'StudentName' => $s->student_name];
                foreach ($values->get($s->student_id, collect()) as $v) {
                    $row['E' . $v->extracurricular_id] = $v->predicate;
                }

                return (object) $row;
            })->values()->all(),
        ];
    }

    /** @param array<int, array{StudentId: int, Values: array<string, ?string>}> $rows Values: [extracurricularId => predikat|""] */
    public function saveExtracurricular(Semester $semester, int $classroomId, array $rows, string $loginUser): DBResult
    {
        $studentIds = $this->assessmentRepo->students($classroomId)->pluck('student_id')->all();
        $extracurricularIds = Extracurricular::query()->pluck('extracurricular_id')->all();

        DB::transaction(function () use ($rows, $studentIds, $extracurricularIds, $semester, $loginUser) {
            foreach ($rows as $row) {
                $studentId = (int) ($row['StudentId'] ?? 0);
                if (!in_array($studentId, $studentIds, true)) {
                    continue;
                }
                foreach ((array) ($row['Values'] ?? []) as $extracurricularId => $predicate) {
                    if (!in_array((int) $extracurricularId, $extracurricularIds, true)) {
                        continue;
                    }
                    $query = StudentExtracurricular::query()->where('semester_id', $semester->semester_id)
                        ->where('student_id', $studentId)->where('extracurricular_id', (int) $extracurricularId);
                    $existing = $query->first();
                    if (!in_array($predicate, self::Predicates, true)) {
                        $existing?->delete();
                    } elseif ($existing === null) {
                        StudentExtracurricular::create(['semester_id' => $semester->semester_id, 'student_id' => $studentId,
                            'extracurricular_id' => (int) $extracurricularId, 'predicate' => $predicate, 'created_by' => $loginUser]);
                    } elseif ($existing->predicate !== $predicate) {
                        $existing->update(['predicate' => $predicate, 'updated_by' => $loginUser]);
                    }
                }
            }
        });

        return new DBResult(true, 'Nilai ekstrakurikuler berhasil disimpan.');
    }
}
