<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatorNote extends Model
{
    protected $fillable = ['user_id','body','is_pinned'];

    protected function casts(): array
    {
        return ['is_pinned'=>'boolean'];
    }
}
