<?php

namespace App\Models;

use App\Enums\AdminActivityAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivityLog extends Model
{
    protected $fillable = [
        'admin_id', 'action', 'subject_type', 'subject_id', 'changes',
    ];

    protected function casts(): array
    {
        return [
            'action' => AdminActivityAction::class,
            'changes' => 'array',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
