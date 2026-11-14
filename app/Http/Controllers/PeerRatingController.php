<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\PeerRating;
use App\Models\PeerRound;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeerRatingController extends Controller
{
    /**
     * Find active user's assigned team for a given course.
     * Returns null if the user is not enrolled in any team for that course.
     */
    private function findUserTeamForCourse(int $courseId, int $userId): ?Team
    {
        $teams = Team::with(['course', 'leader', 'members'])
            ->where('course_id', '=', $courseId)
            ->get();

        foreach ($teams as $t) {
            if ((int) $t->leader_id === $userId) {
                return $t;
            }
            if ($t->members) {
                foreach ($t->members as $m) {
                    if ((int) $m->id === $userId) {
                        return $t;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Display a listing of peer evaluation rounds.
     * Differentiates between Student view (evaluation submission)
     * and Instructor view (round management & team completion tracking) per SRS Section 3.
     */
    public function index(Request $request): View
    {
        // 1. Identify active simulated user (defaults to student Alex Johnson if none set)
        if (!session()->has('active_user_id')) {
            $defaultUser = User::where('role', 'student')->first() ?? User::first();
            if ($defaultUser) {
                session(['active_user_id' => $defaultUser->id, 'active_user_name' => $defaultUser->name]);
            }
        }

        $activeUserId = (int) session('active_user_id');
        $activeUser = User::find($activeUserId) ?? User::first();
        $isInstructor = in_array($activeUser->role ?? '', ['instructor', 'admin'], true);

        // 2. Fetch all rounds with course details
        $rounds = PeerRound::with('course')->orderBy('round_number')->get();
        $courses = Course::all();

        // 3. For Instructor/Admin: Build course-level and team-level completion metrics
        $instructorRounds = [];
        if ($isInstructor) {
            foreach ($rounds as $round) {
                $teams = Team::with(['leader', 'members'])
                    ->where('course_id', '=', $round->course_id)
                    ->get();

                $teamMetrics = [];
                $totalExpectedStudents = 0;
                $totalCompletedStudents = 0;

                foreach ($teams as $team) {
                    $allMembers = $team->members;
                    if ($team->leader && !$allMembers->contains('id', $team->leader->id)) {
                        $allMembers->push($team->leader);
                    }

                    $memberCount = $allMembers->count();
                    $totalExpectedStudents += $memberCount;

                    // Count how many team members have submitted for this round
                    $submittedCount = 0;
                    foreach ($allMembers as $member) {
                        if ($round->hasUserSubmitted((int) $member->id)) {
                            $submittedCount++;
                        }
                    }
                    $totalCompletedStudents += $submittedCount;

                    $pct = $memberCount > 0 ? round(($submittedCount / $memberCount) * 100, 1) : 0;
                    $teamMetrics[] = [
                        'team' => $team,
                        'member_count' => $memberCount,
                        'submitted_count' => $submittedCount,
                        'percentage' => $pct,
                        'is_fully_submitted' => ($memberCount > 0 && $submittedCount >= $memberCount),
                    ];
                }

                $allRatings = PeerRating::where('round_id', '=', $round->id)->get();
                $instructorRounds[] = [
                    'round' => $round,
                    'team_metrics' => $teamMetrics,
                    'total_ratings' => $allRatings->count(),
                    'total_expected' => $totalExpectedStudents,
                    'total_completed' => $totalCompletedStudents,
                    'overall_pct' => $totalExpectedStudents > 0 ? round(($totalCompletedStudents / $totalExpectedStudents) * 100, 1) : 0,
                ];
            }
        }

        // 4. For Students: Build personal round status
        $studentRounds = [];
        if (!$isInstructor) {
            foreach ($rounds as $round) {
                $team = $this->findUserTeamForCourse((int) $round->course_id, $activeUserId);
                $hasSubmitted = $round->hasUserSubmitted($activeUserId);

                $teammateCount = 0;
                if ($team) {
                    $allMembers = $team->members;
                    if ($team->leader && !$allMembers->contains('id', $team->leader->id)) {
                        $allMembers->push($team->leader);
                    }
                    $teammateCount = $allMembers->filter(fn($m) => (int) $m->id !== $activeUserId)->count();
                }

                $studentRounds[] = [
                    'round' => $round,
                    'team' => $team,
                    'has_submitted' => $hasSubmitted,
                    'is_open' => $round->status === PeerRound::STATUS_OPEN,
                    'teammate_count' => $teammateCount,
                ];
            }
        }

        return view('evaluations.index', compact(
            'activeUser',
            'isInstructor',
            'instructorRounds',
            'studentRounds',
            'courses'
        ));
    }

    /**
     * Show the peer evaluation form for a specific round.
     * Enforces SRS F16 Acceptance Criteria:
     * - Only student team members can evaluate peers.
     * - Round must be currently open.
     * - One submission per rater per round (cannot re-rate).
     * - Cannot rate oneself.
     */
    public function create(int $roundId): View|RedirectResponse
    {
        $round = PeerRound::with('course')->findOrFail($roundId);
        $activeUserId = (int) session('active_user_id', 3);
        $activeUser = User::findOrFail($activeUserId);

        // Rule: Instructors manage rounds; only students submit peer ratings (SRS Section 3)
        if (in_array($activeUser->role, ['instructor', 'admin'], true)) {
            return redirect()->route('evaluations.index')
                ->with('info', "As an {$activeUser->role}, you manage round schedules and monitor team completion. Please switch to a student actor to test submitting a peer evaluation.");
        }

        // SRS Rule 1: Round must be currently open
        if ($round->status !== PeerRound::STATUS_OPEN) {
            $statusLabel = PeerRound::STATUSES[$round->status] ?? ucfirst($round->status);
            return redirect()->route('evaluations.index')
                ->with('error', "Peer evaluation round \"{$round->title}\" is currently {$statusLabel} and is not accepting submissions.");
        }

        // SRS Rule 2: One submission per rater per round (ratings cannot be edited after submission)
        if ($round->hasUserSubmitted($activeUserId)) {
            return redirect()->route('evaluations.show', $round->id)
                ->with('info', 'You have already submitted your peer evaluations for this round. Under SRS F16 rules, submissions are strictly final and cannot be edited.');
        }

        // Find user's assigned team for this course
        $team = $this->findUserTeamForCourse((int) $round->course_id, $activeUserId);

        if (!$team) {
            return redirect()->route('evaluations.index')
                ->with('error', "You are not enrolled in an active team for {$round->course->code}. Under SRS rules, you can only evaluate teammates in your assigned team.");
        }

        // SRS Rule 3: Cannot rate oneself — gather all team members strictly excluding active user
        $allMembers = $team->members;
        if ($team->leader && !$allMembers->contains('id', $team->leader->id)) {
            $allMembers->push($team->leader);
        }

        $teammates = $allMembers->filter(function ($member) use ($activeUserId) {
            return (int) $member->id !== $activeUserId;
        })->values();

        if ($teammates->isEmpty()) {
            return redirect()->route('evaluations.index')
                ->with('error', 'There are no other teammates in your team to evaluate.');
        }

        return view('evaluations.create', compact('round', 'team', 'teammates', 'activeUser'));
    }

    /**
     * Store submitted peer ratings.
     * Enforces SRS Acceptance Criteria:
     * 1. One submission per rater per round.
     * 2. Cannot rate oneself.
     * 3. Ratings cannot be edited after submission.
     * 4. Scores must be between 1 and 5 across all 4 criteria.
     */
    public function store(Request $request, int $roundId): RedirectResponse
    {
        $round = PeerRound::findOrFail($roundId);
        $activeUserId = (int) session('active_user_id', 3);
        $activeUser = User::findOrFail($activeUserId);

        if (in_array($activeUser->role, ['instructor', 'admin'], true)) {
            return redirect()->route('evaluations.index')
                ->with('error', 'Instructors cannot submit peer evaluations.');
        }

        // Verification 1: Round must be open
        if ($round->status !== PeerRound::STATUS_OPEN) {
            return redirect()->route('evaluations.index')
                ->with('error', 'This evaluation round is not currently open for submissions.');
        }

        // Verification 2: Check if already submitted
        if ($round->hasUserSubmitted($activeUserId)) {
            return redirect()->route('evaluations.show', $round->id)
                ->with('error', 'You have already submitted your peer evaluations for this round.');
        }

        // Verify team enrollment
        $team = $this->findUserTeamForCourse((int) $round->course_id, $activeUserId);
        if (!$team) {
            return redirect()->route('evaluations.index')
                ->with('error', 'You are not enrolled in an active team for this course.');
        }

        $allMembers = $team->members;
        if ($team->leader && !$allMembers->contains('id', $team->leader->id)) {
            $allMembers->push($team->leader);
        }
        $validTeammateIds = $allMembers->pluck('id')->all();

        $ratingsData = $request->input('ratings', []);
        if (empty($ratingsData) || !is_array($ratingsData)) {
            return redirect()->back()->with('error', 'Please provide ratings for all your teammates before submitting.');
        }

        // Validate each teammate's ratings
        foreach ($ratingsData as $rateeId => $data) {
            $rateeId = (int) $rateeId;

            // Verification 3: Cannot rate oneself
            if ($rateeId === $activeUserId) {
                return redirect()->back()->with('error', 'SRS Rule Violation: A team member cannot submit a self-evaluation.');
            }

            // Verify ratee is an actual teammate
            if (!in_array($rateeId, $validTeammateIds, true)) {
                return redirect()->back()->with('error', 'Invalid teammate selected for evaluation.');
            }

            // Verify effort (1-5)
            $effort = (int) ($data['effort'] ?? 0);
            if ($effort < 1 || $effort > 5) {
                return redirect()->back()->with('error', 'Effort score must be an integer between 1 and 5 for each teammate.');
            }

            // Verify reliability (1-5)
            $reliability = (int) ($data['reliability'] ?? 0);
            if ($reliability < 1 || $reliability > 5) {
                return redirect()->back()->with('error', 'Reliability score must be an integer between 1 and 5 for each teammate.');
            }

            // Verify communication (1-5)
            $communication = (int) ($data['communication'] ?? 0);
            if ($communication < 1 || $communication > 5) {
                return redirect()->back()->with('error', 'Communication score must be an integer between 1 and 5 for each teammate.');
            }

            // Verify quality (1-5)
            $quality = (int) ($data['quality'] ?? 0);
            if ($quality < 1 || $quality > 5) {
                return redirect()->back()->with('error', 'Quality score must be an integer between 1 and 5 for each teammate.');
            }
        }

        // Save peer ratings
        $now = date('Y-m-d H:i:s');
        foreach ($ratingsData as $rateeId => $data) {
            $rateeId = (int) $rateeId;
            $comment = isset($data['comments']) ? trim((string) $data['comments']) : null;

            PeerRating::create([
                'round_id' => $round->id,
                'rater_id' => $activeUserId,
                'ratee_id' => $rateeId,
                'effort' => (int) $data['effort'],
                'reliability' => (int) $data['reliability'],
                'communication' => (int) $data['communication'],
                'quality' => (int) $data['quality'],
                'comments' => $comment !== '' ? $comment : null,
                'submitted_at' => $now,
            ]);
        }

        return redirect()->route('evaluations.show', $round->id)
            ->with('success', "Your anonymous peer evaluation for \"{$round->title}\" was successfully submitted and locked. Under SRS F16, your submission is completely confidential.");
    }

    /**
     * Display round status and confirmation receipt.
     * Enforces strict anonymity: individual ratings and comments are never revealed to students.
     */
    public function show(int $roundId): View|RedirectResponse
    {
        $round = PeerRound::with('course')->findOrFail($roundId);
        $activeUserId = (int) session('active_user_id', 3);
        $activeUser = User::findOrFail($activeUserId);

        $hasSubmitted = $round->hasUserSubmitted($activeUserId);
        $userRatings = PeerRating::where('round_id', '=', $roundId)
            ->where('rater_id', '=', $activeUserId)
            ->get();
        $submissionCount = $userRatings->count();

        // Team stats
        $team = $this->findUserTeamForCourse((int) $round->course_id, $activeUserId);

        return view('evaluations.show', compact(
            'round',
            'activeUser',
            'hasSubmitted',
            'submissionCount',
            'team'
        ));
    }

    /**
     * Instructor Action: Toggle round status (open / closed).
     */
    public function toggleStatus(Request $request, int $roundId): RedirectResponse
    {
        $round = PeerRound::findOrFail($roundId);
        $targetStatus = $request->input('status');

        if (!in_array($targetStatus, [PeerRound::STATUS_OPEN, PeerRound::STATUS_CLOSED], true)) {
            return redirect()->back()->with('error', 'Invalid status specified.');
        }

        $round->status = $targetStatus;
        $round->save();

        $label = ucfirst($targetStatus);
        return redirect()->back()->with('success', "Round \"{$round->title}\" status updated to: {$label}.");
    }

    /**
     * Instructor Action: Create / schedule a new peer evaluation round.
     */
    public function storeRound(Request $request): RedirectResponse
    {
        $request->validate([
            'course_id' => ['required', 'integer'],
            'round_number' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'opens_at' => ['required'],
            'closes_at' => ['required'],
        ]);

        $round = PeerRound::create([
            'course_id' => (int) $request->input('course_id'),
            'round_number' => (int) $request->input('round_number'),
            'title' => trim((string) $request->input('title')),
            'opens_at' => date('Y-m-d H:i:s', strtotime((string) $request->input('opens_at'))),
            'closes_at' => date('Y-m-d H:i:s', strtotime((string) $request->input('closes_at'))),
            'status' => PeerRound::STATUS_OPEN,
        ]);

        return redirect()->back()->with('success', "Evaluation round \"{$round->title}\" created and opened successfully.");
    }

    /**
     * Demo Testing Helper: Clear test ratings to allow repeated simulation testing.
     */
    public function resetDemoRatings(): RedirectResponse
    {
        PeerRating::query()->delete();
        return redirect()->back()->with('success', 'All demo peer ratings have been reset. All student actors can now test submitting fresh evaluations.');
    }
}
