# CollabSpace AI — Backend API

REST API for **CollabSpace AI**, a project collaboration platform: projects, teams,
tasks on a Kanban board, files, real-time chat, meetings, reports and an AI assistant.

The frontend is a separate React app; this repository is the API only.

---

## Progress

| Phase | Module | Status |
|:-:|---|:-:|
| 0 | Foundation (API responses, errors, versioning, filters, repositories) | Done |
| 1 | Authentication, users, roles & permissions, profile | Done |
| 2 | Teams & team members | Done |
| 3 | Projects (teams, guests, members, visibility) | Done |
| 4 | Tasks & Kanban board (drag & drop, progress, review flow) | Done |
| 5 | Files (project / task uploads, signed downloads) | Done |
| 6 | Real-time chat (direct + project / team channels) | Planned |
| 7 | Meetings (calendar, transcripts, notes, AI summary) | Planned |
| 8 | Notifications & global search | Planned |
| 9 | Dashboard, reports & PDF export | Planned |
| 10 | AI assistant (provider-agnostic, Gemini first) | Planned |
| 11 | Hardening (audit, rate limits, docs, performance) | Planned |

---

## Tech stack

| Purpose | Tool |
|---|---|
| Framework | Laravel 13, PHP 8.3 |
| Database | MySQL 8 (SQLite in-memory for tests) |
| API authentication | Laravel Sanctum (Bearer tokens) |
| Google sign-in | Laravel Socialite |
| Roles & permissions | spatie/laravel-permission |
| Audit log | spatie/laravel-activitylog |
| Tests | Pest |
| Code quality | Laravel Pint, Larastan (level 6) |

---

## Getting started

### Requirements

- PHP 8.3+ with `pdo_mysql`, `mbstring`, `fileinfo`
- Composer 2
- MySQL 8

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create a database (e.g. `collabspace`, collation `utf8mb4_unicode_ci`) and set the `DB_*`
values in `.env`, then:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

Check that the API is up: `GET http://localhost:8000/api/v1/health`.

### Environment variables

| Variable | Purpose |
|---|---|
| `FRONTEND_URL` | React app URL, used in emailed links (password reset) |
| `AUTH_TOKEN_TTL_HOURS` | Token lifetime for a normal login (default 24) |
| `AUTH_REMEMBER_TTL_DAYS` | Token lifetime with "Remember me" (default 30) |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | First admin account created by `db:seed` |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Google sign-in |
| `FILES_DISK`, `FILES_MAX_KB` | Disk and max size (KB) for uploaded files |

### Demo data (local environment only)

`php artisan db:seed` creates (password `password` for demo accounts):

| Account | Role |
|---|---|
| `ADMIN_EMAIL` from `.env` | Admin |
| `pm@collabspace.test` | Project Manager |
| `member1@collabspace.test` … `member5@collabspace.test` | Team Members |

Plus the teams *UI/UX, Frontend, Backend, Marketing*, the projects *Alpha* and *SepetGo*,
and the Kanban cards from the design on *Alpha*. The seeder is safe to run more than once.

---

## Architecture

### Request flow

```
Route (v1) → Middleware → FormRequest → Controller → DTO → Action
           → Repository (interface) / Service → API Resource → ApiResponse
```

| Layer | Responsibility |
|---|---|
| **FormRequest** | Validation and authorization (through Policies) |
| **Controller** | Thin: builds a DTO, calls one Action, returns a Resource |
| **DTO** | Typed, read-only data passed from the request to the Action |
| **Action** | One use case (e.g. `CreateProjectAction`), wrapped in a DB transaction when it writes more than once |
| **Repository** | All queries, behind an interface bound in `RepositoryServiceProvider` |
| **Service** | Shared logic or external integrations (tokens, file storage, slugs …) |
| **Policy** | Who can do what, per model |
| **Resource** | The JSON shape of a model |

### Conventions

- **Controllers** only contain resource methods (`index`, `store`, `show`, `update`, `destroy`).
  Any other endpoint is a single-action (invokable) controller.
- **Routes**: `routes/api.php` only loads one file per feature from `routes/api/v1/`.
  Each feature file defines its own prefix, middleware and route names.
- **Filtering**: `?search=&status=&sort=-created_at` through `QueryFilter` classes.
  Only public methods of a filter class are accepted, and only whitelisted columns can be sorted.
- **Enums** for every status / type, exposed to the frontend as `{ "value": "pending", "label": "To-do" }`.
- **Strict models** outside production (lazy loading and missing attributes throw).

### Folder layout

```
app/
├── Actions/            One class per use case, grouped by module
├── DTOs/
├── Enums/
├── Events/
├── Exceptions/         ApiExceptionRenderer, BusinessException
├── Filters/            QueryFilter + one filter per model
├── Http/
│   ├── Controllers/Api/V1/<Module>/
│   ├── Middleware/
│   ├── Requests/V1/<Module>/
│   └── Resources/V1/
├── Interfaces/Repositories/
├── Models/
├── Policies/
├── Repositories/Eloquent/
├── Rules/
├── Services/
├── Support/            ApiResponse
└── Traits/             Filterable, HasAttachments
routes/
├── api.php             requires the feature files below
└── api/v1/             health, auth, profile, users, teams, projects, tasks, files, admin/users
```

---

## API

Base URL: `/api/v1`. Send `Authorization: Bearer <token>` on protected endpoints.

### Response format

```json
{ "success": true, "message": "Project created successfully.", "data": { }, "meta": { "pagination": { } } }
```

Errors use the same shape with `"success": false`, plus `errors` for validation (422).
Model names and internal details are never exposed (404 is always "Resource not found.").

### Roles

| Role | Can |
|---|---|
| **Admin** | Everything, including user management |
| **Project Manager** | Create projects; manage the projects they manage (tasks, teams, guests); manage team members |
| **Team Member** | See the projects they belong to, work on their tasks, upload files |

A user belongs to a project if they manage it, are in one of its teams, or were added as a guest.

### Endpoints

<details>
<summary><b>Auth</b> — <code>/auth</code></summary>

| Method | Endpoint | Notes |
|---|---|---|
| POST | `/auth/register` | New accounts are Team Members |
| POST | `/auth/login` | `remember: true` for a 30-day token |
| POST | `/auth/google` | Body: Google `access_token` |
| POST | `/auth/forgot-password` | Same answer whether the email exists or not |
| POST | `/auth/reset-password` | Logs the user out everywhere |
| POST | `/auth/logout` | Current device only |
| GET | `/auth/me` | User + permission names |

Public auth endpoints are limited to 5 requests per minute per email + IP.
</details>

<details>
<summary><b>Profile</b> — <code>/profile</code></summary>

| Method | Endpoint |
|---|---|
| GET / PUT | `/profile` |
| POST / DELETE | `/profile/avatar` |
| PUT | `/profile/password` (other devices are logged out) |
| GET | `/profile/files` |
</details>

<details>
<summary><b>Users & Admin</b></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/users?search=` | Active users directory (small cards) |
| GET | `/users/{user}` | |
| GET / POST | `/admin/users` | Admin only; filters: `search, role, is_active, availability_status` |
| GET / PUT / DELETE | `/admin/users/{user}` | Soft delete |
| PATCH | `/admin/users/{user}/role` | An admin can't remove their own admin role |
| PATCH | `/admin/users/{user}/status` | Deactivating logs the user out everywhere |
</details>

<details>
<summary><b>Teams</b> — <code>/teams</code></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET / POST | `/teams` | Filters: `search, mine, member_id`; sort by `members_count` |
| GET / PUT / DELETE | `/teams/{team}` | Create / edit / delete: admin |
| GET / POST | `/teams/{team}/members` | Add or update members (admin or project manager) |
| PATCH / DELETE | `/teams/{team}/members/{user}` | A team has at most one leader |
</details>

<details>
<summary><b>Projects</b> — <code>/projects/{slug}</code></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET / POST | `/projects` | Filters: `search, status, priority, manager_id, team_id, deadline_from, deadline_to, overdue` |
| GET / PATCH / DELETE | `/projects/{slug}` | Includes `progress` and `task_stats` |
| GET / POST | `/projects/{slug}/teams` | Assign teams |
| DELETE | `/projects/{slug}/teams/{team}` | Blocked while the team still has tasks in the project |
| GET / POST | `/projects/{slug}/guests` | |
| DELETE | `/projects/{slug}/guests/{user}` | |
| GET | `/projects/{slug}/members` | Manager + team members + guests |

The slug is generated from the name and never changes.
</details>

<details>
<summary><b>Tasks & Kanban</b></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/projects/{slug}/board` | 4 columns in order; filters: `mine, team_id, priority, search` |
| GET / POST | `/projects/{slug}/tasks` | Create: project manager / admin |
| GET | `/tasks` | Every visible task; filters: `project_id, team_id, status, priority, mine, assignee_id, reviewer_id, due_from, due_to, overdue` |
| GET / PATCH / DELETE | `/tasks/{task}` | |
| PATCH | `/tasks/{task}/move` | `{ status, position }` — drag & drop |
| PATCH | `/tasks/{task}/progress` | `{ progress: 0-100 }` |

Statuses: `pending` (shown as **To-do**), `in_progress`, `in_review`, `completed`.
Assignees move their cards up to *In Review*; only the reviewer or a manager completes
(or reopens) a task. Completing sets progress to 100.
</details>

<details>
<summary><b>Files</b></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET / POST | `/projects/{slug}/files` | Project files + all its task files |
| GET / POST | `/tasks/{task}/files` | |
| GET | `/files/recent?limit=5` | Dashboard "Recent Files" |
| GET / DELETE | `/files/{file}` | `show` returns fresh links |
| GET | `/files/{file}/download` | Signed link, no token needed |

Uploads are `multipart/form-data` with `files[]` (max 10 files, 20 MB each, allowed
extensions only). Files are stored under random names on a **private** disk and served
only through signed links that expire after 30 minutes. Images and PDFs get a
`preview_url` (inline); everything else is always downloaded. Projects and tasks can
also be created with `files[]` in the same request.
</details>

---

## Testing & code quality

```bash
composer test      # Pest feature + unit tests
composer analyse   # Larastan
composer lint      # Laravel Pint (fixes style)
composer check     # all three
```

Tests run on an in-memory SQLite database with roles and permissions seeded.

A Postman collection covering every endpoint is shared separately with the team.
