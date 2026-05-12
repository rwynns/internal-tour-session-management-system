<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Database\Factories\SessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['attraction_id', 'start_time', 'end_time', 'max_capacity', 'current_pax', 'status'])]
class Session extends Model
{
    /** @use HasFactory<SessionFactory> */
    use HasFactory;

    protected $table = 'tour_sessions';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'max_capacity' => 'integer',
            'current_pax' => 'integer',
            'status' => SessionStatus::class,
        ];
    }

    public function attraction(): BelongsTo
    {
        return $this->belongsTo(Attraction::class);
    }

    /**
     * Get all guest allocations for this session.
     *
     * @return HasMany<GuestAllocation>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(GuestAllocation::class);
    }

    /**
     * Get only active guest allocations for this session.
     *
     * @return HasMany<GuestAllocation>
     */
    public function activeAllocations(): HasMany
    {
        return $this->hasMany(GuestAllocation::class)->where('status', 'active');
    }

    /**
     * Get the remaining capacity for this session.
     */
    public function remainingCapacity(): int
    {
        return $this->max_capacity - $this->current_pax;
    }
}
