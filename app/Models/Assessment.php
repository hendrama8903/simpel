<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use Auditable;

    public const AuditModule = 'Penilaian';

    protected $table = 'assessments';
    protected $primaryKey = 'assessment_id';
    protected $guarded = [];

    protected $casts = ['assessment_date' => DateOnly::class];

    public function learningObjective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class, 'learning_objective_id', 'learning_objective_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class, 'assessment_id', 'assessment_id');
    }

    public function auditLabel(): string
    {
        return (string) $this->assessment_name;
    }
}
