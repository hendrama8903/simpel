<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use Auditable;

    public const AuditModule = 'Guru';

    protected $table = 'teachers';
    protected $primaryKey = 'teacher_id';
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'birth_date' => DateOnly::class];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'user_id', 'user_id');
    }

    public function homeroomClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id', 'teacher_id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'teacher_id', 'teacher_id');
    }

    public function auditLabel(): string
    {
        return (string) $this->teacher_name;
    }
}
