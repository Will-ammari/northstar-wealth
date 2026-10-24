# Test / verification strategy

## 0. Reporting truthfully

Source **syntax checks have run**, but PHP feature tests, database migrations, frontend typecheck/build and live model calls **have not** been run in the supplied environment. A written CI file is **not** a green CI run. See `VERIFICATION.md`.

## 1. Static checks possible offline

From repository root:

```bash
./scripts/verify-static.sh
```

This lints PHP parser syntax and compiles TS/TSX syntax in transpile-only mode. It **does not** validate Laravel integration, TypeScript types, npm module resolution or runtime behavior.

## 2. Required local integration setup

```bash
docker compose up -d mysql
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan test
vendor/bin/pint --test  # optional style check, may require formatting first
cd ../frontend
cp .env.example .env.local
npm install
npm run typecheck
npm run lint
npm run build
```

For tests create a **separate** MySQL database `portfolio_test` matching environment variables in `backend/phpunit.xml`/CI. Do not run a destructive test suite against seeded demonstration data; `RefreshDatabase` resets test tables.

## 3. Automated test cases authored

**`PortfolioSecurityTest`**: viewer cannot modify quantities; outsider cannot see or edit portfolio; owner cannot oversell; identical idempotency replay cannot double-post; cross-portfolio asset returns 404; viewer cannot revalue; trade execution does not rewrite last market mark; editor may revalue with history; conflicting replay fails; unauthenticated user cannot list; viewer can read dashboard.

**`AssistantAuthorizationTest`**: outsider cannot enter paid agent path; viewer may use read-only assistant; unauthenticated requests are rejected.

**`DatabaseInvariantsTest`**: DB-level rejection of negative quantity and mismatched portfolio/asset foreign key (MySQL-only checks).

**Not yet authored/run:** actual two-connection concurrency race, fake/mock OpenAI Responses payload tests, E2E browser Playwright, scheduled job tests (jobs deliberately excluded), load tests, full accessibility evaluation.

## 4. Manual demo checklist

| Test | How to check | Expected |
| --- | --- | --- |
| Login | owner@example.test via UI | User sees Northstar dashboard |
| Dashboard | View seeded stock/crypto/cash/real estate | No fake live-price labels; total from DB |
| Viewer mutation | Login viewer; direct API POST with same identity | 403 and zero database delta |
| Outsider | Ask for another portfolio ID | 403 |
| Oversell | Owner sends `sell` more than asset quantity | 422 and unchanged quantity |
| Same idempotency key | Send same buy twice | One transaction row, one quantity increment |
| Changed replay | Send changed quantity with old key | 422 |
| Valuation | New mark then check previous transaction | Execution price unchanged; valuation history appended |
| AI (if key) | Ask 'summarize allocation' | Tool-derived context and streamed text |
| AI access | Outsider asks other portfolio | Denied before model invocation |
| AI unavailable | Omit API key | Clear 503, no fabricated response |
| Browser | Resize to mobile width | Nav, forms, tables and chat usable |

## 5. Performance and reliability follow-up

- Bench with 1k, 100k, 1m transactions and realistic per-portfolio distributions; report p50/p95/p99 latencies and DB query plans.
- Concurrent callers using distinct keys selling same asset must never underflow; same-key caller pairs must not double count. A single-threaded feature test **does not prove concurrency safety**.
- Observe deadlocks, retries, lock wait latency, stale valuation age, slow queries, model-tool cost, model timeout and provider error rate.
- Exercise Docker image reproducibility and migration/rollback in a staging-like environment before using 'production-ready' language.

## Primary references

- [Laravel testing](https://laravel.com/docs/12.x/testing)
- [MySQL 8.4 locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html)
