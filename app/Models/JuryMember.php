<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuryMember extends Model
{
    protected $guarded = [];

    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }

    public static function isJuryEmail(int $editionId, string $email): bool
    {
        return static::where('edition_id', $editionId)
            ->where('email', $email)
            ->exists();
    }
}
