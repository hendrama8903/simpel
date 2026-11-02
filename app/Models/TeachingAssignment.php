<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingAssignment extends Model
{
    use Auditable;

    public const AuditModule = 'Tugas Mengajar';

    protected $table = 'teaching_assignments';
    protected $primaryKey = 'teaching_assignment_id';
    protected $guarded = [];

    protected $casts = [];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id', 'classroom_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'teacher_id');
    }

    public function auditLabel(): string
    {
        return $this->classroom?->classroom_name . ' - ' . $this->subject?->subject_name;
    }
}
