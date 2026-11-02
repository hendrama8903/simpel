<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportGrade extends Model
{
    protected $table = 'report_grades';
    protected $primaryKey = 'report_grade_id';
    protected $guarded = [];

    protected $casts = ['final_score' => 'float', 'is_manual' => 'boolean'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }
}
