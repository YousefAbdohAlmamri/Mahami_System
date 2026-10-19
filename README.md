# Mini Trello — Full-Stack Kanban Task Management

A modern, production-grade Kanban-based task and project management web application inspired by Trello. Built with **Laravel 12 REST API** (PHP 8.2), **MySQL**, **Repository Pattern**, **Sanctum Authentication**, and **Vue 3** (Vite, Pinia, Vue Router, Tailwind CSS v4, Sortablejs).

---

## 🌟 Key Features

### 1. Authentication & Security
- **Sanctum SPA & Token Auth**: Session/Bearer token authentication with protected routes.
- **Form Request Validation**: Robust validation for registration, login, board, list, and task operations.
- **Resource Authorization**: Granular Laravel Policies ensuring users only access and modify their own boards, columns, and tasks.
- **Rate Limiting**: Built-in throttle protection against brute-force login attempts (5 requests/min).
- **Cross-Board Move Protection**: Strict policy and database checks preventing moving cards between lists owned by different boards or users.

### 2. Boards Management
- Create, view, edit, and delete Kanban boards.
- **Atomic Creation**: Automatically provisions three default columns (`To Do`, `In Progress`, `Done`) inside a single database transaction (`DB::transaction`).
- Cascade deletions for lists and tasks upon board deletion.

### 3. Lists / Columns Management
- Create custom columns, update titles inline or via modal, and delete columns.
- **Drag-and-Drop Reordering**: Column ordering powered by Sortablejs with continuous position indexing.
- Real-time task count badge per column.

### 4. Tasks Management & Drag-and-Drop
- Create, edit, and delete tasks with priority (`low`, `medium`, `high`) and optional date-only due date (`YYYY-MM-DD`).
- **Interactive Drag-and-Drop**:
  - **Intra-column reordering**: Smooth reordering within the same list.
  - **Inter-column movement**: Dragging cards across columns updates the parent list and position instantly.
- Contiguous position compaction on deletion to eliminate index gaps.
- Full task detail view modal with overdue date indicators and priority badges.

### 5. UI Preferences & Localization
- **Bi-directional Support**: Full **RTL** (Right-to-Left) and **LTR** (Left-to-Right) layout support with CSS logical properties.
- **Bilingual Interface**: Native Arabic (**العربية**) and English (**English**) support without page reloads.
- **Theming**: Dark Mode and Light Mode with instant toggle and `localStorage` persistence.
- **Responsive Design**: Designed for desktop, tablet, and mobile screens.

---

## 🏗️ Architecture & Design Patterns

```
Mahami_System/
├── Mahami-API/                 # Laravel 12 Backend API
│   ├── app/
│   │   ├── Actions/Boards/     # Single-responsibility actions (CreateBoardAction)
│   │   ├── Http/Controllers/Api # REST Controllers (Auth, Board, BoardList, Task)
│   │   ├── Http/Requests/      # FormRequests with custom validation
│   │   ├── Http/Resources/     # API Resource transformers
│   │   ├── Models/             # Eloquent models (User, Board, BoardList, Task)
│   │   ├── Policies/           # Authorization policies
│   │   └── Repositories/       # Repository Pattern
│   │       ├── Contracts/      # Interfaces (Board, BoardList, Task)
│   │       └── Eloquent/       # Eloquent implementations
│   └── tests/                  # Unit & Feature test suites (58 tests)
│
└── Mahami-Frontend/            # Vue 3 Single Page Application
    ├── src/
    │   ├── components/         # Reusable UI components
    │   │   ├── boards/         # BoardCard, BoardForm
    │   │   ├── common/         # AppButton, AppInput, AppModal, ConfirmDialog, etc.
    │   │   ├── lists/          # BoardColumn, ColumnHeader, ColumnForm
    │   │   └── tasks/          # TaskCard, TaskForm, TaskDetailsModal
    │   ├── composables/        # useI18n, useToast
    │   ├── i18n/               # Arabic and English translation dictionaries
    │   ├── layouts/            # AuthLayout, MainLayout
    │   ├── pages/              # LoginPage, RegisterPage, BoardsPage, BoardDetailsPage
    │   ├── router/             # Vue Router with navigation guards
    │   ├── services/           # Axios HTTP service layer
    │   ├── stores/             # Pinia stores (auth, board, preferences, toast)
    │   └── __tests__/          # Vitest test suite (14 tests)
```

---

## 🚀 Getting Started

### Prerequisites
- **PHP** >= 8.2
- **Composer** >= 2.0
- **MySQL** >= 8.0
- **Node.js** >= 18.0 & **npm**

---

### Backend Setup (Mahami-API)

1. Navigate to the API directory:
   ```bash
   cd Mahami-API
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Ensure `.env` contains your MySQL credentials and Sanctum configuration:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=mini_trello
   DB_USERNAME=root
   DB_PASSWORD=

   SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
   SESSION_DOMAIN=localhost
   ```

4. Run migrations and database seeder:
   ```bash
   php artisan migrate --seed
   ```

5. Start the backend server:
   ```bash
   php artisan serve
   ```
   The API will be available at `http://localhost:8000`.

---

### Frontend Setup (Mahami-Frontend)

1. Navigate to the frontend directory:
   ```bash
   cd Mahami-Frontend
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Ensure `.env` is configured:
   ```env
   VITE_API_BASE_URL=http://localhost:8000
   ```

4. Run development server:
   ```bash
   npm run dev
   ```
   The frontend will be available at `http://localhost:5173`.

5. Build for production:
   ```bash
   npm run build
   ```

---

## 🔑 Demo Credentials

The database seeder provisions the following accounts:

| Role | Email | Password |
|---|---|---|
| **Demo User** (contains populated boards) | `demo@example.com` | `password123` |
| **Isolation Test User** | `other@example.com` | `password123` |

---

## 🧪 Testing

### Backend Tests (PHPUnit / Pest)
Run all 58 backend unit and feature tests:
```bash
cd Mahami-API
php artisan test
```

Test coverage includes:
- `RepositoryPatternTest`: Dependency injection container bindings and contiguous position calculations.
- `AuthTest`: Registration, login, throttling, logout, and token authentication.
- `BoardApiTest`: CRUD, authorization policies, atomic column provisioning.
- `BoardListApiTest`: Column operations, validation, cross-board reordering prevention.
- `TaskApiTest`: Task lifecycle, inter-column move, contiguous compaction, cross-board denial.
- `PolicyAuthorizationTest` & `DatabaseConstraintsTest`: Foreign keys and cascade verification.

### Frontend Tests (Vitest)
Run all 14 frontend tests:
```bash
cd Mahami-Frontend
npm test
```

Test coverage includes:
- Preferences store (theme and locale switching, persistence, RTL/LTR computation).
- Toast notification queueing and auto-dismissal.
- Custom i18n composable with parameter interpolation and fallback.
- Common component rendering and interaction (`AppButton`, `AppInput`, `TaskCard`).

---

## 📡 API Endpoints Summary

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `POST` | `/api/auth/register` | Register new user | No |
| `POST` | `/api/auth/login` | Login user & receive token | No |
| `POST` | `/api/auth/logout` | Revoke current token | Yes |
| `GET` | `/api/auth/me` | Fetch authenticated user profile | Yes |
| `GET` | `/api/boards` | List user boards | Yes |
| `POST` | `/api/boards` | Create board with default columns | Yes |
| `GET` | `/api/boards/{board}` | Get board with lists & tasks | Yes |
| `PUT` | `/api/boards/{board}` | Update board title/description | Yes |
| `DELETE` | `/api/boards/{board}` | Delete board | Yes |
| `POST` | `/api/boards/{board}/lists` | Add column to board | Yes |
| `PUT` | `/api/lists/{list}` | Update column title | Yes |
| `DELETE` | `/api/lists/{list}` | Delete column | Yes |
| `POST` | `/api/boards/{board}/lists/reorder`| Reorder columns | Yes |
| `POST` | `/api/lists/{list}/tasks` | Create task in column | Yes |
| `GET` | `/api/tasks/{task}` | Get task details | Yes |
| `PUT` | `/api/tasks/{task}` | Update task | Yes |
| `DELETE` | `/api/tasks/{task}` | Delete task | Yes |
| `POST` | `/api/tasks/{task}/move` | Move task to list and position | Yes |
| `POST` | `/api/lists/{list}/tasks/reorder` | Reorder tasks in list | Yes |

