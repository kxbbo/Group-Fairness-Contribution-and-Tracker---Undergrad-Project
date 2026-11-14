@extends('layouts.app')

@section('title', 'Edit Task: ' . $task->title . ' — FairShare')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-1">{{ $team->name }}</p>
                <h1 class="h2 mb-0">Edit Weighted Task</h1>
            </div>
            <a href="{{ route('tasks.index', $team->id) }}" class="btn btn-outline-secondary">&larr; Back to Task Board</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-light text-dark me-2">Feature 6</span>
                    <strong>Edit Task #{{ $task->id }}</strong>
                </div>
                <span class="badge bg-light text-primary fw-bold">
                    Status: {{ $task->status }}
                </span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('tasks.update', $task->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Task Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $task->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Description / Acceptance Criteria</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $task->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="difficulty_weight" class="form-label fw-bold">Difficulty Weight (1 to 5) <span class="text-danger">*</span></label>
                            <select class="form-select @error('difficulty_weight') is-invalid @enderror" id="difficulty_weight" name="difficulty_weight" required>
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ old('difficulty_weight', (string)$task->difficulty_weight) == (string)$i ? 'selected' : '' }}>
                                        ★ {{ $i }} - @switch($i)
                                            @case(1) Easy / Routine fix @break
                                            @case(2) Minor / Standard feature @break
                                            @case(3) Medium / Multi-component task @break
                                            @case(4) High / Complex architecture @break
                                            @case(5) Very Complex / Core engine @break
                                        @endswitch
                                    </option>
                                @endfor
                            </select>
                            <div class="form-text small">Task weight determines contribution credit in the fairness formula.</div>
                            @error('difficulty_weight')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="assignee_id" class="form-label fw-bold">Assignee</label>
                            <select class="form-select @error('assignee_id') is-invalid @enderror" id="assignee_id" name="assignee_id">
                                <option value="">-- Unassigned --</option>
                                @foreach($members as $m)
                                    <option value="{{ $m->id }}" {{ old('assignee_id', (string)$task->assignee_id) == (string)$m->id ? 'selected' : '' }}>
                                        {{ $m->name }} ({{ $m->role_title ?? $m->role ?? 'Member' }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Only the assignee will be allowed to start this task.</div>
                            @error('assignee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="due_date" class="form-label fw-bold">Due Date (Optional)</label>
                        <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('tasks.index', $team->id) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

