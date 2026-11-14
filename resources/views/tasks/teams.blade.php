@extends('layouts.app')

@section('title', 'Select Team — Task Board F6 | FairShare')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">F6: Weighted Task Management</h1>
        <p class="text-muted mb-0">Select a team to view and manage its 4-stage Kanban task board.</p>
    </div>
    <a href="{{ route('home') }}" class="btn btn-outline-secondary">&larr; Back to Home</a>
</div>

<div class="row g-4">
    @forelse($teams as $team)
        @php
            $totalTasks = $team->tasks->count();
            $totalWeight = $team->tasks->sum('difficulty_weight');
            $doneWeight = $team->tasks->where('status', \App\Models\Task::STATUS_DONE)->sum('difficulty_weight');
            $pct = $totalWeight > 0 ? round(($doneWeight / $totalWeight) * 100, 1) : 0;
        @endphp
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 bg-white">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-success">{{ $team->course->code ?? 'Course' }}</span>
                        <span class="badge bg-light text-dark border">{{ $team->members->count() }} members</span>
                    </div>
                    <h3 class="h4 card-title text-dark mb-1">{{ $team->name }}</h3>
                    <p class="text-muted small mb-3">
                        Leader: <strong>{{ $team->leader->name ?? 'Unassigned' }}</strong>
                    </p>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Weighted Progress</span>
                            <span><strong>{{ $pct }}%</strong> ({{ $doneWeight }}/{{ $totalWeight }} pts)</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between text-muted small mb-4">
                        <span>Total Tasks: <strong>{{ $totalTasks }}</strong></span>
                        <span>In Progress/Review: <strong>{{ $team->tasks->whereIn('status', [\App\Models\Task::STATUS_IN_PROGRESS, \App\Models\Task::STATUS_IN_REVIEW])->count() }}</strong></span>
                    </div>

                    <div class="mt-auto">
                        <a href="{{ route('tasks.index', $team->id) }}" class="btn btn-primary w-100">
                            Open Task Board &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card p-5 text-center text-muted">
                <p>No teams found in the database. Please seed the database using <code>fairshare_seed.sql</code>.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
