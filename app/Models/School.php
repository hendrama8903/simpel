<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use Auditable;

    public const AuditModule = 'Profil Sekolah';

    protected $table = 'schools';
    protected $primaryKey = 'school_id';
    protected $guarded = [];

    protected $casts = [];

    public function auditLabel(): string
    {
        return (string) $this->school_name;
    }
}
