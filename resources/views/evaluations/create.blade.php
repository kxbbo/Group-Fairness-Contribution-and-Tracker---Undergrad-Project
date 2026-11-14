@extends('layouts.app')

@section('title', 'Submit Peer Evaluation — FairShare')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('evaluations.index') }}">Peer Evaluations</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $round->title }}</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2 mb-1">{{ $round->title }}</h1>
            <p class="text-muted mb-0">
                Team: <strong>{{ $team->name }}</strong> &bull; Course: <strong>{{ $round->course->code }}</strong>
            </p>
        </div>
        <div class="text-end">
            <span class="badge bg-success py-2 px-3">Round Open</span>
        </div>
    </div>
</div>

<!-- SRS Rule Reminder Banner -->
<div class="alert alert-info border-info d-flex align-items-center mb-4" role="alert">
    <div class="fs-2 me-3">🛡️</div>
    <div>
        <strong>Confidential Peer Review (SRS F16):</strong>
        Your answers are 100% anonymous to your teammates. They will only see composite, anonymized scores.
        <strong>Note: Once submitted, your evaluations cannot be modified.</strong>
    </div>
</div>

<form action="{{ route('evaluations.store', $round->id) }}" method="POST" id="peerReviewForm">
    @csrf

    <div class="row g-4">
        @foreach($teammates as $teammate)
            <div class="col-12">
                <div class="card shadow-sm border-secondary">
                    <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                {{ strtoupper(substr($teammate->name, 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="h5 mb-0 fw-bold">{{ $teammate->name }}</h4>
                                <span class="text-muted small">
                                    Role: <strong>{{ $teammate->role_title ?? 'Team Member' }}</strong> &bull; {{ $teammate->email }}
                                </span>
                            </div>
                        </div>
                        <span class="badge bg-secondary">Teammate #{{ $loop->iteration }}</span>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-4">
                            <!-- Criterion 1: Effort -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1">
                                    1. Effort &amp; Initiative (1 &ndash; 5) <span class="text-danger">*</span>
                                </label>
                                <p class="text-muted small mb-2">Did they take ownership and actively invest time into sprint deliverables?</p>
                                <div class="btn-group w-100" role="group" aria-label="Effort rating for {{ $teammate->name }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        <input type="radio" class="btn-check" name="ratings[{{ $teammate->id }}][effort]" id="effort_{{ $teammate->id }}_{{ $i }}" value="{{ $i }}" required {{ $i === 3 ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="effort_{{ $teammate->id }}_{{ $i }}" title="{{ $i === 1 ? '1 - Minimal' : ($i === 5 ? '5 - Outstanding' : $i) }}">
                                            <strong>{{ $i }}</strong>
                                            <span class="d-none d-lg-block small text-muted">
                                                @if($i === 1) Poor @elseif($i === 2) Low @elseif($i === 3) Fair @elseif($i === 4) High @else Great @endif
                                            </span>
                                        </label>
                                    @endfor
                                </div>
                            </div>

                            <!-- Criterion 2: Reliability -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1">
                                    2. Reliability &amp; Deadlines (1 &ndash; 5) <span class="text-danger">*</span>
                                </label>
                                <p class="text-muted small mb-2">Did they meet milestones on time and follow through on their commitments?</p>
                                <div class="btn-group w-100" role="group" aria-label="Reliability rating for {{ $teammate->name }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        <input type="radio" class="btn-check" name="ratings[{{ $teammate->id }}][reliability]" id="rel_{{ $teammate->id }}_{{ $i }}" value="{{ $i }}" required {{ $i === 3 ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="rel_{{ $teammate->id }}_{{ $i }}" title="{{ $i === 1 ? '1 - Unreliable' : ($i === 5 ? '5 - Highly Dependable' : $i) }}">
                                            <strong>{{ $i }}</strong>
                                            <span class="d-none d-lg-block small text-muted">
                                                @if($i === 1) Poor @elseif($i === 2) Low @elseif($i === 3) Fair @elseif($i === 4) High @else Great @endif
                                            </span>
                                        </label>
                                    @endfor
                                </div>
                            </div>

                            <!-- Criterion 3: Communication -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1">
                                    3. Communication &amp; Teamwork (1 &ndash; 5) <span class="text-danger">*</span>
                                </label>
                                <p class="text-muted small mb-2">Were they responsive, collaborative, respectful, and transparent about blockers?</p>
                                <div class="btn-group w-100" role="group" aria-label="Communication rating for {{ $teammate->name }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        <input type="radio" class="btn-check" name="ratings[{{ $teammate->id }}][communication]" id="comm_{{ $teammate->id }}_{{ $i }}" value="{{ $i }}" required {{ $i === 3 ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="comm_{{ $teammate->id }}_{{ $i }}" title="{{ $i === 1 ? '1 - Unresponsive' : ($i === 5 ? '5 - Exemplary' : $i) }}">
                                            <strong>{{ $i }}</strong>
                                            <span class="d-none d-lg-block small text-muted">
                                                @if($i === 1) Poor @elseif($i === 2) Low @elseif($i === 3) Fair @elseif($i === 4) High @else Great @endif
                                            </span>
                                        </label>
                                    @endfor
                                </div>
                            </div>

                            <!-- Criterion 4: Quality of Work -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1">
                                    4. Quality of Deliverables (1 &ndash; 5) <span class="text-danger">*</span>
                                </label>
                                <p class="text-muted small mb-2">Was their work accurate, tested, well-documented, and aligned with requirements?</p>
                                <div class="btn-group w-100" role="group" aria-label="Quality rating for {{ $teammate->name }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        <input type="radio" class="btn-check" name="ratings[{{ $teammate->id }}][quality]" id="qual_{{ $teammate->id }}_{{ $i }}" value="{{ $i }}" required {{ $i === 3 ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="qual_{{ $teammate->id }}_{{ $i }}" title="{{ $i === 1 ? '1 - Substandard' : ($i === 5 ? '5 - Flawless' : $i) }}">
                                            <strong>{{ $i }}</strong>
                                            <span class="d-none d-lg-block small text-muted">
                                                @if($i === 1) Poor @elseif($i === 2) Low @elseif($i === 3) Fair @elseif($i === 4) High @else Great @endif
                                            </span>
                                        </label>
                                    @endfor
                                </div>
                            </div>

                            <!-- Optional Constructive Comments -->
                            <div class="col-12 mt-3">
                                <label for="comment_{{ $teammate->id }}" class="form-label fw-semibold">
                                    Confidential Feedback &amp; Notes for {{ $teammate->name }} <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <textarea class="form-control" id="comment_{{ $teammate->id }}" name="ratings[{{ $teammate->id }}][comments]" rows="2" placeholder="Constructive observations regarding strengths or areas for improvement..."></textarea>
                                <div class="form-text small">
                                    This feedback is processed anonymously to assist the instructor with fairness evaluations and team health insights.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Submission Action -->
    <div class="card mt-4 border-primary bg-light">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="card-title mb-1">Ready to Submit?</h5>
                <p class="card-text text-muted small mb-0">
                    By submitting, you certify that these evaluations reflect fair, honest assessments under academic integrity guidelines.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('evaluations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-success btn-lg px-4" onclick="return confirm('Are you sure you want to submit? Under SRS F16 rules, evaluations are strictly permanent and cannot be modified after submission.');">
                    <strong>Submit &amp; Lock Evaluation &check;</strong>
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
