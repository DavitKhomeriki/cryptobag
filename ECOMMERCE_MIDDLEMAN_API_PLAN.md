# Ecommerce Middleman API Plan

This plan describes the new module that will let external ecommerce systems create orders in CryptoBag, receive payment instructions, and learn whether the transaction succeeded, failed, expired, or still needs processing.

The module should be built as an API-first payment gateway between an ordering system and CryptoBag's transaction/payment layer.

```text
User registration
        |
        | choose user or merchant
        v
CryptoBag account
        |
        | if merchant: create merchant profile + API token
        v
Marketplace / ecommerce app
        |
        | create transaction request
        v
CryptoBag ecommerce API
        |
        | validate merchant, order, amount, currency, idempotency
        v
CryptoBag transaction layer / BitGo
        |
        | detect and confirm payment
        v
CryptoBag ecommerce API
        |
        | response, status endpoint, webhook callback
        v
Marketplace / ecommerce app
```

## Guiding Decisions

- Build this as a versioned API module: `/api/v1/...`.
- Merchants are created during the normal CryptoBag registration flow, not through a separate public merchant API registration endpoint.
- Every registrant becomes a normal CryptoBag user; choosing merchant additionally creates a merchant profile and first API token.
- Treat every payment as a stateful transaction, not a simple true/false operation.
- Keep the controller thin. Put business behavior into actions, services, jobs, and models.
- Every external marketplace must authenticate as a merchant.
- Every order creation request must be idempotent so retries cannot create duplicate transactions.
- Every final transaction result must be stored before any webhook is sent.
- Webhook delivery must be retryable.
- The module must be tested through observable API behavior.

## Transaction States

Use explicit transaction states from the beginning. This avoids confusion when payments are slow, partially confirmed, expired, or need manual review.

Recommended states:

```text
Pending
Processing
Succeeded
Failed
Expired
RequiresReview
Cancelled
```

State meanings:

- `Pending`: CryptoBag accepted the order and returned payment instructions.
- `Processing`: payment was detected or transaction work has started.
- `Succeeded`: payment is complete and the ecommerce order can be fulfilled.
- `Failed`: the payment or transaction could not be completed.
- `Expired`: the customer did not complete payment before the expiration time.
- `RequiresReview`: the system saw something unusual and should not auto-approve.
- `Cancelled`: the merchant or system cancelled the transaction before completion.

Suggested allowed transitions:

```text
Pending -> Processing
Pending -> Expired
Pending -> Cancelled
Processing -> Succeeded
Processing -> Failed
Processing -> RequiresReview
RequiresReview -> Succeeded
RequiresReview -> Failed
```

Avoid allowing `Succeeded` to move back into another state.

## Phase 1: Discovery And Current App Review

Goal: understand the existing app before adding the module.

Steps:

1. Inspect installed package versions.
    - Run `composer show --direct`.
    - Check `package.json` for frontend dependencies if any API admin UI is planned later.
2. Review existing API routes.
    - Run `php artisan route:list --path=api --except-vendor`.
    - Confirm whether the app already has API versioning conventions.
3. Review existing wallet and transaction models.
    - Check current models under `app/Models`.
    - Check existing transaction-related services under `app/Services`.
    - Check existing jobs under `app/Jobs`.
4. Review existing tests.
    - Check `tests/Feature`.
    - Match the project's current Pest style.
5. Decide whether this module uses the existing user wallets, merchant wallets, or a dedicated settlement wallet.

Deliverable:

- A short implementation note before coding that identifies existing conventions we will follow.

## Phase 2: Domain Model Design

Goal: define the persistent records before writing endpoints.

Recommended models:

```text
Merchant
MerchantApiToken or existing token mechanism
EcommerceTransaction
EcommerceTransactionEvent
WebhookEndpoint or merchant callback URL
WebhookDelivery
```

### Merchant

Represents an external ecommerce system that can use CryptoBag as a payment middleman.

Suggested fields:

```text
id
user_id
name
public_id
status
webhook_secret
default_callback_url
created_at
updated_at
```

Notes:

- `user_id` links the merchant profile to the normal registered CryptoBag user.
- `public_id` should be safe to expose in API responses.
- `status` should allow active, disabled, and test/sandbox if needed.
- `webhook_secret` is used to sign outgoing webhook payloads.

### Merchant API Authentication

Choose the authentication method after reviewing the app.

Recommended first version:

- API token per merchant.
- Store only hashed token values.
- Show the plain token only once when it is created.
- Require authentication on every ecommerce API route.

Possible fields:

```text
id
merchant_id
name
token_hash
last_used_at
expires_at
revoked_at
created_at
updated_at
```

### Ecommerce Transaction

Represents the order/payment request created by the external marketplace.

Suggested fields:

```text
id
public_id
merchant_id
external_order_id
idempotency_key
status
amount
currency
crypto_asset
expected_crypto_amount
payment_address
expires_at
customer_reference
callback_url
metadata
failure_code
failure_message
succeeded_at
failed_at
created_at
updated_at
```

Important constraints:

- Unique `public_id`.
- Unique pair: `merchant_id + external_order_id`.
- Unique pair: `merchant_id + idempotency_key` when idempotency key is provided.
- Index `merchant_id + status`.
- Index `expires_at` for expiration processing.

### Ecommerce Transaction Event

Stores the audit trail. This makes debugging and merchant support much easier.

Suggested fields:

```text
id
ecommerce_transaction_id
type
payload
created_at
```

Example event types:

```text
transaction.created
transaction.payment_address_assigned
transaction.payment_detected
transaction.confirmed
transaction.succeeded
transaction.failed
transaction.expired
webhook.queued
webhook.delivered
webhook.failed
```

### Webhook Delivery

Stores outgoing callback attempts.

Suggested fields:

```text
id
merchant_id
ecommerce_transaction_id
url
event_type
payload
signature
status
attempts
last_attempted_at
next_attempt_at
response_status
response_body
created_at
updated_at
```

## Phase 3: API Contract

Goal: define the API behavior before implementing controllers.

### Register Account As User Or Merchant

Endpoint:

```http
POST /register
```

User registration request:

```json
{
    "username": "buyer",
    "email": "buyer@example.com",
    "account_type": "user",
    "password": "password",
    "password_confirmation": "password"
}
```

Merchant registration request:

```json
{
    "username": "merchant",
    "email": "merchant@example.com",
    "account_type": "merchant",
    "merchant_name": "Acme Market",
    "merchant_callback_url": "https://acme.example/webhooks/cryptobag",
    "password": "password",
    "password_confirmation": "password"
}
```

Rules:

- `account_type` must be either `user` or `merchant`.
- Normal users keep the existing wallet registration behavior.
- Merchant users also get a merchant profile.
- Merchant users receive a first API token once after registration.
- The API token is stored hashed and must not be recoverable later.
- The merchant must use this token as a bearer token for future payment transaction endpoints.

### Create Transaction

Endpoint:

```http
POST /api/v1/ecommerce/transactions
```

Required headers:

```http
Authorization: Bearer <merchant_api_token>
Idempotency-Key: <unique-key-from-merchant>
Accept: application/json
Content-Type: application/json
```

Request:

```json
{
    "external_order_id": "ORDER-123",
    "amount": "49.99",
    "currency": "USD",
    "crypto_asset": "tbtc4",
    "customer_reference": "customer-789",
    "callback_url": "https://marketplace.example/payments/callback",
    "metadata": {
        "cart_id": "cart-456"
    }
}
```

Success response:

```json
{
    "transaction_id": "txn_abc123",
    "external_order_id": "ORDER-123",
    "status": "pending",
    "amount": "49.99",
    "currency": "USD",
    "crypto_asset": "tbtc4",
    "payment_address": "tb1...",
    "expires_at": "2026-09-14T13:30:00Z"
}
```

Rules:

- Same idempotency key and same request body returns the original transaction response.
- Same idempotency key and different request body returns a conflict error.
- Same `external_order_id` for the same merchant returns the existing transaction or a conflict, depending on the final implementation decision.
- Never create two payable transactions for the same merchant order.

### Show Transaction

Endpoint:

```http
GET /api/v1/ecommerce/transactions/{transaction}
```

Response:

```json
{
    "transaction_id": "txn_abc123",
    "external_order_id": "ORDER-123",
    "status": "succeeded",
    "amount": "49.99",
    "currency": "USD",
    "crypto_asset": "tbtc4",
    "payment_address": "tb1...",
    "created_at": "2026-09-14T13:00:00Z",
    "expires_at": "2026-09-14T13:30:00Z",
    "succeeded_at": "2026-09-14T13:11:00Z"
}
```

Rules:

- A merchant can only access its own transactions.
- Use public IDs in API routes, not internal database IDs.

### Cancel Transaction

Endpoint:

```http
POST /api/v1/ecommerce/transactions/{transaction}/cancel
```

Rules:

- Only pending transactions can be cancelled.
- Processing and succeeded transactions cannot be cancelled.
- Cancellation creates an event and sends a webhook if configured.

### Webhook Payload

Outgoing webhook example:

```json
{
    "event": "transaction.succeeded",
    "transaction": {
        "transaction_id": "txn_abc123",
        "external_order_id": "ORDER-123",
        "status": "succeeded",
        "amount": "49.99",
        "currency": "USD",
        "crypto_asset": "tbtc4"
    },
    "created_at": "2026-09-14T13:11:00Z"
}
```

Recommended headers:

```http
X-CryptoBag-Event: transaction.succeeded
X-CryptoBag-Delivery: whd_abc123
X-CryptoBag-Signature: sha256=<hmac_signature>
```

Webhook signing:

- Sign the raw JSON payload with the merchant webhook secret.
- Use HMAC SHA-256.
- Document the verification method later when merchant-facing docs are created.

## Phase 4: Validation Rules

Goal: reject bad requests before transaction logic runs.

Create transaction validation should include:

- `external_order_id` is required, string, reasonable max length.
- `amount` is required, decimal, greater than zero.
- `currency` is required, supported fiat currency.
- `crypto_asset` is required or defaults to the app's supported asset.
- `callback_url` is optional, valid URL, HTTPS in production.
- `metadata` is optional array/object, capped to a safe size.
- `Idempotency-Key` is required for write requests.

Business validation should include:

- Merchant is active.
- Merchant is allowed to use the requested asset.
- Amount is within allowed min/max limits.
- External order ID has not already created a conflicting transaction.

## Phase 5: Payment Address And Transaction Creation

Goal: create a payable transaction safely.

Steps:

1. Authenticate merchant.
2. Validate request.
3. Start database transaction.
4. Resolve idempotency key.
5. Check duplicate external order ID.
6. Create `EcommerceTransaction` with `Pending` status.
7. Assign or generate payment address.
8. Store `transaction.created` event.
9. Store `transaction.payment_address_assigned` event if an address is assigned.
10. Commit database transaction.
11. Return the transaction resource.

Open design decision:

- Decide whether each ecommerce transaction gets a fresh receive address or whether addresses are reused for a merchant. Fresh address per transaction is strongly preferred for matching payments and auditability.

## Phase 6: Payment Detection And Confirmation

Goal: update transaction state when payment is detected.

Possible approaches:

- Poll BitGo for incoming transactions.
- Use BitGo webhooks if available and configured.
- Match incoming transactions by payment address.

Processing steps:

1. Detect incoming payment to a known ecommerce payment address.
2. Find matching pending transaction.
3. Check asset, amount, and confirmations.
4. Mark transaction as `Processing` if detected but not final.
5. Mark transaction as `Succeeded` once confirmation rules are satisfied.
6. Mark as `RequiresReview` if amount is wrong, asset is wrong, duplicate payment appears, or matching is ambiguous.
7. Create transaction events for every state change.
8. Queue webhook delivery after final or merchant-visible state changes.

Important rule:

- Do not fulfill the merchant order until the transaction reaches `Succeeded`.

## Phase 7: Expiration Handling

Goal: cleanly expire unpaid transactions.

Steps:

1. Add an Artisan command or scheduled job to find pending transactions where `expires_at` has passed.
2. Mark them as `Expired`.
3. Record `transaction.expired` event.
4. Queue webhook delivery for the merchant.

Rules:

- Do not expire transactions already in `Processing`, `Succeeded`, `Failed`, `RequiresReview`, or `Cancelled`.
- Use a lock or atomic update so two workers cannot expire the same transaction twice.

## Phase 8: Webhook Delivery

Goal: notify merchants reliably.

Steps:

1. Create `WebhookDelivery` record.
2. Queue `SendWebhookDelivery` job.
3. Send POST request with signed payload.
4. Store response status and response body excerpt.
5. Mark delivery as delivered on 2xx response.
6. Retry failed delivery with backoff.
7. Stop retrying after a configured maximum attempt count.

Recommended retry schedule:

```text
1 minute
5 minutes
15 minutes
1 hour
6 hours
24 hours
```

Rules:

- Never send webhooks inside the same database transaction that changes payment status.
- Webhook failure must not roll back the transaction status.
- Webhook jobs must be safe to retry.

## Phase 9: API Errors

Goal: give merchants predictable error responses.

Recommended error shape:

```json
{
    "message": "The idempotency key was already used with a different request.",
    "code": "idempotency_conflict"
}
```

Useful error codes:

```text
unauthenticated
merchant_disabled
validation_failed
idempotency_conflict
duplicate_order
transaction_not_found
transaction_not_cancellable
unsupported_currency
unsupported_asset
amount_too_small
amount_too_large
internal_error
```

## Phase 10: Security Requirements

Goal: make the API safe enough for real merchant traffic.

Required:

- Bearer token authentication for all ecommerce API routes.
- Hashed storage for API tokens.
- Merchant-level authorization on every transaction lookup.
- Rate limiting per merchant token.
- Idempotency for write endpoints.
- HMAC-signed outgoing webhooks.
- No trusting merchant-provided transaction status.
- No logging secrets, API tokens, webhook secrets, OTPs, wallet passphrases, or private keys.
- Store enough events for audit and support.

Recommended:

- Separate sandbox/test merchants from production merchants.
- Merchant token rotation.
- Webhook secret rotation.
- IP allowlists later if merchants request them.

## Phase 11: Laravel File Plan

The exact files should follow existing project conventions when implementation begins. This is the expected shape unless the current app suggests a better local pattern.

Possible backend files:

```text
app/Enums/EcommerceTransactionStatus.php
app/Enums/WebhookDeliveryStatus.php
app/Http/Controllers/Api/V1/Ecommerce/TransactionController.php
app/Http/Requests/Api/V1/Ecommerce/StoreTransactionRequest.php
app/Http/Resources/Api/V1/Ecommerce/TransactionResource.php
app/Models/Merchant.php
app/Models/MerchantApiToken.php
app/Models/EcommerceTransaction.php
app/Models/EcommerceTransactionEvent.php
app/Models/WebhookDelivery.php
app/Actions/Ecommerce/CreateEcommerceTransaction.php
app/Actions/Ecommerce/CancelEcommerceTransaction.php
app/Actions/Ecommerce/MarkEcommerceTransactionSucceeded.php
app/Actions/Ecommerce/ExpireEcommerceTransaction.php
app/Jobs/SendEcommerceWebhook.php
app/Console/Commands/ExpireEcommerceTransactions.php
database/migrations/...
database/factories/...
tests/Feature/Api/Ecommerce/...
```

Possible routes:

```php
Route::prefix('v1/ecommerce')
    ->middleware(['auth:merchant-api', 'throttle:ecommerce-api'])
    ->group(function (): void {
        Route::post('transactions', [TransactionController::class, 'store']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::post('transactions/{transaction}/cancel', [TransactionController::class, 'cancel']);
    });
```

The route example is only a planning sketch. During implementation, use the app's existing route and authentication conventions.

## Phase 12: Test Plan

Goal: prove the module through API behavior.

Feature tests should cover:

- Merchant can create a transaction with valid data.
- Unauthenticated request is rejected.
- Disabled merchant is rejected.
- Invalid amount is rejected.
- Unsupported currency is rejected.
- Duplicate idempotency key with same payload returns the original transaction.
- Duplicate idempotency key with different payload returns conflict.
- Duplicate merchant external order ID cannot create a second payable transaction.
- Merchant can view its own transaction.
- Merchant cannot view another merchant's transaction.
- Pending transaction can be cancelled.
- Succeeded transaction cannot be cancelled.
- Expired pending transaction changes status to expired.
- Succeeded transaction queues webhook delivery.
- Webhook payload is signed.
- Webhook delivery retries on failed HTTP response.

Unit or smaller tests may cover:

- Status transition rules.
- Webhook signature creation.
- Idempotency payload fingerprinting.

## Phase 13: Admin And Operations Later

This is not required for the first API version, but should be considered after the core API works.

Possible future screens or commands:

- Merchant management.
- API token creation and revocation.
- Transaction search.
- Transaction event timeline.
- Webhook delivery logs.
- Manual webhook resend.
- Manual transaction review.
- Metrics for succeeded, failed, expired, and pending transactions.

## Phase 14: Build Order

Use this order when we start implementation:

1. Review existing app conventions.
2. Add enums for statuses.
3. Add migrations and models.
4. Add factories.
5. Add merchant API authentication.
6. Add create transaction request validation.
7. Add transaction creation action.
8. Add create transaction API endpoint.
9. Add transaction resource.
10. Add show transaction endpoint.
11. Add cancel transaction endpoint.
12. Add expiration command/job.
13. Add payment detection integration.
14. Add webhook delivery records.
15. Add webhook sending job.
16. Add retry behavior.
17. Add full feature tests for the module.
18. Run formatting and affected tests.

## Acceptance Criteria For First Version

The first usable version is complete when:

- A merchant can authenticate with an API token.
- A merchant can create a transaction for an ecommerce order.
- Repeated create requests are idempotent.
- The API returns payment instructions.
- The merchant can fetch transaction status.
- The system can mark a transaction succeeded, failed, expired, or cancelled.
- Final status changes create events.
- Final status changes can notify the merchant by signed webhook.
- Webhook delivery retries are stored and observable.
- Feature tests cover the main success and failure paths.
