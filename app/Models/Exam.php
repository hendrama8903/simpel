<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use Auditable;

    public const AuditModule = 'Ujian';

    protected $table = 'exams';
    protected $primaryKey = 'exam_id';
    protected $guarded = [];

    protected $casts = ['start_date' => DateOnly::class, 'end_date' => DateOnly::class];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class, 'exam_id', 'exam_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class, 'exam_id', 'exam_id');
    }

    public function auditLabel(): string
    {
        return $this->exam_code . ' - ' . $this->exam_name;
    }
}
