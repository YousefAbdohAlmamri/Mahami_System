# Mahami (مهامي) — Project Overview

## 1. Vision & Executive Summary
**Mahami** is a collaborative, multi-tenant project and team management SaaS platform inspired by Kanban and modern workflow methodologies. Originally built as an MVP task board, Mahami is evolving into a comprehensive enterprise-ready solution supporting multiple workspaces, cross-functional teams, fine-grained role-based access controls (RBAC), milestones, task dependencies, rich collaboration (comments, mentions, attachments), real-time notifications, and data-driven reporting dashboards.

---

## 2. Technical Stack & Runtime Environment

### Backend
- **Framework:** Laravel 12.x
- **Language / Runtime:** PHP 8.2.4 (cli) (ZTS x64)
- **Database Engine:** MySQL 8.x (Default connection: `mysql`, with automated cascade rules and indexes)
- **API Style:** RESTful JSON API with strict Status Codes & FormRequests
- **Authentication:** Laravel Sanctum (Token-based SPA via `Bearer` token in `Authorization` header)
- **Architecture Pattern:** Repository Pattern (Strict decoupling via interfaces and Eloquent implementations bound in Laravel Service Container)
- **Authorization & Security:** Laravel Policies (`BoardPolicy`, `BoardListPolicy`, `TaskPolicy`), contiguous position recompacting, strict IDOR prevention, and cross-board tenant isolation.
- **Testing Engine:** PHPUnit 11.5 / Pest ready (58 active unit and feature tests, 237 assertions).

### Frontend
- **Framework:** Vue 3 (Composition API `<script setup>`)
- **Build Tool / Bundler:** Vite 8.3
- **State Management:** Pinia 4.x
- **Client Routing:** Vue Router 5.x with navigation guards (`requiresAuth`, `guestOnly`)
- **Styling & CSS:** Tailwind CSS v4 (`@tailwindcss/vite` v4.3) with custom theme variant `@custom-variant dark (&:where(.dark, .dark *));`
- **Drag & Drop:** SortableJS 1.15
- **HTTP Client:** Axios 1.20 with request/response interceptors (Bearer token injection, 401 redirect, 422 error normalization)
- **Internationalization (i18n):** Custom reactive composable (`useI18n`) with full Arabic & English dictionaries and automated RTL/LTR directional layout switching.
- **Theme Support:** Dark & Light modes with persistent `localStorage`, OS preference detection (`prefers-color-scheme`), and instant `<head>` initialization script to eliminate white flash (FOUC).
- **Testing Engine:** Vitest 5.x + JSDOM + Vue Test Utils (14 unit/component tests).

---

## 3. High-Level Modular Structure
```
Mahami_System/
├── docs/                     # Comprehensive architecture and progress documentation
├── Mahami-API/               # Laravel 12 REST API Backend
│   ├── app/
│   │   ├── Http/Controllers/ # Thin API Controllers
│   │   ├── Http/Requests/    # Form Request validation rules
│   │   ├── Http/Resources/   # JSON API resource transformers
│   │   ├── Models/           # Eloquent Models (User, Board, BoardList, Task)
│   │   ├── Policies/         # Authorization policies
│   │   ├── Providers/        # Service Providers (RepositoryServiceProvider)
│   │   └── Repositories/     # Contracts & Eloquent Repository implementations
│   ├── database/
│   │   └── migrations/       # MySQL schema migrations
│   ├── routes/               # API route definitions (api.php)
│   └── tests/                # Unit and Feature PHPUnit test suites
└── Mahami-Frontend/          # Vue 3 SPA Client
    ├── src/
    │   ├── components/       # Common, Boards, Lists, Tasks UI components
    │   ├── composables/      # useI18n, useToast
    │   ├── i18n/             # ar.js, en.js dictionaries
    │   ├── layouts/          # AuthLayout, MainLayout
    │   ├── pages/            # BoardsPage, BoardDetailsPage, LoginPage, RegisterPage
    │   ├── router/           # Route definitions & guards
    │   ├── services/         # Axios API clients
    │   ├── stores/           # Pinia stores (auth, board, preferences, toast)
    │   └── style.css         # Tailwind v4 root styles & custom utilities
    └── index.html            # SPA entry point with zero-flicker theme script
```
