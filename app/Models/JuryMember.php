<?php

namespace App\Models;

use App\Services\Content\TokenGenerator;
use Illuminate\Database\Eloquent\Model;

class JuryMember extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $jury) {
            if (empty($jury->link_hash)) {
                $jury->link_hash = app(TokenGenerator::class)->uniqueJuryLinkHash();
            }
        });
    }

    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }

    public function participant()
    {
        return $this->hasOne(Participant::class, 'email', 'email')
            ->whereColumn('edition_id', 'edition_id');
    }

    public function hasVoted(): bool
    {
        $participant = Participant::where('edition_id', $this->edition_id)
            ->where('email', $this->email)
            ->first();

        return $participant !== null && $participant->voted_at !== null;
    }

    public function votingUrl(): ?string
    {
        return $this->link_hash ? url('/jury/'.$this->link_hash) : null;
    }

    public static function isJuryEmail(int $editionId, string $email): bool
    {
        return static::where('edition_id', $editionId)
            ->where('email', $email)
            ->exists();
    }
}
