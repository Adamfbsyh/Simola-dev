<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorPcAssignment extends Model
{
    protected $fillable = ['user_id','fleet_type','pc_number','label','is_active'];

    protected function casts(): array
    {
        return ['pc_number'=>'integer','is_active'=>'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayFleetType(): string
    {
        return $this->fleet_type === 'MT_PERTASHOP' ? 'MT PERTASHOP' : 'MT LPG';
    }
}
