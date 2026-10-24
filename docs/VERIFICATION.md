# Verification report — evidence and limitations

**Build date:** 24 October 2026  
**Source:** `northstar-wealth-final`  
**Warning:** Static syntax success is NOT evidence of a runnable deployment.

## Tests that were actually executed during authoring

| Check | Result | Meaning |
| --- | --- | --- |
| PHP `php -l` on all committed `.php` source files | **Passed** | Parser accepts PHP syntax only. |
| TypeScript `transpileModule` on all 18 frontend TS/TSX source files | **Passed** | Parser/transpiler accepts TS syntax. NO TS types were checked. |
| Project files + ZIP archive | **Will be verified in final packaging step** | Ensures the deliverables exist and archive extracts. |

## Not executed / not verified

- PHP dependencies: no Composer binary and no dependency network access in authoring environment.
- Laravel boot/migrations: no `pdo_mysql` extension and no MySQL service available in authoring environment.
- PHPUnit feature tests / DB constraints / real concurrency: **not run**.
- Frontend `npm install`, `npm run typecheck`, `npm run lint`, `npm run build`, browser rendering: **not run** because no package registry access.
- OpenAI Responses API integration and streaming: **not run**, requires own API key and internet.
- GitHub Actions workflow: authored but **no CI run** or published GitHub repository yet.
- Human 10–15 minute voiceover screen recording: **not created**, user must produce it.

## Static design checks / risk items

- Removed PostgreSQL-specific advisory locks and numeric casts from implementation; migration and CI target MySQL.
- Removed LangChain dependencies and changed the assistant to raw tool calling + SSE.
- BFF token is server-only; Laravel gates all portfolio data. These are design reviews, **not penetration-test evidence**.
- `DECIMAL(24,8)` plus SQL updates avoid binary floating point on authoritative holding quantity. JS display formatting may round values visibly.
- Bounded dashboard asset list at 100 and transaction history at 10; not enterprise pagination.
- Need MySQL **two-connection** concurrency testing, frontend module typechecking, provider mock tests, and live end-to-end smoke testing before claiming working/prod-ready behavior.

## Mandatory pre-submission command sequence

```bash
# With internet, Docker and PHP extensions installed
./scripts/verify-static.sh
docker compose up -d mysql
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan test
vendor/bin/pint --test # optional style check, may require formatting
cd ../frontend
cp .env.example .env.local
# Set BFF_SESSION_SECRET from openssl rand -hex 32, APP_ORIGIN, API_BASE_URL
npm install
npm run typecheck && npm run lint && npm run build
npm run dev
```

Test the browser with owner, viewer and outsider credentials; verify `403`, `422`, duplicate-key replay and the AI key-disabled `503` route. If a provider key is available, run actual tool calls and confirm model output relates to seeded data. Capture the results in an updated report and video. These steps were **not performed in this authoring environment**.
