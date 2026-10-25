# AI-assisted engineering: process, boundaries and honest disclosure

The take-home assessment explicitly values directing **external coding agents** and understanding **every line and architecture decision**. AI use is permitted by the supplied assessment, not an excuse for unreviewed code.

## How this submission was drafted

- ChatGPT assisted with comparing architecture alternatives, generating code and documentation, rewriting the previous Laravel/PostgreSQL/LangChain proposal to Laravel/MySQL/Raw Responses API, and preparing interview/video scripts.
- The design intent was guided by the provided candidate CV/engineering review: strong Laravel/SQL/authorization/transactions; limited React/Next.js/LangChain familiarity.
- During the initial drafting environment, checks were limited to **PHP and TS/TSX parser syntax**. A subsequent **24 October 2026 local verification** on Windows/XAMPP with MariaDB 10.4.32 recorded Laravel migrations/seeding, **16 passing PHPUnit tests (34 assertions)**, successful frontend lint/typecheck/build, and manual browser/API checks. Live OpenAI calls and streaming were not verified. See `VERIFICATION.md`.
- The recorded local verification supports only the documented behaviors; the candidate should still understand and reproduce the key demonstrations, and must not claim unverified CI success, MySQL 8.4 compatibility, concurrency safety, live model integration, production readiness, or personal test runs they did not perform.

## Candidate actions to perform and document truthfully

1. Inspect an Action (`RecordTransaction`) and the DB migration: explain unique retries, locks and rollback semantics.
2. Install dependencies and run MySQL + backend + frontend. Capture actual error messages and corrections (if any).
3. Execute owner/viewer/outsider tests; copy **real passing output** and actual Git commit references, not screenshots of unrun tests.
4. Review Frontend and Responses API integration line-by-line; understand hooks, route handlers, cookie/session boundary, tool round-trip and provider streaming.
5. If modifying generated code, record the original issue, the decision and the verification result. Demonstrate one authentic engineering correction in the walkthrough.

## Suggested honest video wording (adapt to actual actions)

> My strongest prior experience is Laravel backend engineering and data integrity. I used AI-assisted coding to accelerate the Next.js/TypeScript UI scaffolding and to compare different agent integration options. I chose MySQL and Laravel for the security-critical domain layer, and implemented a minimal raw function-calling loop instead of bringing in LangChain solely for two tools. I manually reviewed the boundaries and tests I can demonstrate. The repository includes a verification report that distinguishes code authored from tests actually run.

Only say **'I ran and passed the tests'** if you did so personally. Only mention a specific correction if it truly occurred. Do not conceal unfamiliarity with frontend framework concepts; demonstrate familiarity with the used pieces and explain the learning process.

## Embedded AI vs AI-assisted development

These are separate evaluation dimensions. **Embedded AI** is the actual read-only portfolio assistant and tool calling flow. **AI-assisted development** is the workflow used to design, generate, critique, test and iterate the code. Both must be explained, and neither should be confused with model training or ML engineering.
