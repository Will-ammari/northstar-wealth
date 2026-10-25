# Video walkthrough — candidate script (target: 12–14 minutes)

**IMPORTANT:** You must personally record a 10–15 minute screen recording with your **own voice**. This document is a guide, **not** a generated video. The assessment requires the candidate to explain what was really built and tested. Replace all `[DEMO ...]` brackets with genuine footage and results. State only results documented from real runs. The recorded 24 October local verification is summarized below; a new machine or CI run must be described separately.

**Screen setup:** open the repository README, `docs/PLAN.md`, MySQL migration, `RecordTransaction.php`, `PortfolioPolicy.php`, `llm.ts`, `route.ts`, and the running dashboard if available. Keep browser zoom high enough for reviewers to read. Avoid showing `.env` secrets or personal API tokens.

---

## 00:00–01:10 — Introduction and goals (say aloud)

Hello, my name is William Ammari, and thank you for reviewing my Talent Syria technical assessment. The application is called Northstar Wealth. It is a scoped, multi-asset portfolio tracker covering equities, cryptocurrency, real estate, and US dollar cash. It is a record-keeping prototype, not a trading or investment-advice platform.

I approached this assessment primarily as an engineering-design exercise. My goal was to make a coherent end-to-end product slice with explicit security boundaries and predictable financial-state changes, instead of implementing every possible feature. My professional strength is Laravel backend systems, transactional workflows, authorization, and integrations. For the frontend, I used AI-assisted development to help scaffold the unfamiliar Next.js and TypeScript pieces while retaining responsibility for the system design and what I can verify.

My repository includes a clear engineering plan and a verification report. I distinguish completed source code from behavior actually exercised in an installed environment, because confidence should come from evidence.

## 01:10–02:30 — Scope choices

[SHOW docs/PLAN.md scope matrix.]

The core feature set is straightforward. Authenticated household members can view a portfolio, its assets, manually recorded valuations, and its recent transactions. Owners and editors can add assets, record a buy or sell as a holding movement, and set a new valuation. Viewers can inspect data but cannot change it. There is also a read-only AI assistant that can explain the holdings by invoking tools.

I intentionally deferred market price synchronization, background quote polling, multi-user live collaboration, multi-currency conversion, and automated trading. Those features bring external dependencies, synchronization concerns, cost and financial semantics that are not necessary to show the primary architecture. I would prefer a smaller, more trustworthy prototype to a larger application with implied correctness that I cannot justify.

## 02:30–04:10 — Stack and architecture

[SHOW docs/ARCHITECTURE.md and the diagram.]

My stack is Laravel 12, MySQL 8.4, Next.js 16, TypeScript, and the OpenAI Responses API for direct function calling. I chose MySQL rather than PostgreSQL because I already have production experience with it and InnoDB supports the transactions, locking reads, foreign keys, unique constraints and decimal precision that this scope needs. I am not claiming that one database is always faster.

Laravel owns the domain and all authoritative access decisions. Controllers remain thin: they accept validated input, authorize the user, invoke an Action or Query, and return a response. Mutations use Actions with explicit transaction boundaries; reads go through a query object. The business logic does not live in React.

Next.js provides the responsive interface and a Backend-for-Frontend layer. The browser makes same-origin requests. Laravel's Sanctum bearer token is handled only on the Next.js server and is stored in an encrypted HttpOnly cookie. This provides a clear trust boundary, although it means two runtimes, more configuration and an API contract that needs careful management. I would add OpenAPI-generated TypeScript types and contract tests for a mature version.

I avoided adding Express, Redis or a second database layer because they would not address a demonstrated requirement in this small prototype.

## 04:10–06:05 — Data model and write integrity

[SHOW migration, DATA_MODEL.md and `RecordTransaction.php`.]

I model users, portfolios, memberships, assets, portfolio transactions and valuation history. Each membership has a role, and each asset belongs to exactly one portfolio. I use a composite foreign key on transaction and valuation rows to ensure a referenced asset belongs to the same portfolio. That is a defense against application scoping errors.

Quantities and prices use MySQL DECIMAL with eight fractional digits, and financial changes are performed inside SQL rather than using JavaScript floating-point calculations. An asset has a current cached quantity and current manually recorded unit valuation. A transaction preserves its own execution price. These are deliberately separate concepts: changing the valuation must not rewrite the historical buy price.

When I record a transaction, the action runs inside a database transaction. It checks for a previously used idempotency key, locks the targeted asset row using SELECT FOR UPDATE, validates again after the lock, then executes a conditional quantity update and inserts the immutable transaction event. A sell cannot reduce the quantity below zero. The database has unique and check constraints as additional guards.

For retries, each portfolio and idempotency key pair must be unique. If the exact operation is repeated, the existing transaction is returned. If the same key is reused with different details, the API rejects it. A duplicate-key race causes the losing transaction to roll back before the winning operation is examined. This is a design for consistency, but genuine two-connection concurrency tests are still required before a stronger reliability claim.

## 06:05–07:45 — Application demonstration

[DEMO: The 24 October report records owner login, a new holding of 2 units at USD 100, a buy of 3 units (quantity 5), an oversell rejection with unchanged saved state, and editor valuation update/restoration. Reproduce these on screen if the local environment is running. Otherwise show the code and explicitly state that this new demo was not reproduced.]

The dashboard displays the current manually recorded holdings value and asset allocation. These are snapshots, not live market quotes. The interface also shows a bounded recent transaction feed. The Next.js components contain the loading states, mutation form state, and server error feedback. The backend performs validation and controls whether an action is allowed. Hiding a button for a Viewer is only a user-experience detail, not an access-control mechanism.

For the MVP, I bound the assets returned in detail to one hundred rows and the activity list to ten. The total and allocation are computed from the database over the whole portfolio. For larger portfolios I would introduce cursor pagination and profile the SQL queries before caching.

## 07:45–09:20 — Security demonstration

[SHOW PortfolioPolicy. The 24 October report records Viewer write HTTP 403 and outsider read HTTP 403. Demonstrate again in the currently running app if possible, or identify the outcome as a previously recorded local check rather than fresh footage.]

Here is my most important security boundary. Every portfolio read checks membership, and every write requires an Owner or Editor membership for that exact portfolio. The identity is taken from the authenticated Laravel request, not from a client-supplied user ID. Transactions and valuations also verify that the referenced asset belongs to that portfolio.

This applies to AI too. If a language model is prompted to ignore its instructions, that should not grant it access to data or mutations. Authorization is enforced by the domain API and the model only has tools that the application explicitly exposes.

## 09:20–11:05 — AI tool-calling architecture

[SHOW `frontend/src/lib/llm.ts` and the assistant route, and an actual assistant response if an API key is configured.]

For the embedded assistant I deliberately chose direct Responses API function calling rather than LangChain. There are two tools: get_portfolio_summary and list_holdings. Their argument schemas are empty. The user and portfolio context is already bound in the authenticated server route, so the model cannot choose another portfolio by passing a different ID.

The assistant route checks the session, request origin, maximum question size, and an explicit Laravel authorization and throttle endpoint before calling the model. The model first requests an allowlisted tool. The Next.js server validates the tool call, fetches data from the protected Laravel endpoint, and returns the tool result to the model. The final answer is streamed to the browser as server-sent events.

There are no AI write tools. I prioritized safely answering portfolio questions over natural-language transaction mutations. If tool complexity grows, LangChain or LangGraph could become valuable, but introducing them for these two simple tools would add a framework layer without sufficient benefit. This does not imply that I have prior LangChain production experience.

## 11:05–12:25 — AI-assisted development and quality

[SHOW docs/AI_USAGE.md and docs/VERIFICATION.md; show real terminal screenshots or a fresh test command only if available. Do not imply GitHub Actions has passed.]

I used AI assistance to speed up scaffolding and to challenge architectural choices. This was particularly useful on the Next.js and TypeScript portions that were new to me. My engineering responsibility was to set the correctness requirements, keep permissions and database invariants explicit, inspect the generated code and verify the system through tests.

The repository includes Laravel feature tests for role permissions, insufficient holdings, idempotency, cross-portfolio isolation and database-level constraints. GitHub Actions is configured to run backend tests against MySQL, along with frontend checks. My verification report records a 24 October local run on MariaDB 10.4.32: 16 Laravel tests passed, with 34 assertions; Next.js lint, TypeScript typecheck and production build also passed; and the main browser and permission scenarios were tested manually. The GitHub Actions workflow exists but was not verified remotely. Live OpenAI API calls and streaming remain unverified without an API key. These results were documented previously; when recording, I should distinguish them from anything I have personally rerun today.

## 12:25–13:35 — Trade-offs, limits and wrap-up

[SHOW TRADEOFFS.md and TESTING.md.]

There are deliberate limitations. This is not a live broker, does not value assets in multiple currencies, does not reconcile bank balances, and has not been proven under production load. The next steps would be verifying the reproducible CI build on MySQL 8.4, concurrency tests using multiple database connections, end-to-end agent tests with a valid provider key, API contract generation, pagination and performance profiling. Only after those checks would I consider new product features such as market feeds or real-time collaboration.

The central decision was to make authorization, data integrity and AI tool boundaries explicit while still delivering a full-stack user experience. Thank you for reviewing my assessment. I welcome questions about the trade-offs, known risks and the reasons behind the design.

---

## Practical rehearsal guidance (Arabic)

- الفيديو لازم يكون تسجيل شاشتك **وصوتك**، مو مجرد ملف النص.
- احكي بشكل طبيعي وبوتيرة متوسطة؛ اجعل الشرح مفهوماً حتى لو اختصرته.
- أثناء عرض SQL، أشر إلى `lockForUpdate` وUNIQUE وCHECK ومعنى rollback.
- اشرح بوضوح سبب استبعاد LangChain، ولا تدّعي خبرة 3 سنوات بـNext.js/TypeScript.
- جملة `[DEMO ...]` ليست حقيقة منفّذة: احذفها أو استبدلها بديمو حقيقي فقط بعد تشغيله.
- إن لم يتوفر مفتاح النموذج أو فشل البناء، وضّح ذلك، وأعرض تصميم الـTool Loop ومحدودية التنفيذ بدل الادعاء بنجاحه.
- لا تظهر كلمة المرور الحقيقية أو ملف `.env.local` أو API Key ضمن الفيديو.
