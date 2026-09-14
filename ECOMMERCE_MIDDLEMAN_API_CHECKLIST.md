# Ecommerce Middleman API Checklist

Use this checklist as the execution tracker for the ecommerce middleman API module. Do not skip ahead unless the skipped item is intentionally removed from scope.

## 1. Discovery

- [x] Confirm installed PHP package versions with `composer show --direct`.
- [x] Review `package.json` only if frontend/admin UI work is needed.
- [x] Inspect existing API routes.
- [x] Inspect existing wallet, transaction, and BitGo service code.
- [x] Inspect existing jobs and queued transaction behavior.
- [x] Inspect existing Pest test style.
- [ ] Decide whether ecommerce payments use user wallets, merchant wallets, or a dedicated settlement wallet.

## 2. API Design

- [x] Confirm base route prefix.
- [x] Confirm API versioning pattern.
- [x] Confirm merchant authentication mechanism.
- [x] Confirm merchant registration happens through normal `/register` flow.
- [ ] Confirm public transaction ID format.
- [ ] Confirm required create transaction request fields.
- [ ] Confirm supported fiat currencies.
- [ ] Confirm supported crypto assets.
- [ ] Confirm transaction expiration duration.
- [ ] Confirm webhook signing format.
- [ ] Confirm webhook retry schedule.

## 3. Database Design

- [x] Design `merchants` table or reuse existing equivalent.
- [x] Design merchant API token storage.
- [ ] Design ecommerce transactions table.
- [ ] Design transaction events table.
- [ ] Design webhook deliveries table.
- [ ] Add indexes for merchant, status, external order ID, idempotency key, and expiration.
- [ ] Add uniqueness constraints that prevent duplicate payable orders.
- [ ] Add factories for transaction and webhook models.

## 3A. User Or Merchant Registration

- [x] Add account type choice to registration.
- [x] Keep normal user registration behavior for `account_type=user`.
- [x] Validate merchant name and callback URL only for merchant registrations.
- [x] Create active merchant profile attached to the registered user.
- [x] Issue the first hashed API token.
- [x] Flash the plain API token only once after merchant registration.
- [x] Show the flashed merchant API token on the dashboard.
- [x] Test successful user registration.
- [x] Test successful merchant registration.
- [x] Test merchant validation failures.

## 4. Status Model

- [ ] Add transaction status enum.
- [ ] Add webhook delivery status enum.
- [ ] Define allowed transaction state transitions.
- [ ] Prevent terminal states from being changed accidentally.
- [ ] Record an event for every important state change.

## 5. Merchant Authentication

- [x] Add API token generation.
- [x] Store API tokens hashed.
- [ ] Authenticate ecommerce API requests.
- [ ] Reject revoked or expired tokens.
- [ ] Reject disabled merchants.
- [ ] Track last token usage if useful.
- [ ] Add merchant-level authorization to transaction lookup.

## 6. Create Transaction Endpoint

- [ ] Add request validation.
- [ ] Require `Idempotency-Key`.
- [ ] Validate amount, currency, asset, callback URL, and metadata.
- [ ] Check merchant permissions.
- [ ] Create transaction in a database transaction.
- [ ] Assign payment address.
- [ ] Store transaction events.
- [ ] Return a transaction resource.
- [ ] Test successful creation.
- [ ] Test validation failures.
- [ ] Test unauthenticated request.
- [ ] Test disabled merchant.
- [ ] Test idempotency behavior.
- [ ] Test duplicate external order handling.

## 7. Show Transaction Endpoint

- [ ] Add endpoint for transaction status.
- [ ] Return public transaction data.
- [ ] Hide internal IDs and secrets.
- [ ] Ensure merchant can only see its own transactions.
- [ ] Test own transaction access.
- [ ] Test cross-merchant access rejection.

## 8. Cancel Transaction Endpoint

- [ ] Allow cancellation for pending transactions.
- [ ] Reject cancellation for processing transactions.
- [ ] Reject cancellation for succeeded transactions.
- [ ] Record cancellation event.
- [ ] Queue webhook if configured.
- [ ] Test cancellable transaction.
- [ ] Test non-cancellable transaction.

## 9. Payment Detection

- [ ] Choose detection method: BitGo polling, BitGo webhook, or both.
- [ ] Match incoming payment by payment address.
- [ ] Verify asset.
- [ ] Verify amount.
- [ ] Verify confirmation requirement.
- [ ] Mark detected payment as processing.
- [ ] Mark confirmed payment as succeeded.
- [ ] Mark suspicious payment as requires review.
- [ ] Record events.
- [ ] Queue webhook on merchant-visible state changes.
- [ ] Test successful payment detection.
- [ ] Test underpayment or wrong amount.
- [ ] Test ambiguous payment match.

## 10. Expiration

- [ ] Add expiration command or scheduled job.
- [ ] Find pending transactions past `expires_at`.
- [ ] Mark them expired atomically.
- [ ] Record expiration events.
- [ ] Queue webhook.
- [ ] Test expired pending transactions.
- [ ] Test processing/succeeded transactions are not expired.

## 11. Webhooks

- [ ] Create webhook delivery records.
- [ ] Generate HMAC SHA-256 signature.
- [ ] Send JSON payload to callback URL.
- [ ] Store response status.
- [ ] Store response body excerpt.
- [ ] Mark delivery successful on 2xx.
- [ ] Retry failed delivery with backoff.
- [ ] Stop retrying after max attempts.
- [ ] Test signed payload.
- [ ] Test successful delivery.
- [ ] Test failed delivery retry.

## 12. Error Responses

- [ ] Standardize API error response shape.
- [ ] Add stable error codes.
- [ ] Return conflict for idempotency mismatch.
- [ ] Return not found or forbidden for cross-merchant access.
- [ ] Avoid leaking internal exception details.

## 13. Security

- [ ] Rate limit merchant API routes.
- [x] Avoid logging API tokens.
- [x] Avoid logging webhook secrets.
- [ ] Avoid logging wallet passphrases or private secrets.
- [ ] Sign outgoing webhooks.
- [ ] Use HTTPS callback URLs in production.
- [ ] Keep internal database IDs out of public API responses.
- [ ] Add audit events for support-critical changes.

## 14. Verification

- [ ] Run affected Pest tests after each implementation slice.
- [ ] Run `vendor/bin/pint --dirty --format agent` after PHP edits.
- [ ] Run final affected test files.
- [ ] Ask the user to run the full suite with `php artisan test --compact` after feature tests pass.

## 15. First Version Done

- [ ] Merchant can authenticate.
- [ ] Merchant can create transaction.
- [ ] Transaction creation is idempotent.
- [ ] API returns payment instructions.
- [ ] Merchant can fetch status.
- [ ] Pending transaction can expire.
- [ ] Pending transaction can be cancelled.
- [ ] Successful payment can mark transaction succeeded.
- [ ] Final state can trigger signed webhook.
- [ ] Webhook delivery retry is recorded.
- [ ] Main behavior is covered by feature tests.
