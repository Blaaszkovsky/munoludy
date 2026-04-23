<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SiteSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();
        if (!$row) {
            return $default;
        }
        return $row->is_encrypted ? Crypt::decryptString($row->value) : $row->value;
    }

    public static function set(string $key, ?string $value, bool $encrypt = false): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $encrypt && $value ? Crypt::encryptString($value) : $value,
                'is_encrypted' => $encrypt,
            ]
        );
    }
}
