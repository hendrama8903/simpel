<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSchedule extends Model
{
    protected $table = 'exam_schedules';
    protected $primaryKey = 'exam_schedule_id';
    protected $guarded = [];

    protected $casts = ['exam_date' => DateOnly::class, 'grade_level' => 'integer'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }
}
