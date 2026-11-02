<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentExit extends Model
{
    use Auditable;

    public const AuditModule = 'Mutasi & Kelulusan';

    protected $table = 'student_exits';
    protected $primaryKey = 'student_exit_id';
    protected $guarded = [];

    protected $casts = ['exit_date' => DateOnly::class];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function auditLabel(): string
    {
        return $this->exit_type . ' ' . $this->student?->student_name;
    }
}
