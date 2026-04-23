<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SiteSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();

        if (!$row || $row->value === null || $row->value === '') {
            return $default;
        }

        if (!$row->is_encrypted) {
            return $row->value;
        }

        try {
            return Crypt::decryptString($row->value);
        } catch (DecryptException $e) {
            Log::warning('SiteSetting decrypt failed, returning default', [
                'key' => $key,
                'reason' => $e->getMessage(),
            ]);
            return $default;
        }
    }

    public static function set(string $key, ?string $value, bool $encrypt = false): void
    {
        $hasValue = $value !== null && $value !== '';

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $hasValue && $encrypt ? Crypt::encryptString($value) : $value,
                'is_encrypted' => $hasValue && $encrypt,
            ]
        );
    }
}
