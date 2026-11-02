<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentScore extends Model
{
    public $timestamps = false;

    protected $table = 'assessment_scores';
    protected $primaryKey = 'assessment_score_id';
    protected $guarded = [];

    protected $casts = ['score' => 'float'];
}
