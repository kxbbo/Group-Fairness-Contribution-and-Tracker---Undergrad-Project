<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * List all teams to select a task board.
     */
    public function teams(): View
    {
        $teams = Team::with(['course', 'leader', 'members', 'tasks'])->get();

        return view('tasks.teams', compact('teams'));
    }

    /**
     * Display the Kanban task board for a specific team.
     * Columns: To Do, In Progress, In Review, Done.
     */
    public function index(int $teamId): View
    {
        $team = Team::with(['course', 'leader', 'members'])->findOrFail($teamId);

        $tasks = Task::with(['assignee', 'verifier', 'evidenceFiles'])
            ->where('team_id', $teamId)
            ->orderBy('due_date')
            ->orderByDesc('difficulty_weight')
            ->get();

        $columns = [
            Task::STATUS_TO_DO => $tasks->where('status', Task::STATUS_TO_DO),
            Task::STATUS_IN_PROGRESS => $tasks->where('status', Task::STATUS_IN_PROGRESS),
            Task::STATUS_IN_REVIEW => $tasks->where('status', Task::STATUS_IN_REVIEW),
            Task::STATUS_DONE => $tasks->where('status', Task::STATUS_DONE),
        ];

        // Ensure an active simulated user is set in session for role-rule testing
        if (!session()->has('active_user_id')) {
            $defaultUser = $team->leader ?? $team->members->first() ?? User::first();
            if ($defaultUser) {
                session(['active_user_id' => $defaultUser->id, 'active_user_name' => $defaultUser->name]);
            }
        }

        $activeUserId = session('active_user_id');
        $activeUser = $activeUserId ? User::find($activeUserId) : null;

        // Team members for assignment and switching
        $members = $team->members;
        if ($team->leader && !$members->contains('id', $team->leader->id)) {
            $members->push($team->leader);
        }

        // Summary metrics
        $totalWeight = $tasks->sum('difficulty_weight');
        $completedWeight = $tasks->where('status', Task::STATUS_DONE)->sum('difficulty_weight');
        $progressPercent = $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100, 1) : 0;

        return view('tasks.board', compact(
            'team',
            'columns',
            'tasks',
            'members',
            'activeUser',
            'totalWeight',
            'completedWeight',
            'progressPercent'
        ));
    }

    /**
     * Show form to create a new task for a team.
     */
    public function create(int $teamId): View
    {
        $team = Team::with(['members', 'leader'])->findOrFail($teamId);

        $members = $team->members;
        if ($team->leader && !$members->contains('id', $team->leader->id)) {
            $members->push($team->leader);
        }

        return view('tasks.create', compact('team', 'members'));
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request, int $teamId): RedirectResponse
    {
        $team = Team::findOrFail($teamId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'difficulty_weight' => ['required', 'integer', 'between:1,5'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = Task::create([
            'team_id' => $team->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'difficulty_weight' => (int) $validated['difficulty_weight'],
            'assignee_id' => !empty($validated['assignee_id']) ? (int) $validated['assignee_id'] : null,
            'due_date' => $validated['due_date'] ?? null,
            'status' => Task::STATUS_TO_DO,
        ]);

        return redirect()->route('tasks.index', $team->id)
            ->with('success', "Task \"{$task->title}\" created successfully with difficulty weight {$task->difficulty_weight}.");
    }

    /**
     * Show form to edit an existing task.
     */
    public function edit(int $id): View
    {
        $task = Task::with('team.members', 'team.leader')->findOrFail($id);
        $team = $task->team;

        $members = $team->members;
        if ($team->leader && !$members->contains('id', $team->leader->id)) {
            $members->push($team->leader);
        }

        return view('tasks.edit', compact('task', 'team', 'members'));
    }

    /**
     * Update an existing task.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'difficulty_weight' => ['required', 'integer', 'between:1,5'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'difficulty_weight' => (int) $validated['difficulty_weight'],
            'assignee_id' => !empty($validated['assignee_id']) ? (int) $validated['assignee_id'] : null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        return redirect()->route('tasks.index', $task->team_id)
            ->with('success', "Task \"{$task->title}\" updated successfully.");
    }

    /**
     * Update task status enforcing SRS F6 transition rules:
     * - Only the assignee can start a task (To Do -> In Progress)
     * - Only a non-assignee can verify it (In Review -> Done)
     * - A member cannot move a task to Done directly (must go through In Review)
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $task = Task::with('assignee', 'team')->findOrFail($id);

        $request->validate([
            'status' => ['required', 'string', 'in:to_do,in_progress,in_review,done'],
            'return_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $targetStatus = $request->input('status');
        $activeUserId = session('active_user_id');

        // Check rule enforcement
        $transitionCheck = $task->canTransitionTo($targetStatus, $activeUserId);
        if (!$transitionCheck['allowed']) {
            return redirect()->route('tasks.index', $task->team_id)
                ->with('error', $transitionCheck['message']);
        }

        $updates = ['status' => $targetStatus];

        // If moving to Done: recorded as verified by the active reviewer
        if ($targetStatus === Task::STATUS_DONE) {
            $updates['verified_by'] = $activeUserId;
            $updates['verified_at'] = now();
            $updates['return_reason'] = null;
        }

        // If returned to In Progress from In Review: capture reason if provided
        if ($targetStatus === Task::STATUS_IN_PROGRESS && $task->status === Task::STATUS_IN_REVIEW) {
            $updates['return_reason'] = $request->input('return_reason') ?: 'Task returned by reviewer for revisions.';
            $updates['verified_by'] = null;
            $updates['verified_at'] = null;
        }

        // If reset to To Do: clear verifications
        if ($targetStatus === Task::STATUS_TO_DO) {
            $updates['verified_by'] = null;
            $updates['verified_at'] = null;
        }

        $task->update($updates);

        $statusLabel = Task::STATUSES[$targetStatus];
        return redirect()->route('tasks.index', $task->team_id)
            ->with('success', "Task status updated to \"{$statusLabel}\".");
    }

    /**
     * Delete a task.
     */
    public function destroy(int $id): RedirectResponse
    {
        $task = Task::findOrFail($id);
        $teamId = $task->team_id;
        $title = $task->title;

        $task->delete();

        return redirect()->route('tasks.index', $teamId)
            ->with('success', "Task \"{$title}\" deleted successfully.");
    }

    /**
     * Switch the active simulated user to test role-based rules.
     */
    public function switchUser(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($request->input('user_id'));
        session(['active_user_id' => $user->id, 'active_user_name' => $user->name]);

        return back()->with('info', "Active user switched to: {$user->name} ({$user->role}).");
    }
}
