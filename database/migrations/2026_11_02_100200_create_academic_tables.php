<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akademik: jadwal, presensi harian, tujuan pembelajaran (TP), penilaian, nilai rapor,
     * data wali kelas (ketidakhadiran, catatan, kokurikuler) dan nilai ekstrakurikuler.
     */
    public function up(): void
    {
        $audit = function (Blueprint $table) {
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        };

        Schema::create('schedules', function (Blueprint $table) use ($audit) {
            $table->id('schedule_id');
            $table->foreignId('semester_id')->references('semester_id')->on('semesters')->cascadeOnDelete();
            $table->foreignId('classroom_id')->references('classroom_id')->on('classrooms')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Senin ... 6 = Sabtu
            $table->unsignedTinyInteger('period_no'); // jam ke-
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('subject_id')->references('subject_id')->on('subjects')->restrictOnDelete();
            $table->foreignId('teacher_id')->nullable()->references('teacher_id')->on('teachers')->nullOnDelete();
            $audit($table);

            $table->unique(['semester_id', 'classroom_id', 'day_of_week', 'period_no']);
        });

        Schema::create('attendances', function (Blueprint $table) use ($audit) {
            $table->id('attendance_id');
            $table->foreignId('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->references('classroom_id')->on('classrooms')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->char('status', 1); // H / S / I / A
            $table->string('note', 255)->nullable();
            $audit($table);

            $table->unique(['student_id', 'attendance_date']);
            $table->index(['classroom_id', 'attendance_date']);
        });

        Schema::create('learning_objectives', function (Blueprint $table) use ($audit) {
            $table->id('learning_objective_id');
            $table->foreignId('subject_id')->references('subject_id')->on('subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level');
            $table->unsignedTinyInteger('semester_no');
            $table->string('objective_code', 10); // TP1, TP2, ...
            $table->string('description', 500); // dipakai apa adanya di deskripsi rapor
            $table->boolean('is_active')->default(true);
            $audit($table);

            $table->unique(['subject_id', 'grade_level', 'semester_no', 'objective_code'], 'learning_objectives_code_unique');
        });

        // Satu kolom nilai di grid Input Nilai. FORMATIF/SUMATIF boleh dikaitkan ke satu TP (untuk deskripsi rapor).
        Schema::create('assessments', function (Blueprint $table) use ($audit) {
            $table->id('assessment_id');
            $table->foreignId('semester_id')->references('semester_id')->on('semesters')->cascadeOnDelete();
            $table->foreignId('classroom_id')->references('classroom_id')->on('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->references('subject_id')->on('subjects')->restrictOnDelete();
            $table->string('assessment_type', 10); // FORMATIF / SUMATIF / STS / SAS
            $table->foreignId('learning_objective_id')->nullable()->references('learning_objective_id')->on('learning_objectives')->nullOnDelete();
            $table->string('assessment_name', 50);
            $table->date('assessment_date')->nullable();
            $audit($table);

            $table->index(['semester_id', 'classroom_id', 'subject_id']);
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id('assessment_score_id');
            $table->foreignId('assessment_id')->references('assessment_id')->on('assessments')->cascadeOnDelete();
            $table->foreignId('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            $table->decimal('score', 5, 2);

            $table->unique(['assessment_id', 'student_id']);
        });

        // Nilai akhir + deskripsi per mapel di rapor. Di-generate dari penilaian, boleh diedit (is_manual).
        Schema::create('report_grades', function (Blueprint $table) use ($audit) {
            $table->id('report_grade_id');
            $table->foreignId('semester_id')->references('semester_id')->on('semesters')->cascadeOnDelete();
            $table->foreignId('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->references('subject_id')->on('subjects')->restrictOnDelete();
            $table->foreignId('classroom_id')->references('classroom_id')->on('classrooms')->cascadeOnDelete();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->text('description_high')->nullable(); // capaian yang sudah dikuasai
            $table->text('description_low')->nullable(); // capaian yang perlu bantuan
            $table->boolean('is_manual')->default(false);
            $audit($table);

            $table->unique(['semester_id', 'student_id', 'subject_id']);
        });

        Schema::create('report_notes', function (Blueprint $table) use ($audit) {
            $table->id('report_note_id');
            $table->foreignId('semester_id')->references('semester_id')->on('semesters')->cascadeOnDelete();
            $table->foreignId('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->references('classroom_id')->on('classrooms')->cascadeOnDelete();
            $table->unsignedSmallInteger('sick')->default(0);
            $table->unsignedSmallInteger('permit')->default(0);
            $table->unsignedSmallInteger('absent')->default(0);
            $table->text('cocurricular_note')->nullable();
            $table->text('homeroom_note')->nullable();
            $table->string('promotion_decision', 20)->nullable(); // semester genap: NAIK / TINGGAL / LULUS
            $audit($table);

            $table->unique(['semester_id', 'student_id']);
        });

        Schema::create('student_extracurriculars', function (Blueprint $table) use ($audit) {
            $table->id('student_extracurricular_id');
            $table->foreignId('semester_id')->references('semester_id')->on('semesters')->cascadeOnDelete();
            $table->foreignId('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            $table->foreignId('extracurricular_id')->references('extracurricular_id')->on('extracurriculars')->restrictOnDelete();
            $table->string('predicate', 20)->nullable(); // Sangat Baik / Baik / Cukup / Kurang
            $table->string('description', 255)->nullable();
            $audit($table);

            $table->unique(['semester_id', 'student_id', 'extracurricular_id'], 'student_extracurriculars_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_extracurriculars');
        Schema::dropIfExists('report_notes');
        Schema::dropIfExists('report_grades');
        Schema::dropIfExists('assessment_scores');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('learning_objectives');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('schedules');
    }
};
