<div align="center">

# 🚀 Mahami | مهامي

### Plan Together. Work Smarter. Get Things Done

**A Full-Stack Project & Task Management Platform**

A modern Kanban-based platform inspired by Trello, built to help users organize boards, manage tasks, track progress, and work more efficiently through a clean and responsive interface.

**Arabic & English · RTL & LTR · Dark & Light Mode**

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Vite](https://img.shields.io/badge/Vite-Build_Tool-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vite.dev/)

</div>

---

## 📌 Table of Contents

- [About the Project](#-about-the-project)
- [Key Features](#-key-features)
- [Technology Stack](#-technology-stack)
- [Architecture & Design Patterns](#-architecture--design-patterns)
- [Project Structure](#-project-structure)
- [Getting Started](#-getting-started)
- [Environment Configuration](#-environment-configuration)
- [Running Tests](#-running-tests)
- [API Endpoints](#-api-endpoints)
- [Security](#-security)
- [License](#-license)

---

## 🎯 About the Project

**Mahami (مهامي)** is a full-stack project and task management application inspired by modern Kanban productivity tools.

The platform provides an organized workspace for managing boards, lists, and tasks, allowing users to plan their work, track progress, and keep their projects structured in one place.

The system consists of two applications:

- **Mahami API:** A RESTful backend built with Laravel 12.
- **Mahami Frontend:** A single-page application built with Vue.js 3.

The backend handles authentication, business logic, data persistence, validation, and authorization. The frontend provides an interactive interface for managing boards and tasks.

Mahami emphasizes maintainable code, secure resource access, clear separation of responsibilities, and a responsive user experience.

## ✨ Key Features

### 🔐 1. Authentication & Security

- User registration and login.
- Laravel Sanctum authentication with session and bearer-token support.
- Protected API routes.
- Server-side validation using Laravel Form Requests.
- Resource-level authorization using Laravel Policies.
- Login rate limiting to help protect against brute-force attempts.
- Ownership and access checks for boards, lists, and tasks.
- Validation of cross-board task movements.

### 📋 2. Kanban Board Management

- Create, view, update, and delete boards.
- Organize work using a visual Kanban interface.
- Automatically create the default **To Do**, **In Progress**, and **Done** lists when a board is created.
- Create default lists within a database transaction.
- Maintain relationships between boards, lists, and tasks.
- Cascade deletion of related lists and tasks when a board is deleted.

### 🗂️ 3. Lists & Columns Management

- Create custom lists within boards.
- Rename and delete lists.
- Reorder columns using drag-and-drop interactions.
- Maintain list ordering through position indexing.
- Display task counts for individual columns.
- Keep positions consistent when items are reordered or deleted.

### ✅ 4. Task Management

- Create, view, update, and delete tasks.
- Assign task priorities: Low, Medium, and High.
- Set optional due dates.
- View detailed task information.
- Display priority indicators and overdue-date information.
- Organize tasks into their corresponding lists.
- Maintain task ordering within each list.

### 🖱️ 5. Interactive Drag & Drop

- Reorder tasks within the same column.
- Move tasks between different columns.
- Update task positions through the API.
- Recalculate positions after moving or deleting tasks.
- Prevent unauthorized or invalid cross-board movements.
- Provide an interactive Kanban workflow.

### 🌐 6. Localization & User Experience

- **Arabic and English:** Switch between languages without reloading the page.
- **RTL and LTR:** Support right-to-left and left-to-right layouts.
- **Dark and Light Themes:** Switch between visual themes.
- **Responsive Design:** Adapt the interface to desktop, tablet, and mobile screens.
- **Persistent Preferences:** Save theme and language preferences locally.
- **Reusable Components:** Maintain a consistent interface through shared UI components.
- **Toast Notifications:** Display contextual feedback to users.

---

## 🧰 Technology Stack

| Layer                | Technology                                  |
| -------------------- | ------------------------------------------- |
| Backend              | PHP 8.2+                                    |
| Backend Framework    | Laravel 12                                  |
| API Architecture     | RESTful JSON API                            |
| Authentication       | Laravel Sanctum                             |
| Database             | MySQL 8.0+                                  |
| ORM                  | Laravel Eloquent                            |
| Architecture Pattern | Repository Pattern                          |
| Request Validation   | Laravel Form Requests                       |
| Authorization        | Laravel Policies                            |
| API Responses        | Laravel API Resources                       |
| Frontend Framework   | Vue.js 3                                    |
| Component Syntax     | Vue Single-File Components, Composition API |
| State Management     | Pinia                                       |
| Client-Side Routing  | Vue Router                                  |
| Styling              | Tailwind CSS v4                             |
| Build Tool           | Vite                                        |
| HTTP Client          | Axios                                       |
| Drag & Drop          | SortableJS                                  |
| Backend Testing      | PHPUnit / Laravel Test Runner               |
| Frontend Testing     | Vitest and Vue Test Utils                   |

---

## 🏗️ Architecture & Design Patterns

Mahami separates the frontend application from the backend API to maintain a clear separation of concerns.

```text
                     MAHAMI SYSTEM
                           |
              +------------+------------+
              |                         |
       Mahami Frontend              Mahami API
          Vue.js 3                  Laravel 12
              |                         |
              |      RESTful JSON       |
              +-------------------------+
                                        |
                                  Application Logic
                                        |
                                  Repository Layer
                                        |
                                  Eloquent ORM
                                        |
                                   MySQL Database
```

### Backend Architecture

The Laravel application uses focused components to organize HTTP handling, business logic, data access, and authorization.

| Component             | Responsibility                                |
| --------------------- | --------------------------------------------- |
| Controllers           | Handle HTTP requests and responses            |
| Form Requests         | Validate incoming request data                |
| Models                | Represent database entities and relationships |
| Repositories          | Encapsulate data-access operations            |
| Repository Contracts  | Define repository interfaces                  |
| Eloquent Repositories | Implement database operations                 |
| Policies              | Enforce resource authorization                |
| API Resources         | Structure API responses                       |
| Actions               | Encapsulate focused business operations       |

### Frontend Architecture

The Vue.js application separates interface components, pages, routing, state, and HTTP communication.

| Component    | Responsibility                          |
| ------------ | --------------------------------------- |
| Components   | Reusable interface elements             |
| Pages        | Application screens                     |
| Layouts      | Shared page structure                   |
| Composables  | Reusable reactive logic                 |
| Stores       | Shared application state using Pinia    |
| Router       | Navigation and route guards             |
| Services     | HTTP communication with the backend     |
| Localization | Arabic and English translations         |
| Tests        | Verify frontend behavior and components |

---

## 📂 Project Structure

```text
Mahami_System/
│
├── Mahami-API/
│   ├── app/
│   │   ├── Actions/
│   │   │   └── Boards/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/
│   │   │   ├── Requests/
│   │   │   └── Resources/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Repositories/
│   │       ├── Contracts/
│   │       └── Eloquent/
│   ├── database/
│   │   ├── factories/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   ├── tests/
│   ├── .env.example
│   ├── artisan
│   ├── composer.json
│   └── phpunit.xml
│
└── Mahami-Frontend/
    ├── src/
    │   ├── components/
    │   │   ├── boards/
    │   │   ├── common/
    │   │   ├── lists/
    │   │   └── tasks/
    │   ├── composables/
    │   ├── i18n/
    │   ├── layouts/
    │   ├── pages/
    │   ├── router/
    │   ├── services/
    │   ├── stores/
    │   └── __tests__/
    ├── .env.example
    ├── package.json
    └── vite.config.js
```

---

## ⚙️ Getting Started

Follow these instructions to run Mahami in a local development environment.

### Prerequisites

Install the following tools:

- PHP 8.2 or newer.
- Composer.
- MySQL 8.0 or newer.
- Node.js 18 or newer.
- npm.
- Git.

### 1. Clone the Repository

```bash
git clone YOUR_REPOSITORY_URL
cd Mahami_System
```

Replace `YOUR_REPOSITORY_URL` with your actual GitHub repository URL.

### 2. Configure the Backend

Navigate to the backend directory:

```bash
cd Mahami-API
```

Install the dependencies:

```bash
composer install
```

Create the environment configuration file.

**Windows Command Prompt:**

```bat
copy .env.example .env
```

**Linux / macOS:**

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

### 3. Configure the Database

Create a MySQL database for Mahami, then configure the database connection in the backend `.env` file.

Example configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Replace the example values with your actual local database credentials.

Run the migrations and seeders:

```bash
php artisan migrate --seed
```

### 4. Configure Authentication

Laravel Sanctum is used for API authentication.

Configure the required environment variables according to the authentication flow and local frontend address.

For a local frontend running at `localhost:5173`, the configuration may include:

```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
SESSION_DOMAIN=localhost
```

Ensure the backend CORS configuration, stateful domains, session settings, and frontend origin are consistent with your environment.

### 5. Start the Backend API

From the `Mahami-API` directory:

```bash
php artisan serve
```

The default local development address is:

`http://127.0.0.1:8000`

### 6. Configure the Frontend

Open another terminal from the repository root:

```bash
cd Mahami-Frontend
```

Install the frontend dependencies:

```bash
npm install
```

Configure the frontend environment file using the variable expected by the application.

For example, if the application uses `VITE_API_BASE_URL`:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000
```

### 7. Start the Frontend

Run the development server:

```bash
npm run dev
```

Vite displays the frontend address in the terminal. The default is commonly:

`http://localhost:5173`

Keep the backend and frontend development servers running while using the local application.

### 8. Build for Production

From the `Mahami-Frontend` directory:

```bash
npm run build
```

Vite generates the production frontend assets in the configured build directory.

---

## 🧪 Running Tests

Mahami includes backend and frontend tests.

### Backend Tests

Navigate to the backend directory:

```bash
cd Mahami-API
php artisan test
```

The backend test suite covers areas including:

- Authentication and token handling.
- Registration and login rate limiting.
- Board creation and management.
- List operations and reordering.
- Task creation, updating, deletion, and movement.
- Authorization policies.
- Repository bindings and position calculations.
- Database constraints and cascade behavior.

### Frontend Tests

Navigate to the frontend directory:

```bash
cd Mahami-Frontend
npm test
```

The frontend test suite covers areas including:

- Theme and language preferences.
- RTL/LTR behavior.
- Localization and translation fallback.
- Toast notification behavior.
- Shared component rendering and interactions.
- Task component behavior.

---

## 📡 API Endpoints

The following table summarizes the core authentication, board, list, and task endpoints.

All paths are relative to the API base URL. Protected endpoints require authentication.

| Method   | Endpoint                            | Description                             | Authentication |
| -------- | ----------------------------------- | --------------------------------------- | -------------- |
| `POST`   | `/api/auth/register`                | Register a user                         | No             |
| `POST`   | `/api/auth/login`                   | Authenticate a user                     | No             |
| `POST`   | `/api/auth/logout`                  | Log out and revoke the current token    | Yes            |
| `GET`    | `/api/auth/me`                      | Get the authenticated user              | Yes            |
| `GET`    | `/api/boards`                       | List the user's boards                  | Yes            |
| `POST`   | `/api/boards`                       | Create a board                          | Yes            |
| `GET`    | `/api/boards/{board}`               | Get board details                       | Yes            |
| `PUT`    | `/api/boards/{board}`               | Update a board                          | Yes            |
| `DELETE` | `/api/boards/{board}`               | Delete a board                          | Yes            |
| `POST`   | `/api/boards/{board}/lists`         | Create a list                           | Yes            |
| `PUT`    | `/api/lists/{list}`                 | Update a list                           | Yes            |
| `DELETE` | `/api/lists/{list}`                 | Delete a list                           | Yes            |
| `POST`   | `/api/boards/{board}/lists/reorder` | Reorder board lists                     | Yes            |
| `POST`   | `/api/lists/{list}/tasks`           | Create a task in a list                 | Yes            |
| `GET`    | `/api/tasks/{task}`                 | Get task details                        | Yes            |
| `PUT`    | `/api/tasks/{task}`                 | Update a task                           | Yes            |
| `DELETE` | `/api/tasks/{task}`                 | Delete a task                           | Yes            |
| `POST`   | `/api/tasks/{task}/move`            | Move a task to another list or position | Yes            |
| `POST`   | `/api/lists/{list}/tasks/reorder`   | Reorder tasks within a list             | Yes            |

---

## 🔒 Security

Mahami uses Laravel's built-in security features and application-level authorization to protect user data and application resources.

Security measures include:

- Sanctum-based authentication.
- Validation of incoming request data.
- Authorization policies for protected resources.
- Login rate limiting.
- Ownership checks for boards, lists, and tasks.
- Database constraints and transactional operations where required.
- Protection against invalid cross-board task movements.

### Environment & Credentials

- Never commit `.env` files containing real credentials.
- Keep database passwords, application keys, and private tokens out of public repositories.
- Use `.env.example` for documenting required environment variables without exposing their values.
- Configure production environment variables separately from local development settings.

---

## 📄 License

No license has been specified for this project.

Until a license is added, users should not assume they have permission to redistribute, modify, or reuse the project. The appropriate license should reflect the project owner's intended terms.

---

<div align="center">

### Mahami — مهامي

**Bring your team's work together, one task at a time.**

_Plan better. Collaborate smarter. Achieve more._

</div>

