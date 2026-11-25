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
| 6 | Real-time chat (direct + project / team channels, Reverb) | Done |
| 7 | Meetings (calendar, participants, transcript, notes & comments) | Done |
| 8 | Notifications (in-app + live, reminders) & global search | Done |
| 9 | Dashboard, reports & PDF export | Done |
| 10 | AI assistant (provider-agnostic, Gemini first) | Done |
| 11 | Hardening (audit, rate limits, docs, performance) | Planned |

---

## Tech stack

| Purpose | Tool |
|---|---|
| Framework | Laravel 13, PHP 8.3 |
| Database | MySQL 8 (SQLite in-memory for tests) |
| API authentication | Laravel Sanctum (Bearer tokens) |
| Google sign-in | Laravel Socialite |
| Real-time (WebSockets) | Laravel Reverb |
| Roles & permissions | spatie/laravel-permission |
| Audit log | spatie/laravel-activitylog |
| PDF reports | mPDF (Arabic / RTL support) |
| AI | Google Gemini (REST), behind a provider interface |
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

### Real-time (chat)

Run these next to `php artisan serve`, each in its own terminal:

```bash
php artisan reverb:start   # WebSocket server (default port 8080)
php artisan queue:work     # sends broadcast events and notifications
php artisan schedule:work  # meeting reminders, due-date reminders, cleanups
```

On a server, `schedule:work` is replaced by one cron entry: `* * * * * php artisan schedule:run`.

Projects created before the chat module get their channels with:

```bash
php artisan chat:sync-channels
```

### Environment variables

| Variable | Purpose |
|---|---|
| `APP_TIMEZONE` | Timezone used to read meeting dates / times (e.g. `Africa/Cairo`) |
| `FRONTEND_URL` | React app URL, used in emailed links (password reset) |
| `AUTH_TOKEN_TTL_HOURS` | Token lifetime for a normal login (default 24) |
| `AUTH_REMEMBER_TTL_DAYS` | Token lifetime with "Remember me" (default 30) |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | First admin account created by `db:seed` |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Google sign-in |
| `FILES_DISK`, `FILES_MAX_KB` | Disk and max size (KB) for uploaded files |
| `REPORTS_DISK` | Disk for exported PDF reports (default `local`, private) |
| `BROADCAST_CONNECTION` | `reverb` to send real-time events (`log` / `null` to disable) |
| `AI_PROVIDER` | `fake` (default, no key needed) or `gemini`; the admin can override it in the app |
| `GEMINI_API_KEY`, `GEMINI_MODEL` | Gemini key (never returned by the API) and model (default `gemini-2.5-flash`) |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Reverb server (created by `php artisan reverb:install`) |

### Demo data (local environment only)

`php artisan db:seed` creates (password `password` for demo accounts):

| Account | Role |
|---|---|
| `ADMIN_EMAIL` from `.env` | Admin |
| `pm@collabspace.test` | Project Manager |
| `member1@collabspace.test` … `member5@collabspace.test` | Team Members |

Plus the teams *UI/UX, Frontend, Backend, Marketing*, the projects *Alpha* and *SepetGo*,
the Kanban cards from the design on *Alpha*, the project / team chat channels, a few
channel messages, a direct chat, and this week's meetings on *Alpha* (with a transcript and notes). The seeder is safe to run more than once.

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
├── Console/Commands/   chat:sync-channels, meetings:send-reminders, tasks:send-due-reminders, notifications:prune
├── DTOs/
├── Enums/
├── Events/             Domain events + Chat/ broadcast events
├── Listeners/          CreateProjectChannels, Notifications/* (events -> notifications)
├── Notifications/      AppNotification + one class per notification kind
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
├── Services/           incl. Search/ (GlobalSearch + providers), Reports/ (Dashboard + Report services)
├── Support/            ApiResponse
└── Traits/             Filterable, HasAttachments
routes/
├── api.php             requires the feature files below
├── api/v1/             health, auth, profile, users, teams, projects, tasks, files, chat, meetings, notifications, search, dashboard, reports, admin/users
└── channels.php        real-time channel authorization
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

<details>
<summary><b>Chat</b> — <code>/conversations</code>, <code>/messages</code></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/conversations` | Chats list, newest first; `type=direct` (Inbox) \| `project` \| `team`, `project_id` |
| GET | `/conversations/unread-count` | Navigation badge |
| POST | `/conversations/direct` | `{ user_id }` — opens (or returns) the 1:1 chat |
| GET | `/conversations/{conversation}` | Includes `unread_count`; direct chats also `read_receipts` |
| GET / POST | `/conversations/{conversation}/messages` | Timeline (cursor pagination, newest first); send `{ body?, parent_id?, mention_ids[]?, files[]? }` |
| POST | `/conversations/{conversation}/read` | `{ message_id? }` — read receipt |
| PATCH / DELETE | `/messages/{message}` | Edit: author; delete: author, or in channels the project manager / admin |
| GET | `/messages/{message}/replies` | Thread replies (one level deep) |

Who can open a chat:

| Type | Members |
|---|---|
| Direct | Its two people only (admins can't read other people's direct chats) |
| Project ("Public Channel") | Everyone in the project, and admins |
| Team ("&lt;Team&gt; Team Channel") | Members of that team while it is assigned to the project, the project manager, and admins |

Project channels are created automatically with the project and whenever a team is assigned.
</details>

<details>
<summary><b>Meetings</b> — <code>/meetings</code></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/meetings` | Calendar: `from`, `to` (default: this week), `project_id`, `mine`, `status`, `search`; dashboard: `upcoming=1&mine=1&limit=3` |
| POST | `/meetings` | `{ project_id, title, description?, date, start_time, end_time \| duration_minutes, meeting_link?, participant_ids[]?, cover? }` |
| GET / PATCH / DELETE | `/meetings/{meeting}` | PATCH edits title / description / link only |
| PATCH | `/meetings/{meeting}/reschedule` | `{ date, start_time, end_time \| duration_minutes }` |
| PATCH | `/meetings/{meeting}/cancel` | Stays on the calendar as cancelled |
| PATCH | `/meetings/{meeting}/attendance` | `{ status: accepted \| declined }` |
| POST | `/meetings/{meeting}/join` | From 15 min before the start to the end; records attendance, returns `meeting_link` |
| POST / DELETE | `/meetings/{meeting}/cover` | Cover image (organizer) |
| GET / POST / DELETE | `/meetings/{meeting}/participants` | Invite project members from any team |
| GET / POST / PATCH / DELETE | `/meetings/{meeting}/transcripts` | Bulk lines `{ entries: [{ speaker_id \| speaker_name, offset: "00:40", content, language? }] }`; `?search=&language=` |
| GET / POST / PATCH / DELETE | `/meetings/{meeting}/notes` | `type: note \| comment` |

| Who | Can |
|---|---|
| Project manager / admin | Schedule, edit, reschedule, cancel, delete, manage participants |
| Meeting organizer | Transcript and cover image |
| Participants (and managers) | Answer, join, write notes and comments (edit their own) |
| Other project members | See the meeting, its transcript and notes |

Each meeting has a computed `state`: `upcoming`, `live`, `ended` or `cancelled`.
The video call happens outside the app (`meeting_link`); clicking the cover opens it.
The AI summary is part of the AI module (phase 10).
</details>

<details>
<summary><b>Notifications</b> — <code>/notifications</code></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/notifications` | Newest first; `unread=1`, `kind=`; `meta.unread_count` |
| GET | `/notifications/unread-count` | The bell badge |
| PATCH | `/notifications/{id}/read` | |
| PATCH | `/notifications/read-all` | |
| DELETE | `/notifications/{id}` | |

Every notification is saved and pushed live on `private-App.Models.User.{id}` with the same shape:
`{ id, kind, title, message, target: { type, id, ... }, actor, read_at, created_at }`.
Nobody is notified about their own action, and deactivated users get nothing.

| Kind | Sent to | When |
|---|---|---|
| `added_to_project` | Manager, team members, guests | Project created; team or guest added later |
| `added_to_team` | New members | Added to a team |
| `task_assigned` | New assignees | Task created / assignees changed |
| `task_status_changed` | Assignees + reviewer | Card moved to another column |
| `task_due_soon` | Assignees | Day before the due date (09:00, scheduled) |
| `mentioned` | Mentioned users | `mention_ids` in a chat message |
| `meeting_invited` | Invited participants | Meeting created / people invited |
| `meeting_rescheduled`, `meeting_cancelled` | Participants who did not decline | Reschedule / cancel |
| `meeting_reminder` | Participants who did not decline | 15 minutes before the start (scheduled) |

Read notifications older than 30 days are deleted daily.
</details>

<details>
<summary><b>Global search</b> — <code>/search</code></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/search?q=` | Grouped results: `projects, tasks, meetings, files, messages, teams, people`; `types=projects,tasks`, `limit` (per section), `save=0` while typing |
| GET | `/search/history` | "Recent Searches" (last 10) |
| DELETE | `/search/history/{id}` | Remove one |
| DELETE | `/search/history` | Clear all |

Each result: `{ type, id, title, subtitle, meta }` (meta carries what is needed to open it,
e.g. `slug`, `project_slug`, `conversation_id`). Every section only returns what the user
is allowed to see. New sections are added by writing a `SearchProvider` class and
registering it in `AppServiceProvider`.
</details>

<details>
<summary><b>Dashboard</b> — <code>/dashboard</code></summary>

`GET /dashboard?project_id=&year=` returns every widget in one call, limited to the projects
the user can see:

| Key | Content |
|---|---|
| `task_stats` | `pending`, `in_progress`, `in_review`, `completed` → `{ count, total, trend }`; `completion_rate` → `{ value, previous, change }` |
| `project_overview` | Per month of the year: tasks `created` / `completed` |
| `active_projects` | In-progress projects with `tasks_total`, `tasks_completed`, `progress` |
| `my_tasks` | My open tasks, due first |
| `teams` | My teams (all for admins) with `members_count`, `active_projects_count` |
| `recent_files`, `upcoming_meetings` | Latest 3 of each |

Trends ("+9% from last month"): for a column, new tasks this month vs last month in that
column; for completed, tasks completed this month vs last month; the rate change is in points.

`GET /teams/{team}/workload?project_id=` — each member's assigned / completed / open / overdue tasks.
</details>

<details>
<summary><b>Reports</b> — <code>/reports</code></summary>

All accept `project_id`, `from`, `to` (default: this month and the 5 before; max 2 years).

| Endpoint | Content |
|---|---|
| `GET /reports/overview` | `task_completion_rate`, `project_completion_rate`, `team_activity_rate` (each with the previous period) + `task_time_vs_deadline`, `projects_progress`, `worker_activity` per month |
| `GET /reports/projects` | Totals (active, delayed, completed ...) + one row per project |
| `GET /reports/tasks` | By status / priority, overdue, on-time rate, average days to complete |
| `GET /reports/teams` | Per team: assigned, completed, open, overdue, completion rate, open tasks per member |
| `GET /reports/users/{user}` | Tasks, files, meeting attendance, productivity score, completed per month |

Who: admins see every project; project managers see the projects they manage; anyone can
see their own user report (and a project manager can see the members of their projects).

Definitions:
- **Team activity rate** — members of the teams in scope who completed at least one task in the period.
- **Projects progress** — at the end of each month, completed tasks / tasks created so far.
- **Productivity score** — 60% completion rate + 30% on-time rate + 10% meeting attendance.

**PDF export** ("Submit a detailed report"):

| Method | Endpoint | Notes |
|---|---|---|
| POST | `/reports/exports` | `{ type: overview \| project \| task \| team \| user, note?, from?, to?, project_id?, user_id? }` → returns the export with `download_url` |
| GET | `/reports/exports` | My exported reports (history) |
| DELETE | `/reports/exports/{id}` | Deletes the record and the file |
| GET | `/reports/exports/{id}/download` | Signed link (30 minutes, no token needed) |

The same access rules as the reports apply. PDFs are rendered from Blade templates in
`resources/views/reports/pdf/` with mPDF (Arabic notes are shaped and written right-to-left)
and stored on the private disk. The PDF engine sits behind `App\Interfaces\Reports\PdfRenderer`.
</details>

<details>
<summary><b>AI assistant</b> — <code>/ai</code></summary>

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/ai/status` | `enabled`, `allowed_modules` (show / hide the AI features) |
| GET | `/ai/conversations` | My private AI chats, newest first, with a preview of the last message |
| POST | `/ai/conversations` | `{ message? }` — new chat; answers the first message when sent |
| GET | `/ai/conversations/{id}` | The chat with all its messages |
| DELETE | `/ai/conversations/{id}` | Delete the chat |
| POST | `/ai/conversations/{id}/messages` | `{ content }` → `{ question, answer }` |
| POST | `/ai/ask` | `{ question, project_id? }` — one-shot "Ask AI" (not stored) |
| GET | `/meetings/{id}/summary` | The meeting's AI summary (`null` until generated) |
| POST | `/meetings/{id}/summary` | Generate / regenerate: summary, key points, decisions, action items, risks, next steps (organizer or project managers) |
| GET | `/projects/{slug}/ai-insights?refresh=1` | Summary, `health` (`on_track` / `at_risk` / `off_track`), highlights, risks, recommendations (cached 60 minutes) |
| GET / PUT | `/admin/ai-settings` | Admin: `{ enabled, provider, model, allowed_modules[] }` |

How it works:
- Chats are private: only their owner can read or continue them (admins included).
- The AI only receives the data the user is allowed to see, and only from the modules the
  admin allowed (`projects, tasks, teams, meetings, reports, files`). File contents are never sent, only names.
- Workspace data is sent as data, separate from the instructions, and the model is told to
  ignore any instruction inside it.
- Providers implement `App\Interfaces\Ai\AiProvider`; `AiManager` picks one from the admin
  setting or `AI_PROVIDER`. Adding a provider = one class + one entry in `config/ai.php`.
- `fake` answers instantly without a key (development and tests).
- When AI is off, a module is blocked or the provider fails, the API returns `503`. A failed
  chat message is not saved.
- Every AI call is logged (provider, model, tokens) in the activity log, and limited to
  20 requests per minute per user (`throttle:ai`).
</details>

<details>
<summary><b>Real-time</b> — Laravel Reverb</summary>

The React app connects with Laravel Echo (Pusher protocol) and authorizes private channels
with `POST /api/broadcasting/auth` and the same `Authorization: Bearer <token>` header.

| Channel | Events | Payload |
|---|---|---|
| `private-conversation.{id}` | `.message.sent`, `.message.updated` | `{ message }` |
| | `.message.deleted` | `{ id, conversation_id, parent_id }` |
| | `.conversation.read` | `{ conversation_id, user_id, last_read_message_id }` |
| `private-App.Models.User.{id}` | `.message.sent` (direct chats) | `{ message }` — keeps the Inbox and badge up to date |
| `presence-online` | join / leave | `{ id, name, avatar_url }` — online indicators |

Events are not sent back to the sender's own socket (send the `X-Socket-ID` header from Echo).
`users.last_seen_at` is updated (at most once a minute) on authenticated API calls.
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
