<?php

namespace App\Repositories;

use App\Models\Classroom;
use App\Models\ReportGrade;
use App\Models\ReportNote;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentExtracurricular;
use App\Models\Subject;
use App\Support\Academic;

/** Kelengkapan & data cetak rapor (Kurikulum Merdeka) per siswa. */
class ReportCardRepo
{
    public function __construct(private AssessmentRepo $assessmentRepo)
    {
    }

    /** @return object[] anggota rombel + kelengkapan data rapornya */
    public function search(Semester $semester, int $classroomId): array
    {
        $students = $this->assessmentRepo->students($classroomId);
        $ids = $students->pluck('student_id');
        $grades = ReportGrade::query()->where('semester_id', $semester->semester_id)->whereIn('student_id', $ids)->whereNotNull('final_score')
            ->selectRaw('student_id, COUNT(*) AS total')->groupBy('student_id')->pluck('total', 'student_id');
        $notes = ReportNote::query()->where('semester_id', $semester->semester_id)->whereIn('student_id', $ids)->get()->keyBy('student_id');
        $subjectCount = Subject::query()->where('is_active', true)->count();

        return $students->map(fn (Student $s) => (object) [
            'StudentId' => $s->student_id,
            'Nis' => $s->nis,
            'Nisn' => $s->nisn,
            'StudentName' => $s->student_name,
            'GradeCount' => (int) ($grades[$s->student_id] ?? 0),
            'SubjectCount' => $subjectCount,
            'HasNote' => $notes->get($s->student_id)?->homeroom_note !== null,
            'Status' => $s->status,
        ])->values()->all();
    }

    /** Data satu rapor per siswa untuk view PDF. @return object[] */
    public function printData(Semester $semester, int $classroomId, array $studentIds): array
    {
        $classroom = Classroom::query()->with(['homeroomTeacher', 'academicYear'])->findOrFail($classroomId);
        $students = $this->assessmentRepo->students($classroomId)->when($studentIds !== [], fn ($c) => $c->whereIn('student_id', $studentIds));
        $ids = $students->pluck('student_id');

        $grades = ReportGrade::query()->with('subject')->where('semester_id', $semester->semester_id)->whereIn('student_id', $ids)->get()->groupBy('student_id');
        $notes = ReportNote::query()->where('semester_id', $semester->semester_id)->whereIn('student_id', $ids)->get()->keyBy('student_id');
        $extras = StudentExtracurricular::query()->with('extracurricular')->where('semester_id', $semester->semester_id)
            ->whereIn('student_id', $ids)->get()->groupBy('student_id');

        return $students->map(function (Student $s) use ($grades, $notes, $extras, $classroom) {
            $subjects = $grades->get($s->student_id, collect())
                ->filter(fn ($g) => $g->subject !== null && ($g->final_score !== null || $g->description_high !== null))
                ->sortBy(fn ($g) => [$g->subject->subject_group === 'MULOK' ? 1 : 0, $g->subject->report_order, $g->subject->subject_name])
                ->values();
            $note = $notes->get($s->student_id);

            return (object) [
                'student' => $s,
                'subjects' => $subjects,
                'note' => $note,
                'extracurriculars' => $extras->get($s->student_id, collect())->sortBy(fn ($e) => $e->extracurricular->extracurricular_name)->values(),
                'decision' => $this->decisionText($note?->promotion_decision, $classroom->grade_level),
            ];
        })->values()->all();
    }

    private function decisionText(?string $decision, int $gradeLevel): ?string
    {
        return match ($decision) {
            'NAIK' => 'Naik ke kelas ' . ($gradeLevel + 1) . ' (' . Academic::roman($gradeLevel + 1) . ')',
            'TINGGAL' => 'Tinggal di kelas ' . $gradeLevel . ' (' . Academic::roman($gradeLevel) . ')',
            'LULUS' => 'Lulus',
            default => null,
        };
    }
}
