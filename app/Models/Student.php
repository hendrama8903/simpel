<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use Auditable;

    public const AuditModule = 'Siswa';

    protected $table = 'students';
    protected $primaryKey = 'student_id';
    protected $guarded = [];

    protected $casts = ['birth_date' => DateOnly::class, 'entry_date' => DateOnly::class, 'entry_grade' => 'integer'];

    public function memberships(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class, 'student_id', 'student_id');
    }

    public function auditLabel(): string
    {
        return $this->nis . ' - ' . $this->student_name;
    }
}
