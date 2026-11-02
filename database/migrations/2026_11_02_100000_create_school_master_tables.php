<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan sekolah & data master: profil sekolah (satu baris), tahun ajaran + semester,
     * mata pelajaran, ruangan, ekstrakurikuler.
     */
    public function up(): void
    {
        $audit = function (Blueprint $table) {
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        };

        Schema::create('schools', function (Blueprint $table) use ($audit) {
            $table->id('school_id');
            $table->string('npsn', 20)->nullable();
            $table->string('school_name', 150);
            $table->string('school_level', 10)->default('SD'); // SD / MI
            $table->string('address', 255)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 100)->nullable();
            $table->string('principal_name', 100)->nullable();
            $table->string('principal_nip', 30)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('report_place', 100)->nullable(); // kota di tanda tangan rapor/kartu
            // Penilaian: KKTP dan bobot nilai akhir rapor (persen, dinormalisasi terhadap komponen yang ada nilainya).
            $table->unsignedTinyInteger('kktp')->default(70);
            $table->unsignedTinyInteger('weight_summative')->default(50);
            $table->unsignedTinyInteger('weight_sts')->default(25);
            $table->unsignedTinyInteger('weight_sas')->default(25);
            $audit($table);
        });

        Schema::create('academic_years', function (Blueprint $table) use ($audit) {
            $table->id('academic_year_id');
            $table->string('year_name', 9)->unique(); // 2026/2027
            $table->date('start_date');
            $table->date('end_date');
            $audit($table);
        });

        // Tepat satu semester aktif (is_active); tahun ajaran aktif = tahun ajaran semester tersebut.
        Schema::create('semesters', function (Blueprint $table) use ($audit) {
            $table->id('semester_id');
            $table->foreignId('academic_year_id')->references('academic_year_id')->on('academic_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester_no'); // 1 = Ganjil, 2 = Genap
            $table->date('start_date');
            $table->date('end_date');
            $table->date('report_date')->nullable(); // tanggal rapor
            $table->boolean('is_active')->default(false);
            $audit($table);

            $table->unique(['academic_year_id', 'semester_no']);
        });

        Schema::create('subjects', function (Blueprint $table) use ($audit) {
            $table->id('subject_id');
            $table->string('subject_code', 10)->unique();
            $table->string('subject_name', 100);
            $table->string('subject_group', 10)->default('UMUM'); // UMUM / MULOK
            $table->string('taught_by', 10)->default('GURU_KELAS'); // GURU_KELAS / GURU_MAPEL
            $table->unsignedSmallInteger('report_order')->default(0);
            $table->boolean('is_active')->default(true);
            $audit($table);
        });

        Schema::create('rooms', function (Blueprint $table) use ($audit) {
            $table->id('room_id');
            $table->string('room_code', 10)->unique();
            $table->string('room_name', 100);
            $table->unsignedSmallInteger('capacity')->default(28);
            $table->boolean('is_active')->default(true);
            $audit($table);
        });

        Schema::create('extracurriculars', function (Blueprint $table) use ($audit) {
            $table->id('extracurricular_id');
            $table->string('extracurricular_code', 10)->unique();
            $table->string('extracurricular_name', 100);
            $table->string('coach_name', 100)->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $audit($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurriculars');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('schools');
    }
};
