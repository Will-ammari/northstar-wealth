# Verification Report

**Project:** Northstar Wealth - Wealth Portfolio Tracker  
**Verification date:** 24 October 2026  
**Status:** Locally tested prototype, not a production deployment

## Environment

- Windows with XAMPP PHP 8.2.12
- Laravel 12 and MariaDB 10.4.32
- Node.js 24.15.0 and Next.js 16.4.3
- Development database: northstar_dev_20261024
- Separate automated test database: portfolio_test

## Backend verification

The application was successfully bootstrapped locally. Database
migrations and seeding completed successfully.

Command: `php artisan test`

Result: **16 tests passed, 34 assertions, 17.26 seconds.**

Automated coverage includes:

- Owner, Editor, Viewer, and outsider authorization
- Rejection of unauthorized portfolio mutations
- Rejection of overselling existing holdings
- Idempotency-key replay and conflicting payloads
- Cross-portfolio asset access protection
- Database constraints for negative holdings and mismatched assets
- Valuation history and transaction-price separation
- Read-only assistant authorization checks

## Frontend verification

The following commands completed successfully:

- `npm run lint` - passed, no errors
- `npm run typecheck` - passed
- `npm run build` - passed

Next.js generated the production build and application routes
successfully. This was a local build, not a hosted deployment.

## Manual browser and API verification

- Owner signed in and viewed the seeded portfolio.
- Owner created a test asset with quantity 2 and unit value USD 100.
- Owner recorded an additional buy of 3 units.
- The holding persisted after refresh with quantity 5.
- An attempted sale of 100 units was rejected with the message
  "Insufficient holding quantity."
- After refresh, quantity remained 5, market value remained USD 500,
  total portfolio value remained USD 687,091, and no sale was recorded.
- Viewer could read the portfolio and did not see mutation controls.
- Viewer direct API asset-creation attempt returned HTTP 403.
- Outsider direct API portfolio-read attempt returned HTTP 403.
- Outsider portfolio listing showed only Restricted Portfolio.
- Editor successfully changed the test asset unit valuation from
  USD 100 to USD 101; the API returned HTTP 201.
- The updated valuation was read back successfully.
- Editor restored the unit valuation to USD 100; API returned HTTP 201.

The manual transaction rejection status code was not independently
recorded; only the error message and unchanged persisted state were
verified.

## AI assistant scope and limitations

The prototype includes a read-only assistant integration using
OpenAI tool calling and portfolio-data retrieval.

Automated Laravel tests passed for assistant authorization checks.
However, live OpenAI Responses API calls and streaming output were
not verified because no API key was available.

No paid API usage is required to inspect the implementation.
A valid API key is required for live assistant operation.

## Remaining limitations

- Docker Compose was not executed locally.
- GitHub Actions CI has not yet been verified on a remote repository.
- Real concurrent requests using multiple database connections were
  not load-tested.
- No automated browser end-to-end test suite was run.
- Tested locally with MariaDB 10.4.32; MySQL 8.4 compatibility
  has not been independently verified.
- The application has not been deployed to a production environment.
- This prototype does not constitute investment or financial advice.

## Reproduction

Backend:
1. Configure `backend/.env` from `backend/.env.example`.
2. Install dependencies with `composer install`.
3. Run `php artisan key:generate`.
4. Run `php artisan migrate` and `php artisan db:seed`.
5. Start the API using `php artisan serve`.
6. Run automated tests with `php artisan test`, using a separate
   disposable test database.

Frontend:
1. Configure `frontend/.env.local` from `frontend/.env.example`.
2. Install dependencies with `npm install`.
3. Run `npm run lint`, `npm run typecheck`, and `npm run build`.
4. Start the UI using `npm run dev`.

Do not commit environment files, database passwords, or API secrets.
