# Mahami Database Schema (Current Baseline & Evolution Plan)

## 1. Existing Active Tables (Phase 0 Baseline)

### 1.1 `users`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| `name` | VARCHAR(255) | NOT NULL | User's full name |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | User's email address |
| `email_verified_at` | TIMESTAMP | NULLABLE | Email verification timestamp |
| `password` | VARCHAR(255) | NOT NULL | Hashed password (Bcrypt) |
| `remember_token` | VARCHAR(100) | NULLABLE | Session remember token |
| `created_at` | TIMESTAMP | NULLABLE | Creation timestamp |
| `updated_at` | TIMESTAMP | NULLABLE | Last update timestamp |

### 1.2 `personal_access_tokens` (Sanctum)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Token ID |
| `tokenable_type` | VARCHAR(255) | NOT NULL | Morph model class (`App\Models\User`) |
| `tokenable_id` | BIGINT UNSIGNED | NOT NULL | User ID reference |
| `name` | VARCHAR(255) | NOT NULL | Token description name |
| `token` | VARCHAR(64) | UNIQUE, NOT NULL | Hashed token value |
| `abilities` | TEXT | NULLABLE | Permissions array |
| `last_used_at` | TIMESTAMP | NULLABLE | Last usage timestamp |
| `expires_at` | TIMESTAMP | NULLABLE | Expiration timestamp |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.3 `boards`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Board ID |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)` ON DELETE CASCADE | Creator / Owner ID |
| `title` | VARCHAR(255) | NOT NULL | Board name |
| `description` | TEXT | NULLABLE | Board notes / description |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |
*Indexes:* `INDEX(user_id, created_at)`

### 1.4 `board_lists` (Columns)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Column ID |
| `board_id` | BIGINT UNSIGNED | NOT NULL, FK -> `boards(id)` ON DELETE CASCADE | Parent Board ID |
| `title` | VARCHAR(255) | NOT NULL | Column title (e.g. To Do, In Progress) |
| `position` | INT UNSIGNED | NOT NULL, DEFAULT 0 | 0-based contiguous ordering index |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |
*Indexes:* `INDEX(board_id, position)`

### 1.5 `tasks`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Task ID |
| `board_list_id` | BIGINT UNSIGNED | NOT NULL, FK -> `board_lists(id)` ON DELETE CASCADE | Parent Column ID |
| `title` | VARCHAR(255) | NOT NULL | Task title |
| `description` | TEXT | NULLABLE | Task details / markdown notes |
| `priority` | ENUM('low', 'medium', 'high') | NOT NULL, DEFAULT 'medium' | Task priority |
| `due_date` | DATE | NULLABLE | Due date (YYYY-MM-DD) |
| `position` | INT UNSIGNED | NOT NULL, DEFAULT 0 | 0-based contiguous ordering index |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |
*Indexes:* `INDEX(board_list_id, position)`, `INDEX(priority)`, `INDEX(due_date)`

---

## 2. Planned Schema Extensions (Upcoming Phases)

### Phase 1 — Workspaces & Memberships
- `workspaces`: `id`, `name`, `slug`, `description`, `owner_id` (FK -> `users`), `created_at`, `updated_at`
- `workspace_members`: `id`, `workspace_id` (FK), `user_id` (FK), `role` (owner, admin, member, guest), `status` (active, suspended), UNIQUE(`workspace_id`, `user_id`)
- `workspace_invitations`: `id`, `workspace_id` (FK), `email`, `role`, `token` (hashed 64-char), `invited_by` (FK -> `users`), `expires_at`, `accepted_at`, `cancelled_at`, `created_at`
- *Migration link for `boards`:* add nullable `workspace_id` (FK -> `workspaces`) with backward compatibility for existing personal boards.

### Phase 2 — Teams & Granular RBAC
- `teams`: `id`, `workspace_id` (FK), `name`, `description`, `leader_id` (FK -> `users`), `created_at`, `updated_at`
- `team_members`: `id`, `team_id` (FK), `user_id` (FK), `role`, UNIQUE(`team_id`, `user_id`)
- `project_members`: `id`, `board_id` (FK), `user_id` (FK), `role` (manager, member, viewer), UNIQUE(`board_id`, `user_id`)

### Phase 3 — Advanced Tasks & Milestones
- `milestones`: `id`, `board_id` (FK), `title`, `description`, `due_date`, `status`
- `task_assignees`: `id`, `task_id` (FK), `user_id` (FK), UNIQUE(`task_id`, `user_id`)
- `task_labels`: `id`, `board_id` (FK), `name`, `color`
- `task_label_pivot`: `task_id` (FK), `label_id` (FK)
- `task_dependencies`: `id`, `task_id` (FK), `depends_on_task_id` (FK)
- `subtasks`: `id`, `task_id` (FK), `title`, `is_completed`, `position`

### Phase 4 — Collaboration & Files
- `comments`: `id`, `task_id` (FK), `user_id` (FK), `parent_id` (FK nullable for threaded replies), `content` (sanitized text), `created_at`, `updated_at`
- `attachments`: `id`, `task_id` (FK nullable), `comment_id` (FK nullable), `user_id` (FK), `original_name`, `stored_path`, `disk`, `mime_type`, `file_size`, `created_at`
- `activity_logs`: `id`, `workspace_id` (FK), `board_id` (FK nullable), `task_id` (FK nullable), `user_id` (FK), `event_type`, `description`, `properties` (JSON), `created_at`

### Phase 5 — Real-Time Notifications
- `notifications`: standard Laravel notification table (`id` UUID, `type`, `notifiable_type`, `notifiable_id`, `data` JSON, `read_at`, `created_at`)
- `notification_preferences`: `id`, `user_id` (FK), `channel` (database, mail, broadcast), `event_type`, `is_enabled`
