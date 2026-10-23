<div align="center">Mahami | مهامي

Plan Together. Work Smarter. Get Things Done.

A Modern Project & Team Management Platform

Mahami is a collaborative project and team management platform designed to help individuals and teams organize projects, manage tasks, coordinate responsibilities, and track progress in one place.

Built with Laravel and Vue.js, Mahami brings together task management, Kanban boards, team collaboration, notifications, and a flexible user experience to make teamwork more organized and productive.

Arabic & English · RTL & LTR · Dark & Light Mode

"Laravel" (<https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white>)
"PHP" (<https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white>)
"Vue.js" (<https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white>)
"MySQL" (<https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white>)

</div>---

📌 Table of Contents

- "About the Project" (#-about-the-project)
- "Why Mahami?" (#-why-mahami)
- "Key Features" (#-key-features)
- "Technology Stack" (#-technology-stack)
- "System Architecture" (#-system-architecture)
- "Project Structure" (#-project-structure)
- "Getting Started" (#-getting-started)
- "Running Tests" (#-running-tests)
- "Security" (#-security)
- "Future Improvements" (#-future-improvements)
- "License" (#-license)

🚀 About the Project

Mahami (مهامي) is a full-stack project and team management application inspired by modern Kanban-based productivity tools.

It provides a centralized workspace where users can organize projects, create teams, manage boards, assign tasks, collaborate with teammates, and monitor work progress.

The system consists of two main applications:

- Mahami API: A RESTful backend powered by Laravel.
- Mahami Frontend: A responsive web application built with Vue.js.

Mahami focuses on maintainable architecture, secure access control, modular development, and an intuitive user experience.

💡 Why Mahami?

Managing projects across multiple tools can make collaboration difficult and reduce visibility into team progress.

Mahami aims to simplify teamwork by bringing essential project management capabilities into one organized platform.

- Organize work: Manage projects, boards, lists, and tasks in one place.
- Improve collaboration: Coordinate responsibilities and communicate with team members.
- Track progress: Monitor task statuses, priorities, deadlines, and activity.
- Manage access: Organize team participation and permissions.
- Work comfortably: Switch between Arabic and English, RTL and LTR layouts, and dark and light themes.

✨ Key Features

📋 Project & Task Management

- Create and manage Kanban boards.
- Organize tasks into lists and cards.
- Move tasks between lists using drag-and-drop interactions.
- Assign tasks to team members.
- Track priorities, deadlines, and task progress.
- Organize tasks using labels and checklists.
- Attach files and supporting documents to tasks.

👥 Workspaces & Team Management

- Create and manage workspaces.
- Organize members and teams.
- Manage team participation in projects and boards.
- Assign responsibilities to team members.
- Apply role-based permissions to protected resources.

💬 Team Collaboration

- Add comments to tasks.
- Keep discussions connected to the relevant work.
- Review task and project activity.
- Notify team members about relevant updates.
- Support collaboration workflows within shared workspaces.

🔔 Notifications & Activity Tracking

- Keep users informed about relevant changes.
- Track important project and task activities.
- Support background jobs and notification workflows where configured.
- Improve visibility into team operations.

🌐 Modern User Experience

- Arabic and English language support.
- Right-to-left (RTL) and left-to-right (LTR) layouts.
- Dark and light themes.
- Responsive layouts for desktop, tablet, and mobile screens.
- Reusable Vue.js components and organized application state.

🔐 Security & Maintainability

- Laravel Sanctum authentication.
- Server-side request validation.
- Policy-based authorization and access control.
- Repository Pattern for organizing data access.
- Database migrations and structured data management.
- Backend and frontend testing.

Feature availability depends on the current implementation and environment configuration.

🧰 Technology Stack

Component| Technologies
Backend| PHP 8.2+, Laravel 12
API| RESTful JSON API
Authentication| Laravel Sanctum
Database| MySQL
ORM| Laravel Eloquent
Architecture| Repository Pattern, Policies, Form Requests
Frontend| Vue.js 3, Composition API
State Management| Pinia
Routing| Vue Router
Styling| Tailwind CSS
Build Tool| Vite
HTTP Client| Axios
Drag & Drop| SortableJS
Backend Testing| PHPUnit
Frontend Testing| Vitest, Vue Test Utils

🏗️ System Architecture

Mahami separates the backend API from the frontend application to improve maintainability, testing, and independent development.

                   MAHAMI SYSTEM
                         |
              +----------+----------+
              |                     |
       Mahami Frontend         Mahami API
          Vue.js 3              Laravel 12
              |                     |
              |    RESTful API      |
              +---------------------+
                                    |
                               Eloquent ORM
                                    |
                              MySQL Database

Backend

The Laravel backend provides API endpoints, authentication, validation, business logic, authorization, and database access.

Its main architectural elements include:

- Controllers for handling HTTP requests.
- Form Requests for input validation.
- Models for database entities.
- Repositories for data access.
- Policies for authorization.
- API Resources for consistent API responses.
- Application actions for focused business operations.

Frontend

The Vue.js frontend provides the interactive user interface and communicates with the backend through HTTP API requests.

Its main elements include:

- Reusable UI components.
- Pages and layouts.
- API services.
- Pinia stores for state management.
- Vue Router for navigation.
- Shared composables and utilities.
- Localization and theme management.

📂 Project Structure

Mahami_System/
│
├── Mahami-API/
│ ├── app/
│ │ ├── Actions/
│ │ ├── Http/
│ │ ├── Models/
│ │ ├── Policies/
│ │ └── Repositories/
│ ├── database/
│ ├── routes/
│ ├── tests/
│ ├── .env.example
│ └── composer.json
│
└── Mahami-Frontend/
├── src/
│ ├── components/
│ ├── composables/
│ ├── i18n/
│ ├── pages/
│ ├── router/
│ ├── services/
│ ├── stores/
│ └── **tests**/
├── package.json
└── vite.config.js

The directory structure may vary depending on the current repository version.

⚙️ Getting Started

Follow these steps to run Mahami locally.

Prerequisites

Install the following tools:

- PHP 8.2 or newer.
- Composer.
- MySQL 8.x or a compatible database.
- Node.js and npm.
- Git.

1. Clone the Repository

git clone YOUR_REPOSITORY_URL
cd Mahami_System

Replace "YOUR_REPOSITORY_URL" with the actual URL of your GitHub repository.

1. Set Up the Backend

Navigate to the backend directory:

cd Mahami-API

Install the PHP dependencies:

composer install

Create the environment file.

Windows:

copy .env.example .env

Linux / macOS:

cp .env.example .env

Generate the application key:

php artisan key:generate

Configure your database credentials and required environment variables in ".env".

Run the database migrations:

php artisan migrate

Start the backend server:

php artisan serve

The default local address is:

"<http://127.0.0.1:8000>"

1. Set Up the Frontend

Open another terminal from the repository root:

cd Mahami-Frontend

Install the frontend dependencies:

npm install

Configure the API base URL using the environment variable expected by your frontend. For example, if it uses "VITE_API_BASE_URL", create a ".env" file containing:

VITE_API_BASE_URL=<http://localhost:....>

Start the frontend development server:

npm run dev

Vite will display the local frontend address in the terminal, commonly:

"<http://localhost:....>"

1. Authentication Configuration

Depending on the authentication flow implemented in the application, Laravel Sanctum may require configuration for:

- CORS settings.
- Stateful frontend domains.
- Session cookie domains.
- API and frontend origins.
- CSRF cookie handling.

Configure these settings according to your local environment and deployment setup.

🧪 Running Tests

Backend Tests

From the "Mahami-API" directory:

php artisan test

Frontend Tests

From the "Mahami-Frontend" directory:

npm test

Build the Frontend for Production

From the frontend directory:

npm run build

Run the relevant tests and production build before deploying changes.

🔒 Security

Security is an important part of Mahami's development.

- Never commit ".env" files or application secrets.
- Never publish database passwords, API tokens, or private credentials.
- Keep dependencies updated.
- Validate incoming data on the server.
- Enforce authorization checks for protected resources.
- Configure CORS, sessions, and cookies appropriately for production.
- Review ".gitignore" before pushing changes to GitHub.

If you discover a security vulnerability, avoid publishing sensitive exploit details in a public issue. Contact the project maintainer privately.

🤝 Contributing

Contributions, suggestions, and improvements are welcome.

1. Fork the repository.
2. Create a feature branch.
3. Implement your changes.
4. Run the relevant tests.
5. Submit a pull request describing your changes.

Please keep contributions focused and follow the existing project conventions.

📄 License

No license has been specified for this project yet.

Until a license is added to the repository, users should not assume they have permission to redistribute, modify, or reuse the code. Add an appropriate "LICENSE" file when the licensing terms have been decided.

---

<div align="center">Mahami — مهامي

Bring your team's work together, one task at a time.

Plan better. Collaborate smarter. Achieve more.

</div>
