@extends('layouts.app')

@section('title', 'Courses - F1 | FairShare')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">F1: Course and Fairness-Formula Setup</h1>
            <p class="text-muted mb-0">Create courses, set team size limits, evaluation rounds and the 5 fairness weights (must total 100).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('home') }}" class="btn btn-outline-secondary">&larr; Back to Home</a>
            <a href="{{ route('courses.create') }}" class="btn btn-primary">+ New Course</a>
        </div>
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
@endsection
