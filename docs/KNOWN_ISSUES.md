# Mahami Known Issues & Environment Constraints

## 1. Network & Local Environment Binding on Windows
- **Issue:** When running `php artisan serve` on Windows, Laravel may bind to IPv4 (`127.0.0.1:8000`) while Node/Vite resolves `localhost` to IPv6 `::1`.
- **Status:** Resolved in `Mahami-Frontend/src/services/http.js` and `vite.config.js` by explicitly pointing `VITE_API_BASE_URL` to `http://127.0.0.1:8000`.

## 2. Real-Time Broadcasting Engine (Phase 5 Consideration)
- **Constraint:** Windows development environments and shared hosting servers often lack native support for running long-running daemon processes like Laravel Reverb via CLI without process supervisors.
- **Mitigation Strategy:** Evaluate Pusher or Ably as a turnkey fallback driver alongside Laravel Reverb so WebSocket notifications function seamlessly on both local and production environments.

## 3. Email Delivery Setup (Phase 1 & 5 Consideration)
- **Constraint:** Invitation emails and notification reminders require active SMTP/Mailgun/SES credentials in `.env`.
- **Mitigation Strategy:** Provide a `log` or `array` driver default during testing/local development with documented environment variables, allowing invitation tokens to be retrieved directly via API response and UI link copying.
