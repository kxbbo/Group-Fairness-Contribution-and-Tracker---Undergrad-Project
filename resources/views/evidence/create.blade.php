@extends('layouts.app')

@section('title', 'Upload Evidence — ' . $task->title . ' | FairShare')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-1">{{ $task->team->name ?? 'Team #' . $task->team_id }}</p>
                <h1 class="h2 mb-0">Upload Evidence</h1>
                <p class="text-muted small mt-1">For task: <strong>{{ $task->title }}</strong></p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('evidence.index', $task->id) }}">&larr; Back to Task Vault</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <span class="badge bg-light text-dark me-2">Feature 11</span>
                <strong>Verifiable Evidence Submission</strong>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('evidence.store', $task->id) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="evidence_file" class="form-label fw-bold">Select File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('evidence_file') is-invalid @enderror" id="evidence_file" name="evidence_file" required>
                        <div class="form-text">
                            Maximum file size: 10 MB. A cryptographic SHA-256 hash will automatically be calculated and recorded to detect any tampering.
                        </div>
                        @error('evidence_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label fw-bold">Notes / Description (Optional)</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4" placeholder="Describe what this evidence represents (e.g. GitHub commit link, test output, design artifact, meeting minutes)...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a class="btn btn-outline-secondary" href="{{ route('evidence.index', $task->id) }}">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Submit Evidence &rarr;</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
