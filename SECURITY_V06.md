# Sino Money v0.6 — Security update

## Changes
- DB connection settings support `.env` and real environment variables; `.env` stays ignored by Git.
- Sign out is a POST form with a CSRF token, not a GET link.
- Each successful transfer rotates a session-bound form nonce to reject accidental repeated form submissions.
- Existing prepared queries, per-user account access, CSRF checks and MySQL transactions are preserved.
- The card CSS and flashlight effect are unchanged.

## Setup
1. Copy `.env.example` to `.env` and edit the values for your local MySQL installation. Do **not** commit `.env`.
2. Keep your existing database and migration; **no new migration is required**.
3. Start with `php -S localhost:8000` from the `sinomoney` directory.
4. Verify two-account transfers, history filters, sign-out and sign-in.

## Limitations / before public deployment
- This is a virtual-money educational demo, **not** a real financial service.
- A nonce protects against repeated submission of the same form, but is **not** a database-backed idempotency key. Production-grade retries need a unique transfer ID stored transactionally in MySQL.
- Configure HTTPS, secure session cookies, rate limits, a least-privileged DB user and production error logging before exposing to the internet.
- The original sample SQL contains plaintext demo PIN values. Never use real card data; consider removing the unused PIN field in a future schema migration.
- Runtime integration testing against your local MySQL is still required.
