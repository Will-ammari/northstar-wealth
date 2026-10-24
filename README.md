# Northstar Wealth — Portfolio Tracker

**Talent Syria take-home assessment · 24 October 2026 · Candidate: William Ammari**  
**Submission:** scoped code prototype + engineering plan + video outline.  
**Status:** authored and **syntax-checked only**, **NOT** locally integration-tested. This is not production software. [Verification facts](docs/VERIFICATION.md).

A permission-aware multi-asset wealth tracking prototype. The central engineering claim is that portfolio data and write operations are governed by **Laravel authorization and database invariants**, including when an AI assistant requests portfolio information. A deliberately bounded scope lets reviewers assess design decisions, data integrity, the front-to-back contract, and AI orchestration rather than feature count.

## Tech stack / architectural decisions

- **Laravel 12 + PHP 8.2+** for authentication, policy enforcement, application actions, financial invariants, API endpoints and SQL queries.
- **MySQL 8.4 / InnoDB** for foreign keys, `DECIMAL(24,8)`, enforced `CHECK`s, locks and atomic transactions. No PostgreSQL-specific code.
- **Next.js 16 + TypeScript** for responsive dashboard, forms, loading/error states, server-only Backend-for-Frontend (BFF) routes.
- **Raw OpenAI Responses API with tool calling** (not LangChain) for two **read-only** portfolio tools and response streaming. Zero writable tools.
- **GitHub Actions** for proposed PHP/MySQL and frontend quality gates; these jobs require a real CI run to establish results.

## What is implemented in source

| Capability | Notes |
| --- | --- |
| Auth | Laravel Sanctum 8-hour bearer token held in encrypted, HttpOnly, SameSite=Strict BFF cookie. Browser never reads the token. |
| Tenancy / roles | Memberships with Owner, Editor and Viewer. Laravel Policies authorize each read/write, assistant gate and tool query. |
| Assets | Stock, crypto, real estate and USD cash; opening quantity and opening ledger record in one DB transaction. |
| Transactions | Buy/sell as **record-keeping only**; conditional SQL update, asset-row lock, nonnegative quantity, unique idempotency key. |
| Valuations | Latest manual unit mark cached on asset; append-only valuation history. Execution prices never overwrite valuation. |
| Dashboard | Total, classes, bounded asset list, bounded transaction list, loading, empty/error states. |
| AI | API-key-gated two-tool LLM loop, pre-authorization + throttle, no arbitrary portfolio/SQL/tool inputs, streamed answer. |
| Tests | PHP feature tests and CI workflow **written**, not executed in this environment. |

**Out of scope:** user self-signup, invitation flow, real trades or execution, market quotes, FX, rebalancing recommendations, realtime collaboration, queues and background market workers. No financial advice.

## Local setup (needs internet for first dependency install)

**Requirements:** PHP >=8.2 with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`; Composer 2; Node.js >=20.9; npm; MySQL 8.4 (or Docker Compose); an optional OpenAI API key. There are no `vendor/` or `node_modules/` directories and no generated lockfiles in the archive; generate, verify and commit lockfiles before claiming reproducibility.

**1. Start MySQL:**

```bash
docker compose up -d mysql
```

The Compose password/ports are intentionally **local-demo only**. Alternatively, create MySQL database `portfolio` and user `portfolio` with a strong password. Prefer a distinct test database (`portfolio_test`) for tests.

**2. Backend (separate terminal):**

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --force
php artisan db:seed
php artisan serve --host=127.0.0.1 --port=8000
```

Check `.env`: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=portfolio`, credentials matching your local MySQL. Seeder refuses to run outside `local` and `testing`.

**3. Frontend (separate terminal):**

```bash
cd frontend
cp .env.example .env.local
openssl rand -hex 32   # set BFF_SESSION_SECRET to this output
npm install
npm run dev
```

Set `APP_ORIGIN=http://localhost:3000` and `API_BASE_URL=http://127.0.0.1:8000/api` in `frontend/.env.local`. Set `OPENAI_API_KEY` and optionally `OPENAI_MODEL=gpt-4o-mini` to enable AI. Without a key, AI returns **HTTP 503**; it does **not** silently pretend to be intelligent.

Open `http://localhost:3000`.

### Demo users (only after running the seed in local environment)

| User | Password | Access |
| --- | --- | --- |
| `owner@example.test` | `DemoPass123!` | Owner of Northstar Family Office |
| `editor@example.test` | `DemoPass123!` | Editor of Northstar Family Office |
| `viewer@example.test` | `DemoPass123!` | Viewer only; cannot write |
| `outsider@example.test` | `DemoPass123!` | Own restricted portfolio, no access to Northstar |

**Never seed these accounts in production**.

## How to check this submission

```bash
./scripts/verify-static.sh  # syntax-only, available without dependency install
cd backend && php artisan test && vendor/bin/pint --test     # optional style check; format code with vendor/bin/pint if needed
cd ../frontend && npm run typecheck && npm run lint && npm run build
```

For local/CI tests, `backend/phpunit.xml` forces DB name `portfolio_test` to avoid destroying demo data. Before local tests, create this separate schema and grant permissions, e.g. `CREATE DATABASE IF NOT EXISTS portfolio_test; GRANT ALL PRIVILEGES ON portfolio_test.* TO 'portfolio'@'%';`. CI provisions a MySQL service automatically. There is **no claim that these runtime jobs already passed**. See [TESTING](docs/TESTING.md) and [VERIFICATION](docs/VERIFICATION.md).

## Documentation / assessment deliverables

- **[START_HERE_AR](START_HERE_AR.md)** — Arabic handoff and exact next steps

- **[PLAN](docs/PLAN.md)** — prioritized written assessment plan (minimum deliverable)
- **[ARCHITECTURE](docs/ARCHITECTURE.md)** — responsibilities, flows and practical trade-offs
- **[DATA_MODEL](docs/DATA_MODEL.md)** — entities, invariants, indices and capacity caveats
- **[SECURITY](docs/SECURITY.md)** — roles, threat boundaries, test scenarios, AI safety
- **[API](docs/API.md)** — endpoint contracts and examples
- **[TESTING](docs/TESTING.md)** — static/runtime/manual/reliability checks
- **[TRADEOFFS](docs/TRADEOFFS.md)** — deliberate decisions and deferrals
- **[AI_USAGE](docs/AI_USAGE.md)** — truthful disclosure of AI-assisted engineering
- **[VERIFICATION](docs/VERIFICATION.md)** — factual checks and blockers
- **[SUMMARY_AR](docs/SUMMARY_AR.md)** — Arabic engineering report and explanations
- **[INTERVIEW_QA](docs/INTERVIEW_QA.md)** — interview preparation
- **[VIDEO_SCRIPT](docs/VIDEO_SCRIPT.md)** — 10–15 minute English walkthrough with demo gates

## Important accounting limitations

This is a *holdings tracker*, not a regulated financial ledger. An increase or decrease in a holding is a **recorded** transaction; the application does not move cash or perform brokerage settlement. Only USD-valued manual snapshots are supported. `NUMERIC/DECIMAL` arithmetic in MySQL is authoritative; JavaScript floating-point is used only to format values for display and chart proportions. Summaries include all assets; the detail view is intentionally limited to 100 assets (without pagination yet), and the transaction feed shows 10 recent events. This is not tested for production scale.
