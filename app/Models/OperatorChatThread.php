<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperatorChatThread extends Model
{
    protected $fillable = ['fleet_type','pc_number','status','last_message_at','last_message_user_id'];

    protected function casts(): array
    {
        return ['pc_number'=>'integer','last_message_at'=>'datetime'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OperatorChatMessage::class, 'thread_id');
    }

    public function lastMessageUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_message_user_id');
    }

    public function displayFleetType(): string
    {
        return $this->fleet_type === 'MT_PERTASHOP' ? 'MT PERTASHOP' : 'MT LPG';
    }

    public function displayName(): string
    {
        return $this->displayFleetType().' · PC '.$this->pc_number;
    }
}
