<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Menu;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Repositories\ReportGradeRepo;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur utama sekolah di atas data DemoSeeder (TA 2026/2027 Ganjil aktif, rombel 1A-6A @20 siswa,
 * akun admin/Admin@123, guru1 = wali 1A, guru.pai = guru PAI semua rombel, password guru Guru@12345).
 */
class SchoolFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function login(string $username, string $password = 'Guru@12345'): void
    {
        $this->post('/Auth/Validate', ['username' => $username, 'password' => $password])->assertJson(['status' => true]);
    }

    private function ajax(string $url, array $data = [])
    {
        return $this->post($url, $data, ['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function classroom(string $name): Classroom
    {
        return Classroom::query()->where('classroom_name', $name)->firstOrFail();
    }

    public function test_admin_can_open_every_menu(): void
    {
        $this->login('admin', 'Admin@123');

        foreach (Menu::query()->whereNotNull('menu_url')->pluck('menu_url') as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_report_grade_uses_weights_and_describes_objectives_against_kktp(): void
    {
        $this->login('admin', 'Admin@123');
        $semester = Semester::query()->where('is_active', true)->firstOrFail();
        $classroom = $this->classroom('1A');
        $subject = Subject::query()->where('subject_code', 'MTK')->firstOrFail();
        $base = ['semesterId' => $semester->semester_id, 'classroomId' => $classroom->classroom_id, 'subjectId' => $subject->subject_id];
        $objectives = \App\Models\LearningObjective::query()->where('subject_id', $subject->subject_id)->where('grade_level', 1)
            ->orderBy('objective_code')->pluck('learning_objective_id');

        $columns = [];
        foreach ([['FORMATIF', $objectives[0]], ['FORMATIF', $objectives[1]], ['SUMATIF', null], ['STS', null], ['SAS', null]] as [$type, $objectiveId]) {
            $columns[] = $this->ajax('/Assessment/SaveColumn', $base + ['AssessmentType' => $type, 'LearningObjectiveId' => $objectiveId])
                ->assertJson(['status' => true])->json('returnId');
        }
        $student = Student::query()->whereIn('student_id', $classroom->members()->select('student_id'))->firstOrFail();
        // TP1 = 90 (tuntas), TP2 = 60 (< KKTP 70); sumatif 80, STS 70, SAS 90 -> (80*50 + 70*25 + 90*25) / 100 = 80.
        $scores = array_combine($columns, [90, 60, 80, 70, 90]);
        $this->ajax('/Assessment/SaveScores', $base + ['rows' => json_encode([['StudentId' => $student->student_id, 'Scores' => $scores]])])
            ->assertJson(['status' => true]);
        $this->ajax('/Assessment/SaveScores', $base + ['rows' => json_encode([['StudentId' => $student->student_id, 'Scores' => [$columns[0] => 101]]])])
            ->assertJson(['status' => false]);

        $this->ajax('/ReportGrade/Generate', $base)->assertJson(['status' => true]);
        $row = collect($this->ajax('/ReportGrade/Sheet', $base)->json('data'))->firstWhere('StudentId', $student->student_id);

        $this->assertEquals(80, $row['FinalScore']);
        $this->assertStringContainsString('menunjukkan penguasaan yang baik dalam memahami nilai tempat bilangan cacah', $row['DescriptionHigh']);
        $this->assertStringContainsString('perlu bantuan dalam melakukan operasi penjumlahan dan pengurangan', $row['DescriptionLow']);

        // Perubahan manual tidak ditimpa saat Hitung ulang, kecuali diminta.
        $this->ajax('/ReportGrade/Save', $base + ['rows' => json_encode([['StudentId' => $student->student_id, 'FinalScore' => 85, 'DescriptionHigh' => 'Manual.']])]);
        $this->ajax('/ReportGrade/Generate', $base);
        $this->assertEquals(85, collect($this->ajax('/ReportGrade/Sheet', $base)->json('data'))->firstWhere('StudentId', $student->student_id)['FinalScore']);
        $this->ajax('/ReportGrade/Generate', $base + ['overwriteManual' => 1]);
        $this->assertEquals(80, collect($this->ajax('/ReportGrade/Sheet', $base)->json('data'))->firstWhere('StudentId', $student->student_id)['FinalScore']);

        $this->get('/ReportCard/Print?' . http_build_query(['semesterId' => $semester->semester_id, 'classroomId' => $classroom->classroom_id, 'cover' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_description_mentions_lowest_objective_when_all_are_achieved(): void
    {
        [$high, $low] = app(ReportGradeRepo::class)->describe('Budi', [
            1 => ['description' => 'Membaca lancar', 'scores' => [95]],
            2 => ['description' => 'menulis rapi', 'scores' => [80, 90]],
            3 => ['description' => 'berhitung', 'scores' => [75]],
        ], 70);

        $this->assertSame('Ananda Budi menunjukkan penguasaan yang baik dalam membaca lancar dan menulis rapi.', $high);
        $this->assertSame('Ananda Budi perlu peningkatan dalam berhitung.', $low);
    }

    public function test_teachers_only_reach_their_own_classrooms_and_subjects(): void
    {
        $semesterId = Semester::query()->where('is_active', true)->value('semester_id');
        $own = $this->classroom('1A');
        $other = $this->classroom('2A');

        $this->login('guru1');
        $this->assertSame(['1A'], collect($this->ajax('/Attendance/Classrooms', ['semesterId' => $semesterId])->json('data.classrooms'))->pluck('ClassroomName')->all());
        $this->ajax('/Attendance/Sheet', ['semesterId' => $semesterId, 'classroomId' => $other->classroom_id, 'date' => '2026-11-02'])->assertJson(['status' => false]);
        $subjects = collect($this->ajax('/Assessment/Subjects', ['semesterId' => $semesterId, 'classroomId' => $own->classroom_id])->json('data'))->pluck('SubjectCode');
        $this->assertContains('MTK', $subjects);
        $this->assertNotContains('PAI', $subjects);
        $this->ajax('/Teacher/Search')->assertForbidden();

        $this->login('guru.pai');
        $this->assertSame(['PAI'], collect($this->ajax('/Assessment/Subjects', ['semesterId' => $semesterId, 'classroomId' => $own->classroom_id])->json('data'))->pluck('SubjectCode')->all());
        $this->ajax('/Homeroom/Sheet', ['semesterId' => $semesterId, 'classroomId' => $own->classroom_id])->assertForbidden();
    }

    public function test_attendance_is_upserted_per_day_and_excludes_students_who_left(): void
    {
        $this->login('guru1');
        $semesterId = Semester::query()->where('is_active', true)->value('semester_id');
        $classroom = $this->classroom('1A');
        [$a, $b] = $classroom->members()->orderBy('student_id')->limit(2)->pluck('student_id')->all();
        $base = ['semesterId' => $semesterId, 'classroomId' => $classroom->classroom_id, 'date' => '2026-10-05'];

        $this->ajax('/Attendance/Save', $base + ['rows' => json_encode([['StudentId' => $a, 'Status' => 'S'], ['StudentId' => $b, 'Status' => 'A']])])->assertJson(['status' => true]);
        $this->ajax('/Attendance/Save', $base + ['rows' => json_encode([['StudentId' => $a, 'Status' => 'H'], ['StudentId' => $b, 'Status' => '']])])->assertJson(['status' => true]);
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', ['student_id' => $a, 'status' => 'H']);

        $this->login('admin', 'Admin@123');
        $this->ajax('/StudentExit/AddEdit', ['screenMode' => 'ADD', 'StudentId' => $b, 'ExitType' => 'MUTASI', 'ExitDate' => '2026-10-10', 'DestinationSchool' => 'SDN 2'])
            ->assertJson(['status' => true]);
        $this->assertSame('MUTASI', Student::query()->find($b)->status);
        $ids = fn (string $date) => collect($this->ajax('/Attendance/Sheet', ['date' => $date] + $base)->json('data'))->pluck('StudentId');
        $this->assertContains($b, $ids('2026-10-09'));
        $this->assertNotContains($b, $ids('2026-10-10'));
    }

    public function test_student_can_only_join_one_classroom_per_year(): void
    {
        $this->login('admin', 'Admin@123');
        $student = Student::create(['nis' => '2027001', 'student_name' => 'Siswa Baru', 'gender' => 'L', 'created_by' => 'test']);

        $this->ajax('/ClassMember/Add', ['classroomId' => $this->classroom('1A')->classroom_id, 'studentIds' => [$student->student_id]])->assertJson(['status' => true]);
        $this->ajax('/ClassMember/Add', ['classroomId' => $this->classroom('2A')->classroom_id, 'studentIds' => [$student->student_id]])->assertJson(['status' => false]);
        $this->assertNotContains($student->student_id, collect($this->ajax('/ClassMember/Candidates', ['classroomId' => $this->classroom('2A')->classroom_id])->json('data'))->pluck('StudentId'));
    }

    public function test_exam_participants_are_numbered_per_grade_and_seated_by_room_capacity(): void
    {
        $this->login('admin', 'Admin@123');
        $examId = \App\Models\Exam::query()->where('exam_code', 'SAS2627')->value('exam_id');

        $this->ajax('/ExamParticipant/Generate', ['examId' => $examId, 'gradeLevels' => [1, 2]])->assertJson(['status' => true]);
        $this->ajax('/ExamParticipant/Generate', ['examId' => $examId, 'gradeLevels' => [1]])->assertJson(['status' => false]);
        $rooms = \App\Models\Room::query()->orderBy('room_code')->pluck('room_id')->all();
        $this->ajax('/ExamParticipant/AssignRooms', ['examId' => $examId, 'roomIds' => [$rooms[0]], 'onlyEmpty' => 1])->assertJson(['status' => false]);
        $this->ajax('/ExamParticipant/AssignRooms', ['examId' => $examId, 'roomIds' => [$rooms[0], $rooms[1]], 'onlyEmpty' => 1])->assertJson(['status' => true]);

        $participants = collect($this->ajax('/ExamParticipant/Search', ['examId' => $examId])->json('data.participants'));
        $this->assertCount(40, $participants);
        $this->assertSame('SAS2627-1-001', $participants->first()['ParticipantNo']);
        $this->assertSame('SAS2627-2-020', $participants->last()['ParticipantNo']);
        $this->assertSame(24, $participants->where('RoomId', $rooms[0])->count());
        $this->assertSame(1, $participants[24]['SeatNo']);

        $this->get("/ExamParticipant/PrintCards?examId={$examId}")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}
