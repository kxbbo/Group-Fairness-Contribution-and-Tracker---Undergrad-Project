<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceFile extends Model
{
    use HasFactory;

    protected $table = 'evidence_files';

    protected $fillable = [
        'task_id',
        'uploader_id',
        'file_name',
        'file_path',
        'file_size_bytes',
        'mime_type',
        'version',
        'sha256_hash',
        'notes',
        'is_altered',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'version' => 'integer',
            'is_altered' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public static function getUploadDirectory(): string
    {
        return storage_path('evidence');
    }

    public static function getNextVersion(int $taskId, string $fileName): int
    {
        $maxVersion = self::where('task_id', $taskId)
            ->where('file_name', $fileName)
            ->max('version');

        return $maxVersion === null ? 1 : ((int) $maxVersion) + 1;
    }

    public function isAltered(): bool
    {
        if ($this->is_altered) {
            return true;
        }

        if (empty($this->file_path)) {
            return true;
        }

        $fullPath = self::getUploadPath($this->file_path);
        if ($fullPath === null || !is_file($fullPath) || empty($this->sha256_hash)) {
            return true;
        }

        $currentHash = hash_file('sha256', $fullPath);
        return $currentHash === false || !hash_equals($this->sha256_hash, $currentHash);
    }

    public static function getUploadPath(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }
        $relativePath = str_replace('\\', '/', $relativePath);
        $segments = explode('/', $relativePath);

        if ($relativePath === '' || in_array('..', $segments, true) || strpos($relativePath, "\0") !== false) {
            return null;
        }

        $uploadRoot = realpath(self::getUploadDirectory());
        if ($uploadRoot === false) {
            return self::getUploadDirectory() . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        }

        $path = $uploadRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
        $resolvedPath = realpath($path);

        if ($resolvedPath === false) {
            return $path;
        }

        $rootPrefix = rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $prefixMatches = DIRECTORY_SEPARATOR === '\\'
            ? strncasecmp($resolvedPath, $rootPrefix, strlen($rootPrefix)) === 0
            : strpos($resolvedPath, $rootPrefix) === 0;

        return $prefixMatches ? $resolvedPath : null;
    }
}
