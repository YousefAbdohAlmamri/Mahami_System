# Mahami Database Schema (Active Tables & Evolution Plan)

## 1. Active Implemented Tables (100% Migrated in Database)

### 1.1 `users`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| `name` | VARCHAR(255) | NOT NULL | User's full name |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | User's email address |
| `email_verified_at` | TIMESTAMP | NULLABLE | Email verification timestamp |
| `password` | VARCHAR(255) | NOT NULL | Hashed password (Bcrypt) |
| `remember_token` | VARCHAR(100) | NULLABLE | Session remember token |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.2 `personal_access_tokens` (Sanctum)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Token ID |
| `tokenable_type` | VARCHAR(255) | NOT NULL | Morph model class (`App\Models\User`) |
| `tokenable_id` | BIGINT UNSIGNED | NOT NULL | User ID reference |
| `name` | VARCHAR(255) | NOT NULL | Token description name |
| `token` | VARCHAR(64) | UNIQUE, NOT NULL | Hashed token value |
| `abilities` | TEXT | NULLABLE | Permissions array |
| `last_used_at` / `expires_at` | TIMESTAMP | NULLABLE | Usage & Expiration timestamps |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.3 `workspaces` (Phase 1)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Workspace ID |
| `owner_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)` ON DELETE CASCADE | Owner User ID |
| `name` | VARCHAR(255) | NOT NULL | Workspace display name |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | URL-friendly unique identifier |
| `description` | TEXT | NULLABLE | Brief workspace overview |
| `is_archived` | TINYINT(1) | NOT NULL, DEFAULT 0 | Archive state |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.4 `workspace_members` (Phase 1)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Membership ID |
| `workspace_id` | BIGINT UNSIGNED | NOT NULL, FK -> `workspaces(id)` ON DELETE CASCADE | Workspace reference |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)` ON DELETE CASCADE | User reference |
| `role` | VARCHAR(32) | NOT NULL, DEFAULT 'member' | `owner`, `admin`, `member`, `guest` |
| `status` | VARCHAR(32) | NOT NULL, DEFAULT 'active' | `active`, `suspended` |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |
*Unique constraint:* `UNIQUE(workspace_id, user_id)`

### 1.5 `workspace_invitations` (Phase 1)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Invitation ID |
| `workspace_id` | BIGINT UNSIGNED | NOT NULL, FK -> `workspaces(id)` ON DELETE CASCADE | Target workspace |
| `email` | VARCHAR(255) | NOT NULL | Invitee email |
| `role` | VARCHAR(32) | NOT NULL, DEFAULT 'member' | Invited role |
| `token` | VARCHAR(64) | UNIQUE, NOT NULL | Secure 64-character token |
| `invited_by` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)` ON DELETE CASCADE | Inviting user |
| `expires_at` | TIMESTAMP | NOT NULL | Expiration timestamp |
| `accepted_at` / `declined_at` / `cancelled_at` | TIMESTAMP | NULLABLE | Status timestamps |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.6 `workspace_teams` (Phase 2)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Team ID |
| `workspace_id` | BIGINT UNSIGNED | NOT NULL, FK -> `workspaces(id)` ON DELETE CASCADE | Parent workspace |
| `name` | VARCHAR(255) | NOT NULL | Team name |
| `description` | TEXT | NULLABLE | Team description |
| `created_by` | BIGINT UNSIGNED | NULLABLE, FK -> `users(id)` ON DELETE SET NULL | Creator user |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.7 `team_members` (Phase 2)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Pivot ID |
| `team_id` | BIGINT UNSIGNED | NOT NULL, FK -> `workspace_teams(id)` ON DELETE CASCADE | Team reference |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)` ON DELETE CASCADE | User reference |
| `role` | VARCHAR(32) | NOT NULL, DEFAULT 'member' | `lead`, `member` |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |
*Unique constraint:* `UNIQUE(team_id, user_id)`

### 1.8 `boards`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Board ID |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)` ON DELETE CASCADE | Creator ID |
| `workspace_id` | BIGINT UNSIGNED | NULLABLE, FK -> `workspaces(id)` ON DELETE SET NULL | Scoped workspace |
| `title` | VARCHAR(255) | NOT NULL | Board name |
| `description` | TEXT | NULLABLE | Board notes / description |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.9 `board_teams` (Phase 2)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Pivot ID |
| `board_id` | BIGINT UNSIGNED | NOT NULL, FK -> `boards(id)` ON DELETE CASCADE | Board reference |
| `team_id` | BIGINT UNSIGNED | NOT NULL, FK -> `workspace_teams(id)` ON DELETE CASCADE | Team reference |
| `permission` | VARCHAR(32) | NOT NULL, DEFAULT 'editor' | `viewer`, `editor`, `admin` |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |
*Unique constraint:* `UNIQUE(board_id, team_id)`

### 1.10 `board_lists` (Columns)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Column ID |
| `board_id` | BIGINT UNSIGNED | NOT NULL, FK -> `boards(id)` ON DELETE CASCADE | Parent Board ID |
| `title` | VARCHAR(255) | NOT NULL | Column title |
| `position` | INT UNSIGNED | NOT NULL, DEFAULT 0 | 0-based ordering index |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.11 `tasks`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Task ID |
| `board_list_id` | BIGINT UNSIGNED | NOT NULL, FK -> `board_lists(id)` ON DELETE CASCADE | Parent Column ID |
| `title` | VARCHAR(255) | NOT NULL | Task title |
| `description` | TEXT | NULLABLE | Task details / markdown notes |
| `priority` | ENUM('low', 'medium', 'high') | NOT NULL, DEFAULT 'medium' | Task priority |
| `due_date` | DATE | NULLABLE | Due date (YYYY-MM-DD) |
| `position` | INT UNSIGNED | NOT NULL, DEFAULT 0 | 0-based ordering index |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.12 `task_assignees` (Phase 3)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Pivot ID |
| `task_id` | BIGINT UNSIGNED | NOT NULL, FK -> `tasks(id)` ON DELETE CASCADE | Task reference |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users(id)` ON DELETE CASCADE | Assigned User reference |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |
*Unique constraint:* `UNIQUE(task_id, user_id)`

### 1.13 `board_labels` (Phase 3)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Label ID |
| `board_id` | BIGINT UNSIGNED | NOT NULL, FK -> `boards(id)` ON DELETE CASCADE | Board reference |
| `name` | VARCHAR(255) | NOT NULL | Label title / tag |
| `color` | VARCHAR(32) | NOT NULL, DEFAULT '#3b82f6' | Hex color or color key |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.14 `task_labels` (Phase 3)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Pivot ID |
| `task_id` | BIGINT UNSIGNED | NOT NULL, FK -> `tasks(id)` ON DELETE CASCADE | Task reference |
| `label_id` | BIGINT UNSIGNED | NOT NULL, FK -> `board_labels(id)` ON DELETE CASCADE | Label reference |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |
*Unique constraint:* `UNIQUE(task_id, label_id)`

### 1.15 `task_checklists` (Phase 3)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Checklist ID |
| `task_id` | BIGINT UNSIGNED | NOT NULL, FK -> `tasks(id)` ON DELETE CASCADE | Task reference |
| `title` | VARCHAR(255) | NOT NULL, DEFAULT 'Checklist' | Checklist title |
| `position` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Ordering index |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.16 `task_checklist_items` (Phase 3)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Item ID |
| `checklist_id` | BIGINT UNSIGNED | NOT NULL, FK -> `task_checklists(id)` ON DELETE CASCADE | Parent Checklist |
| `title` | VARCHAR(255) | NOT NULL | Item description |
| `is_completed` | TINYINT(1) | NOT NULL, DEFAULT 0 | Completion status |
| `position` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Ordering index |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

### 1.17 `task_attachments` (Phase 3)
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Attachment ID |
| `task_id` | BIGINT UNSIGNED | NOT NULL, FK -> `tasks(id)` ON DELETE CASCADE | Parent Task |
| `user_id` | BIGINT UNSIGNED | NULLABLE, FK -> `users(id)` ON DELETE SET NULL | Uploader reference |
| `file_name` | VARCHAR(255) | NOT NULL | Original filename |
| `file_path` | VARCHAR(255) | NOT NULL | Storage path on disk |
| `file_size` | BIGINT UNSIGNED | NOT NULL | File size in bytes |
| `mime_type` | VARCHAR(128) | NOT NULL | MIME content type |
| `created_at` / `updated_at` | TIMESTAMP | NULLABLE | Timestamps |

---

## 2. Planned Schema Extensions (Upcoming Phases 4 - 6)

### Phase 4 — Collaboration & Activity
- `task_comments`: `id`, `task_id` (FK), `user_id` (FK), `parent_id` (FK nullable), `content`, `created_at`, `updated_at`
- `activity_logs`: `id`, `workspace_id` (FK), `board_id` (FK), `task_id` (FK nullable), `user_id` (FK), `action`, `details` (JSON), `created_at`

### Phase 5 — Real-Time Notifications
- `notifications`: standard Laravel notification table (`id` UUID, `type`, `notifiable_type`, `notifiable_id`, `data` JSON, `read_at`, `created_at`)
- `notification_preferences`: `id`, `user_id` (FK), `channel`, `is_enabled`
