<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Courses - FairShare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">FairShare</a>
        <span class="navbar-text">F1 - Course and Fairness-Formula Setup</span>
    </div>
</nav>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Courses</h1>
        <a href="{{ route('courses.create') }}" class="btn btn-primary">+ New Course</a>
    </div>

    @if($courses->isEmpty())
        <div class="alert alert-info">No courses yet. Click "New Course" to create one.</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered bg-white align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Instructor</th>
                        <th>Team size</th>
                        <th>Rounds</th>
                        <th>Fairness weights (%)</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($courses as $course)
                    <tr>
                        <td>{{ $course->code }}</td>
                        <td>{{ $course->title }}</td>
                        <td>{{ optional($course->instructor)->name }}</td>
                        <td>{{ $course->team_size_min }} - {{ $course->team_size_max }}</td>
                        <td>{{ $course->evaluation_rounds_count }}</td>
                        <td>
                            Tasks {{ $course->formula_weights['tasks'] }} |
                            Peer {{ $course->formula_weights['peer_rating'] }} |
                            Hours {{ $course->formula_weights['hours'] }} |
                            Evidence {{ $course->formula_weights['evidence'] }} |
                            Attendance {{ $course->formula_weights['attendance'] }}
                        </td>
                        <td>
                            @if($course->is_active)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td><a href="{{ route('courses.edit', $course->id) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</body>
</html>
