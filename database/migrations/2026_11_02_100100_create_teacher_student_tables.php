<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guru & tendik, siswa, rombel (per tahun ajaran), anggota rombel, tugas mengajar, mutasi/kelulusan.
     */
    public function up(): void
    {
        $audit = function (Blueprint $table) {
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        };

        Schema::create('teachers', function (Blueprint $table) use ($audit) {
            $table->id('teacher_id');
            $table->string('nip', 30)->nullable()->unique();
            $table->string('nuptk', 30)->nullable()->unique();
            $table->string('teacher_name', 100);
            $table->char('gender', 1); // L / P
            $table->string('birth_place', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('position', 30)->default('GURU_KELAS'); // KEPSEK / GURU_KELAS / GURU_MAPEL / TENDIK
            $table->string('employment_status', 20)->nullable(); // PNS / PPPK / GTY / GTT / HONORER
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('photo_path', 255)->nullable();
            // Akun login guru (opsional). Satu akun hanya untuk satu guru.
            $table->foreignId('user_id')->nullable()->unique()->references('user_id')->on('user_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $audit($table);
        });

        Schema::create('students', function (Blueprint $table) use ($audit) {
            $table->id('student_id');
            $table->string('nis', 20)->unique();
            $table->string('nisn', 10)->nullable()->unique();
            $table->string('nik', 16)->nullable();
            $table->string('student_name', 100);
            $table->char('gender', 1); // L / P
            $table->string('birth_place', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('father_name', 100)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('guardian_name', 100)->nullable();
            $table->string('parent_phone', 20)->nullable();
            $table->string('entry_type', 10)->default('BARU'); // BARU / PINDAHAN
            $table->date('entry_date')->nullable();
            $table->unsignedTinyInteger('entry_grade')->default(1);
            $table->string('previous_school', 150)->nullable();
            $table->string('photo_path', 255)->nullable();
            // AKTIF / MUTASI / LULUS / KELUAR — diubah lewat menu Mutasi & Kelulusan, bukan dari form siswa.
            $table->string('status', 10)->default('AKTIF');
            $audit($table);

            $table->index('status');
        });

        Schema::create('classrooms', function (Blueprint $table) use ($audit) {
            $table->id('classroom_id');
            $table->foreignId('academic_year_id')->references('academic_year_id')->on('academic_years')->restrictOnDelete();
            $table->unsignedTinyInteger('grade_level'); // 1-6, fase A (1-2), B (3-4), C (5-6)
            $table->string('classroom_name', 20); // 1A, 1B, ...
            $table->foreignId('homeroom_teacher_id')->nullable()->references('teacher_id')->on('teachers')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->references('room_id')->on('rooms')->nullOnDelete();
            $audit($table);

            $table->unique(['academic_year_id', 'classroom_name']);
        });

        // Satu siswa hanya boleh di satu rombel per tahun ajaran (dicek di ClassMemberRepo).
        Schema::create('classroom_students', function (Blueprint $table) {
            $table->id('classroom_student_id');
            $table->foreignId('classroom_id')->references('classroom_id')->on('classrooms')->cascadeOnDelete();
            $table->foreignId('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['classroom_id', 'student_id']);
        });

        Schema::create('teaching_assignments', function (Blueprint $table) use ($audit) {
            $table->id('teaching_assignment_id');
            $table->foreignId('semester_id')->references('semester_id')->on('semesters')->cascadeOnDelete();
            $table->foreignId('classroom_id')->references('classroom_id')->on('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->references('subject_id')->on('subjects')->restrictOnDelete();
            $table->foreignId('teacher_id')->references('teacher_id')->on('teachers')->restrictOnDelete();
            $audit($table);

            $table->unique(['semester_id', 'classroom_id', 'subject_id']);
        });

        // Riwayat siswa keluar: MUTASI (pindah sekolah), LULUS, KELUAR (putus sekolah, dll).
        // Menghapus baris ini mengembalikan status siswa ke AKTIF.
        Schema::create('student_exits', function (Blueprint $table) use ($audit) {
            $table->id('student_exit_id');
            $table->foreignId('student_id')->unique()->references('student_id')->on('students')->cascadeOnDelete();
            $table->string('exit_type', 10);
            $table->date('exit_date');
            $table->string('destination_school', 150)->nullable();
            $table->string('reason', 255)->nullable();
            $audit($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_exits');
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('classroom_students');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('students');
        Schema::dropIfExists('teachers');
    }
};
