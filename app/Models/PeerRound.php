<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PeerRound extends Model
{
    use HasFactory;

    protected $table = 'peer_rounds';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_PROCESSED = 'processed';

    public const STATUSES = [
        self::STATUS_SCHEDULED => 'Scheduled',
        self::STATUS_OPEN => 'Open',
        self::STATUS_CLOSED => 'Closed',
        self::STATUS_PROCESSED => 'Processed',
    ];

    protected $fillable = [
        'course_id',
        'round_number',
        'title',
        'opens_at',
        'closes_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(PeerRating::class, 'round_id');
    }

    /**
     * Determine if this evaluation round is currently active and open for submissions.
     */
    public function isOpen(): bool
    {
        if ($this->status !== self::STATUS_OPEN) {
            return false;
        }

        $now = Carbon::now();
        $opensAt = Carbon::parse($this->opens_at);
        $closesAt = Carbon::parse($this->closes_at);

        return $now->betweenIncluded($opensAt, $closesAt);
    }

    /**
     * Check if a specific rater has already completed this round.
     */
    public function hasUserSubmitted(int $raterId): bool
    {
        return PeerRating::where('round_id', '=', $this->id)
            ->where('rater_id', '=', $raterId)
            ->first() !== null;
    }
}
