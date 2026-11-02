<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use Auditable;

    public const AuditModule = 'Rombel';

    protected $table = 'classrooms';
    protected $primaryKey = 'classroom_id';
    protected $guarded = [];

    protected $casts = ['grade_level' => 'integer'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'academic_year_id');
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id', 'teacher_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class, 'classroom_id', 'classroom_id');
    }

    public function auditLabel(): string
    {
        return (string) $this->classroom_name;
    }
}
