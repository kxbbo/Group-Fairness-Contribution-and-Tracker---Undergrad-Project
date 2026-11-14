<?php

if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence');
}

require_once __DIR__ . '/../Config/Database.php';

class EvidenceFile
{
    public static function getConnection(): PDO
    {
        return Database::connection();
    }

    public static function getTasks(): array
    {
        $statement = self::getConnection()->query(
            'SELECT tasks.id, tasks.title, teams.name AS team_name
             FROM tasks
             INNER JOIN teams ON teams.id = tasks.team_id
             ORDER BY teams.name, tasks.title'
        );

        return $statement->fetchAll();
    }

    public static function findTask($id): ?array
    {
        $statement = self::getConnection()->prepare(
            'SELECT tasks.id, tasks.title, tasks.assignee_id, teams.name AS team_name
             FROM tasks
             INNER JOIN teams ON teams.id = tasks.team_id
             WHERE tasks.id = ?
             LIMIT 1'
        );
        $statement->execute([(int) $id]);
        $task = $statement->fetch();

        return $task === false ? null : $task;
    }

    public static function getAllByTask($taskId): array
    {
        $statement = self::getConnection()->prepare(
            'SELECT * FROM evidence_files WHERE task_id = ? ORDER BY file_name, version DESC'
        );
        $statement->execute([(int) $taskId]);

        return $statement->fetchAll();
    }

    public static function find($id): ?array
    {
        $statement = self::getConnection()->prepare(
            'SELECT * FROM evidence_files WHERE id = ? LIMIT 1'
        );
        $statement->execute([(int) $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public static function create(array $data): string
    {
        $statement = self::getConnection()->prepare(
            'INSERT INTO evidence_files
                (task_id, uploader_id, file_name, file_path, file_size_bytes, mime_type, version, sha256_hash, notes)
             VALUES
                (:task_id, :uploader_id, :file_name, :file_path, :file_size_bytes, :mime_type, :version, :sha256_hash, :notes)'
        );

        $statement->execute([
            ':task_id' => (int) $data['task_id'],
            ':uploader_id' => (int) $data['uploader_id'],
            ':file_name' => $data['file_name'],
            ':file_path' => $data['file_path'],
            ':file_size_bytes' => (int) $data['file_size_bytes'],
            ':mime_type' => $data['mime_type'],
            ':version' => (int) $data['version'],
            ':sha256_hash' => $data['sha256_hash'],
            ':notes' => $data['notes'],
        ]);

        return self::getConnection()->lastInsertId();
    }

    public static function delete($id): bool
    {
        $statement = self::getConnection()->prepare(
            'DELETE FROM evidence_files WHERE id = ?'
        );
        $statement->execute([(int) $id]);

        return $statement->rowCount() > 0;
    }

    public static function getNextVersion($taskId, $fileName): int
    {
        $statement = self::getConnection()->prepare(
            'SELECT MAX(version) FROM evidence_files WHERE task_id = ? AND file_name = ?'
        );
        $statement->execute([(int) $taskId, $fileName]);
        $maximum = $statement->fetchColumn();

        return $maximum === null ? 1 : ((int) $maximum) + 1;
    }

    public static function isAltered(array $row): bool
    {
        $relativePath = $row['file_path'] ?? '';
        $path = self::resolveUploadPath($relativePath);
        $storedHash = (string) ($row['sha256_hash'] ?? '');

        if ($path === null || !is_file($path) || $storedHash === '') {
            return true;
        }

        $currentHash = hash_file('sha256', $path);

        return $currentHash === false || !hash_equals($storedHash, $currentHash);
    }

    public static function getUploadPath(string $relativePath): ?string
    {
        return self::resolveUploadPath($relativePath);
    }

    private static function resolveUploadPath(string $relativePath): ?string
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $segments = explode('/', $relativePath);

        if ($relativePath === '' || in_array('..', $segments, true) || strpos($relativePath, "\0") !== false) {
            return null;
        }

        $uploadRoot = realpath(UPLOAD_DIR);
        if ($uploadRoot === false) {
            return null;
        }

        $path = $uploadRoot . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
        $resolvedPath = realpath($path);

        if ($resolvedPath === false) {
            return $path;
        }

        $rootPrefix = rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $prefixMatches = DIRECTORY_SEPARATOR === '\\'
            ? strncasecmp($resolvedPath, $rootPrefix, strlen($rootPrefix)) === 0
            : strpos($resolvedPath, $rootPrefix) === 0;
        if (!$prefixMatches) {
            return null;
        }

        return $resolvedPath;
    }
}
