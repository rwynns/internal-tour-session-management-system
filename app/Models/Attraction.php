<?php

namespace App\Models;

use Database\Factories\AttractionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'duration_minutes', 'is_active'])]
class Attraction extends Model
{
    /** @use HasFactory<AttractionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    /**
     * Get the sessions for this attraction.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
