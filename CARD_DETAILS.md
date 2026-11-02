# Virtual card details

The existing registration code already generates a unique 16-digit **demo** card number. This update displays that number on the overview and transfer screens, masked by default. Click **Show details** to reveal it and **Copy number** to copy it. Pointer movement adds a subtle spotlight effect; touch devices use the buttons. No database migration is needed.

## Testing
1. Register two demo users in separate browser sessions.
2. On the recipient account, open the overview and select **Show details**, then **Copy number**.
3. Log into the sender account and paste the 16 digits into the recipient card field on the transfer page.
4. Send a small virtual amount and confirm both balances and transaction histories.

These are internal demo identifiers, not payment card numbers. Do not use real card information.
