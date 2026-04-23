<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageContent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
    ];

    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->content, $key, $default);
    }
}
