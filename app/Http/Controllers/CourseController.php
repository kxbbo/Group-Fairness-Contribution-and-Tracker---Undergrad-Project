<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * F1: Course and Fairness-Formula Setup
 * - Instructor creates / edits a course
 * - Sets team size limits, evaluation rounds and the 5 fairness weights
 * - Weights MUST total 100
 * - Changing weights is written to audit_logs
 */
class CourseController extends Controller
{
    public const WEIGHT_LABELS = [
        'tasks'       => 'Verified tasks',
        'peer_rating' => 'Peer rating',
        'hours'       => 'Hours',
        'evidence'    => 'Evidence',
        'attendance'  => 'Attendance',
    ];

    /** List all courses. */
    public function index(): View
    {
        $courses = Course::with('instructor')->orderByDesc('created_at')->get();

        return view('courses.index', compact('courses'));
    }

    /** Show the "new course" form. */
    public function create(): View
    {
        return view('courses.create');
    }

    /** Save a new course. */
    public function store(Request $request): RedirectResponse
    {
        $result = $this->validateInput($request, true);

        if (!empty($result['errors'])) {
            session(['errors' => $result['errors'], 'old_input' => $request->all()]);
            return redirect()->route('courses.create');
        }

        $instructor = $this->currentInstructor();
        if (!$instructor) {
            session(['error' => 'No instructor account found. Import database/fairshare_seed.sql first.']);
            return redirect()->route('courses.index');
        }

        $data = $result['data'];

        $course = Course::create([
            'instructor_id'           => (int) $instructor->id,
            'code'                    => $data['code'],
            'title'                   => $data['title'],
            'description'             => $data['description'],
            'team_size_min'           => $data['team_size_min'],
            'team_size_max'           => $data['team_size_max'],
            'evaluation_rounds_count' => $data['evaluation_rounds_count'],
            'formula_weights'         => json_encode($data['weights']),
            'is_active'               => 1,
        ]);

        foreach ($data['milestones'] as $m) {
            Milestone::create([
                'course_id'      => (int) $course->id,
                'title'          => $m['title'],
                'deadline'       => $m['deadline'],
                'weight_percent' => 0,
            ]);
        }

        $this->audit('course_created', (int) $course->id, null, $data['weights'], (int) $instructor->id);

        session(['success' => "Course {$course->code} created. Weights total 100."]);
        return redirect()->route('courses.index');
    }

    /** Show the "edit course" form. */
    public function edit(int $id): View
    {
        $course  = Course::findOrFail($id);
        $weights = $course->formula_weights ?? [];

        return view('courses.edit', compact('course', 'weights'));
    }

    /** Update a course. Changing the weights is audit-logged. */
    public function update(Request $request, int $id): RedirectResponse
    {
        $course = Course::findOrFail($id);
        $oldWeights = $course->formula_weights ?? [];

        $result = $this->validateInput($request, false);

        if (!empty($result['errors'])) {
            session(['errors' => $result['errors'], 'old_input' => $request->all()]);
            return redirect()->route('courses.edit', $id);
        }

        $data = $result['data'];

        $course->update([
            'code'                    => $data['code'],
            'title'                   => $data['title'],
            'description'             => $data['description'],
            'team_size_min'           => $data['team_size_min'],
            'team_size_max'           => $data['team_size_max'],
            'evaluation_rounds_count' => $data['evaluation_rounds_count'],
            'formula_weights'         => json_encode($data['weights']),
            'is_active'               => $request->has('is_active') ? 1 : 0,
        ]);

        if ($oldWeights != $data['weights']) {
            $instructor = $this->currentInstructor();
            $this->audit('weights_updated', (int) $course->id, $oldWeights, $data['weights'], $instructor ? (int) $instructor->id : null);
        }

        session(['success' => "Course {$data['code']} updated."]);
        return redirect()->route('courses.index');
    }

    /**
     * Check every field. Returns ['data' => [...], 'errors' => [...]].
     * Main rule: the 5 weights must be whole numbers 0-100 and total exactly 100.
     */
    private function validateInput(Request $request, bool $withMilestones): array
    {
        $errors = [];

        $code        = trim((string) $request->input('code', ''));
        $title       = trim((string) $request->input('title', ''));
        $description = trim((string) $request->input('description', ''));

        if ($code === '' || mb_strlen($code) > 50) {
            $errors['code'] = 'Course code is required (max 50 characters).';
        }
        if ($title === '' || mb_strlen($title) > 255) {
            $errors['title'] = 'Course title is required (max 255 characters).';
        }

        $min    = (string) $request->input('team_size_min', '');
        $max    = (string) $request->input('team_size_max', '');
        $rounds = (string) $request->input('evaluation_rounds_count', '');

        $minOk = ctype_digit($min) && (int) $min >= 1 && (int) $min <= 20;
        $maxOk = ctype_digit($max) && (int) $max >= 1 && (int) $max <= 20;

        if (!$minOk) {
            $errors['team_size_min'] = 'Minimum team size must be a whole number from 1 to 20.';
        }
        if (!$maxOk) {
            $errors['team_size_max'] = 'Maximum team size must be a whole number from 1 to 20.';
        }
        if ($minOk && $maxOk && (int) $max < (int) $min) {
            $errors['team_size_max'] = 'Maximum team size cannot be smaller than the minimum.';
        }
        if (!ctype_digit($rounds) || (int) $rounds < 1 || (int) $rounds > 10) {
            $errors['evaluation_rounds_count'] = 'Evaluation rounds must be a whole number from 1 to 10.';
        }

        // ---- Fairness formula weights ----
        $weights    = [];
        $sum        = 0;
        $weightsOk  = true;

        foreach (self::WEIGHT_LABELS as $key => $label) {
            $raw = (string) $request->input('w_' . $key, '');
            if (!ctype_digit($raw) || (int) $raw > 100) {
                $errors['w_' . $key] = "{$label} weight must be a whole number from 0 to 100.";
                $weights[$key] = 0;
                $weightsOk = false;
                continue;
            }
            $weights[$key] = (int) $raw;
            $sum += (int) $raw;
        }

        if ($weightsOk && $sum !== 100) {
            $errors['weights'] = "The 5 weights must total 100. They currently total {$sum}.";
        }

        // ---- Milestones (optional, create form only) ----
        $milestones = [];
        if ($withMilestones) {
            $titles    = $request->input('ms_title', []);
            $deadlines = $request->input('ms_deadline', []);
            if (!is_array($titles)) {
                $titles = [];
            }
            if (!is_array($deadlines)) {
                $deadlines = [];
            }
            foreach ($titles as $i => $t) {
                $t = trim((string) $t);
                $d = trim((string) ($deadlines[$i] ?? ''));
                if ($t === '' && $d === '') {
                    continue;
                }
                if ($t === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    $errors['milestones'] = 'Each milestone needs both a title and a deadline date.';
                    continue;
                }
                $milestones[] = ['title' => $t, 'deadline' => $d . ' 23:59:00'];
            }
        }

        return [
            'errors' => $errors,
            'data'   => [
                'code'                    => $code,
                'title'                   => $title,
                'description'             => $description !== '' ? $description : null,
                'team_size_min'           => (int) $min,
                'team_size_max'           => (int) $max,
                'evaluation_rounds_count' => (int) $rounds,
                'weights'                 => $weights,
                'milestones'              => $milestones,
            ],
        ];
    }

    /** The logged-in (simulated) instructor, or the first instructor in the database. */
    private function currentInstructor(): ?User
    {
        $id = session('active_user_id');
        if ($id) {
            $user = User::find($id);
            if ($user && $user->role === 'instructor') {
                return $user;
            }
        }

        return User::where('role', 'instructor')->first();
    }

    /** Write one row to audit_logs (insert only). */
    private function audit(string $action, int $entityId, ?array $old, ?array $new, ?int $actorId): void
    {
        AuditLog::create([
            'actor_id'    => $actorId,
            'action'      => $action,
            'entity_type' => 'Course',
            'entity_id'   => $entityId,
            'old_values'  => $old === null ? null : json_encode($old),
            'new_values'  => $new === null ? null : json_encode($new),
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }
}
