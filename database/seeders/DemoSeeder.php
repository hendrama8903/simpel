<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\LearningObjective;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\UserAccount;
use App\Support\PasswordHasher;
use Faker\Factory;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk development/demo (JANGAN dijalankan di production):
 *   php artisan db:seed --class=DemoSeeder
 *
 * Membuat profil sekolah, TA 2026/2027 (Ganjil aktif), 6 ruang & rombel 1A-6A, guru + akun login,
 * 20 siswa per rombel, tugas mengajar guru mapel, TP Matematika & Bahasa Indonesia, jadwal, dan ujian SAS.
 * Akun guru (password Guru@12345): kepsek, guru1 .. guru6 (wali kelas 1A..6A), guru.pai, guru.pjok.
 */
class DemoSeeder extends Seeder
{
    private const Password = 'Guru@12345';

    public function run(): void
    {
        $by = 'demo';
        $faker = Factory::create('id_ID');
        $faker->seed(2026);

        School::query()->firstOrCreate([], [
            'npsn' => '20212345', 'school_name' => 'SD Negeri Contoh 1', 'school_level' => 'SD',
            'address' => 'Jl. Pendidikan No. 1', 'village' => 'Sukamaju', 'district' => 'Cibeunying', 'city' => 'Bandung',
            'province' => 'Jawa Barat', 'postal_code' => '40123', 'phone' => '022-1234567', 'email' => 'sdn.contoh1@example.sch.id',
            'principal_name' => 'Dra. Hj. Euis Kurniasih, M.Pd.', 'principal_nip' => '196805121992032005', 'report_place' => 'Bandung',
            'created_by' => $by,
        ]);

        $year = AcademicYear::query()->firstOrCreate(['year_name' => '2026/2027'], [
            'start_date' => '2026-07-13', 'end_date' => '2027-06-19', 'created_by' => $by,
        ]);
        $semester = Semester::query()->firstOrCreate(['academic_year_id' => $year->academic_year_id, 'semester_no' => 1], [
            'start_date' => '2026-07-13', 'end_date' => '2026-12-19', 'report_date' => '2026-12-19', 'created_by' => $by,
        ]);
        Semester::query()->firstOrCreate(['academic_year_id' => $year->academic_year_id, 'semester_no' => 2], [
            'start_date' => '2027-01-04', 'end_date' => '2027-06-19', 'report_date' => '2027-06-19', 'created_by' => $by,
        ]);
        if (!Semester::query()->where('is_active', true)->exists()) {
            $semester->update(['is_active' => true]);
        }

        // ---------- Guru + akun ----------
        $roles = Role::query()->pluck('role_id', 'role_code');
        $teacher = function (string $username, string $name, string $gender, string $position, string $roleCode, ?string $nip) use ($roles, $by) {
            $user = UserAccount::query()->firstOrCreate(['username' => $username], [
                'full_name' => $name, 'role_id' => $roles[$roleCode], 'is_ad_user' => false,
                'password_hash' => PasswordHasher::hash(self::Password), 'employee_no' => $nip, 'created_by' => $by,
            ]);

            return Teacher::query()->firstOrCreate(['user_id' => $user->user_id], [
                'nip' => $nip, 'teacher_name' => $name, 'gender' => $gender, 'position' => $position,
                'employment_status' => $nip ? 'PNS' : 'GTY', 'created_by' => $by,
            ]);
        };
        $teacher('kepsek', 'Dra. Hj. Euis Kurniasih, M.Pd.', 'P', 'KEPSEK', 'KEPSEK', '196805121992032005');
        $homerooms = [];
        foreach ([
            ['Siti Aminah, S.Pd.', 'P'], ['Rina Marlina, S.Pd.', 'P'], ['Dedi Supriadi, S.Pd.', 'L'],
            ['Nurul Hidayah, S.Pd.', 'P'], ['Agus Setiawan, S.Pd.', 'L'], ['Yuli Rahmawati, S.Pd.', 'P'],
        ] as $i => [$name, $gender]) {
            $homerooms[$i + 1] = $teacher('guru' . ($i + 1), $name, $gender, 'GURU_KELAS', 'GURU_KELAS', '19850' . ($i + 1) . '0120100120' . ($i + 1));
        }
        $pai = $teacher('guru.pai', 'Ahmad Fauzan, S.Pd.I.', 'L', 'GURU_MAPEL', 'GURU_MAPEL', null);
        $pjok = $teacher('guru.pjok', 'Rizky Pratama, S.Pd.', 'L', 'GURU_MAPEL', 'GURU_MAPEL', null);

        // ---------- Ruang, rombel, siswa ----------
        $subjects = Subject::query()->pluck('subject_id', 'subject_code');
        $nis = 2026000;
        $busy = [];
        foreach ($homerooms as $grade => $homeroom) {
            $room = Room::query()->firstOrCreate(['room_code' => 'R0' . $grade], ['room_name' => "Ruang Kelas {$grade}", 'capacity' => 24, 'created_by' => $by]);
            $classroom = Classroom::query()->firstOrCreate(['academic_year_id' => $year->academic_year_id, 'classroom_name' => "{$grade}A"], [
                'grade_level' => $grade, 'homeroom_teacher_id' => $homeroom->teacher_id, 'room_id' => $room->room_id, 'created_by' => $by,
            ]);

            if (!$classroom->members()->exists()) {
                for ($n = 0; $n < 20; $n++) {
                    $gender = $faker->randomElement(['L', 'P']);
                    $student = Student::create([
                        'nis' => (string) (++$nis),
                        'nisn' => '01' . $faker->unique()->numerify('########'),
                        'student_name' => $faker->firstName($gender === 'L' ? 'male' : 'female') . ' ' . $faker->lastName(),
                        'gender' => $gender,
                        'birth_place' => $faker->randomElement(['Bandung', 'Cimahi', 'Garut', 'Sumedang', 'Jakarta']),
                        'birth_date' => now()->setDate(2026 - 6 - $grade, $faker->numberBetween(1, 12), $faker->numberBetween(1, 28))->toDateString(),
                        'religion' => $faker->randomElement(['Islam', 'Islam', 'Islam', 'Islam', 'Kristen', 'Katolik']),
                        'address' => $faker->streetAddress(),
                        'father_name' => $faker->firstNameMale() . ' ' . $faker->lastName(),
                        'mother_name' => $faker->firstNameFemale() . ' ' . $faker->lastName(),
                        'parent_phone' => '08' . $faker->numerify('##########'),
                        'entry_type' => 'BARU',
                        'entry_date' => (2026 - $grade + 1) . '-07-13',
                        'entry_grade' => 1,
                        'created_by' => $by,
                    ]);
                    ClassroomStudent::create(['classroom_id' => $classroom->classroom_id, 'student_id' => $student->student_id, 'created_by' => $by]);
                }
            }

            foreach (['PAI' => $pai, 'PJOK' => $pjok] as $code => $t) {
                TeachingAssignment::query()->firstOrCreate(['semester_id' => $semester->semester_id, 'classroom_id' => $classroom->classroom_id,
                    'subject_id' => $subjects[$code]], ['teacher_id' => $t->teacher_id, 'created_by' => $by]);
            }

            // Jadwal Senin-Jumat, 6 jam @35 menit (istirahat setelah jam ke-3). Slot guru mapel (PAI, PJOK) dipilih
            // lebih dulu di jam yang gurunya masih kosong, sisanya diisi mapel guru kelas berurutan.
            if (!Schedule::query()->where('semester_id', $semester->semester_id)->where('classroom_id', $classroom->classroom_id)->exists()) {
                $times = [1 => ['07:00', '07:35'], ['07:35', '08:10'], ['08:10', '08:45'], ['09:00', '09:35'], ['09:35', '10:10'], ['10:10', '10:45']];
                $slots = [];
                foreach (range(1, 5) as $day) {
                    foreach (array_keys($times) as $period) {
                        $slots[] = [$day, $period];
                    }
                }
                $week = [];
                foreach (['PAI' => [$pai, 3], 'PJOK' => [$pjok, 2]] as $code => [$t, $count]) {
                    for ($i = 0; $i < count($slots) && $count > 0; $i++) {
                        [$day, $period] = $slots[($i + $grade * 7) % count($slots)];
                        if (!isset($week[$day][$period]) && !isset($busy[$t->teacher_id][$day][$period])) {
                            $week[$day][$period] = $code;
                            $busy[$t->teacher_id][$day][$period] = true;
                            $count--;
                        }
                    }
                }
                $others = array_merge(array_fill(0, 3, 'PP'), array_fill(0, 6, 'BIND'), array_fill(0, 6, 'MTK'), array_fill(0, 4, 'IPAS'),
                    array_fill(0, 2, 'SENI'), array_fill(0, 2, 'BING'), array_fill(0, 2, 'BDAE'));
                foreach ($slots as [$day, $period]) {
                    $code = $week[$day][$period] ?? array_shift($others);
                    Schedule::create([
                        'semester_id' => $semester->semester_id, 'classroom_id' => $classroom->classroom_id, 'day_of_week' => $day,
                        'period_no' => $period, 'start_time' => $times[$period][0], 'end_time' => $times[$period][1], 'subject_id' => $subjects[$code],
                        'teacher_id' => match ($code) { 'PAI' => $pai->teacher_id, 'PJOK' => $pjok->teacher_id, 'BING' => null, default => $homeroom->teacher_id },
                        'created_by' => $by,
                    ]);
                }
            }
        }

        // ---------- Tujuan pembelajaran contoh (semester Ganjil) ----------
        $objectives = [
            'MTK' => ['memahami nilai tempat bilangan cacah', 'melakukan operasi penjumlahan dan pengurangan', 'mengenal bangun datar dan sifat-sifatnya',
                'mengukur panjang dan berat benda'],
            'BIND' => ['menyimak informasi dari teks yang dibacakan', 'membaca teks pendek dengan lafal dan intonasi yang tepat',
                'menulis kalimat sederhana dengan ejaan yang benar', 'menceritakan kembali isi teks secara lisan'],
        ];
        foreach ($objectives as $code => $list) {
            for ($grade = 1; $grade <= 6; $grade++) {
                foreach ($list as $i => $description) {
                    LearningObjective::query()->firstOrCreate(['subject_id' => $subjects[$code], 'grade_level' => $grade, 'semester_no' => 1,
                        'objective_code' => 'TP' . ($i + 1)], ['description' => $description, 'created_by' => $by]);
                }
            }
        }

        // ---------- Ujian SAS Ganjil ----------
        $exam = Exam::query()->firstOrCreate(['exam_code' => 'SAS2627'], [
            'semester_id' => $semester->semester_id, 'exam_name' => 'Sumatif Akhir Semester Ganjil', 'exam_type' => 'SAS',
            'start_date' => '2026-12-01', 'end_date' => '2026-12-05', 'card_note' => 'Kartu wajib dibawa dan ditunjukkan kepada pengawas setiap hari ujian.',
            'created_by' => $by,
        ]);
        if (!$exam->schedules()->exists()) {
            foreach ([['2026-12-01', ['PP', 'BIND']], ['2026-12-02', ['MTK', 'IPAS']], ['2026-12-03', ['PAI', 'SENI']], ['2026-12-04', ['BING', 'BDAE']]] as [$date, $codes]) {
                foreach ($codes as $i => $code) {
                    for ($grade = 1; $grade <= 6; $grade++) {
                        ExamSchedule::create(['exam_id' => $exam->exam_id, 'grade_level' => $grade, 'subject_id' => $subjects[$code], 'exam_date' => $date,
                            'start_time' => $i === 0 ? '07:30' : '09:30', 'end_time' => $i === 0 ? '09:00' : '11:00', 'created_by' => $by]);
                    }
                }
            }
        }
    }
}
