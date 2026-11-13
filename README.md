# FairShare: Group Project Contribution and Fairness Tracker
A CSE-470 BRAC University Project

**FairShare** is an intelligent web platform where student teams plan work, upload verifiable evidence of contribution, and evaluate each other anonymously. Instructors receive an explainable fairness score for every member, early warnings about struggling teams, and suggested grade adjustments.

---

## Tech Stack
* **Web Framework:** Laravel 11 (PHP 8.2+) / MVC Architecture
* **Database:** MySQL 8.0 (InnoDB)
* **Frontend:** Blade, Bootstrap 5, Chart.js
* **AI/ML Microservice:** Python 3 (FastAPI/Flask + Scikit-Learn Isolation Forest)

---

## Repository Structure
```
├── database/
│   ├── fairshare_database.sql    # Complete MySQL 8.0 schema (24 tables)
│   ├── fairshare_seed.sql        # Realistic demo & testing seed data
│   └── README.md                 # Database import instructions & docs
├── docs/
│   └── Project idea 2 .docx      # Project Proposal and SRS Specification
└── README.md
```

---

## 4-Member Workload Distribution (20 Features)
* **Member 1 (Setup, Teams & Dashboard - F1 to F5):** Course & formula setup, team formation & size limits, digital charter, skill-based team suggestions, team pulse dashboard.
* **Member 2 (Tasks & Workload Fairness - F6 to F10):** Weighted task board (1-5 difficulty), peer verification, workload balance advisor, shared task splits, handover credit logs.
* **Member 3 (Evidence & Analytics - F11 to F15):** Evidence vault with SHA-256 hashing, time logging with plausibility checks, meetings with rotating check-in codes, contribution analytics, rush detector.
* **Member 4 (Peer Review, AI Service & Grading - F16 to F20):** Anonymous peer evaluation rounds, AI rating integrity checks (Isolation Forest), explainable fairness score engine & what-if simulator, team health risk detection, grade adjustments, private concerns, and report export.

---

## Getting Started with the Database
See [database/README.md](database/README.md) for full instructions on setting up MySQL and importing the schema and seed data.
