<?php

namespace App\Models;

use App\Enums\CopyCondition;
use App\Observers\AvtoreferatCopyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([AvtoreferatCopyObserver::class])]
class AvtoreferatCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'avtoreferat_id', 'inventory_number', 'condition',
        'acquisition_act_number', 'acquisition_act_at',
        'disposal_act_number', 'disposal_act_at',
    ];

    protected function casts(): array
    {
        return [
            'condition' => AsEnumCollection::of(CopyCondition::class),
            'acquisition_act_at' => 'date',
            'disposal_act_at' => 'date',
        ];
    }

    public function avtoreferat(): BelongsTo
    {
        return $this->belongsTo(Avtoreferat::class);
    }
}
