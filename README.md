# FairShare: Group Project Contribution and Fairness Tracker
A CSE-470 BRAC University Project

**FairShare** is an intelligent web platform where student teams plan work, upload verifiable evidence of contribution, and evaluate each other anonymously. Instructors receive an explainable fairness score for every member, early warnings about struggling teams, and suggested grade adjustments.

---

## Tech Stack
* **Web Framework:** Laravel 12 (PHP 8.2+) / Full MVC Architecture (Eloquent ORM, Blade Templating, CSRF Protection)
* **Database:** MySQL 8.0 (InnoDB) / utf8mb4
* **Frontend:** Blade, HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3
* **AI/ML Microservice:** Python 3 (FastAPI/Flask + Scikit-Learn Isolation Forest)

---

## Implemented Features

### 0. F1 — Course and Fairness-Formula Setup (SRS Core Feature - Member 1)
Instructors create and edit courses from the main site (`/courses`, also linked from the navigation bar and home page):
* **Course setup:** code, title, description, team size limits and number of evaluation rounds, plus optional milestones.
* **Fairness formula:** the 5 weights (verified tasks, peer rating, hours, evidence, attendance) must total exactly **100**; anything else is rejected.
* **Audit log:** every weight change is written to `audit_logs` (`weights_updated`).
* **Files:** `CourseController.php`, `Course.php`, `Milestone.php`, `AuditLog.php`, `resources/views/courses/` (all pages use the shared `layouts/app` layout).

### 1. F6 — Weighted Task Management (SRS Core Feature)
A 4-stage Kanban task board (`To Do`, `In Progress`, `In Review`, `Done`):
* **Difficulty Weighting:** Every task has a weight from **1 to 5** (1: Easy/Routine fix, 2: Minor/Standard, 3: Medium/Multi-component, 4: High/Complex architecture, 5: Very Complex/Core engine) which directly influences the fairness formula.
* **Assignee & Due Date:** Clear task ownership and deadline tracking.
* **SRS Transition Rules & Acceptance Criteria:**
  1. **Only the assignee** can start a task (`To Do` &rarr; `In Progress`).
  2. **Only a non-assignee teammate** can verify a task (`In Review` &rarr; `Done`).
  3. A member **cannot move a task directly to Done** (must pass through `In Review` for peer verification).
  4. Non-assignee reviewer may return a task from `In Review` back to `In Progress` with a specific review note.

### 2. F11 — Evidence Vault (SRS Verification Feature)
A tamper-evident file vault linking work deliverables to specific tasks:
* **Cryptographic Integrity:** Computes SHA-256 hash on upload and verifies tamper-resistance.
* **Version Management:** Incremental file versioning per task (`v1`, `v2`, ...).
* **Strict Validation:** Restricts uploads to <10MB and supported formats (`pdf`, `zip`, `docx`, `png`, `jpg`, `txt`, `py`, `sql`, `md`).

### 3. F16 — Anonymous Peer Evaluation Rounds (SRS Evaluation Feature - Member 4)
A confidential peer evaluation system enabling team members to evaluate each other across core sprint dimensions:
* **Evaluation Dimensions:** Rated on a scale of **1 to 5** across:
  1. **Effort & Initiative** (1: Minimal, 5: Outstanding initiative)
  2. **Reliability & Deadlines** (1: Unreliable, 5: Highly dependable)
  3. **Communication & Teamwork** (1: Unresponsive, 5: Exemplary)
  4. **Quality of Deliverables** (1: Substandard, 5: Flawless)
* **SRS Transition Rules & Acceptance Criteria:**
  1. **Strict Anonymity:** Ratings and feedback are completely anonymous. Teammates can never see who assigned which score.
  2. **One-Time Submission:** Exactly one submission per rater per round. Once submitted, ratings cannot be edited or resubmitted.
  3. **No Self-Rating:** A member evaluates only their teammates; self-evaluation is strictly prohibited and blocked by database and business logic constraints.
  4. **Round Schedule Window:** Submissions are only accepted when the round is in `open` status and within its scheduled time window (`opens_at` to `closes_at`).

---

## Repository Structure (Laravel 12 MVC)
```
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Controller.php         # Base Laravel Controller
│   │       ├── CourseController.php   # Feature 1: Course & Fairness-Formula Setup (Member 1)
│   │       ├── TaskController.php     # Feature 6: Weighted Task Board & Rule Enforcement
│   │       ├── EvidenceController.php # Feature 11: Evidence Vault & Cryptographic Verification
│   │       └── PeerRatingController.php # Feature 16: Anonymous Peer Evaluations (Member 4)
│   ├── Models/
│   │   ├── User.php                   # Authenticatable User model & relationships
│   │   ├── Team.php                   # Team model with members, leader, course
│   │   ├── Course.php                 # Course model
│   │   ├── TeamMember.php             # Team member pivot model
│   │   ├── Task.php                   # Weighted Task model with F6 status transition logic
│   │   ├── EvidenceFile.php           # Evidence file model with SHA-256 hashing
│   │   ├── PeerRound.php              # Feature 16: Peer evaluation round model
│   │   └── PeerRating.php             # Feature 16: Peer rating matrix model
│   └── Providers/
│       └── AppServiceProvider.php     # Application service provider
├── bootstrap/
│   ├── app.php                        # Laravel 12 application bootstrap & middleware
│   └── providers.php                  # Service provider registry
├── config/
│   ├── app.php                        # Application configuration
│   ├── database.php                   # MySQL Eloquent configuration
│   ├── filesystems.php                # Storage configuration
│   └── session.php                    # Session driver configuration
├── database/
│   ├── fairshare_database.sql         # Complete MySQL 8.0 schema (24 tables)
│   ├── fairshare_seed.sql             # Realistic demo & testing seed data
│   └── README.md                      # Database import instructions
├── public/
│   ├── css/
│   │   └── fairshare.css              # Custom styling
│   ├── js/
│   │   └── main.js                    # Custom scripts
│   └── index.php                      # Laravel front controller entry point
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php          # Main layout with user switcher & navigation
│       ├── home.blade.php             # Landing page
│       ├── tasks/                     # F6 Weighted Task views
│       │   ├── teams.blade.php        # Team selector
│       │   ├── board.blade.php        # 4-Column Kanban board
│       │   ├── create.blade.php       # Task creation form
│       │   └── edit.blade.php         # Task edit form
│       ├── evidence/                  # F11 Evidence Vault views
│       │   ├── tasks.blade.php        # Task selector
│       │   ├── index.blade.php        # Evidence list & verify
│       │   └── create.blade.php       # File upload form
│       └── evaluations/               # F16 Peer Evaluation views (Member 4)
│           ├── index.blade.php        # Rounds list & completion status
│           ├── create.blade.php       # 4-Criteria rating matrix form
│           └── show.blade.php         # Sealed receipt & anonymity confirmation
├── routes/
│   ├── web.php                        # Web routes (F6, F11 & F16)
│   └── console.php                    # Console routes
├── storage/                           # Storage logs, framework caches, and uploaded evidence
├── .env.example                       # Environment template
├── artisan                            # Laravel CLI runner
├── composer.json                      # PHP dependencies & PSR-4 autoloading
└── README.md
```

---

## Getting Started

### 1. Database Setup
1. Ensure MySQL (8.0+) is running.
2. Import the schema and seed data:
   ```bash
   mysql -u root -p < database/fairshare_database.sql
   mysql -u root -p < database/fairshare_seed.sql
   ```
   *(See [database/README.md](database/README.md) for detailed credentials and options)*

### 2. Configure Environment
1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
2. Verify MySQL settings in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=fairshare_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### 3. Install Dependencies & Run
```bash
composer install
php artisan serve
```
Open your browser at `http://localhost:8000`.

---

## Testing F6 Transition Rules
In the top-right corner of the navigation bar, an **Actor / Simulated User Switcher** dropdown is provided. You can switch between team members (e.g., Alice Vance, Bob Chen, Charlie Davis) to test:
* **Assignee Rule:** Only the task assignee will see the "Start Working &rarr;" button to move a task from `To Do` to `In Progress`. Other members will see "Assignee only" disabled.
* **Submit for Review:** The assignee moves the task from `In Progress` to `In Review`.
* **Peer Reviewer Rule:** Only non-assignee teammates will see the "Verify & Mark Done &check;" or "Return for Revisions" buttons. The assignee cannot verify their own work.
* **Direct Done Prohibition:** Direct transition from `To Do` to `Done` is strictly blocked in both UI and backend validation.
