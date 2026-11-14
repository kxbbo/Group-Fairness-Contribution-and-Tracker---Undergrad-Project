@extends('layouts.app')

@section('title', $team->name . ' — Task Board — FairShare')

@section('content')
<!-- Header & Team Metrics -->
<div class="card bg-white shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('tasks.teams') }}">Teams</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $team->name }}</li>
                    </ol>
                </nav>
                <h1 class="h2 mb-1 fw-bold">{{ $team->name }} &mdash; Weighted Task Board</h1>
                <p class="text-muted small mb-0">
                    Course: <strong>{{ $team->course->code ?? 'CSE470' }}</strong> &bull;
                    Leader: <strong>{{ $team->leader->name ?? 'Unassigned' }}</strong> &bull;
                    Team Members:
                    @foreach(($members ?? $team->members) as $m)
                        <span class="badge {{ $activeUser && $activeUser->id === $m->id ? 'bg-primary' : 'bg-light text-dark border' }} me-1">
                            {{ $m->name }}
                        </span>
                    @endforeach
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('tasks.create', $team->id) }}" class="btn btn-primary">
                    + Add New Task
                </a>
                <a href="{{ route('evidence.tasks') }}" class="btn btn-outline-secondary">
                    Evidence Vault (F11)
                </a>
            </div>
        </div>

        <!-- Weighted Progress Bar -->
        <div class="row align-items-center mt-3 pt-3 border-top g-3">
            <div class="col-md-6">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span><strong>Weighted Completion:</strong> {{ $progressPercent }}%</span>
                    <span>{{ $completedWeight }} of {{ $totalWeight }} Total Difficulty Points Done</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progressPercent }}%" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <div class="col-md-6 text-md-end small text-muted">
                <span class="badge bg-light text-dark border me-1">★ 1: Easy</span>
                <span class="badge bg-light text-dark border me-1">★ 2: Minor</span>
                <span class="badge bg-light text-dark border me-1">★ 3: Medium</span>
                <span class="badge bg-light text-dark border me-1">★ 4: High</span>
                <span class="badge bg-light text-dark border">★ 5: Complex</span>
            </div>
        </div>

        <!-- Rules Banner -->
        <div class="alert alert-light border mt-3 mb-0 small d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <strong>F6 Transition Rules:</strong>
                <span class="ms-2">&bull; <strong>Start:</strong> Only assignee</span>
                <span class="ms-2">&bull; <strong>Verify &rarr; Done:</strong> Only non-assignee teammate</span>
                <span class="ms-2">&bull; <strong>Direct to Done:</strong> Strictly prohibited</span>
            </div>
            <div class="text-muted">
                Current Actor: <strong>{{ $activeUser->name ?? 'Nobody' }}</strong> (switch in top navbar to test peer verification)
            </div>
        </div>
    </div>
</div>

<!-- 4-Column Kanban Board -->
<div class="row g-3">
    @php
        $columnConfigs = [
            \App\Models\Task::STATUS_TO_DO => [
                'title' => 'To Do',
                'badge' => 'bg-secondary',
                'desc' => 'Planned tasks awaiting start',
            ],
            \App\Models\Task::STATUS_IN_PROGRESS => [
                'title' => 'In Progress',
                'badge' => 'bg-primary',
                'desc' => 'Actively being worked on',
            ],
            \App\Models\Task::STATUS_IN_REVIEW => [
                'title' => 'In Review',
                'badge' => 'bg-warning text-dark',
                'desc' => 'Awaiting peer verification',
            ],
            \App\Models\Task::STATUS_DONE => [
                'title' => 'Done (Verified)',
                'badge' => 'bg-success',
                'desc' => 'Peer-verified completion',
            ],
        ];
    @endphp

    @foreach($columnConfigs as $statusKey => $cfg)
        @php
            $columnTasks = $columns[$statusKey] ?? collect();
            $colWeight = $columnTasks->sum('difficulty_weight');
        @endphp
        <div class="col-12 col-md-6 col-xl-3">
            <div class="kanban-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0 fw-bold">
                        <span class="badge {{ $cfg['badge'] }} me-1">{{ $columnTasks->count() }}</span>
                        {{ $cfg['title'] }}
                    </h2>
                    <span class="badge bg-light text-dark border small" title="Sum of difficulty weights">
                        {{ $colWeight }} pts
                    </span>
                </div>
                <p class="text-muted small mb-3">{{ $cfg['desc'] }}</p>

                <div class="kanban-task-list">
                    @forelse($columnTasks as $task)
                        @php
                            $isAssignee = $activeUser && $task->assignee_id && (int)$task->assignee_id === (int)$activeUser->id;
                            $canStart = $isAssignee;
                            $canVerify = $activeUser && (!$task->assignee_id || (int)$task->assignee_id !== (int)$activeUser->id);
                        @endphp
                        <div class="kanban-card">
                            <!-- Card Header: Difficulty Weight & Due Date -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge badge-weight-{{ $task->difficulty_weight }}" title="Difficulty Weight (1-5)">
                                    ★ Weight {{ $task->difficulty_weight }}
                                </span>
                                @if($task->due_date)
                                    @php $isOverdue = $task->due_date->isPast() && $task->status !== \App\Models\Task::STATUS_DONE; @endphp
                                    <span class="small {{ $isOverdue ? 'text-danger fw-bold' : 'text-muted' }}" title="Due Date">
                                        📅 {{ $task->due_date->format('M d') }}
                                        @if($isOverdue) <span class="badge bg-danger text-white">Overdue</span> @endif
                                    </span>
                                @endif
                            </div>

                            <!-- Title & Description -->
                            <h3 class="h6 fw-bold mb-1 text-dark">{{ $task->title }}</h3>
                            @if($task->description)
                                <p class="text-muted small mb-2" style="font-size: 0.85rem; line-height: 1.4;">
                                    {{ Str::limit($task->description, 100) }}
                                </p>
                            @endif

                            <!-- Assignee & Evidence link -->
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                                <div>
                                    @if($task->assignee)
                                        <span class="badge bg-light text-dark border" title="Assignee">
                                            👤 {{ $task->assignee->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">Unassigned</span>
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('evidence.index', $task->id) }}" class="text-decoration-none text-muted" title="View/Upload Evidence Files (F11)">
                                        📎 {{ $task->evidenceFiles->count() }} Files
                                    </a>
                                </div>
                            </div>

                            <!-- Verifier info if Done -->
                            @if($task->status === \App\Models\Task::STATUS_DONE && $task->verifier)
                                <div class="mt-2 p-1 bg-light rounded text-success small">
                                    ✓ Verified by <strong>{{ $task->verifier->name }}</strong>
                                    @if($task->verified_at)
                                        <span class="text-muted">on {{ $task->verified_at->format('M d') }}</span>
                                    @endif
                                </div>
                            @endif

                            <!-- Return reason notice if returned -->
                            @if($task->return_reason)
                                <div class="mt-2 alert alert-warning p-2 small mb-0">
                                    <strong>Returned for revisions:</strong> {{ $task->return_reason }}
                                </div>
                            @endif

                            <!-- Action Controls enforcing F6 rules -->
                            <div class="mt-3 pt-2 border-top">
                                <!-- Column 1: TO DO transitions -->
                                @if($task->status === \App\Models\Task::STATUS_TO_DO)
                                    <div class="d-flex gap-1">
                                        @if($canStart)
                                            <form method="POST" action="{{ route('tasks.updateStatus', $task->id) }}" class="flex-grow-1">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="in_progress">
                                                <button type="submit" class="btn btn-sm btn-primary w-100" title="Start working on this task">
                                                    Start Task &rarr;
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary w-100 text-muted" disabled title="Only the assignee ({{ $task->assignee->name ?? 'Unassigned' }}) can start this task per SRS F6 rules.">
                                                🔒 Only Assignee Can Start
                                            </button>
                                        @endif
                                    </div>
                                @endif

                                <!-- Column 2: IN PROGRESS transitions -->
                                @if($task->status === \App\Models\Task::STATUS_IN_PROGRESS)
                                    <div class="d-flex flex-column gap-1">
                                        <form method="POST" action="{{ route('tasks.updateStatus', $task->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="in_review">
                                            <button type="submit" class="btn btn-sm btn-warning text-dark w-100">
                                                Submit for Peer Review &rarr;
                                            </button>
                                        </form>
                                        <div class="text-center small text-muted mt-1" style="font-size: 0.75rem;">
                                            Cannot move directly to Done (must pass review)
                                        </div>
                                    </div>
                                @endif

                                <!-- Column 3: IN REVIEW transitions (Peer Verification) -->
                                @if($task->status === \App\Models\Task::STATUS_IN_REVIEW)
                                    <div class="d-flex flex-column gap-1">
                                        @if($canVerify)
                                            <form method="POST" action="{{ route('tasks.updateStatus', $task->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="done">
                                                <button type="submit" class="btn btn-sm btn-success w-100">
                                                    ✓ Verify &amp; Mark Done
                                                </button>
                                            </form>
                                            <!-- Return to In Progress with Reason -->
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-1" data-bs-toggle="modal" data-bs-target="#returnModal{{ $task->id }}">
                                                &larr; Return for Revisions
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary w-100 text-muted" disabled title="Self-verification is forbidden per SRS F6. Only a teammate who is NOT the assignee can verify this task.">
                                                🔒 Only Teammate Can Verify
                                            </button>
                                            <small class="text-muted text-center" style="font-size: 0.75rem;">
                                                Switch actor in navbar to verify
                                            </small>
                                        @endif
                                    </div>

                                    <!-- Return Reason Modal -->
                                    <div class="modal fade" id="returnModal{{ $task->id }}" tabindex="-1" aria-labelledby="returnModalLabel{{ $task->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('tasks.updateStatus', $task->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="in_progress">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="returnModalLabel{{ $task->id }}">Return Task: {{ $task->title }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="small text-muted">
                                                            Returning this task will move it back to <strong>In Progress</strong> for the assignee to make corrections.
                                                        </p>
                                                        <div class="mb-3">
                                                            <label for="reason{{ $task->id }}" class="form-label fw-bold">Reason for Return <span class="text-danger">*</span></label>
                                                            <textarea class="form-control" id="reason{{ $task->id }}" name="return_reason" rows="3" required placeholder="Specify what needs revision or what evidence is missing..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Return Task</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Column 4: DONE transitions -->
                                @if($task->status === \App\Models\Task::STATUS_DONE)
                                    <div class="text-center text-success small py-1">
                                        <strong>✓ Task Completed</strong>
                                    </div>
                                @endif

                                <!-- Edit & Delete Options -->
                                <div class="d-flex justify-content-end gap-1 mt-2 pt-1 border-top" style="font-size: 0.75rem;">
                                    <a href="{{ route('tasks.edit', $task->id) }}" class="text-muted text-decoration-none me-2">Edit</a>
                                    <form method="POST" action="{{ route('tasks.destroy', $task->id) }}" onsubmit="return confirm('Delete task: {{ $task->title }}?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link p-0 text-danger text-decoration-none" style="font-size: 0.75rem;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            No tasks in this column.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
