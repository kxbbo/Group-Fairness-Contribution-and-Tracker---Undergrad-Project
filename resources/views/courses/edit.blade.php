@extends('layouts.app')

@section('title', 'Edit Course - F1 | FairShare')

@section('content')
<div style="max-width: 760px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h2 mb-1">Edit Course {{ $course->code }}</h1>
            <p class="text-muted mb-0">F1: Course and Fairness-Formula Setup</p>
        </div>
        <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary">&larr; Back to Courses</a>
    </div>

    <form method="POST" action="{{ route('courses.update', $course->id) }}" class="card card-body">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Course code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $course->code) }}" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Course title</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $course->title) }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Description (optional)</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $course->description) }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Min team size</label>
                <input type="number" name="team_size_min" class="form-control" value="{{ old('team_size_min', $course->team_size_min) }}" min="1" max="20" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Max team size</label>
                <input type="number" name="team_size_max" class="form-control" value="{{ old('team_size_max', $course->team_size_max) }}" min="1" max="20" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Evaluation rounds</label>
                <input type="number" name="evaluation_rounds_count" class="form-control" value="{{ old('evaluation_rounds_count', $course->evaluation_rounds_count) }}" min="1" max="10" required>
            </div>
        </div>

        <hr>
        <h2 class="h5">Fairness formula weights <span id="total" class="badge bg-danger">0</span> / 100</h2>
        <p class="text-muted small">The 5 weights must total exactly 100. Any change is saved in the audit log.</p>
        <div class="row g-3">
            <div class="col">
                <label class="form-label">Verified tasks</label>
                <input type="number" name="w_tasks" class="form-control w" value="{{ old('w_tasks', $weights['tasks']) }}" min="0" max="100" required>
            </div>
            <div class="col">
                <label class="form-label">Peer rating</label>
                <input type="number" name="w_peer_rating" class="form-control w" value="{{ old('w_peer_rating', $weights['peer_rating']) }}" min="0" max="100" required>
            </div>
            <div class="col">
                <label class="form-label">Hours</label>
                <input type="number" name="w_hours" class="form-control w" value="{{ old('w_hours', $weights['hours']) }}" min="0" max="100" required>
            </div>
            <div class="col">
                <label class="form-label">Evidence</label>
                <input type="number" name="w_evidence" class="form-control w" value="{{ old('w_evidence', $weights['evidence']) }}" min="0" max="100" required>
            </div>
            <div class="col">
                <label class="form-label">Attendance</label>
                <input type="number" name="w_attendance" class="form-control w" value="{{ old('w_attendance', $weights['attendance']) }}" min="0" max="100" required>
            </div>
        </div>

        <div class="form-check mt-3 mb-3">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @if($course->is_active) checked @endif>
            <label class="form-check-label" for="is_active">Course is active</label>
        </div>

        <button type="submit" class="btn btn-primary">Save changes</button>
    </form>
</div>

<script>
function calcTotal() {
    var t = 0;
    document.querySelectorAll('.w').forEach(function (e) { t += parseInt(e.value || 0, 10) || 0; });
    var el = document.getElementById('total');
    el.textContent = t;
    el.className = (t === 100) ? 'badge bg-success' : 'badge bg-danger';
}
document.querySelectorAll('.w').forEach(function (e) { e.addEventListener('input', calcTotal); });
calcTotal();
</script>
@endsection
