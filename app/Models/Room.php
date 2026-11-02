<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use Auditable;

    public const AuditModule = 'Ruangan';

    protected $table = 'rooms';
    protected $primaryKey = 'room_id';
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function auditLabel(): string
    {
        return $this->room_code . ' - ' . $this->room_name;
    }
}
