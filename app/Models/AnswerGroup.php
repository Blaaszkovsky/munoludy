<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerGroup extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function aliases()
    {
        return $this->hasMany(AnswerAlias::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function finalPoints(): int
    {
        return $this->points_override ?? $this->aggregated_points;
    }
}
