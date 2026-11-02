<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassMemberController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamParticipantController;
use App\Http\Controllers\ExtracurricularController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HomeroomController;
use App\Http\Controllers\LearningObjectiveController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportGradeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentExitController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| Path ditulis PascalCase "/{Controller}/{Action}" dan HARUS sama persis dengan
| menus.menu_url (dipakai middleware MenuAccess, judul halaman, dan link sidebar).
| Satu menu = satu group middleware('menu.access:/<Menu>/Index') supaya semua
| endpoint AJAX-nya ikut terlindungi hak akses menu.
|
*/

Route::get('/', [HomeController::class, 'index'])->middleware('menu.access');
Route::get('/Home/Index', [HomeController::class, 'index'])->middleware('menu.access');

Route::get('/Auth/Login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/Auth/Validate', [AuthController::class, 'validateLogin']);
Route::post('/Auth/ChangePassword', [AuthController::class, 'changePassword'])->middleware('menu.access');
Route::get('/Auth/Logout', [AuthController::class, 'logout']);

/** Endpoint CRUD standar (HMA.crudPage): Index, Search, GetByKey, AddEdit, DeleteMultiple, DownloadExcel. */
$crud = function (string $name, string $controller) {
    Route::get("/{$name}/Index", [$controller, 'index']);
    Route::post("/{$name}/Search", [$controller, 'search']);
    Route::post("/{$name}/GetByKey", [$controller, 'getByKey']);
    Route::post("/{$name}/AddEdit", [$controller, 'addEdit']);
    Route::post("/{$name}/DeleteMultiple", [$controller, 'deleteMultiple']);
    Route::get("/{$name}/DownloadExcel", [$controller, 'downloadExcel']);
};

// ---------- Pengaturan Sekolah ----------

Route::middleware('menu.access:/School/Index')->group(function () {
    Route::get('/School/Index', [SchoolController::class, 'index']);
    Route::post('/School/Save', [SchoolController::class, 'save']);
    Route::post('/School/UploadLogo', [SchoolController::class, 'uploadLogo']);
});

Route::middleware('menu.access:/AcademicYear/Index')->group(fn () => $crud('AcademicYear', AcademicYearController::class));

// ---------- Data Master ----------

Route::middleware('menu.access:/Subject/Index')->group(fn () => $crud('Subject', SubjectController::class));

Route::middleware('menu.access:/Classroom/Index')->group(function () use ($crud) {
    $crud('Classroom', ClassroomController::class);
    Route::post('/Classroom/CopyFrom', [ClassroomController::class, 'copyFrom']);
});

Route::middleware('menu.access:/Room/Index')->group(fn () => $crud('Room', RoomController::class));
Route::middleware('menu.access:/Extracurricular/Index')->group(fn () => $crud('Extracurricular', ExtracurricularController::class));

// ---------- Kepegawaian ----------

Route::middleware('menu.access:/Teacher/Index')->group(function () use ($crud) {
    $crud('Teacher', TeacherController::class);
    Route::post('/Teacher/CreateAccounts', [TeacherController::class, 'createAccounts']);
    Route::post('/Teacher/Upload', [TeacherController::class, 'upload']);
    Route::get('/Teacher/DownloadTemplate', [TeacherController::class, 'downloadTemplate']);
});

Route::middleware('menu.access:/TeachingAssignment/Index')->group(function () {
    Route::get('/TeachingAssignment/Index', [TeachingAssignmentController::class, 'index']);
    Route::post('/TeachingAssignment/Sheet', [TeachingAssignmentController::class, 'sheet']);
    Route::post('/TeachingAssignment/Save', [TeachingAssignmentController::class, 'save']);
    Route::post('/TeachingAssignment/CopyFromSemester', [TeachingAssignmentController::class, 'copyFromSemester']);
    Route::get('/TeachingAssignment/DownloadExcel', [TeachingAssignmentController::class, 'downloadExcel']);
});

// ---------- Kesiswaan ----------

Route::middleware('menu.access:/Student/Index')->group(function () use ($crud) {
    $crud('Student', StudentController::class);
    Route::post('/Student/UploadPhoto', [StudentController::class, 'uploadPhoto']);
    Route::get('/Student/Photo', [StudentController::class, 'photo']);
    Route::post('/Student/Upload', [StudentController::class, 'upload']);
    Route::get('/Student/DownloadTemplate', [StudentController::class, 'downloadTemplate']);
});

Route::middleware('menu.access:/ClassMember/Index')->group(function () {
    Route::get('/ClassMember/Index', [ClassMemberController::class, 'index']);
    Route::post('/ClassMember/Members', [ClassMemberController::class, 'members']);
    Route::post('/ClassMember/Candidates', [ClassMemberController::class, 'candidates']);
    Route::post('/ClassMember/Add', [ClassMemberController::class, 'add']);
    Route::post('/ClassMember/Remove', [ClassMemberController::class, 'remove']);
});

Route::middleware('menu.access:/StudentExit/Index')->group(function () use ($crud) {
    $crud('StudentExit', StudentExitController::class);
    Route::post('/StudentExit/Graduate', [StudentExitController::class, 'graduate']);
});

// ---------- Akademik ----------

Route::middleware('menu.access:/Schedule/Index')->group(function () {
    Route::get('/Schedule/Index', [ScheduleController::class, 'index']);
    Route::post('/Schedule/Classrooms', [ScheduleController::class, 'classrooms']);
    Route::post('/Schedule/Sheet', [ScheduleController::class, 'sheet']);
    Route::post('/Schedule/Save', [ScheduleController::class, 'save']);
    Route::get('/Schedule/Print', [ScheduleController::class, 'print']);
});

Route::middleware('menu.access:/Attendance/Index')->group(function () {
    Route::get('/Attendance/Index', [AttendanceController::class, 'index']);
    Route::post('/Attendance/Classrooms', [AttendanceController::class, 'classrooms']);
    Route::post('/Attendance/Sheet', [AttendanceController::class, 'sheet']);
    Route::post('/Attendance/Save', [AttendanceController::class, 'save']);
    Route::post('/Attendance/Recap', [AttendanceController::class, 'recap']);
    Route::get('/Attendance/DownloadRecap', [AttendanceController::class, 'downloadRecap']);
});

Route::middleware('menu.access:/LearningObjective/Index')->group(fn () => $crud('LearningObjective', LearningObjectiveController::class));

Route::middleware('menu.access:/Assessment/Index')->group(function () {
    Route::get('/Assessment/Index', [AssessmentController::class, 'index']);
    Route::post('/Assessment/Classrooms', [AssessmentController::class, 'classrooms']);
    Route::post('/Assessment/Subjects', [AssessmentController::class, 'subjects']);
    Route::post('/Assessment/Sheet', [AssessmentController::class, 'sheet']);
    Route::post('/Assessment/SaveScores', [AssessmentController::class, 'saveScores']);
    Route::post('/Assessment/SaveColumn', [AssessmentController::class, 'saveColumn']);
    Route::post('/Assessment/DeleteColumn', [AssessmentController::class, 'deleteColumn']);
    Route::get('/Assessment/DownloadExcel', [AssessmentController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/ReportGrade/Index')->group(function () {
    Route::get('/ReportGrade/Index', [ReportGradeController::class, 'index']);
    Route::post('/ReportGrade/Classrooms', [ReportGradeController::class, 'classrooms']);
    Route::post('/ReportGrade/Subjects', [ReportGradeController::class, 'subjects']);
    Route::post('/ReportGrade/Sheet', [ReportGradeController::class, 'sheet']);
    Route::post('/ReportGrade/Generate', [ReportGradeController::class, 'generate']);
    Route::post('/ReportGrade/Save', [ReportGradeController::class, 'save']);
});

Route::middleware('menu.access:/Homeroom/Index')->group(function () {
    Route::get('/Homeroom/Index', [HomeroomController::class, 'index']);
    Route::post('/Homeroom/Classrooms', [HomeroomController::class, 'classrooms']);
    Route::post('/Homeroom/Sheet', [HomeroomController::class, 'sheet']);
    Route::post('/Homeroom/Save', [HomeroomController::class, 'save']);
    Route::post('/Homeroom/FillAttendance', [HomeroomController::class, 'fillAttendance']);
    Route::post('/Homeroom/ExtracurricularSheet', [HomeroomController::class, 'extracurricularSheet']);
    Route::post('/Homeroom/SaveExtracurricular', [HomeroomController::class, 'saveExtracurricular']);
});

Route::middleware('menu.access:/ReportCard/Index')->group(function () {
    Route::get('/ReportCard/Index', [ReportCardController::class, 'index']);
    Route::post('/ReportCard/Classrooms', [ReportCardController::class, 'classrooms']);
    Route::post('/ReportCard/Search', [ReportCardController::class, 'search']);
    Route::get('/ReportCard/Print', [ReportCardController::class, 'print']);
});

// ---------- Ujian ----------

Route::middleware('menu.access:/Exam/Index')->group(function () use ($crud) {
    $crud('Exam', ExamController::class);
    Route::post('/Exam/Schedules', [ExamController::class, 'schedules']);
    Route::post('/Exam/SaveSchedules', [ExamController::class, 'saveSchedules']);
});

Route::middleware('menu.access:/ExamParticipant/Index')->group(function () {
    Route::get('/ExamParticipant/Index', [ExamParticipantController::class, 'index']);
    Route::post('/ExamParticipant/Search', [ExamParticipantController::class, 'search']);
    Route::post('/ExamParticipant/Generate', [ExamParticipantController::class, 'generate']);
    Route::post('/ExamParticipant/AssignRooms', [ExamParticipantController::class, 'assignRooms']);
    Route::post('/ExamParticipant/Save', [ExamParticipantController::class, 'save']);
    Route::post('/ExamParticipant/DeleteMultiple', [ExamParticipantController::class, 'deleteMultiple']);
    Route::get('/ExamParticipant/PrintCards', [ExamParticipantController::class, 'printCards']);
    Route::get('/ExamParticipant/PrintAttendance', [ExamParticipantController::class, 'printAttendance']);
    Route::get('/ExamParticipant/DownloadExcel', [ExamParticipantController::class, 'downloadExcel']);
});

// ---------- Administrasi ----------

Route::middleware('menu.access:/Role/Index')->group(function () {
    Route::get('/Role/Index', [RoleController::class, 'index']);
    Route::post('/Role/Search', [RoleController::class, 'search']);
    Route::post('/Role/GetByKey', [RoleController::class, 'getByKey']);
    Route::post('/Role/AddEdit', [RoleController::class, 'addEdit']);
    Route::post('/Role/DeleteMultiple', [RoleController::class, 'deleteMultiple']);
    Route::post('/Role/GetAccess', [RoleController::class, 'getAccess']);
    Route::post('/Role/SaveAccess', [RoleController::class, 'saveAccess']);
    Route::get('/Role/DownloadExcel', [RoleController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/User/Index')->group(function () {
    Route::get('/User/Index', [UserController::class, 'index']);
    Route::post('/User/Search', [UserController::class, 'search']);
    Route::post('/User/GetByKey', [UserController::class, 'getByKey']);
    Route::post('/User/AddEdit', [UserController::class, 'addEdit']);
    Route::post('/User/ResetPassword', [UserController::class, 'resetPassword']);
    Route::post('/User/DeleteMultiple', [UserController::class, 'deleteMultiple']);
    Route::get('/User/DownloadExcel', [UserController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/Menu/Index')->group(function () {
    Route::get('/Menu/Index', [MenuController::class, 'index']);
    Route::post('/Menu/Search', [MenuController::class, 'search']);
    Route::post('/Menu/AddEdit', [MenuController::class, 'addEdit']);
    Route::post('/Menu/Delete', [MenuController::class, 'delete']);
    Route::get('/Menu/DownloadExcel', [MenuController::class, 'downloadExcel']);
});

Route::middleware('menu.access:/AuditTrail/Index')->group(function () {
    Route::get('/AuditTrail/Index', [AuditTrailController::class, 'index']);
    Route::post('/AuditTrail/Search', [AuditTrailController::class, 'search']);
    Route::post('/AuditTrail/GetByKey', [AuditTrailController::class, 'getByKey']);
    Route::get('/AuditTrail/DownloadExcel', [AuditTrailController::class, 'downloadExcel']);
});

// ---------- Shared (cukup login) ----------

Route::middleware('menu.access')->group(function () {
    Route::get('/Lookup/Role', [LookupController::class, 'role']);
    Route::get('/Lookup/UserList', [LookupController::class, 'userList']);
    Route::get('/Lookup/Teacher', [LookupController::class, 'teacher']);
    Route::get('/Lookup/Student', [LookupController::class, 'student']);
    Route::get('/School/Logo', [SchoolController::class, 'logo']);

    Route::post('/Notification/List', [NotificationController::class, 'list']);
    Route::post('/Notification/MarkRead', [NotificationController::class, 'markRead']);
});
