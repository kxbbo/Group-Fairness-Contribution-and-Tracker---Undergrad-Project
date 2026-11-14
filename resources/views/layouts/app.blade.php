<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'FairShare: Group Project Contribution & Fairness Tracker')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/fairshare.css') }}" rel="stylesheet">
    <style>
        .badge-weight-1 { background-color: #A7B58B; color: #2B2118; }
        .badge-weight-2 { background-color: #88A060; color: #fff; }
        .badge-weight-3 { background-color: #D29A2E; color: #2B2118; }
        .badge-weight-4 { background-color: #C06830; color: #fff; }
        .badge-weight-5 { background-color: #8E4150; color: #fff; }
        .kanban-column {
            background-color: var(--paper, #F8F3E6);
            border: 1px solid var(--line, rgba(43,33,24,.18));
            border-radius: 12px;
            padding: 1rem;
            min-height: 520px;
        }
        .kanban-card {
            background: #fff;
            border: 1px solid var(--line, rgba(43,33,24,.18));
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.85rem;
            box-shadow: 0 2px 4px rgba(43,33,24,0.06);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(43,33,24,0.12);
        }
    </style>
    @stack('styles')
</head>
<body>
<a class="fs-skip" href="#main">Skip to content</a>

<header class="fs-nav">
    <nav class="container navbar navbar-expand-lg p-0" aria-label="Main">
        <a class="fs-brand" href="{{ route('home') }}">
            <span class="fs-mark" aria-hidden="true"></span>FairShare
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#fsMenu" aria-controls="fsMenu" aria-expanded="false" aria-label="Toggle menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="fsMenu">
            <ul class="navbar-nav me-3">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('tasks.teams') }}"><strong>F6: Task Board</strong></a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('evidence.tasks') }}"><strong>F11: Evidence Vault</strong></a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('evaluations.index') }}"><strong>F16: Peer Evaluations</strong></a></li>
            </ul>

            <!-- Active User Switcher for Testing Rules -->
            @php
                try {
                    $allUsers = \App\Models\User::orderBy('name')->get();
                } catch (\Throwable $e) {
                    $allUsers = collect([]);
                }
                $currentUserId = session('active_user_id', 1);
                $currentUser = $allUsers->firstWhere('id', $currentUserId) ?? $allUsers->first();
            @endphp
            <div class="d-flex align-items-center">
                <form action="{{ route('user.switch') }}" method="POST" class="d-flex align-items-center gap-1 me-2">
                    @csrf
                    <span class="badge bg-dark d-none d-md-inline" title="Current simulated active user for testing F6 rules">
                        Actor:
                    </span>
                    <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 170px;" title="Switch actor to test assignee vs reviewer rules">
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}" {{ $currentUser && $currentUser->id === $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ ucfirst($u->role) }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </nav>
</header>

<main id="main" class="py-4">
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success:</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Rule Enforced:</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </div>
</main>

<footer class="fs-footer">
    <div class="container d-flex flex-wrap justify-content-between align-items-center">
        <span><strong>FairShare</strong> &mdash; Group Project Contribution and Fairness Tracker (CSE470)</span>
        <span>Built with <strong>Laravel 11 MVC</strong> &amp; Bootstrap 5</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/main.js') }}"></script>
@stack('scripts')
</body>
</html>
