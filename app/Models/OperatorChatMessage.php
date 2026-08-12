<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorChatMessage extends Model
{
    protected $fillable = ['thread_id','sender_user_id','sender_type','body','read_at'];

    protected function casts(): array
    {
        return ['read_at'=>'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(OperatorChatThread::class, 'thread_id');
    }
}
