<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Extracurricular extends Model
{
    use Auditable;

    public const AuditModule = 'Ekstrakurikuler';

    protected $table = 'extracurriculars';
    protected $primaryKey = 'extracurricular_id';
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'is_mandatory' => 'boolean'];

    public function auditLabel(): string
    {
        return $this->extracurricular_code . ' - ' . $this->extracurricular_name;
    }
}
