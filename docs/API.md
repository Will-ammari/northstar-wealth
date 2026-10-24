# REST/BFF contract — v1 scoped prototype

Browser talks only to `http://localhost:3000/api/*` (Next.js BFF). BFF talks to Laravel at `${API_BASE_URL}` with the server-side Sanctum token. JSON data is subject to Laravel validation/authorization, not frontend checks.

## Authentication

| Method/path in BFF | Laravel route | Purpose |
| --- | --- | --- |
| `POST /api/session/login` | `POST /api/login` | Email/password; BFF encrypts Sanctum token in HttpOnly cookie. |
| `GET /api/session` | `GET /api/me` | Current authenticated user. |
| `POST /api/session/logout` | `POST /api/logout` | Revoke token and expire BFF cookie. |

Demo login: `{"email":"viewer@example.test","password":"DemoPass123!"}`.

## Portfolio APIs

| BFF route | Laravel route | Allowed roles | Response |
| --- | --- | --- | --- |
| `GET /api/portfolios` | `GET /api/portfolios` | Any member | `{data:[{id,name,base_currency,role}]}` |
| `GET /api/portfolios/{id}` | `GET /api/portfolios/{id}` | Owner/Editor/Viewer | `{data:{portfolio,total_value,asset_count,allocation,assets,recent_transactions}}` |
| `POST /api/portfolios/{id}/assets` | Same | Owner/Editor | 201 asset fields |
| `POST /api/portfolios/{id}/assets/{assetId}/transactions` | Same | Owner/Editor | 201 transaction fields |
| `POST /api/portfolios/{id}/assets/{assetId}/valuation` | Same | Owner/Editor | 201 `{ok:true}` |
| `POST /api/portfolios/{id}/assistant` | `POST /api/portfolios/{id}/assistant/authorize` + protected portfolio reads | Any member, rate-limited | SSE `status`, `token`, `done` or `error` |

### Create asset

```json
{"type":"stock","name":"Index Fund","symbol":"INDEX","quantity":"2.50000000","unit_price":"125.00000000"}
```

### Record transaction

```json
{"type":"sell","quantity":"0.50000000","unit_price":"127.00000000","idempotency_key":"d63f35c3-09d2-4932-aa13-7c766f7e6774"}
```

Repeated request with the same key and exactly equivalent decimal values returns the *same* record. A repeated key with a changed quantity, price, side or asset is rejected as 422. `type=buy/sell` denotes recorded holding movement, not a live trade.

### Update valuation

```json
{"unit_price":"130.00000000"}
```

Creates a valuation row and atomically updates the cached current mark. Does not change historical execution price or quantity.

### Ask assistant

```json
{"message":"How is my portfolio allocated by asset class?"}
```

Event stream example (illustrative shape, **not an output captured from a running system**):

```text
event: status
data: {"message":"Retrieving authorized portfolio context"}

event: token
data: {"text":"Your"}

event: done
data: {}
```

The tool selection response is a **real API call** when `OPENAI_API_KEY` is configured; model output is not pre-baked or hard-coded.

## Error contract

| Status | Meaning |
| --- | --- |
| 401 | Missing/expired login |
| 403 | Insufficient portfolio access or cross-origin mutation |
| 404 | Wrong asset-portfolio relationship or unknown resource |
| 413/415 | Oversized body / not JSON |
| 422 | Invalid fields, oversell, conflicting idempotency replay |
| 429 | Laravel assistant or general request rate limit |
| 502 | Laravel backend unreachable |
| 503 | Assistant key not configured |

Laravel validation errors use standard `{message,errors:{field:[...]}}`. Unknown internal/provider errors are not exposed to the browser.
