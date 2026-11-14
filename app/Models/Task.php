<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    public const STATUS_TO_DO = 'to_do';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_DONE = 'done';

    public const STATUSES = [
        self::STATUS_TO_DO => 'To Do',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_IN_REVIEW => 'In Review',
        self::STATUS_DONE => 'Done',
    ];

    protected $fillable = [
        'team_id',
        'milestone_id',
        'assignee_id',
        'title',
        'description',
        'difficulty_weight',
        'status',
        'due_date',
        'verified_by',
        'verified_at',
        'return_reason',
    ];

    protected function casts(): array
    {
        return [
            'difficulty_weight' => 'integer',
            'due_date' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function evidenceFiles(): HasMany
    {
        return $this->hasMany(EvidenceFile::class);
    }

    /**
     * Check if a given user can transition this task to target status.
     * Enforces the F6 SRS Acceptance Rules:
     * 1. Only the assignee can start a task (to_do -> in_progress).
     * 2. Only a non-assignee can verify it (in_review -> done).
     * 3. A member cannot move a task to Done directly (must go through in_review).
     *
     * @param string $targetStatus
     * @param int|null $userId
     * @return array{allowed: bool, message: string}
     */
    public function canTransitionTo(string $targetStatus, ?int $userId): array
    {
        if (!in_array($targetStatus, array_keys(self::STATUSES), true)) {
            return ['allowed' => false, 'message' => "Invalid target status: {$targetStatus}."];
        }

        if ($this->status === $targetStatus) {
            return ['allowed' => false, 'message' => "Task is already in status: " . self::STATUSES[$targetStatus] . "."];
        }

        // Rule: A member cannot move a task to Done directly (must go through In Review)
        if ($targetStatus === self::STATUS_DONE && $this->status !== self::STATUS_IN_REVIEW) {
            return [
                'allowed' => false,
                'message' => 'A task cannot be moved to Done directly. It must first be moved to In Review for peer verification.',
            ];
        }

        // Rule: Only the assignee can start a task (To Do -> In Progress)
        if ($this->status === self::STATUS_TO_DO && $targetStatus === self::STATUS_IN_PROGRESS) {
            if (!$this->assignee_id) {
                return [
                    'allowed' => false,
                    'message' => 'Task must have an assignee before it can be started.',
                ];
            }
            if ($userId !== null && (int)$this->assignee_id !== (int)$userId) {
                return [
                    'allowed' => false,
                    'message' => 'Only the assigned team member can start this task (move to In Progress).',
                ];
            }
        }

        // Rule: Only a non-assignee can verify it (In Review -> Done)
        if ($this->status === self::STATUS_IN_REVIEW && $targetStatus === self::STATUS_DONE) {
            if ($userId !== null && (int)$this->assignee_id === (int)$userId) {
                return [
                    'allowed' => false,
                    'message' => 'Only a non-assignee teammate can verify this task and move it to Done. Self-verification is strictly prohibited.',
                ];
            }
        }

        // Return from In Review to In Progress (rejection / revision)
        if ($this->status === self::STATUS_IN_REVIEW && $targetStatus === self::STATUS_IN_PROGRESS) {
            if ($userId !== null && (int)$this->assignee_id === (int)$userId) {
                return [
                    'allowed' => false,
                    'message' => 'Only a reviewer (non-assignee) can return a task from In Review to In Progress.',
                ];
            }
        }

        return ['allowed' => true, 'message' => 'Transition allowed.'];
    }
}
