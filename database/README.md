# FairShare Database Documentation

This directory contains the complete MySQL 8.0 schema and seed dataset for **FairShare: Group Project Contribution and Fairness Tracker**.

---

## Files

1. **`fairshare_database.sql`**
   - The primary DDL schema file.
   - Contains 24 relational tables, foreign key constraints, `CHECK` constraints, JSON attributes, and indexes.
   - Database name: `fairshare_db`
   - Character set: `utf8mb4`, Collation: `utf8mb4_unicode_ci`

2. **`fairshare_seed.sql`**
   - Realistic initial seed data for testing and demonstrations.
   - Pre-loaded with:
     - 1 Admin (`admin@fairshare.edu` / `password123`)
     - 1 Instructor (`instructor@fairshare.edu` / `password123`)
     - 4 Students (`alex@student.edu`, `brian@student.edu`, `clara@student.edu`, `david@student.edu` / `password123`)
     - 1 Course (`CSE470`)
     - Milestones, Team Alpha, digital charter, weighted tasks, verifications, evidence files, time logs, and peer review round 1.

---

## How to Import

### Option 1: Via MySQL Command Line (CLI) / XAMPP Shell
```bash
mysql -u root -p < database/fairshare_database.sql
mysql -u root -p < database/fairshare_seed.sql
```

### Option 2: Via phpMyAdmin (XAMPP)
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open your browser and navigate to `http://localhost/phpmyadmin/`.
3. Click on the **Import** tab at the top.
4. Choose `fairshare_database.sql` and click **Import**.
5. Repeat for `fairshare_seed.sql` to populate sample data.

### Option 3: Via MySQL Workbench
1. Open MySQL Workbench and connect to your local MySQL instance.
2. Go to **File -> Open SQL Script...** and select `fairshare_database.sql`.
3. Click the lightning icon (Execute) to run the script.
4. Open and execute `fairshare_seed.sql` to seed test data.

---

## Schema Overview (24 Tables)

| Category | Tables |
| :--- | :--- |
| **Auth & Team Setup (F1 - F5)** | `users`, `courses`, `milestones`, `teams`, `team_members`, `charters`, `charter_acceptances` |
| **Tasks & Workload Fairness (F6 - F10)** | `tasks`, `task_verifications`, `contribution_splits`, `task_handovers` |
| **Evidence & Analytics (F11 - F15)** | `evidence_files`, `time_logs`, `meetings`, `meeting_attendances`, `meeting_action_items` |
| **Peer Review, AI & Grading (F16 - F20)** | `peer_rounds`, `peer_ratings`, `rating_flags`, `fairness_scores`, `team_health`, `concerns`, `grade_suggestions` |
| **Audit & Integrity** | `audit_logs` |

