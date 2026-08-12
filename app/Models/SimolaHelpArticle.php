<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimolaHelpArticle extends Model
{
    protected $fillable = [
        'title',
        'module',
        'keywords',
        'content',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
