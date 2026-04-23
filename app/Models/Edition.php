<?php

namespace App\Models;

use App\Enums\EditionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Edition extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'results_published_at' => 'datetime',
        'is_active' => 'boolean',
        'status' => EditionStatus::class,
    ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function juryMembers()
    {
        return $this->hasMany(JuryMember::class);
    }

    public function pageContents()
    {
        return $this->hasMany(PageContent::class);
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public function isVotingOpen(): bool
    {
        if ($this->status !== EditionStatus::Active) {
            return false;
        }
        $now = now();
        return (!$this->starts_at || $now->gte($this->starts_at))
            && (!$this->ends_at || $now->lte($this->ends_at));
    }
}
