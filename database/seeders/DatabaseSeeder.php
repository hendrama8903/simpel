<?php

namespace Database\Seeders;

use App\Models\Extracurricular;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subject;
use App\Models\UserAccount;
use App\Support\PasswordHasher;
use Illuminate\Database\Seeder;

/**
 * Data awal SIMPEL: role, menu, permission, hak akses per role, mapel default Kurikulum Merdeka SD, user admin.
 * Setiap menu baru: tambahkan menu + permission di sini. Aman dijalankan ulang (firstOrCreate).
 *
 * Login awal: admin / Admin@123 (WAJIB diganti setelah login pertama).
 * Data contoh (tahun ajaran, rombel, guru, siswa, akun guru): php artisan db:seed --class=DemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $by = 'system';

        // ---------- Role ----------
        $roles = [];
        foreach ([
            ['ADMIN', 'Administrator', 'Akses penuh ke semua menu dan pengaturan', true],
            ['OPERATOR', 'Operator Sekolah', 'Kelola data sekolah, guru, siswa, akademik, dan ujian', false],
            ['KEPSEK', 'Kepala Sekolah', 'Melihat semua data dan laporan, mencetak rapor & kartu ujian', false],
            ['GURU_KELAS', 'Guru Kelas', 'Wali kelas: presensi, nilai, catatan & cetak rapor rombelnya', false],
            ['GURU_MAPEL', 'Guru Mapel', 'Mengisi nilai mapel yang diajar', false],
        ] as [$code, $name, $description, $isSystem]) {
            $roles[$code] = Role::firstOrCreate(['role_code' => $code], [
                'role_name' => $name, 'description' => $description, 'is_system' => $isSystem, 'created_by' => $by,
            ]);
        }

        // ---------- Menu ----------
        $menu = fn (string $url, ?Menu $parent, string $name, string $icon, int $sort) => Menu::firstOrCreate(['menu_url' => $url], [
            'parent_menu_id' => $parent?->menu_id, 'menu_type' => 'SCREEN', 'menu_name' => $name, 'menu_icon' => $icon, 'sort_order' => $sort, 'created_by' => $by,
        ]);
        $group = fn (string $name, string $icon, int $sort) => Menu::firstOrCreate(['parent_menu_id' => null, 'menu_name' => $name], [
            'menu_type' => 'MENU', 'menu_icon' => $icon, 'sort_order' => $sort, 'created_by' => $by,
        ]);

        $m = [];
        $m['home'] = $menu('/Home/Index', null, 'Beranda', 'fa-dashboard', 10);

        $g = $group('Pengaturan Sekolah', 'fa-university', 20);
        $m['school'] = $menu('/School/Index', $g, 'Profil Sekolah', 'fa-university', 10);
        $m['year'] = $menu('/AcademicYear/Index', $g, 'Tahun Ajaran', 'fa-calendar', 20);

        $g = $group('Data Master', 'fa-database', 30);
        $m['subject'] = $menu('/Subject/Index', $g, 'Mata Pelajaran', 'fa-book', 10);
        $m['classroom'] = $menu('/Classroom/Index', $g, 'Rombel', 'fa-users', 20);
        $m['room'] = $menu('/Room/Index', $g, 'Ruangan', 'fa-building-o', 30);
        $m['extracurricular'] = $menu('/Extracurricular/Index', $g, 'Ekstrakurikuler', 'fa-futbol-o', 40);

        $g = $group('Kepegawaian', 'fa-id-badge', 40);
        $m['teacher'] = $menu('/Teacher/Index', $g, 'Guru & Tendik', 'fa-user', 10);
        $m['teaching'] = $menu('/TeachingAssignment/Index', $g, 'Tugas Mengajar', 'fa-tasks', 20);

        $g = $group('Kesiswaan', 'fa-child', 50);
        $m['student'] = $menu('/Student/Index', $g, 'Data Siswa', 'fa-child', 10);
        $m['member'] = $menu('/ClassMember/Index', $g, 'Anggota Rombel', 'fa-exchange', 20);
        $m['exit'] = $menu('/StudentExit/Index', $g, 'Mutasi & Kelulusan', 'fa-sign-out', 30);

        $g = $group('Akademik', 'fa-graduation-cap', 60);
        $m['schedule'] = $menu('/Schedule/Index', $g, 'Jadwal Pelajaran', 'fa-clock-o', 10);
        $m['attendance'] = $menu('/Attendance/Index', $g, 'Presensi Siswa', 'fa-check-square-o', 20);
        $m['objective'] = $menu('/LearningObjective/Index', $g, 'Tujuan Pembelajaran', 'fa-bullseye', 30);
        $m['assessment'] = $menu('/Assessment/Index', $g, 'Input Nilai', 'fa-pencil', 40);
        $m['reportGrade'] = $menu('/ReportGrade/Index', $g, 'Nilai Rapor', 'fa-calculator', 50);
        $m['homeroom'] = $menu('/Homeroom/Index', $g, 'Wali Kelas', 'fa-address-book-o', 60);
        $m['reportCard'] = $menu('/ReportCard/Index', $g, 'Cetak Rapor', 'fa-print', 70);

        $g = $group('Ujian', 'fa-file-text-o', 70);
        $m['exam'] = $menu('/Exam/Index', $g, 'Kegiatan Ujian', 'fa-calendar-check-o', 10);
        $m['participant'] = $menu('/ExamParticipant/Index', $g, 'Peserta & Kartu Ujian', 'fa-id-card-o', 20);

        $g = $group('Administrasi', 'fa-cogs', 90);
        $m['user'] = $menu('/User/Index', $g, 'Pengguna', 'fa-user-circle-o', 10);
        $m['role'] = $menu('/Role/Index', $g, 'Peran & Akses', 'fa-key', 20);
        $m['menu'] = $menu('/Menu/Index', $g, 'Master Menu', 'fa-sitemap', 30);
        $m['audit'] = $menu('/AuditTrail/Index', $g, 'Jejak Audit', 'fa-history', 40);

        // ---------- Permission ----------
        $crud = fn (string $key, string $prefix) => [[$key, "{$prefix}-ADD", 'Tambah'], [$key, "{$prefix}-EDIT", 'Ubah'], [$key, "{$prefix}-DELETE", 'Hapus']];
        $all = fn (string $key, string $prefix) => [$key, "{$prefix}-ALL", 'Semua Rombel'];
        $permissions = array_merge(
            [['school', 'SCHOOL-EDIT', 'Ubah']],
            $crud('year', 'ACADEMICYEAR'),
            $crud('subject', 'SUBJECT'),
            $crud('classroom', 'CLASSROOM'),
            $crud('room', 'ROOM'),
            $crud('extracurricular', 'EXTRACURRICULAR'),
            $crud('teacher', 'TEACHER'),
            [['teacher', 'TEACHER-UPLOAD', 'Upload Excel'], ['teacher', 'TEACHER-ACCOUNT', 'Buat Akun Login']],
            [['teaching', 'TEACHINGASSIGNMENT-EDIT', 'Ubah']],
            $crud('student', 'STUDENT'),
            [['student', 'STUDENT-UPLOAD', 'Upload Excel']],
            [['member', 'CLASSMEMBER-EDIT', 'Ubah']],
            $crud('exit', 'STUDENTEXIT'),
            [['schedule', 'SCHEDULE-EDIT', 'Ubah'], $all('schedule', 'SCHEDULE')],
            [['attendance', 'ATTENDANCE-EDIT', 'Isi Presensi'], $all('attendance', 'ATTENDANCE')],
            $crud('objective', 'LEARNINGOBJECTIVE'),
            [['assessment', 'ASSESSMENT-EDIT', 'Isi Nilai'], $all('assessment', 'ASSESSMENT')],
            [['reportGrade', 'REPORTGRADE-EDIT', 'Hitung & Ubah'], $all('reportGrade', 'REPORTGRADE')],
            [['homeroom', 'HOMEROOM-EDIT', 'Ubah'], $all('homeroom', 'HOMEROOM')],
            [['reportCard', 'REPORTCARD-PRINT', 'Cetak'], $all('reportCard', 'REPORTCARD')],
            $crud('exam', 'EXAM'),
            [['participant', 'EXAMPARTICIPANT-EDIT', 'Ubah Peserta'], ['participant', 'EXAMPARTICIPANT-PRINT', 'Cetak Kartu']],
            [['user', 'USER-ADD', 'Tambah'], ['user', 'USER-EDIT', 'Ubah'], ['user', 'USER-DELETE', 'Hapus'], ['user', 'USER-RESETPASSWORD', 'Reset Password']],
            [['role', 'ROLE-ADD', 'Tambah'], ['role', 'ROLE-EDIT', 'Ubah'], ['role', 'ROLE-DELETE', 'Hapus'], ['role', 'ROLE-ACCESS', 'Atur Akses']],
            $crud('menu', 'MENU'),
        );
        foreach ($permissions as [$key, $code, $name]) {
            Permission::firstOrCreate(['permission_code' => $code], [
                'menu_id' => $m[$key]->menu_id, 'permission_name' => $name, 'created_by' => $by,
            ]);
        }

        // ---------- Hak akses role ----------
        $adminMenus = ['user', 'role', 'menu', 'audit'];
        $grant = function (Role $role, array $menuKeys, array $permissionCodes) use ($m, $by) {
            $menuIds = array_map(fn ($k) => $m[$k]->menu_id, $menuKeys);
            // Grup induk ikut diberikan supaya sidebar bisa menampilkan submenu.
            $menuIds = array_merge($menuIds, Menu::query()->whereIn('menu_id', $menuIds)->whereNotNull('parent_menu_id')->pluck('parent_menu_id')->all());
            $pivot = ['created_by' => $by, 'created_at' => now()];
            $role->menus()->syncWithoutDetaching(array_fill_keys(array_unique($menuIds), $pivot));
            $role->permissions()->syncWithoutDetaching(array_fill_keys(
                Permission::query()->whereIn('permission_code', $permissionCodes)->pluck('permission_id')->all(), $pivot));
        };
        $codesOf = fn (array $menuKeys) => Permission::query()->whereIn('menu_id', array_map(fn ($k) => $m[$k]->menu_id, $menuKeys))
            ->pluck('permission_code')->all();
        $schoolMenus = array_values(array_diff(array_keys($m), $adminMenus));

        $grant($roles['ADMIN'], array_keys($m), Permission::query()->pluck('permission_code')->all());
        $grant($roles['OPERATOR'], $schoolMenus, $codesOf($schoolMenus));
        $grant($roles['KEPSEK'], $schoolMenus, array_merge(
            Permission::query()->where('permission_code', 'like', '%-ALL')->pluck('permission_code')->all(),
            ['REPORTCARD-PRINT', 'EXAMPARTICIPANT-PRINT'],
        ));
        $grant($roles['GURU_KELAS'], ['home', 'student', 'schedule', 'attendance', 'objective', 'assessment', 'reportGrade', 'homeroom', 'reportCard'], [
            'ATTENDANCE-EDIT', 'LEARNINGOBJECTIVE-ADD', 'LEARNINGOBJECTIVE-EDIT', 'LEARNINGOBJECTIVE-DELETE',
            'ASSESSMENT-EDIT', 'REPORTGRADE-EDIT', 'HOMEROOM-EDIT', 'REPORTCARD-PRINT',
        ]);
        $grant($roles['GURU_MAPEL'], ['home', 'schedule', 'objective', 'assessment', 'reportGrade'], [
            'LEARNINGOBJECTIVE-ADD', 'LEARNINGOBJECTIVE-EDIT', 'LEARNINGOBJECTIVE-DELETE', 'ASSESSMENT-EDIT', 'REPORTGRADE-EDIT',
        ]);

        // ---------- Mapel default Kurikulum Merdeka SD (boleh diubah di menu Mata Pelajaran) ----------
        foreach ([
            ['PAI', 'Pendidikan Agama Islam dan Budi Pekerti', 'UMUM', 'GURU_MAPEL'],
            ['PP', 'Pendidikan Pancasila', 'UMUM', 'GURU_KELAS'],
            ['BIND', 'Bahasa Indonesia', 'UMUM', 'GURU_KELAS'],
            ['MTK', 'Matematika', 'UMUM', 'GURU_KELAS'],
            ['IPAS', 'Ilmu Pengetahuan Alam dan Sosial', 'UMUM', 'GURU_KELAS'],
            ['PJOK', 'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'UMUM', 'GURU_MAPEL'],
            ['SENI', 'Seni Rupa', 'UMUM', 'GURU_KELAS'],
            ['BING', 'Bahasa Inggris', 'UMUM', 'GURU_MAPEL'],
            ['BDAE', 'Bahasa Daerah', 'MULOK', 'GURU_KELAS'],
        ] as $i => [$code, $name, $groupCode, $taughtBy]) {
            Subject::firstOrCreate(['subject_code' => $code], [
                'subject_name' => $name, 'subject_group' => $groupCode, 'taught_by' => $taughtBy, 'report_order' => ($i + 1) * 10, 'created_by' => $by,
            ]);
        }
        Extracurricular::firstOrCreate(['extracurricular_code' => 'PRAMUKA'], [
            'extracurricular_name' => 'Pramuka', 'is_mandatory' => true, 'created_by' => $by,
        ]);

        UserAccount::firstOrCreate(['username' => 'admin'], [
            'full_name' => 'Administrator',
            'role_id' => $roles['ADMIN']->role_id,
            'is_ad_user' => false,
            'password_hash' => PasswordHasher::hash('Admin@123'),
            'created_by' => $by,
        ]);
    }
}
