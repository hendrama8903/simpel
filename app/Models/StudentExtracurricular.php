<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentExtracurricular extends Model
{
    protected $table = 'student_extracurriculars';
    protected $primaryKey = 'student_extracurricular_id';
    protected $guarded = [];

    protected $casts = [];

    public function extracurricular(): BelongsTo
    {
        return $this->belongsTo(Extracurricular::class, 'extracurricular_id', 'extracurricular_id');
    }
}
