@extends('layouts.app')

@section('title', 'F16: Anonymous Peer Evaluation Rounds — FairShare')

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h1 class="h2 mb-1">Peer Evaluation Rounds</h1>
        <p class="text-muted mb-0">
            <strong>Feature 16 (Member 4):</strong> Confidential teammate performance evaluations across effort, reliability, communication, and work quality.
        </p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <span class="badge bg-secondary py-2 px-3">
            Current Actor: <strong>{{ $activeUser->name ?? 'User' }}</strong> ({{ ucfirst($activeUser->role ?? 'student') }})
        </span>
    </div>
</div>



@if($isInstructor)
    <!-- ========================================== -->
    <!-- INSTRUCTOR / ADMIN DASHBOARD (SRS Section 3) -->
    <!-- ========================================== -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h4 mb-0">Instructor Round Management &amp; Team Tracking</h3>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#newRoundCollapse">
                + Schedule New Round
            </button>
            <form action="{{ route('evaluations.resetDemo') }}" method="POST" onsubmit="return confirm('Reset all demo peer ratings so students can submit anew?');">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm" title="Clears test peer_ratings rows for quick re-testing">
                    ↺ Reset Test Submissions
                </button>
            </form>
        </div>
    </div>

    <!-- Create Round Collapsible Form -->
    <div class="collapse mb-4" id="newRoundCollapse">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white py-2">
                <strong>Schedule a New Peer Evaluation Round (F1 / F16)</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('evaluations.storeRound') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Course</label>
                            <select name="course_id" class="form-select form-select-sm" required>
                                @foreach($courses as $c)
                                    <option value="{{ $c->id }}">{{ $c->code }} &mdash; {{ $c->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Round #</label>
                            <input type="number" name="round_number" class="form-control form-control-sm" value="2" min="1" max="10" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Round Title</label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Sprint 2 Peer Evaluation" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Opens At</label>
                            <input type="datetime-local" name="opens_at" class="form-control form-control-sm" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Closes At</label>
                            <input type="datetime-local" name="closes_at" class="form-control form-control-sm" value="{{ date('Y-m-d\TH:i', strtotime('+7 days')) }}" required>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-success btn-sm px-4">Create &amp; Open Round</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Instructor Rounds List -->
    <div class="row g-4">
        @forelse($instructorRounds as $item)
            @php $round = $item['round']; @endphp
            <div class="col-12">
                <div class="card shadow-sm border-secondary">
                    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <span class="badge bg-{{ $round->status === 'open' ? 'success' : ($round->status === 'closed' ? 'dark' : 'warning') }} text-uppercase me-2">
                                Round {{ $round->round_number }} &bull; {{ $round->status }}
                            </span>
                            <strong class="fs-5">{{ $round->title }}</strong>
                            <span class="text-muted ms-2 small">({{ $round->course->code ?? 'CSE470' }})</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <form action="{{ route('evaluations.toggleStatus', $round->id) }}" method="POST" class="d-inline">
                                @csrf
                                @if($round->status === 'open')
                                    <input type="hidden" name="status" value="closed">
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Close this round to submissions">
                                        Close Round
                                    </button>
                                @else
                                    <input type="hidden" name="status" value="open">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Open this round for submissions">
                                        Open Round
                                    </button>
                                @endif
                            </form>
                            <span class="badge bg-info text-dark">
                                Overall: {{ $item['total_completed'] }}/{{ $item['total_expected'] }} Students ({{ $item['overall_pct'] }}%)
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Team</th>
                                        <th>Leader</th>
                                        <th>Team Size</th>
                                        <th>Submissions Progress</th>
                                        <th>Completion</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($item['team_metrics'] as $tm)
                                        <tr>
                                            <td><strong>{{ $tm['team']->name }}</strong></td>
                                            <td>{{ $tm['team']->leader->name ?? 'N/A' }}</td>
                                            <td>{{ $tm['member_count'] }} members</td>
                                            <td style="min-width: 180px;">
                                                <div class="progress" style="height: 14px;">
                                                    <div class="progress-bar {{ $tm['is_fully_submitted'] ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $tm['percentage'] }}%;"></div>
                                                </div>
                                                <small class="text-muted">{{ $tm['submitted_count'] }} of {{ $tm['member_count'] }} submitted</small>
                                            </td>
                                            <td><strong>{{ $tm['percentage'] }}%</strong></td>
                                            <td>
                                                @if($tm['is_fully_submitted'])
                                                    <span class="badge bg-success">&check; Fully Submitted</span>
                                                @elseif($tm['submitted_count'] > 0)
                                                    <span class="badge bg-warning text-dark">In Progress</span>
                                                @else
                                                    <span class="badge bg-secondary">Awaiting Submissions</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">No teams registered for this course yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-2 small text-muted d-flex justify-content-between">
                        <span>Window: <strong>{{ $round->opens_at instanceof \DateTimeInterface ? $round->opens_at->format('M d, Y H:i') : $round->opens_at }}</strong> &rarr; <strong>{{ $round->closes_at instanceof \DateTimeInterface ? $round->closes_at->format('M d, Y H:i') : $round->closes_at }}</strong></span>
                        <span>Total Ratings Matrix Entries: <strong>{{ $item['total_ratings'] }}</strong> (Individual rater entries sealed for anonymity)</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-warning">No evaluation rounds scheduled yet. Click "+ Schedule New Round" above.</div>
            </div>
        @endforelse
    </div>

@else
    <!-- ========================================== -->
    <!-- STUDENT DASHBOARD (SRS Section 3 & 9 F16)  -->
    <!-- ========================================== -->
    <div class="row g-4">
        @forelse($studentRounds as $data)
            @php
                $round = $data['round'];
                $team = $data['team'];
                $hasSubmitted = $data['has_submitted'];
                $isOpen = $data['is_open'];
            @endphp
            <div class="col-12">
                <div class="card shadow-sm border-{{ $hasSubmitted ? 'success' : ($isOpen ? 'primary' : 'secondary') }}">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="badge bg-{{ $round->status === 'open' ? 'success' : ($round->status === 'closed' ? 'dark' : 'warning') }} text-uppercase me-2">
                                Round {{ $round->round_number }} &bull; {{ $round->status }}
                            </span>
                            <strong class="fs-5">{{ $round->title }}</strong>
                        </div>
                        <div>
                            <span class="text-muted small">Course: <strong>{{ $round->course->code ?? 'CSE470' }}</strong> &mdash; {{ $round->course->title ?? '' }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                @if($team)
                                    <div class="mb-2">
                                        <span class="text-muted">Your Assigned Team:</span>
                                        <strong>{{ $team->name }}</strong>
                                        <span class="badge bg-light text-dark border ms-2">
                                            {{ $data['teammate_count'] }} teammate(s) to evaluate
                                        </span>
                                    </div>
                                @else
                                    <div class="alert alert-warning py-2 mb-2 small">
                                        ⚠️ You are not enrolled in a team for this course. Peer evaluations require team membership.
                                    </div>
                                @endif
                                <div class="small text-muted mb-2">
                                    <span>📅 <strong>Window:</strong> {{ $round->opens_at instanceof \DateTimeInterface ? $round->opens_at->format('M d, Y H:i') : date('M d, Y H:i', strtotime((string)$round->opens_at)) }} &rarr; {{ $round->closes_at instanceof \DateTimeInterface ? $round->closes_at->format('M d, Y H:i') : date('M d, Y H:i', strtotime((string)$round->closes_at)) }}</span>
                                </div>
                                <p class="mb-0 text-secondary small">
                                    Under SRS F16, evaluations are strictly anonymous and factored into individual contribution analytics (F18) after AI credibility checks (F17).
                                </p>
                            </div>
                            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                                @if($hasSubmitted)
                                    <div class="alert alert-success d-inline-block py-2 px-3 mb-0 text-start">
                                        <div class="fw-bold text-success mb-0">&check; Evaluation Completed &amp; Locked</div>
                                        <small class="text-muted">Ratings submitted anonymously (SRS: 1 submission only).</small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('evaluations.show', $round->id) }}" class="btn btn-sm btn-outline-secondary">
                                            View Receipt &rarr;
                                        </a>
                                    </div>
                                @elseif($isOpen && $team)
                                    <a href="{{ route('evaluations.create', $round->id) }}" class="btn btn-primary btn-lg shadow-sm">
                                        <strong>Complete Peer Evaluation &rarr;</strong>
                                    </a>
                                    <div class="mt-2 text-muted small">
                                        Action Required &bull; Confidential
                                    </div>
                                @elseif(!$team)
                                    <button class="btn btn-secondary" disabled>Team Required</button>
                                @else
                                    <button class="btn btn-secondary" disabled>Round Closed</button>
                                    <div class="mt-1 text-muted small">Submissions not accepted outside open window.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-warning">No peer evaluation rounds are currently scheduled.</div>
            </div>
        @endforelse
    </div>
@endif
@endsection
