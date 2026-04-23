<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $guarded = [];

    public function submission()
    {
        return $this->belongsTo(VoteSubmission::class, 'vote_submission_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function questionOption()
    {
        return $this->belongsTo(QuestionOption::class);
    }

    public function group()
    {
        return $this->belongsTo(AnswerGroup::class, 'answer_group_id');
    }
}
