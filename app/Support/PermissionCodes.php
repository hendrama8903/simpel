<?php

namespace App\Support;

/**
 * Harus sama dengan permissions.permission_code di database (lihat DatabaseSeeder).
 * Konvensi: <PREFIX>-ADD / <PREFIX>-EDIT / <PREFIX>-DELETE, dipakai Controller::crudPermissions().
 * <PREFIX>-ALL = boleh melihat semua rombel; tanpa itu guru hanya melihat rombel yang ia walikan/ajar (Academic::classrooms).
 * Tambahkan konstanta baru di sini setiap membuat menu baru.
 */
final class PermissionCodes
{
    // ---------- Pengaturan Sekolah ----------
    public const SchoolEdit = 'SCHOOL-EDIT';
    public const AcademicYearAdd = 'ACADEMICYEAR-ADD';
    public const AcademicYearEdit = 'ACADEMICYEAR-EDIT';
    public const AcademicYearDelete = 'ACADEMICYEAR-DELETE';

    // ---------- Data Master ----------
    public const SubjectAdd = 'SUBJECT-ADD';
    public const SubjectEdit = 'SUBJECT-EDIT';
    public const SubjectDelete = 'SUBJECT-DELETE';
    public const ClassroomAdd = 'CLASSROOM-ADD';
    public const ClassroomEdit = 'CLASSROOM-EDIT';
    public const ClassroomDelete = 'CLASSROOM-DELETE';
    public const RoomAdd = 'ROOM-ADD';
    public const RoomEdit = 'ROOM-EDIT';
    public const RoomDelete = 'ROOM-DELETE';
    public const ExtracurricularAdd = 'EXTRACURRICULAR-ADD';
    public const ExtracurricularEdit = 'EXTRACURRICULAR-EDIT';
    public const ExtracurricularDelete = 'EXTRACURRICULAR-DELETE';

    // ---------- Kepegawaian ----------
    public const TeacherAdd = 'TEACHER-ADD';
    public const TeacherEdit = 'TEACHER-EDIT';
    public const TeacherDelete = 'TEACHER-DELETE';
    public const TeacherUpload = 'TEACHER-UPLOAD';
    public const TeacherAccount = 'TEACHER-ACCOUNT';
    public const TeachingAssignmentEdit = 'TEACHINGASSIGNMENT-EDIT';

    // ---------- Kesiswaan ----------
    public const StudentAdd = 'STUDENT-ADD';
    public const StudentEdit = 'STUDENT-EDIT';
    public const StudentDelete = 'STUDENT-DELETE';
    public const StudentUpload = 'STUDENT-UPLOAD';
    public const ClassMemberEdit = 'CLASSMEMBER-EDIT';
    public const StudentExitAdd = 'STUDENTEXIT-ADD';
    public const StudentExitEdit = 'STUDENTEXIT-EDIT';
    public const StudentExitDelete = 'STUDENTEXIT-DELETE';

    // ---------- Akademik ----------
    public const ScheduleEdit = 'SCHEDULE-EDIT';
    public const ScheduleAll = 'SCHEDULE-ALL';
    public const AttendanceEdit = 'ATTENDANCE-EDIT';
    public const AttendanceAll = 'ATTENDANCE-ALL';
    public const LearningObjectiveAdd = 'LEARNINGOBJECTIVE-ADD';
    public const LearningObjectiveEdit = 'LEARNINGOBJECTIVE-EDIT';
    public const LearningObjectiveDelete = 'LEARNINGOBJECTIVE-DELETE';
    public const AssessmentEdit = 'ASSESSMENT-EDIT';
    public const AssessmentAll = 'ASSESSMENT-ALL';
    public const ReportGradeEdit = 'REPORTGRADE-EDIT';
    public const ReportGradeAll = 'REPORTGRADE-ALL';
    public const HomeroomEdit = 'HOMEROOM-EDIT';
    public const HomeroomAll = 'HOMEROOM-ALL';
    public const ReportCardPrint = 'REPORTCARD-PRINT';
    public const ReportCardAll = 'REPORTCARD-ALL';

    // ---------- Ujian ----------
    public const ExamAdd = 'EXAM-ADD';
    public const ExamEdit = 'EXAM-EDIT';
    public const ExamDelete = 'EXAM-DELETE';
    public const ExamParticipantEdit = 'EXAMPARTICIPANT-EDIT';
    public const ExamParticipantPrint = 'EXAMPARTICIPANT-PRINT';

    // ---------- Administrasi ----------
    public const UserAdd = 'USER-ADD';
    public const UserEdit = 'USER-EDIT';
    public const UserDelete = 'USER-DELETE';
    public const UserResetPassword = 'USER-RESETPASSWORD';

    public const RoleAdd = 'ROLE-ADD';
    public const RoleEdit = 'ROLE-EDIT';
    public const RoleDelete = 'ROLE-DELETE';
    public const RoleAccess = 'ROLE-ACCESS';

    public const MenuAdd = 'MENU-ADD';
    public const MenuEdit = 'MENU-EDIT';
    public const MenuDelete = 'MENU-DELETE';
}
