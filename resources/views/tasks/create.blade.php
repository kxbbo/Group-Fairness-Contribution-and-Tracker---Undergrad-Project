@extends('layouts.app')

@section('title', 'Create Task — ' . $team->name . ' | FairShare')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-1">{{ $team->name }}</p>
                <h1 class="h2 mb-0">Create New Weighted Task</h1>
            </div>
            <a href="{{ route('tasks.index', $team->id) }}" class="btn btn-outline-secondary">&larr; Back to Task Board</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white">
                <span class="badge bg-light text-dark me-2">Feature 6</span>
                <strong>Weighted Task Definition</strong>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('tasks.store', $team->id) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Task Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required placeholder="e.g., Implement OAuth Authentication flow">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Description / Acceptance Criteria</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Outline specific deliverables and criteria for completion...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="difficulty_weight" class="form-label fw-bold">Difficulty Weight (1 to 5) <span class="text-danger">*</span></label>
                            <select class="form-select @error('difficulty_weight') is-invalid @enderror" id="difficulty_weight" name="difficulty_weight" required>
                                <option value="1" {{ old('difficulty_weight', '1') == '1' ? 'selected' : '' }}>★ 1 - Easy / Routine fix</option>
                                <option value="2" {{ old('difficulty_weight') == '2' ? 'selected' : '' }}>★ 2 - Minor / Standard feature</option>
                                <option value="3" {{ old('difficulty_weight') == '3' ? 'selected' : '' }}>★ 3 - Medium / Multi-component task</option>
                                <option value="4" {{ old('difficulty_weight') == '4' ? 'selected' : '' }}>★ 4 - High / Complex architecture</option>
                                <option value="5" {{ old('difficulty_weight') == '5' ? 'selected' : '' }}>★ 5 - Very Complex / Core engine</option>
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
                                    <option value="{{ $m->id }}" {{ old('assignee_id') == $m->id ? 'selected' : '' }}>
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
                        <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ old('due_date') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('tasks.index', $team->id) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Create Task &rarr;</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
