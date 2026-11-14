<?php

require_once __DIR__ . '/../../Models/EvidenceFile.php';

class EvidenceController
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public function index($taskId): void
    {
        $taskId = $this->validatedId($taskId, 'task');
        $files = EvidenceFile::getAllByTask($taskId);

        require dirname(__DIR__, 3) . '/Resources/Views/evidence/index.php';
    }

    public function create($taskId): void
    {
        $taskId = $this->validatedId($taskId, 'task');

        require dirname(__DIR__, 3) . '/Resources/Views/evidence/create.php';
    }

    public function store($taskId): void
    {
        $taskId = $this->validatedId($taskId, 'task');
        $file = $_FILES['evidence_file'] ?? null;

        if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
            throw new RuntimeException('Select a file to upload.');
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The file upload failed. Please try again.');
        }

        $temporaryPath = (string) $file['tmp_name'];
        $fileSize = filesize($temporaryPath);

        if (!is_uploaded_file($temporaryPath) || $fileSize === false) {
            throw new RuntimeException('The uploaded file is not valid.');
        }

        if ($fileSize >= self::MAX_FILE_SIZE) {
            throw new RuntimeException('The file must be smaller than 10 MB.');
        }

        $originalFileName = basename(str_replace('\\', '/', (string) $file['name']));
        if ($originalFileName === '' || strlen($originalFileName) > 255) {
            throw new RuntimeException('The original file name is invalid or too long.');
        }

        $notes = $_POST['notes'] ?? null;
        if ($notes !== null && !is_string($notes)) {
            throw new RuntimeException('The notes value is invalid.');
        }
        $notes = $notes === null || trim($notes) === '' ? null : trim($notes);

        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
            throw new RuntimeException('The evidence upload directory could not be created.');
        }

        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);
        $storedFileName = time() . '_' . bin2hex(random_bytes(12))
            . ($extension === '' ? '' : '.' . strtolower($extension));
        $destinationPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $storedFileName;

        if (!move_uploaded_file($temporaryPath, $destinationPath)) {
            throw new RuntimeException('The uploaded file could not be saved.');
        }

        $sha256Hash = hash_file('sha256', $destinationPath);
        $storedSize = filesize($destinationPath);

        if ($sha256Hash === false || $storedSize === false) {
            unlink($destinationPath);
            throw new RuntimeException('The saved file could not be verified.');
        }

        try {
            EvidenceFile::create([
                'task_id' => $taskId,
                'uploader_id' => isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 1,
                'file_path' => $storedFileName,
                'original_file_name' => $originalFileName,
                'sha256_hash' => $sha256Hash,
                'version' => EvidenceFile::getNextVersion($taskId, $originalFileName),
                'notes' => $notes,
                'file_size_bytes' => $storedSize,
            ]);
        } catch (Throwable $exception) {
            if (is_file($destinationPath) && !unlink($destinationPath)) {
                error_log('Unable to remove uploaded evidence after a database error: ' . $destinationPath);
            }

            throw $exception;
        }

        $this->redirectToTask($taskId, 'uploaded');
    }

    public function destroy($id, $taskId): void
    {
        $id = $this->validatedId($id, 'evidence file');
        $taskId = $this->validatedId($taskId, 'task');
        $file = EvidenceFile::find($id);

        if ($file === null || (int) $file['task_id'] !== $taskId) {
            http_response_code(404);
            throw new RuntimeException('The evidence file was not found for this task.');
        }

        if (is_dir(UPLOAD_DIR)) {
            $filePath = EvidenceFile::getUploadPath((string) $file['file_path']);
            if ($filePath === null) {
                throw new RuntimeException('The stored evidence path is invalid.');
            }

            if (is_file($filePath) && !unlink($filePath)) {
                throw new RuntimeException('The evidence file could not be deleted from storage.');
            }
        }

        if (!EvidenceFile::delete($id)) {
            throw new RuntimeException('The evidence record could not be deleted.');
        }

        $this->redirectToTask($taskId, 'deleted');
    }

    private function validatedId($value, string $label): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new InvalidArgumentException('A valid ' . $label . ' ID is required.');
        }

        return $id;
    }

    private function redirectToTask(int $taskId, string $status): void
    {
        $query = http_build_query([
            'page' => 'evidence',
            'task_id' => $taskId,
            'status' => $status,
        ]);

        header('Location: index.php?' . $query);
        exit;
    }
}
