<?php

namespace App\Models;

use App\Enums\ParticipantType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'type' => ParticipantType::class,
        'consented_privacy' => 'boolean',
        'consented_marketing' => 'boolean',
        'voted_at' => 'datetime',
    ];

    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }

    public function submission()
    {
        return $this->hasOne(VoteSubmission::class);
    }

    public function hasVoted(): bool
    {
        return $this->voted_at !== null;
    }
}
