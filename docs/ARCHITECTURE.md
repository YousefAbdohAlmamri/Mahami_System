# Mahami Architecture & Design Principles

## 1. Architectural Philosophy
Mahami adheres strictly to **Clean Architecture**, **SOLID principles**, and the **Repository Pattern** to guarantee modularity, testability, and long-term maintainability without introducing unnecessary cognitive overhead.

```
                  ┌──────────────────────────────────────────────┐
                  │           Frontend (Vue 3 SPA)               │
                  │  Pinia Stores ──> API Services ──> Axios    │
                  └───────────────────────┬──────────────────────┘
                                          │ HTTPS / JSON (Bearer Token)
                                          ▼
                  ┌──────────────────────────────────────────────┐
                  │           Backend API (Laravel 12)           │
                  │                 Routes (api.php)             │
                  │                        │                     │
                  │         Middleware (Sanctum Auth)            │
                  │                        │                     │
                  │       Controllers (Thin Orchestrators)       │
                  │                        │                     │
                  │   FormRequests (Validation) + Policies (Auth)│
                  │                        │                     │
                  │             Repository Contracts             │
                  │                        │                     │
                  │        Eloquent Repository Implementations   │
                  │                        │                     │
                  │              MySQL 8 Database                │
                  └──────────────────────────────────────────────┘
```

---

## 2. Backend Design Patterns

### 2.1 Repository Pattern
Every domain entity encapsulates data access logic behind an interface:
- **`BoardRepositoryInterface`** ⟵ **`EloquentBoardRepository`**
  - Manages boards scoped to authenticated user, eager loads lists and ordered tasks.
  - Automatically provisions the 3 default Kanban columns (`To Do`, `In Progress`, `Done` / `للإنجاز`, `قيد التنفيذ`, `مكتملة`) inside a database transaction upon board creation.
- **`BoardListRepositoryInterface`** ⟵ **`EloquentBoardListRepository`**
  - Manages column position sorting, reordering via contiguous zero-based indexing inside transactions.
- **`TaskRepositoryInterface`** ⟵ **`EloquentTaskRepository`**
  - Handles intra-column reordering, inter-column moving, position normalization, and position recompaction upon deletion.

All bindings are registered in [`app/Providers/RepositoryServiceProvider.php`](file:///e:/yousef/informations/Mahami_System/Mahami-API/app/Providers/RepositoryServiceProvider.php).

### 2.2 Security & Multi-Tenant Authorization
- **Strict Policy Enforcement:**
  - `BoardPolicy`: `view`, `update`, `delete` verify `$user->id === $board->user_id`.
  - `BoardListPolicy`: enforces that the column belongs to a board owned by the user.
  - `TaskPolicy`: enforces task ownership and verifies cross-board restrictions (preventing a task from being moved to a column owned by another board or user).
- **Contiguous Positioning Logic:** Reordering transactions lock rows or calculate max contiguous positions ($0, 1, 2, \dots$) to prevent duplicate positions or gaps upon insertion/removal.
- **CSRF & CORS:** `api/*` routes are excluded from stateful session CSRF tokens in `bootstrap/app.php` to allow stateless Bearer token authentication via Axios.

---

## 3. Frontend Architecture

### 3.1 Component Hierarchy
- **Layouts:**
  - `AuthLayout`: Centered, backdrop glow container for Login & Registration screens.
  - `MainLayout`: Glassmorphic navigation header (`glass-nav`), language switcher, dark/light toggle, and user profile dropdown.
- **Pages (Views):**
  - `BoardsPage`: Grid view of all boards with real-time search filtering.
  - `BoardDetailsPage`: Horizontal Kanban view supporting drag-and-drop column and task sorting via `SortableJS`.
  - `LoginPage` & `RegisterPage`: Responsive auth pages with password visibility toggle and 1-click Demo credentials.
- **Common Atoms & Molecules:**
  - `AppButton`: Reusable button supporting `primary`, `secondary`, `danger`, and `ghost` variants with micro-interactions.
  - `AppInput`: Accessible inputs supporting text, email, password (with show/hide), date, and textarea.
  - `AppModal`: Transitioned accessible modal dialog (`teleport to body`).
  - `AppToast`: Toast notification banner stack.

### 3.2 State Management (Pinia)
- **`authStore`:** Manages user token in `localStorage`, handles `login`, `register`, `logout`, and tracks user profile state.
- **`boardStore`:** Handles CRUD for boards, lists, and tasks, performing optimistic client-side updates with server reconciliation on error.
- **`preferencesStore`:** Reactive preferences for `locale` (`ar`/`en`), `dir` (`rtl`/`ltr`), and `theme` (`light`/`dark`).
- **`toastStore`:** Dispatches auto-dismissing notifications (`success`, `error`, `warning`, `info`).
