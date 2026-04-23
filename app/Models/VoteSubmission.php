<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteSubmission extends Model
{
    protected $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}
