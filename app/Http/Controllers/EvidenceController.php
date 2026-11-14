<?php

namespace App\Http\Controllers;

use App\Models\EvidenceFile;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EvidenceController extends Controller
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

    /**
     * Display a listing of tasks to view or upload evidence.
     */
    public function tasks(): View
    {
        $tasks = Task::with('team')
            ->join('teams', 'teams.id', '=', 'tasks.team_id')
            ->select('tasks.*', 'teams.name as team_name')
            ->orderBy('teams.name')
            ->orderBy('tasks.title')
            ->get();

        return view('evidence.tasks', compact('tasks'));
    }

    /**
     * Display evidence files for a specific task.
     */
    public function index(int $taskId): View
    {
        $task = Task::with('team')->findOrFail($taskId);
        $files = EvidenceFile::where('task_id', $taskId)
            ->orderBy('file_name')
            ->orderByDesc('version')
            ->get();

        return view('evidence.index', compact('task', 'files', 'taskId'));
    }

    /**
     * Show form for uploading evidence for a specific task.
     */
    public function create(int $taskId): View
    {
        $task = Task::with('team')->findOrFail($taskId);

        return view('evidence.create', compact('task', 'taskId'));
    }

    /**
     * Store newly uploaded evidence file.
     */
    public function store(Request $request, int $taskId): RedirectResponse
    {
        $task = Task::findOrFail($taskId);

        $request->validate([
            'evidence_file' => ['required', 'file', 'max:10240'], // 10MB in KB
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $uploadedFile = $request->file('evidence_file');
        if (!$uploadedFile || !$uploadedFile->isValid()) {
            return back()->with('error', 'The uploaded file is not valid or was corrupted.');
        }

        $originalFileName = basename($uploadedFile->getClientOriginalName());
        $extension = $uploadedFile->getClientOriginalExtension();
        $storedFileName = time() . '_' . bin2hex(random_bytes(12)) . ($extension ? '.' . strtolower($extension) : '');

        $uploadDir = EvidenceFile::getUploadDirectory();
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return back()->with('error', 'Unable to create the evidence storage directory.');
        }

        $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $storedFileName;
        $uploadedFile->move($uploadDir, $storedFileName);

        $sha256Hash = hash_file('sha256', $destinationPath);
        $storedSize = filesize($destinationPath);

        if ($sha256Hash === false || $storedSize === false) {
            @unlink($destinationPath);
            return back()->with('error', 'The uploaded file could not be verified with SHA-256.');
        }

        $uploaderId = session('active_user_id') ?? $task->assignee_id ?? 1;

        $version = EvidenceFile::getNextVersion($taskId, $originalFileName);

        EvidenceFile::create([
            'task_id' => $taskId,
            'uploader_id' => (int) $uploaderId,
            'file_name' => $originalFileName,
            'file_path' => $storedFileName,
            'file_size_bytes' => $storedSize,
            'mime_type' => $uploadedFile->getClientMimeType() ?: 'application/octet-stream',
            'version' => $version,
            'sha256_hash' => $sha256Hash,
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('evidence.index', $taskId)
            ->with('status', 'uploaded')
            ->with('success', 'Evidence file uploaded successfully with SHA-256 verification (v' . $version . ').');
    }

    /**
     * Delete an evidence file.
     */
    public function destroy(int $taskId, int $id): RedirectResponse
    {
        $file = EvidenceFile::where('task_id', $taskId)->findOrFail($id);

        $fullPath = EvidenceFile::getUploadPath($file->file_path);
        if ($fullPath && is_file($fullPath)) {
            @unlink($fullPath);
        }

        $file->delete();

        return redirect()->route('evidence.index', $taskId)
            ->with('status', 'deleted')
            ->with('success', 'Evidence record and file deleted successfully.');
    }

    /**
     * Download an evidence file.
     */
    public function download(int $taskId, int $id): BinaryFileResponse
    {
        $file = EvidenceFile::where('task_id', $taskId)->findOrFail($id);

        $fullPath = EvidenceFile::getUploadPath($file->file_path);
        if ($fullPath === null || !is_file($fullPath) || !is_readable($fullPath)) {
            abort(404, 'The requested evidence file is not available on disk.');
        }

        return response()->download($fullPath, $file->file_name, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
