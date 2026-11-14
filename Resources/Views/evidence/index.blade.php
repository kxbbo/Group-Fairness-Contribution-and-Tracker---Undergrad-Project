@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Evidence for Task {{ $taskId }}</h1>
        <a href="{{ route('evidence.create', ['taskId' => $taskId]) }}" class="btn btn-primary">
            Upload New Evidence
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">Original Name</th>
                    <th scope="col">Version</th>
                    <th scope="col">SHA-256 Hash</th>
                    <th scope="col">Notes</th>
                    <th scope="col">Upload Date</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($evidenceFiles as $file)
                    <tr>
                        <td>
                            {{ $file->original_file_name }}
                            @if ($file->isAltered())
                                <span class="badge bg-danger">⚠️ Altered</span>
                            @endif
                        </td>
                        <td>{{ $file->version }}</td>
                        <td><code>{{ substr($file->sha256_hash, 0, 10) }}...</code></td>
                        <td>{{ $file->notes ?? '—' }}</td>
                        <td>{{ optional($file->created_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            <form action="{{ route('evidence.destroy', $file->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this evidence file?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No evidence files have been uploaded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
