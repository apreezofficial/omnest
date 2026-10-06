# Omnest

**Limits that talk back.** An Android parental control app: screen time, app limits, remote lock, and **Knock** (the child asks for more time, the parent answers in one tap).

| Folder | What | Stack |
|---|---|---|
| [`backend/`](backend/) | API | Vanilla PHP 8.2+, MySQL, no framework |
| [`web/`](web/) | Landing page + parent dashboard | Next.js, TypeScript, Tailwind |
| [`android/`](android/) | Child app | Kotlin, Jetpack Compose |
| [`docs/`](docs/) | [Roadmap](docs/tasks.md) and [design system](docs/specs.md) | |

## Quick start

```bash
# API (http://localhost:8000)
cd backend && cp .env.example .env && composer install
composer migrate && composer serve

# Web (http://localhost:3000)
cd web && pnpm install && pnpm dev

# Android: open android/ in Android Studio
```

## Branching

- `main` is always deployable; protected, changes land through pull requests.
- Work on short-lived branches: `feat/<area>-<thing>`, `fix/<area>-<thing>`, `chore/<thing>` (area = `be`, `web`, `and`, `all`).
- Keep commits small; CI must be green before merging.
