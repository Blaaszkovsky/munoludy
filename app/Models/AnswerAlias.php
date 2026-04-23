<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerAlias extends Model
{
    protected $guarded = [];

    public function group()
    {
        return $this->belongsTo(AnswerGroup::class, 'answer_group_id');
    }
}
