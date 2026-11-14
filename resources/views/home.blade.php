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
                <a class="btn btn-lg btn-success" href="{{ route('tasks.teams') }}">
                    <strong>Open F6: Task Board &rarr;</strong>
                </a>
                <a class="btn btn-lg btn-outline-dark" href="{{ route('evidence.tasks') }}">
                    <strong>F11: Evidence Vault</strong>
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
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <span class="badge bg-light text-dark me-2">Sprint 1</span>
                    <strong>F6: Weighted Task Management</strong>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        A full 4-column Kanban task board (<strong>To Do</strong>, <strong>In Progress</strong>, <strong>In Review</strong>, <strong>Done</strong>) where each task has:
                    </p>
                    <ul>
                        <li>Difficulty weight between <strong>1 and 5</strong></li>
                        <li>Assignee and due date</li>
                        <li><strong>Rule 1:</strong> Only the assignee can start a task (To Do &rarr; In Progress).</li>
                        <li><strong>Rule 2:</strong> A member cannot move a task to Done directly (must go through In Review).</li>
                        <li><strong>Rule 3:</strong> Only a non-assignee teammate can verify it (In Review &rarr; Done).</li>
                    </ul>
                    <a href="{{ route('tasks.teams') }}" class="btn btn-outline-success">View Task Boards &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <span class="badge bg-light text-dark me-2">Sprint 1</span>
                    <strong>F11: Evidence Vault</strong>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Verifiable contribution evidence repository linked directly to tasks:
                    </p>
                    <ul>
                        <li>Upload files and notes associated with specific tasks</li>
                        <li>Automatic SHA-256 cryptographic hashing to detect altered files</li>
                        <li>Automatic version numbering for repeated uploads</li>
                        <li>Secure download and deletion workflow</li>
                    </ul>
                    <a href="{{ route('evidence.tasks') }}" class="btn btn-outline-primary">Open Evidence Vault &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
