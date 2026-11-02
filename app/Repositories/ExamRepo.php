<?php

namespace App\Repositories;

use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\Semester;
use App\Models\Subject;
use App\Support\Academic;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Kegiatan ujian (STS/SAS/ASAJ) per semester + jadwal per tingkat. exam_code menjadi awalan nomor peserta.
 */
class ExamRepo
{
    public const Types = ['STS' => 'Sumatif Tengah Semester', 'SAS' => 'Sumatif Akhir Semester', 'ASAJ' => 'Asesmen Sumatif Akhir Jenjang', 'LAINNYA' => 'Lainnya'];

    /** @return object[] */
    public function search(?int $semesterId): array
    {
        return Exam::query()->with('semester.academicYear')->withCount(['participants', 'schedules'])
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Exam $e) => $this->mapExam($e))
            ->all();
    }

    public function getByKey(int $examId): ?object
    {
        $exam = Exam::query()->with('semester.academicYear')->withCount(['participants', 'schedules'])->find($examId);

        return $exam === null ? null : $this->mapExam($exam);
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        $code = mb_strtoupper(trim((string) ($data['ExamCode'] ?? '')));
        if (!preg_match('/^[A-Z0-9]{2,15}$/', $code)) {
            return new DBResult(false, 'Kode ujian wajib diisi, 2-15 huruf/angka tanpa spasi (mis. SAS2627).');
        }
        if (Exam::query()->where('exam_code', $code)->exists()) {
            return new DBResult(false, "Kode ujian {$code} sudah dipakai.");
        }
        if ($error = $this->validate($fields)) {
            return new DBResult(false, $error);
        }

        $exam = Exam::create($fields + ['exam_code' => $code, 'created_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil disimpan.', $exam->exam_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $exam = Exam::query()->find($data['ExamId'] ?? 0);
        if ($exam === null) {
            return new DBResult(false, 'Ujian tidak ditemukan.');
        }
        $fields = $this->fields($data);
        if ($error = $this->validate($fields)) {
            return new DBResult(false, $error);
        }
        if ($fields['semester_id'] !== $exam->semester_id && $exam->participants()->exists()) {
            return new DBResult(false, 'Semester tidak bisa diubah karena ujian sudah punya peserta.');
        }

        // Kode tidak ikut di-update: sudah dipakai di nomor peserta.
        $exam->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data berhasil diperbarui.');
    }

    public function delete(int $examId): DBResult
    {
        $exam = Exam::query()->find($examId);
        if ($exam === null) {
            return new DBResult(false, 'Ujian tidak ditemukan.');
        }

        // Jadwal & peserta ikut terhapus (cascade).
        $exam->delete();

        return new DBResult(true, 'Data berhasil dihapus.');
    }

    /** @return object[] */
    public function schedules(int $examId): array
    {
        return ExamSchedule::query()->with('subject')->where('exam_id', $examId)
            ->orderBy('exam_date')->orderBy('start_time')->orderBy('grade_level')->get()
            ->map(fn (ExamSchedule $s) => (object) [
                'ExamScheduleId' => $s->exam_schedule_id,
                'GradeLevel' => $s->grade_level,
                'SubjectId' => $s->subject_id,
                'SubjectName' => $s->subject?->subject_name,
                'ExamDate' => $s->exam_date->format('Y-m-d'),
                'StartTime' => substr($s->start_time, 0, 5),
                'EndTime' => substr($s->end_time, 0, 5),
            ])
            ->all();
    }

    /** Ganti seluruh jadwal ujian. @param array<int, array<string, mixed>> $rows */
    public function saveSchedules(int $examId, array $rows, string $loginUser): DBResult
    {
        $exam = Exam::query()->find($examId);
        if ($exam === null) {
            return new DBResult(false, 'Ujian tidak ditemukan.');
        }
        $subjects = Subject::query()->pluck('subject_name', 'subject_id');
        $records = [];
        $seen = [];
        foreach ($rows as $i => $row) {
            $line = $i + 1;
            $grade = (int) ($row['GradeLevel'] ?? 0);
            $subjectId = (int) ($row['SubjectId'] ?? 0);
            $date = (string) ($row['ExamDate'] ?? '');
            $start = $this->time($row['StartTime'] ?? null);
            $end = $this->time($row['EndTime'] ?? null);
            $error = match (true) {
                $grade < 1 || $grade > 6 => 'kelas wajib diisi',
                !isset($subjects[$subjectId]) => 'mapel wajib diisi',
                strtotime($date) === false => 'tanggal wajib diisi',
                $date < $exam->start_date->format('Y-m-d') || $date > $exam->end_date->format('Y-m-d') => 'tanggal di luar periode ujian',
                $start === null || $end === null || $start >= $end => 'jam mulai/selesai tidak valid (HH:MM)',
                isset($seen["{$grade}-{$subjectId}"]) => "kelas {$grade} {$subjects[$subjectId]} tercantum dua kali",
                default => null,
            };
            if ($error !== null) {
                return new DBResult(false, "Baris {$line}: {$error}.");
            }
            $seen["{$grade}-{$subjectId}"] = true;
            $records[] = ['exam_id' => $examId, 'grade_level' => $grade, 'subject_id' => $subjectId, 'exam_date' => $date,
                'start_time' => $start, 'end_time' => $end, 'created_by' => $loginUser, 'created_at' => now()];
        }

        DB::transaction(function () use ($examId, $records) {
            ExamSchedule::query()->where('exam_id', $examId)->delete();
            ExamSchedule::query()->insert($records);
        });

        return new DBResult(true, 'Jadwal ujian berhasil disimpan (' . count($records) . ' mapel).');
    }

    private function fields(array $data): array
    {
        return [
            'semester_id' => (int) ($data['SemesterId'] ?? 0),
            'exam_name' => mb_substr(trim((string) ($data['ExamName'] ?? '')), 0, 100),
            'exam_type' => (string) ($data['ExamType'] ?? ''),
            'start_date' => trim((string) ($data['StartDate'] ?? '')) ?: null,
            'end_date' => trim((string) ($data['EndDate'] ?? '')) ?: null,
            'card_note' => mb_substr(trim((string) ($data['CardNote'] ?? '')), 0, 255) ?: null,
        ];
    }

    private function validate(array $fields): ?string
    {
        return match (true) {
            !Semester::query()->whereKey($fields['semester_id'])->exists() => 'Semester wajib dipilih.',
            $fields['exam_name'] === '' => 'Nama ujian wajib diisi.',
            !isset(self::Types[$fields['exam_type']]) => 'Jenis ujian wajib dipilih.',
            $fields['start_date'] === null || $fields['end_date'] === null => 'Tanggal mulai dan selesai wajib diisi.',
            $fields['start_date'] > $fields['end_date'] => 'Tanggal mulai harus sebelum tanggal selesai.',
            default => null,
        };
    }

    private function time(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value . ':00' : null;
    }

    private function mapExam(Exam $e): object
    {
        return (object) [
            'ExamId' => $e->exam_id,
            'SemesterId' => $e->semester_id,
            'SemesterLabel' => Academic::semesterLabel($e->semester),
            'ExamCode' => $e->exam_code,
            'ExamName' => $e->exam_name,
            'ExamType' => $e->exam_type,
            'ExamTypeName' => self::Types[$e->exam_type] ?? $e->exam_type,
            'StartDate' => $e->start_date->format('Y-m-d'),
            'EndDate' => $e->end_date->format('Y-m-d'),
            'CardNote' => $e->card_note,
            'ScheduleCount' => $e->schedules_count ?? 0,
            'ParticipantCount' => $e->participants_count ?? 0,
            'CreatedBy' => $e->created_by,
            'CreatedDate' => $e->created_at,
            'ModifiedBy' => $e->updated_by,
            'ModifiedDate' => $e->updated_at,
        ];
    }
}
