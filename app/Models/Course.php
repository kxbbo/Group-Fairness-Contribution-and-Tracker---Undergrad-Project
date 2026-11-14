<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'code',
        'title',
        'description',
        'team_size_min',
        'team_size_max',
        'evaluation_rounds_count',
        'formula_weights',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'formula_weights' => 'array',
            'is_active' => 'boolean',
            'team_size_min' => 'integer',
            'team_size_max' => 'integer',
            'evaluation_rounds_count' => 'integer',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
