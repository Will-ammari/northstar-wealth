# Architecture Decision Records (ADRs)

## ADR-001: Laravel API + Next.js BFF rather than pure Next.js or Laravel Blade

**Decision:** Keep Laravel as the business authority. Use Next.js for responsive UI and a small server-only proxy.  
**Why:** Reuses candidate's proven Laravel design experience while concretely demonstrating a frontend framework required by the vacancy.  
**Costs:** Two deployments, separate runtime stacks, protected bearer exchange, explicit Origin checks, session key lifecycle.  
**Alternative:** Laravel Blade is simpler, but doesn't demonstrate Next.js / TypeScript; pure Next.js reduces tier count but relocates policies/domain to unfamiliar tooling.

## ADR-002: MySQL 8.4 instead of PostgreSQL

**Decision:** MySQL 8.4 InnoDB using enforced constraints and row locks.  
**Why:** Candidate has production MySQL expertise; supports current data requirements, including `DECIMAL(24,8)`, foreign keys, `CHECK`, `UNIQUE`, atomic transactions and unique-key row locking.  
**Costs:** MySQL dialect, isolation and index behavior must be tested; PostgreSQL has powerful native features not needed in this MVP.  
**Important:** Benchmarks are required before claiming one database is faster or more scalable.

## ADR-003: Raw LLM Responses API instead of LangChain

**Decision:** Two allowlisted read-only function tools and a bounded two-call LLM orchestration in Next.js; second call streams text.  
**Why:** Tiny tool surface; shows understanding of tool calling without adding a framework layer.  
**Costs:** Must maintain call validation, schema evolution, timeouts, streaming parser and evaluation ourselves. If multi-step workflows, memory, human approvals or tool routing grow, re-evaluate LangChain/LangGraph.  
**Vacancy gap:** This does **not** prove LangChain experience, which is listed in the job posting. Do not claim otherwise.

## ADR-004: Manual valuations, not external market feeds

**Decision:** User records a unit valuation; stored independently of historical execution price.  
**Why:** Avoid real-price vendor complexity, license restrictions, data-latency ambiguity and fake price expectations while demonstrating clean financial modeling.  
**Cost:** Snapshot values become stale; UI labels this clearly; no live quote claims.

## ADR-005: Serialized per-asset writes and unique idempotency

**Decision:** Lock only the asset row, make quantity update conditional, append ledger row, enforce unique portfolio/key.  
**Why:** Allows writes to independent assets while preventing negative holdings; unique key finalizes duplicate prevention across assets.  
**Cost:** Hot assets serialize; MySQL deadlocks and duplicate insert races require tests, monitoring and backoff strategies. No load benchmarks yet.

## ADR-006: Agent with no write tools

**Decision:** Agent can only retrieve portfolio summary and holdings through protected Laravel endpoint.  
**Why:** Assessment specifically probes whether AI agents can bypass permission boundaries; a narrow read-only agent is safer to validate than automatic financial mutation.  
**Cost:** Can't log transactions through natural language. Future write flow needs review-screen confirmation, policy checks, audit trail, replay guard, careful prompts/tool schemas and negative tests.

## ADR-007: Dashboard aggregate query now, read model later

**Decision:** SQL aggregates each view and detail output capped at 100 assets / 10 transactions.  
**Why:** Straightforward to explain and test for MVP without introducing cache invalidation errors.  
**Cost:** Aggregate work increases with portfolio size, and UI has no pagination. Add cursor pagination and materialized portfolio balances only after query profiling.
