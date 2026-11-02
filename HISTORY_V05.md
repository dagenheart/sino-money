# Sino Money v0.5 — Transaction History

A new `history.php` page displays transactions belonging to the signed-in user's virtual card.

- Search transaction descriptions (case sensitivity depends on the MySQL collation).
- Filter incoming and outgoing transfers.
- Filter by inclusive start/end dates.
- Sort by date or absolute transaction amount.
- Browse 20 transactions per page while keeping the filters.
- The Overview page still shows the six most recent transactions, with a link to the full history.

## Installation

Replace your existing application files with this archive's `sinomoney` files, keeping a backup of your current working copy. No database migration is required. From the project directory, run `php -S localhost:8000`, then open `http://localhost:8000/history.php` after signing in.

## Verification checklist

1. Sign in as account A and confirm account B's transactions are not shown.
2. Transfer virtual funds in both directions and test Incoming and Outgoing.
3. Test dates, search, and all sort options.
4. Confirm Reset clears filters and pagination works if more than 20 records exist.
5. Verify Overview's card size and flashlight effect remain unchanged.

This release has not been tested against your running MySQL instance.
