@extends('layouts.app')

@section('title', 'Evidence Vault — Task Selection | FairShare')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Evidence Vault (Feature 11)</h1>
        <p class="text-muted mb-0">Select a task to view, verify SHA-256 hashes, or upload contribution evidence.</p>
    </div>
    <a href="{{ route('tasks.teams') }}" class="btn btn-outline-success">
        &larr; Go to Task Boards (F6)
    </a>
</div>

@if($tasks->isEmpty())
    <div class="card p-4 text-center text-muted">
        <p class="mb-2">No tasks found in the database.</p>
        <div>
            <a href="{{ route('tasks.teams') }}" class="btn btn-primary btn-sm">Create Tasks in Task Board</a>
        </div>
    </div>
@else
    <div class="list-group shadow-sm">
        @foreach($tasks as $t)
            <a href="{{ route('evidence.index', $t->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                <div>
                    <h5 class="mb-1 text-dark">{{ $t->title }}</h5>
                    <small class="text-muted">
                        Team: <strong>{{ $t->team_name ?? $t->team->name ?? 'Team #' . $t->team_id }}</strong>
                        &bull; Status: <span class="badge bg-secondary">{{ \App\Models\Task::STATUSES[$t->status] ?? $t->status }}</span>
                        &bull; Weight: <span class="badge badge-weight-{{ $t->difficulty_weight }}">Difficulty {{ $t->difficulty_weight }}/5</span>
                    </small>
                </div>
                <div class="text-end">
                    <span class="btn btn-sm btn-outline-primary">Open Vault &rarr;</span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
