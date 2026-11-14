<?php

if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', dirname(__DIR__, 2) . '/public/evidence');
}

class EvidenceFile
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = 'mysql:host=127.0.0.1;dbname=fairshare;charset=utf8mb4';
            self::$connection = new PDO($dsn, 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$connection;
    }

    public static function getAllByTask($taskId): array
    {
        $statement = self::getConnection()->prepare(
            'SELECT * FROM evidence_files WHERE task_id = ? ORDER BY version DESC'
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
                (task_id, uploader_id, file_path, original_file_name, sha256_hash, version, notes, file_size_bytes)
             VALUES
                (:task_id, :uploader_id, :file_path, :original_file_name, :sha256_hash, :version, :notes, :file_size_bytes)'
        );

        $statement->execute([
            ':task_id' => (int) $data['task_id'],
            ':uploader_id' => (int) $data['uploader_id'],
            ':file_path' => $data['file_path'],
            ':original_file_name' => $data['original_file_name'],
            ':sha256_hash' => $data['sha256_hash'],
            ':version' => (int) $data['version'],
            ':notes' => $data['notes'],
            ':file_size_bytes' => (int) $data['file_size_bytes'],
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
            'SELECT MAX(version) FROM evidence_files WHERE task_id = ? AND original_file_name = ?'
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

        if ($relativePath === '' || in_array('..', $segments, true) || str_contains($relativePath, "\0")) {
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
        if (strpos($resolvedPath, $rootPrefix) !== 0) {
            return null;
        }

        return $resolvedPath;
    }
}
