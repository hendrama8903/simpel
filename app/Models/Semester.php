<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Semester extends Model
{
    use Auditable;

    public const AuditModule = 'Semester';

    protected $table = 'semesters';
    protected $primaryKey = 'semester_id';
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'semester_no' => 'integer', 'start_date' => DateOnly::class, 'end_date' => DateOnly::class, 'report_date' => DateOnly::class];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'academic_year_id');
    }

    public function auditLabel(): string
    {
        return $this->academicYear?->year_name . ' ' . ($this->semester_no === 1 ? 'Ganjil' : 'Genap');
    }
}
