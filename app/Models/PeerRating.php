<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerRating extends Model
{
    use HasFactory;

    protected $table = 'peer_ratings';

    protected $fillable = [
        'round_id',
        'rater_id',
        'ratee_id',
        'effort',
        'reliability',
        'communication',
        'quality',
        'comments',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'effort' => 'integer',
            'reliability' => 'integer',
            'communication' => 'integer',
            'quality' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(PeerRound::class, 'round_id');
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }

    /**
     * Compute average score across the four rating dimensions.
     */
    public function getAverageScoreAttribute(): float
    {
        return round(($this->effort + $this->reliability + $this->communication + $this->quality) / 4.0, 2);
    }
}
