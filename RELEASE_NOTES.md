# Sino Money v1.0 — Portfolio packaging

This release updates documentation and provides a clean database schema for fresh installations.

- No PHP application logic, CSS, or JavaScript behavior was changed from v0.6.
- The original `bank.sql` legacy sample dump is omitted from the public-ready package because it contains outdated demo records and plaintext sample PINs.
- `schema.sql` is for **new databases only**; `migration_001_auth.sql` remains for older databases that have not been migrated.
- README includes setup, architecture, security limitations, and AI assistance disclosure.
- Screenshots must be added manually from the running application; none were fabricated.

Before pushing to GitHub, verify the project with your local MySQL, inspect `git status`, and ensure `.env` and database backups are not committed.
