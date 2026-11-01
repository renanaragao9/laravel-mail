<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    protected static function bootBelongsToUser(): void
    {
        static::addGlobalScope('owner', function (Builder $query): void {
            $user = Auth::user();

            if ($user instanceof User && ! $user->is_master) {
                $query->where($query->getModel()->getTable().'.user_id', $user->id);
            }
        });

        static::creating(function ($model): void {
            if ($model->user_id === null && Auth::id() !== null) {
                $model->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
