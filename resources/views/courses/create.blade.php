<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Course - FairShare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">FairShare</a>
        <a class="btn btn-sm btn-outline-light" href="{{ route('courses.index') }}">Back to courses</a>
    </div>
</nav>

<div class="container" style="max-width: 760px;">
    <h1 class="h3 mb-3">New Course</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('courses.store') }}" class="card card-body">
        @csrf

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Course code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="CSE470" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Course title</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Description (optional)</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Min team size</label>
                <input type="number" name="team_size_min" class="form-control" value="{{ old('team_size_min', 3) }}" min="1" max="20" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Max team size</label>
                <input type="number" name="team_size_max" class="form-control" value="{{ old('team_size_max', 5) }}" min="1" max="20" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Evaluation rounds</label>
                <input type="number" name="evaluation_rounds_count" class="form-control" value="{{ old('evaluation_rounds_count', 2) }}" min="1" max="10" required>
            </div>
        </div>

        <hr>
        <h2 class="h5">Fairness formula weights <span id="total" class="badge bg-danger">0</span> / 100</h2>
        <p class="text-muted small">The 5 weights must total exactly 100.</p>
        <div class="row g-3">
            <div class="col">
                <label class="form-label">Verified tasks</label>
                <input type="number" name="w_tasks" class="form-control w" value="{{ old('w_tasks', 30) }}" min="0" max="100" required>
            </div>
            <div class="col">
                <label class="form-label">Peer rating</label>
                <input type="number" name="w_peer_rating" class="form-control w" value="{{ old('w_peer_rating', 30) }}" min="0" max="100" required>
            </div>
            <div class="col">
                <label class="form-label">Hours</label>
                <input type="number" name="w_hours" class="form-control w" value="{{ old('w_hours', 15) }}" min="0" max="100" required>
            </div>
            <div class="col">
                <label class="form-label">Evidence</label>
                <input type="number" name="w_evidence" class="form-control w" value="{{ old('w_evidence', 15) }}" min="0" max="100" required>
            </div>
            <div class="col">
                <label class="form-label">Attendance</label>
                <input type="number" name="w_attendance" class="form-control w" value="{{ old('w_attendance', 10) }}" min="0" max="100" required>
            </div>
        </div>

        <hr>
        <h2 class="h5">Milestones (optional)</h2>
        <div class="row g-2 mb-2">
            <div class="col-8"><input type="text" name="ms_title[]" class="form-control" placeholder="Milestone title (e.g. Proposal)"></div>
            <div class="col-4"><input type="date" name="ms_deadline[]" class="form-control"></div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-8"><input type="text" name="ms_title[]" class="form-control" placeholder="Milestone title"></div>
            <div class="col-4"><input type="date" name="ms_deadline[]" class="form-control"></div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-8"><input type="text" name="ms_title[]" class="form-control" placeholder="Milestone title"></div>
            <div class="col-4"><input type="date" name="ms_deadline[]" class="form-control"></div>
        </div>

        <button type="submit" class="btn btn-primary">Create course</button>
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
</body>
</html>
