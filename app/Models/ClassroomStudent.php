<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomStudent extends Model
{
    public $timestamps = false;

    protected $table = 'classroom_students';
    protected $primaryKey = 'classroom_student_id';
    protected $guarded = [];

    protected $casts = [];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id', 'classroom_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
