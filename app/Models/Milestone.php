<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    use HasFactory;

    protected $table = 'milestones';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'deadline',
        'weight_percent',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
