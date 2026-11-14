@extends('layouts.app')

@section('title', 'Evaluation Receipt — FairShare')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('evaluations.index') }}">Peer Evaluations</a></li>
            <li class="breadcrumb-item active" aria-current="page">Receipt</li>
        </ol>
    </nav>
    <h1 class="h2 mb-1">Evaluation Receipt &amp; Status</h1>
    <p class="text-muted mb-0">
        {{ $round->title }} &bull; {{ $round->course->code ?? 'CSE470' }}
    </p>
</div>

<div class="row g-4 justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">&check; Submission Recorded &amp; Locked</span>
                <span class="badge bg-light text-success">SRS F16 Compliant</span>
            </div>
            <div class="card-body p-4">
                <div class="text-center my-3">
                    <div class="display-3 text-success mb-2">&check;</div>
                    <h3 class="card-title fw-bold">Evaluation Successfully Recorded</h3>
                    <p class="text-muted">
                        Thank you, <strong>{{ $activeUser->name }}</strong>. Your peer ratings for this round have been securely saved and locked.
                    </p>
                </div>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded border">
                            <span class="text-muted d-block small">Evaluation Round</span>
                            <strong>{{ $round->title }}</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded border">
                            <span class="text-muted d-block small">Course</span>
                            <strong>{{ $round->course->code ?? 'CSE470' }} &mdash; {{ $round->course->title ?? '' }}</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded border">
                            <span class="text-muted d-block small">Submissions Count</span>
                            <strong>{{ $submissionCount }} Teammate Evaluation(s)</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded border">
                            <span class="text-muted d-block small">Submission Status</span>
                            <span class="badge bg-success">Locked &bull; Immutable</span>
                        </div>
                    </div>
                </div>

                <div class="alert alert-secondary mt-4 mb-0 small" role="alert">
                    <h6 class="alert-heading fw-bold mb-1">🔒 Anonymity Guarantee (F16 SRS Acceptance Criterion):</h6>
                    <p class="mb-0">
                        In accordance with the software specification, the identities of raters and their specific rating entries are never disclosed to students or teammates. All ratings are automatically ingested by the Fairness Calculation Engine (F18) and the AI Anomaly Credibility Checker (F17).
                    </p>
                </div>
            </div>
            <div class="card-footer bg-white text-center py-3">
                <a href="{{ route('evaluations.index') }}" class="btn btn-outline-primary me-2">
                    &larr; Back to Peer Evaluations
                </a>
                <a href="{{ route('home') }}" class="btn btn-secondary">
                    Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
