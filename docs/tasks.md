# Omnest - tasks.md

Master roadmap. Supersedes the separate mobile / backend / landing lists.
Design source of truth: `specs.md`.

**Product:** Omnest, an Android parental control app. Screen time, app limits, remote lock, and **Knock** (the child asks for more time, the parent answers in one tap).
**Stack:** Kotlin + Jetpack Compose (child app) - vanilla PHP 8.2+ + MySQL (API, no framework) - Next.js + Tailwind (landing + parent dashboard) - FCM (push to the child phone, messaging only) + Pusher Channels (live updates for the parent dashboard) - Paystack (billing).
**Early market:** Nigeria (works on low-end Tecno / Infinix / itel phones, offline-first, data-light).

Legend: `[BE]` backend - `[AND]` Android child app - `[WEB]` landing/dashboard - `[ALL]` cross-cutting

---

## Phase 0 - Foundations (do first)
- [ ] `[ALL]` Lock name (Omnest) and check availability: domain, Play Store name, social handles, trademark search
- [ ] `[ALL]` Logo (nest/door mark + accent dot), app icon, favicon
- [x] `[ALL]` Implement design tokens from `specs.md` (Tailwind config, Compose theme)
- [ ] `[ALL]` Git repos + branching + issue board
- [x] `[BE]` Project structure: `public/index.php` front controller, `src/` (Controllers, Services, Repositories, Middleware), `config/`, `migrations/`; Composer + PSR-4 autoload
- [x] `[BE]` Small router (method + path + params, route groups, `/api/v1`), request/response helpers, standard JSON success/error format
- [x] `[BE]` PDO wrapper: prepared statements only, transactions, utf8mb4, env-based config (`vlucas/phpdotenv`)
- [x] `[BE]` Simple migration runner (numbered SQL files + `migrations` table)
- [x] `[BE]` Middleware pipeline: CORS, JSON body parsing, auth (parent), auth (device), rate limiter, error handler
- [x] `[BE]` Validation helper (required, type, length, enum) used on every endpoint
- [ ] `[BE]` Logging (Monolog), central exception handler, error tracking
- [x] `[BE]` PHPUnit tests, CI on push
- [x] `[AND]` Kotlin + Compose project (minSdk 26), Hilt, Retrofit/OkHttp, Room, DataStore, WorkManager
- [ ] `[AND]` FCM messaging only (Firebase project, `google-services.json`, no other Firebase SDKs), build variants (debug/release), signing config, CI build + lint
- [ ] `[BE]` Pusher Channels app + `pusher/pusher-php-server`; `[WEB]` `pusher-js` client; private channels per parent with a signed auth endpoint
- [x] `[WEB]` Next.js (TS) + Tailwind project, `next/font` (Bricolage Grotesque, DM Sans, JetBrains Mono), shared components
- [x] `[ALL]` Set up Phosphor icons on web and Android

## Phase 1 - Accounts and pairing (the spine)
- [x] `[BE]` Parent register/login: `password_hash` (Argon2id/bcrypt), opaque API tokens (random 32+ bytes, store only SHA-256 hash, expiry + revoke), email verification, password reset, rate limiting
- [x] `[BE]` Mailer via PHPMailer / provider API (Resend, Mailgun, SES)
- [x] `[BE]` Tables: users, children, devices
- [x] `[BE]` CRUD children (name, birth date / age tier: kid / preteen / teen, avatar)
- [x] `[BE]` Pairing: short-lived 6-digit code + QR payload, expiry, attempt rate limit
- [x] `[BE]` Pair endpoint issues a **per-device token** (never the parent's login); revoke/unpair
- [x] `[BE]` Store and refresh FCM tokens per device
- [x] `[WEB]` Dashboard auth pages (login, register, reset)
- [x] `[WEB]` Add child + "Pair a device" flow (shows code + QR)
- [ ] `[AND]` Welcome screen + pairing screen (6-box code input, QR scan)
- [ ] `[AND]` Store device token in EncryptedSharedPreferences
- [ ] `[AND]` Permission wizard, one per screen with plain-language reason: Usage Access, Overlay, Device Admin, Notifications, Battery optimization exemption, (Accessibility only if needed)
- [ ] `[AND]` OEM guides for autostart/background: Tecno (HiOS), Infinix (XOS), itel, Xiaomi, Samsung, Oppo/Vivo
- [ ] `[AND]` Detect missing permissions and re-prompt

## Phase 2 - Usage tracking and reports
- [ ] `[AND]` Read per-app usage (UsageStatsManager), compute daily total + per-app
- [ ] `[AND]` Store in Room; periodic WorkManager sync (batched, data-light, retry with backoff offline)
- [ ] `[AND]` Send installed-apps list; handle installs/uninstalls
- [ ] `[BE]` Idempotent usage ingest endpoint; tables: usage_daily, usage_app_daily, installed_apps
- [ ] `[BE]` Aggregations: daily/weekly totals, top apps; retention + cleanup job
- [ ] `[BE]` Parent endpoints for usage per child
- [ ] `[WEB]` Child overview: today's screen time, top apps
- [ ] `[WEB]` Reports: daily/weekly charts

## Phase 3 - Rules and enforcement
- [ ] `[BE]` Tables: rules, app_limits, schedules, blocked_apps, allowed_apps
- [ ] `[BE]` Parent endpoints to create/update rules; default presets per age tier
- [ ] `[BE]` Device endpoint: fetch rules with version/ETag; FCM "rules updated" data message
- [ ] `[WEB]` Rules editor: daily limit, per-app limits, blocked apps, schedules (bedtime, school, homework), presets
- [ ] `[AND]` Fetch and cache rules in Room (must enforce fully offline)
- [ ] `[AND]` Foreground service watching current app
- [ ] `[AND]` Enforce: daily limit, per-app limits, blocked apps, schedules, always-allowed apps (phone, messages, emergency)
- [ ] `[AND]` Apply age presets as default rule sets
- [ ] `[AND]` Block overlay screen when a limit is hit (see Knock screen in `specs.md`)

## Phase 4 - Knock (the hook)
- [ ] `[BE]` Table: time_requests (child, minutes, note, status, expires_at)
- [ ] `[BE]` Device endpoint to create a request; parent approve/deny endpoint
- [ ] `[BE]` Notify parent instantly (Pusher Channels event to the dashboard, web push / WhatsApp when the dashboard is closed), push decision back to the child device via FCM
- [ ] `[BE]` Rules: max requests per day, auto-expire pending, request history
- [ ] `[AND]` Knock button on blocked screen (15 / 30 / 60 min), optional short note
- [ ] `[AND]` States: waiting, approved (grant temp time + enforce expiry), denied (friendly message)
- [ ] `[AND]` Queue requests offline and send when connected; daily request cap
- [ ] `[AND]` Knock wiggle animation, respects reduced motion
- [ ] `[WEB]` Requests inbox: approve / deny in one tap, history

## Phase 5 - Remote lock and device health
- [ ] `[BE]` Table: device_commands (lock, unlock, sync now); parent send endpoint; ack + status
- [ ] `[BE]` FCM delivery with poll fallback; heartbeat endpoint and "last seen"
- [ ] `[BE]` Alerts when heartbeat stops or permissions are revoked
- [ ] `[AND]` Handle FCM lock/unlock (DevicePolicyManager.lockNow), persistent locked state, ack back
- [ ] `[AND]` Poll fallback if push is delayed
- [ ] `[AND]` Heartbeat every N minutes; BOOT_COMPLETED restart; WorkManager watchdog revives service
- [ ] `[AND]` Detect revoked permissions / Device Admin removed and alert parent
- [ ] `[AND]` Uninstall protection (Device Admin) + parent PIN to exit, change settings, uninstall
- [ ] `[AND]` Detect force-stop / cleared data where possible, alert on next launch
- [ ] `[WEB]` Lock / unlock button, device status (last seen, missing permissions)
- [ ] `[AND]` Test on low-end and Tecno / Infinix / itel devices (background kill behavior)

## Phase 6 - Child-facing app polish
- [ ] `[AND]` Home: time left today (time ring), current rules in plain language
- [ ] `[AND]` Request history (approved / denied)
- [ ] `[AND]` Teen mode: teen sees exactly what parent can see (transparency)
- [ ] `[AND]` Empty, loading, error states in every screen; dark mode
- [ ] `[AND]` Keep APK small (font subsetting, R8, resource shrinking); test on low-RAM devices
- [ ] `[AND]` Accessibility: 48 touch targets, font scaling to 130%, content descriptions

## Phase 7 - Landing page and waitlist (can start in parallel after Phase 0)
- [ ] `[WEB]` Copy: headline around "Limits that talk back", features, age tiers, privacy/trust, pricing, FAQ
- [ ] `[WEB]` Sections: navbar, hero + app mockup, problem/solution, **Knock demo** (interactive mockup of the blocked screen), how it works (install, pair, set rules), features, age tiers, privacy/trust, pricing, FAQ, final CTA, footer
- [ ] `[WEB]` Waitlist form (email + optional WhatsApp number) with validation and success/error states
- [ ] `[BE]` Waitlist endpoint + confirmation email + rate limiting / spam protection
- [ ] `[WEB]` Legal/info pages: privacy policy, terms, contact, child safety and data protection (NDPR), 404
- [ ] `[WEB]` Polish pass: consistent spacing/type/buttons, hover/focus/active on everything, subtle scroll reveals, dark mode, optimized images
- [ ] `[WEB]` SEO: meta, OG/Twitter cards, sitemap, robots, structured data (FAQ, Organization)
- [ ] `[WEB]` Analytics + waitlist conversion events
- [ ] `[WEB]` Test on real low-end Android phones and slow 3G; Lighthouse pass
- [ ] `[WEB]` Deploy with custom domain + SSL

## Phase 8 - Notifications and reports to parents
- [ ] `[BE]` FCM sender using HTTP v1 API (Google service-account JWT -> OAuth token, cached), retries on failure
- [ ] `[BE]` DB-backed job queue (`jobs` table, `SELECT ... FOR UPDATE SKIP LOCKED`, attempts, failed jobs) + CLI worker run by Supervisor
- [ ] `[BE]` Cron-driven scheduler script (weekly summaries, cleanup, expiring pending Knock requests); notification preferences per parent
- [ ] `[BE]` WhatsApp weekly report (WhatsApp Business API provider) + email fallback
- [ ] `[WEB]` Notification settings page

## Phase 9 - Billing
- [ ] `[BE]` Plans (free tier + paid) and feature limits
- [ ] `[BE]` Paystack: subscribe, webhook, cancel; subscription checks on API; failed payment / grace period
- [ ] `[WEB]` Billing and settings pages; pricing page wired to checkout
- [ ] `[ALL]` Decide pricing suited to Nigerian market (affordable tier)

## Phase 10 - Security and compliance (start early, finish before launch)
- [ ] `[BE]` Validation on every endpoint; authorization policies (parent only sees own children)
- [ ] `[BE]` HTTPS only, secure headers, CORS, hashed tokens, encrypted sensitive fields
- [ ] `[BE]` Audit log for sensitive actions
- [ ] `[BE]` Data export + full account/data delete (NDPR); retention policy
- [ ] `[ALL]` Parental consent record + in-app consent screen for parent and child
- [ ] `[ALL]` Privacy policy and terms finalized (review by a professional if possible)
- [ ] `[BE]` Penetration/security review of pairing and device-token flows

## Phase 11 - Beta and Play Store
- [ ] `[AND]` Data Safety form
- [ ] `[AND]` Permission declaration form + demo video (Accessibility / Device Admin usage)
- [ ] `[AND]` Store listing: name, icon, screenshots, description, feature graphic
- [ ] `[AND]` Crash reporting (Crashlytics) + basic analytics
- [ ] `[ALL]` Closed testing track with real Nigerian families; collect feedback
- [ ] `[ALL]` Fix top issues, especially background-kill problems on Tecno / Infinix / itel
- [ ] `[BE]` Production deploy: VPS (Nginx, PHP-FPM, MySQL), Supervisor queue worker, cron scheduler
- [ ] `[BE]` Automated DB backups, staging environment, monitoring + uptime alerts, API docs (OpenAPI / Postman)
- [ ] `[ALL]` Production release + launch plan (WhatsApp groups, Nigerian parent communities, X, Instagram)

## Later (post-launch)
- [ ] `[AND]` Parent Android app (mobile version of the dashboard)
- [ ] `[AND]` Web content filtering (VPN-based)
- [ ] `[AND]` Location tracking + geofences
- [ ] `[ALL]` Earn-your-time rewards (chores / homework unlock minutes)
- [ ] `[ALL]` Multi-parent / guardian sharing
- [ ] `[ALL]` Multi-language (Yoruba, Igbo, Hausa, Pidgin)

---

## Suggested build order
1. Phase 0 (foundations), then Phase 1 (pairing) end to end: backend, dashboard, Android
2. Phase 2 (usage) then Phase 3 (enforcement)
3. Phase 4 (**Knock**) - this is the product; get it feeling great
4. Phase 5 (lock + reliability), start Nigerian-device testing here, not at the end
5. Phase 7 (landing + waitlist) runs in parallel from early on
6. Phases 8-11 to launch