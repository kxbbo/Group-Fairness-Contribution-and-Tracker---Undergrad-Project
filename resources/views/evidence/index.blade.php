@extends('layouts.app')

@section('title', $task->title . ' — Evidence Vault | FairShare')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-1">{{ $task->team->name ?? 'Team #' . $task->team_id }}</p>
        <h1 class="h2 mb-0">{{ $task->title }} &mdash; Evidence Vault</h1>
        <div class="mt-2">
            <span class="badge bg-secondary">{{ \App\Models\Task::STATUSES[$task->status] ?? $task->status }}</span>
            <span class="badge badge-weight-{{ $task->difficulty_weight }}">Difficulty Weight: {{ $task->difficulty_weight }}/5</span>
            @if($task->assignee)
                <span class="badge bg-light text-dark border">Assignee: {{ $task->assignee->name }}</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('evidence.tasks') }}">All Tasks</a>
        <a class="btn btn-outline-success" href="{{ route('tasks.index', $task->team_id) }}">Task Board (F6)</a>
    </div>
</div>

<div class="mb-3">
    <a class="btn btn-primary" href="{{ route('evidence.create', $task->id) }}">
        + Upload New Evidence
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">File Name</th>
                    <th scope="col" style="width: 90px;">Version</th>
                    <th scope="col">SHA-256 Hash</th>
                    <th scope="col">Notes</th>
                    <th scope="col">Uploaded</th>
                    <th scope="col" class="text-end" style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($files as $file)
                    @php $isAltered = $file->isAltered(); @endphp
                    <tr>
                        <td>
                            <strong>{{ $file->file_name }}</strong>
                            @if($isAltered)
                                <span class="badge bg-danger ms-1" title="The cryptographic SHA-256 hash does not match disk contents or file is missing.">
                                    Altered / Corrupted
                                </span>
                            @else
                                <span class="badge bg-success ms-1" title="SHA-256 integrity check verified">
                                    Verified
                                </span>
                            @endif
                            <div class="small text-muted">{{ number_format($file->file_size_bytes / 1024, 1) }} KB</div>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">v{{ $file->version }}</span>
                        </td>
                        <td>
                            <code class="small text-break" style="font-size: 0.78rem;">{{ $file->sha256_hash }}</code>
                        </td>
                        <td>
                            <span class="small">{{ $file->notes ?: '—' }}</span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $file->created_at ? (is_object($file->created_at) ? $file->created_at->format('M d, Y H:i') : \Carbon\Carbon::parse($file->created_at)->format('M d, Y H:i')) : '—' }}</small>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-primary" href="{{ route('evidence.download', [$task->id, $file->id]) }}">
                                    Download
                                </a>
                                <form method="POST" action="{{ route('evidence.destroy', [$task->id, $file->id]) }}" onsubmit="return confirm('Are you sure you want to delete this evidence record?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No evidence files uploaded for this task yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
