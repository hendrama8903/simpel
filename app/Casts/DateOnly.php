<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Kolom DATE: dibaca sebagai Carbon (jam 00:00), disimpan sebagai 'Y-m-d'.
 * Cast bawaan 'date' menyimpan 'Y-m-d H:i:s', sehingga perbandingan where('tanggal', 'Y-m-d')
 * gagal di database yang tidak memotong jam (mis. SQLite).
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null || $value === '' ? null : Carbon::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? null : Carbon::parse($value)->format('Y-m-d');
    }
}
