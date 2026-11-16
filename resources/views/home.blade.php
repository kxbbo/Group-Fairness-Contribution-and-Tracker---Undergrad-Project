@extends('layouts.app')

@section('title', 'FairShare: Fair Credit for Group Projects')

@section('content')
<section class="fs-hero py-4">
    <div class="row align-items-center g-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold">Every teammate’s share, on the record.</h1>
            <p class="fs-lead mt-3">
                FairShare shows instructors who did what in university group projects. Work is planned with difficulty weights, backed by verifiable evidence, peer-reviewed, and turned into an explainable fairness score.
            </p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a class="btn btn-lg btn-dark" href="{{ route('courses.index') }}">
                    <strong>F1: Courses &amp; Formula &rarr;</strong>
                </a>
                <a class="btn btn-lg btn-success" href="{{ route('tasks.teams') }}">
                    <strong>Open F6: Task Board &rarr;</strong>
                </a>
                <a class="btn btn-lg btn-outline-dark" href="{{ route('evidence.tasks') }}">
                    <strong>F11: Evidence Vault</strong>
                </a>
                <a class="btn btn-lg btn-primary" href="{{ route('evaluations.index') }}">
                    <strong>F16: Peer Evaluations &rarr;</strong>
                </a>
            </div>
        </div>
        <div class="col-lg-6">
            <article class="fs-score">
                <header class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <p class="fs-score-title mb-0"><strong>Fairness Score Breakdown</strong><br><span class="text-muted">Sample Member (Course: CSE470)</span></p>
                    </div>
                    <div class="fs-score-num text-success fw-bold display-5">79<small class="fs-6 text-muted">/100</small></div>
                </header>
                <div class="progress mb-3" style="height: 18px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 24.6%" title="Verified tasks (24.6%)"></div>
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 22.8%" title="Peer rating (22.8%)"></div>
                    <div class="progress-bar bg-info" role="progressbar" style="width: 10.5%" title="Approved hours (10.5%)"></div>
                    <div class="progress-bar bg-danger" role="progressbar" style="width: 12.0%" title="Evidence (12.0%)"></div>
                    <div class="progress-bar bg-secondary" role="progressbar" style="width: 9.0%" title="Attendance (9.0%)"></div>
                </div>
                <ul class="fs-rows">
                    <li><i class="c-tasks"></i><span>Verified tasks (Weight 1-5)</span><span class="calc">82 &times; 30%</span><b>24.6</b></li>
                    <li><i class="c-peer"></i><span>Peer evaluation</span><span class="calc">76 &times; 30%</span><b>22.8</b></li>
                    <li><i class="c-hours"></i><span>Approved hours</span><span class="calc">70 &times; 15%</span><b>10.5</b></li>
                    <li><i class="c-evidence"></i><span>Evidence vault</span><span class="calc">80 &times; 15%</span><b>12.0</b></li>
                    <li><i class="c-attend"></i><span>Meeting attendance</span><span class="calc">90 &times; 10%</span><b>9.0</b></li>
                </ul>
                <p class="fs-note mb-0 text-muted small">Configured via F1 formula weights (must sum to 100%).</p>
            </article>
        </div>
    </div>
</section>

<section class="fs-section my-5 pt-4 border-top">
    <h2 class="mb-4">Implemented Features in this Workspace</h2>
    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-dark">
                <div class="card-header bg-dark text-white">
                    <span class="badge bg-light text-dark me-2">Sprint 1</span>
                    <strong>F1: Course Setup (M1)</strong>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Instructors configure each course and its fairness formula:
                    </p>
                    <ul class="small">
                        <li>Course code, title, team size limits</li>
                        <li>Evaluation rounds and milestones</li>
                        <li><strong>Rule:</strong> the 5 formula weights must total 100.</li>
                        <li><strong>Rule:</strong> weight changes are audit-logged.</li>
                    </ul>
                    <a href="{{ route('courses.index') }}" class="btn btn-outline-dark btn-sm">Open Courses &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <span class="badge bg-light text-dark me-2">Sprint 1</span>
                    <strong>F6: Weighted Tasks (M2)</strong>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        A full 4-column Kanban task board (<strong>To Do</strong>, <strong>In Progress</strong>, <strong>In Review</strong>, <strong>Done</strong>):
                    </p>
                    <ul class="small">
                        <li>Difficulty weight between <strong>1 and 5</strong></li>
                        <li>Assignee and due date</li>
                        <li><strong>Rule:</strong> Only assignee can start task.</li>
                        <li><strong>Rule:</strong> Teammate peer verification for Done.</li>
                    </ul>
                    <a href="{{ route('tasks.teams') }}" class="btn btn-outline-success btn-sm">View Task Boards &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <span class="badge bg-light text-dark me-2">Sprint 1</span>
                    <strong>F11: Evidence Vault (M3)</strong>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Verifiable contribution deliverables linked directly to tasks:
                    </p>
                    <ul class="small">
                        <li>Upload deliverables associated with tasks</li>
                        <li>Automatic SHA-256 cryptographic hashing</li>
                        <li>Incremental versioning (v1, v2, ...)</li>
                        <li>Secure download &amp; audit tracking</li>
                    </ul>
                    <a href="{{ route('evidence.tasks') }}" class="btn btn-outline-primary btn-sm">Open Evidence Vault &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 shadow-sm border-info">
                <div class="card-header bg-info text-dark">
                    <span class="badge bg-dark text-white me-2">Sprint 1</span>
                    <strong>F16: Peer Evaluations (M4)</strong>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Anonymous, tamper-evident peer evaluation rounds across 4 dimensions:
                    </p>
                    <ul class="small">
                        <li>Rate teammates on <strong>Effort, Reliability, Communication, Quality</strong> (1–5)</li>
                        <li><strong>Rule:</strong> Strict anonymity &mdash; identities sealed.</li>
                        <li><strong>Rule:</strong> Exactly one submission per round (locked).</li>
                        <li><strong>Rule:</strong> Self-rating strictly prohibited.</li>
                    </ul>
                    <a href="{{ route('evaluations.index') }}" class="btn btn-outline-primary btn-sm">Start Evaluations &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
