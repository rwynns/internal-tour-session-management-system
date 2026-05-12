<?php

namespace App\Models;

use Database\Factories\GuestAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['session_id', 'guest_name', 'pax', 'source', 'notes', 'status', 'allocated_by'])]
class GuestAllocation extends Model
{
    /** @use HasFactory<GuestAllocationFactory> */
    use HasFactory;

    protected $table = 'guest_allocations';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pax' => 'integer',
            'session_id' => 'integer',
            'allocated_by' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
