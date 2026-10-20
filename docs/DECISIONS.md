# Mahami Architectural & Engineering Decisions (ADR)

## ADR-001: Maintain Repository Pattern with Explicit Contracts
- **Context:** The system needs to scale across workspaces, teams, advanced task features, and notifications.
- **Decision:** Retain and expand the existing Repository Pattern architecture located in `app/Repositories/Contracts` and `app/Repositories/Eloquent`. Every new domain aggregate (e.g. Workspace, Team, Comment, Attachment) will have a contract interface and an Eloquent repository registered in `RepositoryServiceProvider`.
- **Consequences:** Controllers remain thin and focused only on HTTP request handling; business logic and complex queries are encapsulated and easily mockable in tests.

## ADR-002: Sanctum Bearer Token Authentication for SPA
- **Context:** Choice between stateful session cookies (`web` guard) vs stateless Bearer tokens (`auth:sanctum`).
- **Decision:** Keep the active Bearer token mechanism stored in client `localStorage` under `mini_trello_token` and automatically transmitted via Axios interceptor.
- **Consequences:** Avoids cross-origin cookie issues during local development (Vite on port 5173 / API on port 8000), enables clean mobile and third-party API integration, and eliminates CSRF mismatch on stateless endpoints (`api/*`).

## ADR-003: Backward-Compatible Tenant Scoping for Workspaces
- **Context:** Existing users have `boards` owned directly via `user_id`. Introducing `workspaces` must not delete or break existing user boards.
- **Decision:** In Phase 1, `boards.workspace_id` will be introduced as a nullable foreign key. An automatic migration step will provision a "Default Workspace" for each existing user and link their existing boards to it.
- **Consequences:** 100% backward compatibility; zero data loss; smooth transition to workspace-scoped multi-tenancy.

## ADR-004: Zero-Flicker Pre-Paint Theme Initialization
- **Context:** Modern single-page applications suffer from FOUC (flash of white screen) when dark mode is loaded via JavaScript bundles.
- **Decision:** Embed a tiny, non-blocking inline `<script>` in `<head>` of `index.html` that inspects `localStorage` and `prefers-color-scheme` to immediately inject `.dark` and `data-theme="dark"` into `document.documentElement` before CSS parsing begins.
- **Consequences:** Instantaneous dark mode rendering with zero screen flashing on reload.

