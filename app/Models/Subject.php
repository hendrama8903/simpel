<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use Auditable;

    public const AuditModule = 'Mata Pelajaran';

    protected $table = 'subjects';
    protected $primaryKey = 'subject_id';
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function auditLabel(): string
    {
        return $this->subject_code . ' - ' . $this->subject_name;
    }
}
