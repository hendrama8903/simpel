<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use Auditable;

    public const AuditModule = 'Tahun Ajaran';

    protected $table = 'academic_years';
    protected $primaryKey = 'academic_year_id';
    protected $guarded = [];

    protected $casts = ['start_date' => DateOnly::class, 'end_date' => DateOnly::class];

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class, 'academic_year_id', 'academic_year_id');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'academic_year_id', 'academic_year_id');
    }

    public function auditLabel(): string
    {
        return (string) $this->year_name;
    }
}
