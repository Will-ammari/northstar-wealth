# Interview preparation — 22 likely engineering questions and defensible answers

**Important:** These are sample answers. Speak from code you actually understand and from tests you genuinely execute. Do not imply production validation or past framework experience you do not have.

### 1. Why separate Next.js and Laravel instead of one application?

Laravel owns the domain: validation, authorization, SQL consistency and service actions. Next.js provides a typed responsive client and same-origin BFF. This fits my stronger Laravel background while exercising the frontend stack of the job. The cost is two runtimes, authentication handoff and API contract discipline. If the workload were smaller or skills different, Blade could reduce complexity.

### 2. Why MySQL and not PostgreSQL?

MySQL 8.4 InnoDB meets this prototype's requirements: DECIMAL precision, row locks, atomic transactions, foreign keys and CHECK constraints. My production experience is stronger with MySQL. PostgreSQL is not inherently unsuitable; this is a team/product trade-off, not a claim that MySQL wins every benchmark.

### 3. Why use Raw LLM API rather than LangChain?

There are two read-only tools and one tightly bounded tool-calling step. Direct Responses API keeps the workflow visible and avoids unnecessary framework overhead. With complex branching, persistent state, approvals and many tools, I would benchmark LangChain/LangGraph. I do not claim prior LangChain experience merely because it appears in the vacancy.

### 4. Explain the difference between a chat completion and function calling.

A chat answer is text. Function calling returns a structured request for an application-owned tool; my server validates the tool name/schema, invokes authorized code, then supplies the result back to the model. The model never executes SQL itself. See `frontend/src/lib/llm.ts`.

### 5. What is the exact trust boundary for the agent?

Next.js reads an HttpOnly encrypted session token, asks Laravel to authorize portfolio access before the model call, then uses only fixed read tools that query the same Laravel protected dashboard. No model-chosen user/portfolio IDs or mutation tools exist. A prompt instruction cannot grant a DB role.

### 6. Can prompt injection still happen?

Yes, LLM output can be manipulated or misleading. This design **limits impact** because available tools are read-only and scoped; it does not guarantee that the prose is always accurate. A production system needs prompt-injection evaluation, output checks and privacy review.

### 7. What stops a Viewer from modifying assets through HTTP?

React may hide controls for UX, but every mutation controller calls `Gate::authorize('mutate', portfolio)`. `PortfolioPolicy` permits only owner/editor of that specific portfolio. A viewer's direct POST should return 403 with no database changes; verify the feature test.

### 8. What prevents cross-tenant asset references?

Controllers require the asset's portfolio ID match the requested portfolio. In MySQL, a composite foreign key `(asset_id,portfolio_id)` references `(id,portfolio_id)` on assets; even a mistaken raw INSERT cannot link a transaction to the wrong portfolio.

### 9. How does your idempotency flow handle duplicate requests?

Client generates UUID for the transaction. Database UNIQUE `(portfolio_id,idempotency_key)` ensures a single winning event. Before and after taking asset row lock, code checks existing requests and compares asset, side, quantity and price. A MySQL duplicate-key error causes transaction rollback, then re-reads the winning record; conflicting payload returns 422. Verify race behavior with two real MySQL connections before claiming it is proven.

### 10. What if two sells arrive concurrently against the same 10 shares?

`SELECT ... FOR UPDATE` serializes updates of the same asset. The SQL decrement is conditional (`quantity >= requested`), so a stale sequential check cannot underflow holdings. MySQL `CHECK (quantity >= 0)` is an additional safeguard. A stress test with concurrent sessions is still required.

### 11. Why not update the valuation when buying an asset?

Execution price is the recorded trade price; current valuation is a separately observed price. Conflating them creates misleading performance charts. A trade updates holding quantity and appends a transaction, whereas a valuation action appends history and updates cached last mark.

### 12. Why are quantities and prices DECIMAL rather than double or PHP float?

Binary floats cannot represent all decimal values exactly. We store `DECIMAL(24,8)` and do authoritative arithmetic inside SQL. JavaScript Number is presentation-only; financial totals may be visually rounded in the UI.

### 13. How does your migration enforce allowed roles and asset types?

MySQL 8.4 CHECK constraints reject values outside the declared sets. Validation catches errors early, but constraints are the final boundary. These CHECKs must actually be run against MySQL 8.4 to confirm behavior.

### 14. Is this a double-entry accounting ledger?

No. It is an append-only record of holding movements, not settled cash accounting. Brokerage cash-flow settlement, taxes, fees, corporate actions and reconciliation are out of scope. Claiming full finance-ledger correctness would be inaccurate.

### 15. What scales poorly in the current design?

Dashboard aggregate queries grow with portfolio size; response caps the holdings list at 100 without pagination. A high-contention single asset serializes writers. At higher traffic I would measure query plans/p95 latency, add pagination and evaluate materialized balances with transactional invalidation.

### 16. Why aren't Redis, queues and realtime present?

There is no justified market-feed workload, async valuation sync or multiplayer use case in the MVP. I would add a queue for recurring price pulls once data source reliability and staleness semantics are designed. Real-time streaming of authoritative prices is a separate product concern.

### 17. What is the purpose of the BFF cookie encryption?

The browser holds only an encrypted HttpOnly cookie. Next.js decrypts the Laravel bearer credential server-side and forwards authorized requests. `SameSite=Strict` and Origin checking reduce CSRF. This does not eliminate same-origin XSS risks or replace Laravel authorization.

### 18. Why is typing across Laravel/Next not fully end-to-end safe?

TypeScript types in Next describe expected JSON but are not generated from Laravel contracts yet. The next step would be OpenAPI + generated TS types and contract tests, validating server JSON responses and CI drift. TypeScript cannot by itself guarantee runtime JSON shapes.

### 19. How did you use AI-assisted coding, and how did you verify it?

Explain honestly that ChatGPT helped generate portions of the code and documentation and compare the architectures. Point to a concrete Action, Policy, query and agent flow that you personally reviewed and **only** cite tests you actually ran. AI speed is useful, but engineering responsibility remains with the developer.

### 20. If you had another week, what would you improve first?

First make installation/build/CI reliably green, execute concurrency and agent-integration tests, add pagination and contract validation, measure key SQL queries, then harden privacy, monitoring, assistant cost controls and deployment. I would not add extra product features before proving existing ones.

### 21. How can a provider failure affect the user's portfolio?

It cannot mutate assets because the provider only chooses from read tools. The BFF returns a streaming error, and backend transactions are separate from the model workflow. Cost, latency and availability affect the assistant UX, not the holding ledger.

### 22. What if the reviewer asks you to modify a React hook or agent tool live?

Be candid that the UI/framework is newer to you. Explain the component's props, local state, request lifecycle and error state. For a tool, show the strict allowlist, where identity/portfolio binding comes from, and how Laravel returns 403. Only claim independent mastery to the level you can demonstrate.

## Five questions to rehearse aloud

- Trace a request from a browser click to a committed MySQL transaction.
- Why can a model not access a different portfolio even if told to?
- Demonstrate the same idempotency key twice and explain the database row count.
- Explain an engineering trade-off you intentionally made to cut scope.
- Name one thing you have **not** verified and how you would validate it.
