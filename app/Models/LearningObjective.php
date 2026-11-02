<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningObjective extends Model
{
    use Auditable;

    public const AuditModule = 'Tujuan Pembelajaran';

    protected $table = 'learning_objectives';
    protected $primaryKey = 'learning_objective_id';
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'grade_level' => 'integer', 'semester_no' => 'integer'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    public function auditLabel(): string
    {
        return $this->subject?->subject_code . ' kelas ' . $this->grade_level . ' ' . $this->objective_code;
    }
}
