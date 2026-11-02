<?php

namespace App\Support;

use App\Models\Classroom;
use App\Models\School;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;

/**
 * Konteks akademik bersama: semester aktif, profil sekolah, label (hari, fase, semester),
 * dan pembatasan data per guru.
 *
 * Pembatasan rombel: menu akademik punya permission "<PREFIX>-ALL" (Semua Rombel). User tanpa permission itu
 * hanya melihat rombel yang ia walikan atau ia ajar (teaching_assignments), lewat guru yang terhubung ke akunnya.
 */
final class Academic
{
    public const Days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

    public const AttendanceStatuses = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'];

    public const Religions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];

    private static ?Semester $activeSemester = null;

    private static bool $activeLoaded = false;

    private static array $teacherIds = [];

    public static function school(): School
    {
        return School::query()->first() ?? new School(['school_name' => config('app.name'), 'kktp' => 70,
            'weight_summative' => 50, 'weight_sts' => 25, 'weight_sas' => 25]);
    }

    public static function activeSemester(): ?Semester
    {
        if (!self::$activeLoaded) {
            self::$activeSemester = Semester::query()->with('academicYear')->where('is_active', true)->first();
            self::$activeLoaded = true;
        }

        return self::$activeSemester;
    }

    /** Semester dari request (semesterId) atau semester aktif. */
    public static function semester(?int $semesterId): ?Semester
    {
        if ($semesterId) {
            return Semester::query()->with('academicYear')->find($semesterId);
        }

        return self::activeSemester();
    }

    public static function semesterName(int $semesterNo): string
    {
        return $semesterNo === 1 ? 'Ganjil' : 'Genap';
    }

    public static function semesterLabel(?Semester $semester): string
    {
        return $semester === null ? '-' : $semester->academicYear->year_name . ' ' . self::semesterName($semester->semester_no);
    }

    /** Daftar semua semester untuk dropdown, terbaru dulu. */
    public static function semesterOptions(): array
    {
        return Semester::query()->with('academicYear')
            ->join('academic_years', 'academic_years.academic_year_id', '=', 'semesters.academic_year_id')
            ->orderByDesc('academic_years.year_name')->orderByDesc('semesters.semester_no')
            ->select('semesters.*')
            ->get()
            ->map(fn (Semester $s) => (object) [
                'SemesterId' => $s->semester_id,
                'Label' => self::semesterLabel($s),
                'IsActive' => $s->is_active,
            ])
            ->all();
    }

    /** Fase Kurikulum Merdeka SD: A (kelas 1-2), B (3-4), C (5-6). */
    public static function phase(int $gradeLevel): string
    {
        return match (true) {
            $gradeLevel <= 2 => 'A',
            $gradeLevel <= 4 => 'B',
            default => 'C',
        };
    }

    public static function roman(int $number): string
    {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$number] ?? (string) $number;
    }

    /** Guru yang terhubung ke akun login (null kalau akun bukan guru). */
    public static function teacherId(): ?int
    {
        $userId = SessionAuth::userId();
        if ($userId === null) {
            return null;
        }
        if (!array_key_exists($userId, self::$teacherIds)) {
            self::$teacherIds[$userId] = Teacher::query()->where('user_id', $userId)->value('teacher_id');
        }

        return self::$teacherIds[$userId];
    }

    public static function canSeeAll(string $permissionPrefix): bool
    {
        return SessionAuth::hasPermission($permissionPrefix . '-ALL');
    }

    /**
     * Rombel tahun ajaran semester tsb yang boleh diakses di menu ber-prefix permission $permissionPrefix.
     *
     * @return object[] {ClassroomId, ClassroomName, GradeLevel, IsHomeroom}
     */
    public static function classrooms(string $permissionPrefix, ?Semester $semester): array
    {
        if ($semester === null) {
            return [];
        }

        $query = Classroom::query()->where('academic_year_id', $semester->academic_year_id);
        $teacherId = self::teacherId();
        if (!self::canSeeAll($permissionPrefix)) {
            if ($teacherId === null) {
                return [];
            }
            $taught = TeachingAssignment::query()->where('semester_id', $semester->semester_id)
                ->where('teacher_id', $teacherId)->pluck('classroom_id');
            $query->where(fn ($q) => $q->where('homeroom_teacher_id', $teacherId)->orWhereIn('classroom_id', $taught));
        }

        return $query->orderBy('grade_level')->orderBy('classroom_name')->get()
            ->map(fn (Classroom $c) => (object) [
                'ClassroomId' => $c->classroom_id,
                'ClassroomName' => $c->classroom_name,
                'GradeLevel' => $c->grade_level,
                'IsHomeroom' => $teacherId !== null && $c->homeroom_teacher_id === $teacherId,
            ])
            ->all();
    }

    public static function canAccessClassroom(string $permissionPrefix, ?Semester $semester, int $classroomId): bool
    {
        foreach (self::classrooms($permissionPrefix, $semester) as $c) {
            if ($c->ClassroomId === $classroomId) {
                return true;
            }
        }

        return false;
    }

    /** Hanya wali kelas rombel tsb (atau yang punya <PREFIX>-ALL). Dipakai menu Wali Kelas & Cetak Rapor. */
    public static function canManageHomeroom(string $permissionPrefix, ?Semester $semester, int $classroomId): bool
    {
        if (self::canSeeAll($permissionPrefix)) {
            return self::canAccessClassroom($permissionPrefix, $semester, $classroomId);
        }
        $teacherId = self::teacherId();

        return $teacherId !== null && Classroom::query()->where('classroom_id', $classroomId)
            ->where('homeroom_teacher_id', $teacherId)->exists();
    }

    /**
     * Mapel yang boleh dinilai user di satu rombel:
     * - <PREFIX>-ALL: semua mapel aktif;
     * - wali kelas: mapel "diajar guru kelas" + mapel yang ditugaskan kepadanya;
     * - guru mapel: hanya mapel yang ditugaskan kepadanya di rombel tsb.
     *
     * @return object[] {SubjectId, SubjectCode, SubjectName}
     */
    public static function subjects(string $permissionPrefix, ?Semester $semester, int $classroomId): array
    {
        if ($semester === null || !self::canAccessClassroom($permissionPrefix, $semester, $classroomId)) {
            return [];
        }

        $query = Subject::query()->where('is_active', true);
        if (!self::canSeeAll($permissionPrefix)) {
            $teacherId = self::teacherId();
            $assigned = TeachingAssignment::query()->where('semester_id', $semester->semester_id)
                ->where('classroom_id', $classroomId)->where('teacher_id', $teacherId)->pluck('subject_id');
            $isHomeroom = Classroom::query()->where('classroom_id', $classroomId)->where('homeroom_teacher_id', $teacherId)->exists();
            // Mapel guru kelas yang sudah ditugaskan ke guru lain tidak lagi dipegang wali kelas.
            $assignedToOthers = TeachingAssignment::query()->where('semester_id', $semester->semester_id)
                ->where('classroom_id', $classroomId)->where('teacher_id', '!=', $teacherId)->pluck('subject_id');
            $query->where(fn ($q) => $q->whereIn('subject_id', $assigned)
                ->when($isHomeroom, fn ($q2) => $q2->orWhere(fn ($q3) => $q3
                    ->where('taught_by', 'GURU_KELAS')->whereNotIn('subject_id', $assignedToOthers))));
        }

        return $query->orderBy('report_order')->orderBy('subject_name')->get()
            ->map(fn (Subject $s) => (object) [
                'SubjectId' => $s->subject_id,
                'SubjectCode' => $s->subject_code,
                'SubjectName' => $s->subject_name,
            ])
            ->all();
    }

    public static function canAccessSubject(string $permissionPrefix, ?Semester $semester, int $classroomId, int $subjectId): bool
    {
        foreach (self::subjects($permissionPrefix, $semester, $classroomId) as $s) {
            if ($s->SubjectId === $subjectId) {
                return true;
            }
        }

        return false;
    }

    /** Teks tanggal Indonesia, mis. "2 November 2026". */
    public static function dateText(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return \Illuminate\Support\Carbon::parse($date)->locale('id')->translatedFormat('j F Y');
    }
}
