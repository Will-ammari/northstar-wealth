# Technical Assessment — Engineering Plan

**Project:** Northstar Wealth (Wealth Portfolio Tracker)  
**Role:** Full-Stack Software Engineer  
**Candidate:** William Ammari  
**Assessment deadline:** 25 October 2026, end of day Damascus time

## 1. Problem statement and engineering priorities

Build a demonstrable *household* multi-asset portfolio workspace supporting USD-denominated stocks, cryptocurrency, real estate, and cash. A user can inspect manually recorded holdings and changes, with strict Owner/Editor/Viewer access. Embed a read-only, tool-calling AI assistant without allowing a language model to bypass backend authorization.

**Success means**: one coherent secure vertical slice, explicit correctness invariants, understandable code, documented compromises, repeatable tests and a reproducible recorded walkthrough. **Not success**: maximum features, a fragile distributed stack, AI-generated code that cannot be explained, or a polished UI over fake data.

## 2. Scope matrix

| Priority | Capability | Status in submitted source | Rationale |
| --- | --- | --- | --- |
| P0 | Membership/roles and Laravel policies | Authored, runtime unverified | Tenant isolation is non-negotiable. |
| P0 | Portfolio asset records + valuations + transactions | Authored, runtime unverified | Demonstrates core domain and data integrity. |
| P0 | Next.js BFF and usable responsive dashboard | Authored, runtime unverified | End-to-end full-stack experience. |
| P0 | Written plan + README + evidence/limits | Included | Explicit minimum from assessment. |
| P0 | 10–15 min screen/voice video | **User must record** | Required deliverable, not generated here. |
| P1 | Two read-only AI tools with streaming UX | Authored, provider-key integration unverified | Show tool flow without unsafe write permissions. |
| P1 | MySQL feature tests and CI | Authored, **not executed** | Provide verification path; don't claim green CI. |
| P2 | AI write tool with confirmation | Deferred | Requires approval UX, abuse tests and step-up auth. |
| P2 | Async market sync and realtime collaboration | Deferred | External providers, queue semantics and cost distract from P0. |
| P2 | FX and financial advice | Excluded | Complex domain/compliance/risk not feasible for this prototype. |

## 3. Chosen stack and accountability

- **Laravel 12:** user-authenticated API, Form Requests, Policies, thin Controllers, Actions for mutations, Query objects for reads. Developer's demonstrated strongest area.
- **MySQL 8.4 InnoDB:** `DECIMAL(24,8)`, unique/foreign/check constraints, transactional atomicity, `SELECT ... FOR UPDATE`. Familiar and fit for current workload; PostgreSQL is an equally legitimate alternative, not a requirement.
- **Next.js 16 + TypeScript:** React-based responsive dashboard and server-only BFF; demonstrates the frontend stack listed in vacancy, supported by AI-assisted engineering and code review.
- **Raw LLM Responses API:** minimum two named tools and streaming response; choosing it instead of LangChain avoids framework cost for only two tools and one short interaction loop. LangChain is separately requested in the job posting and this project does not claim expertise in it.
- **CI:** GitHub Actions with MySQL service plus PHP and frontend checks. A workflow definition is not evidence of a successful run.

## 4. Architecture boundaries

```text
Browser (React UI)
   | same-origin requests / HttpOnly session
Next.js BFF (Route Handlers, token encryption, origin check)
   | Laravel Bearer token from server-only cookie
Laravel API (auth:sanctum -> FormRequest -> Policy -> Action/Query)
   | atomic SQL, row locks, CHECK/UNIQUE/FK
MySQL (portfolios, memberships, assets, valuations, transactions)

Browser -> Next.js assistant route -> Laravel authorize (per-user throttle)
  -> OpenAI Responses API tool choice -> allowlisted read-only Laravel API tools
  -> OpenAI streamed final answer -> browser SSE UX
```

There is no model-to-database connection; the model does **not** receive API tokens, database credentials, arbitrary SQL, or a tool argument for another tenant's ID.

## 5. Domain model and invariants

Tables: `users`, `personal_access_tokens`, `portfolios`, `portfolio_memberships`, `assets`, `asset_valuations`, `portfolio_transactions` (+ password reset token table). Each portfolio has one USD base currency; each user can have a role per portfolio; every asset and transaction/valuation belongs to one portfolio.

- Constrain roles and asset types at the DB where supported.
- Reject negative quantities/prices at validation **and** DB check boundaries.
- On asset opening, create the holding, opening transaction, and first valuation in one atomic transaction.
- A transaction appends to immutable transaction history and updates cached asset quantity under an asset-row lock; sell fails if quantity is insufficient.
- Valuation is separate from trade execution price; appending valuation and refreshing cached mark occurs atomically.
- A unique `(portfolio_id,idempotency_key)` prevents a repeated request from double-recording a transaction; replays with conflicting payload are rejected.
- Compound `(asset_id,portfolio_id)` foreign keys block linking a transaction to a holding from a different portfolio.
- USD cash has value 1 USD per unit; no mixed-currency arithmetic without documented FX rates.

See [DATA_MODEL](DATA_MODEL.md) and [SECURITY](SECURITY.md) for more precise constraints.

## 6. API and UI surfaces

Authenticated reads: current user, portfolio links, specific dashboard. Authorized mutations: open a new asset, add a holding increase/decrease, update a manual valuation. Assistant gate: separately throttled `POST` authorization. Next.js forwards via BFF, React renders breakdown and transaction history, with loading/errors/empty states, and mutation forms visible only to writer roles (server remains authoritative).

See [API](API.md) for exact routes/payloads.

## 7. Agent lifecycle

1. Browser submits question to same-origin `/api/portfolios/{id}/assistant`.
2. BFF verifies Origin, encrypted session, input limit and available API key.
3. Laravel verifies `view` permission using the real user identity and applies a low-rate gate.
4. Responses API receives model instructions + two fixed schema tools (`get_portfolio_summary`, `list_holdings`), **without** any user-supplied portfolio selector.
5. BFF validates tool names and empty argument schemas; retrieves data through same protected Laravel dashboard endpoint.
6. BFF feeds structured tool results to the model in a second request and streams final text SSE. The model is not granted arbitrary actions. Provider failures show a user-facing error, not invented financial data.

The provider API key remains server-side. Data is sent to the configured provider; this demo must not process real client portfolios without legal/privacy review and an appropriate data-handling agreement.

## 8. Error states and failure containment

- 401 unauthenticated, 403 unauthorized, 404 cross-portfolio asset, 422 invalid transaction/insufficient holdings/reused key conflict, 429 assistant throttle, 502 backend unavailable, 503 assistant key missing.
- Transactions rollback on validation/SQL failures; any DB-level duplicate-key race rolls back before the winner is rechecked.
- External model outage cannot mutate portfolios because the model has no write tool.
- Lost browser session = no API access; logout revokes the backend token and clears cookie.

## 9. Test strategy and acceptance criteria

**Must demonstrate on an installed environment before claiming completion:** clean migration/seed, owner mutation, viewer forbidden mutation with unchanged DB rows, outsider 403, cross-portfolio 404, oversell 422 with unchanged quantity, identical idempotency replay writes once, reuse with altered payload 422, valuation vs execution price separation, assistant authorization and provider streaming, frontend build.

Additional necessary before production: concurrent race tests against real MySQL, load benchmarks, user-owned portfolio creation/invitations, migrations with rollback strategy, secrets rotation, observability, market data design, FX precision policies and model response evaluation. This prototype does **not** provide those assurances.

## 10. Delivery and transparency

Deliver the repository source and this plan, plus a **candidate-recorded video** of 10–15 minutes with real screen and voice. `README.md` explains installation; `docs/VERIFICATION.md` distinguishes static checks from tests that still need running. AI-use disclosure must be truthful, including generated code, the candidate's review and real examples of corrections or failed tests.

## References

- [Talent Syria assessment](../README.md) — submission requirements are summarized; source is the PDF sent to candidate.
- [Laravel database transactions](https://laravel.com/docs/12.x/database#database-transactions)
- [Laravel authorization](https://laravel.com/docs/12.x/authorization)
- [MySQL 8.4 locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html)
- [OpenAI function calling](https://developers.openai.com/api/docs/guides/function-calling)
- [Next.js Route Handlers](https://nextjs.org/docs/app/getting-started/route-handlers)
