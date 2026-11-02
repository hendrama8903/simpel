<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ujian (STS/SAS/ASAJ): kegiatan ujian, jadwal per tingkat, peserta (nomor peserta, ruang, kursi).
     */
    public function up(): void
    {
        $audit = function (Blueprint $table) {
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        };

        Schema::create('exams', function (Blueprint $table) use ($audit) {
            $table->id('exam_id');
            $table->foreignId('semester_id')->references('semester_id')->on('semesters')->restrictOnDelete();
            $table->string('exam_code', 15)->unique(); // awalan nomor peserta, mis. SAS2627
            $table->string('exam_name', 100);
            $table->string('exam_type', 10); // STS / SAS / ASAJ / LAINNYA
            $table->date('start_date');
            $table->date('end_date');
            $table->string('card_note', 255)->nullable(); // catatan di kartu ujian
            $audit($table);
        });

        Schema::create('exam_schedules', function (Blueprint $table) use ($audit) {
            $table->id('exam_schedule_id');
            $table->foreignId('exam_id')->references('exam_id')->on('exams')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level');
            $table->foreignId('subject_id')->references('subject_id')->on('subjects')->restrictOnDelete();
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $audit($table);

            $table->unique(['exam_id', 'grade_level', 'subject_id']);
        });

        Schema::create('exam_participants', function (Blueprint $table) use ($audit) {
            $table->id('exam_participant_id');
            $table->foreignId('exam_id')->references('exam_id')->on('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->references('classroom_id')->on('classrooms')->cascadeOnDelete();
            $table->string('participant_no', 30);
            $table->foreignId('room_id')->nullable()->references('room_id')->on('rooms')->nullOnDelete();
            $table->unsignedSmallInteger('seat_no')->nullable();
            $audit($table);

            $table->unique(['exam_id', 'student_id']);
            $table->unique(['exam_id', 'participant_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_participants');
        Schema::dropIfExists('exam_schedules');
        Schema::dropIfExists('exams');
    }
};
