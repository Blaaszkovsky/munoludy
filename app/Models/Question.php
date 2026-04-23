<?php

namespace App\Models;

use App\Enums\FormAudience;
use App\Enums\QuestionFieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'audience' => FormAudience::class,
        'field_type' => QuestionFieldType::class,
        'is_required' => 'boolean',
        'validation_rules' => 'array',
        'ranked_points' => 'array',
    ];

    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function answerGroups()
    {
        return $this->hasMany(AnswerGroup::class);
    }

    public function pointsForPosition(int $position): int
    {
        $points = $this->ranked_points ?? [5, 4, 3, 2, 1];
        return $points[$position - 1] ?? 0;
    }
}
