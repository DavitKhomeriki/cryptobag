# CryptoBag

CryptoBag is a cryptocurrency wallet application built with **Laravel**, **Inertia.js**, and **React**. It integrates with **BitGo** to provide Bitcoin Testnet4 wallet functionality such as wallet creation, balance tracking, receive addresses, sending transactions, and transaction history.

The project currently uses **Bitcoin Testnet4 (`tbtc4`)**, allowing wallet and transaction functionality to be developed and tested without using real Bitcoin.

## Features

- User registration and authentication
- Automatic BitGo wallet creation
- Bitcoin Testnet4 (`tbtc4`) support
- Wallet balance tracking
- BTC/USD exchange rate display
- Generate receive addresses
- Send Testnet4 Bitcoin
- Transaction history
- Transaction pagination
- USD transaction value calculation
- Transaction confirmation status
- BitGo spending-limited access token support
- Client-side and server-side transaction validation
- Prevention of sending funds to the user's own wallet addresses
- Dark cryptocurrency-themed dashboard

## Tech Stack

### Backend

- PHP
- Laravel 13
- Laravel Fortify
- Laravel HTTP Client
- BitGo API
- BitGo Express

### Frontend

- React
- TypeScript
- Inertia.js
- Tailwind CSS
- Lucide React

### Infrastructure / Services

- BitGo Test Environment
- BitGo Express
- Bitcoin Testnet4
- BTC/USD price API
- Vite

## Architecture

CryptoBag uses Laravel as the application backend and React with Inertia.js for the frontend.

```text
React / Inertia
      │
      ▼
Laravel
      │
      ├──────────────► BitGo REST API
      │                Wallet data
      │                Balances
      │                Addresses
      │                Transactions
      │
      └──────────────► BitGo Express
                       Wallet generation
                       Transaction signing
                              │
                              ▼
                       Bitcoin Testnet4
```

BitGo Express runs locally and handles operations that require wallet key functionality, such as transaction signing.

## Wallet Model

Each CryptoBag user has a BitGo wallet associated with their account.

A Bitcoin wallet can control multiple addresses:

```text
User
 │
 ▼
Wallet
 │
 ├── Receive Address
 ├── Receive Address
 ├── Change Address
 ├── Change Address
 └── ...
```

BitGo may automatically generate change addresses when transactions are made. These addresses still belong to the same wallet.

## Transaction Flow

When a user sends Bitcoin:

```text
User enters:
    Recipient Address
    Amount
          │
          ▼
Laravel validates request
          │
          ▼
BitGoService
          │
          ▼
BitGo Express
          │
          ▼
Transaction signed
          │
          ▼
BitGo / Bitcoin Testnet4
          │
          ▼
Transaction broadcast
```

Bitcoin uses the UTXO model, so sending a transaction may also create a new change address for the remaining Bitcoin.

## Requirements

Make sure the following are installed:

- PHP
- Composer
- Node.js
- npm
- Laravel-compatible database
- BitGo Express
- BitGo test account

## Installation

Clone the repository:

```bash
git clone <repository-url>
cd cryptobag
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Configure your database in `.env`, then run:

```bash
php artisan migrate
```

## BitGo Configuration

Add your BitGo configuration to `.env`:

```env
BITGO_URL=https://app.bitgo-test.com
BITGO_EXPRESS_URL=http://localhost:3080

BITGO_TOKEN=
BITGO_ENTERPRISE_ID=
```

Do **not** commit real access tokens, wallet passphrases, OTP codes, or other secrets to Git.

The BitGo configuration is loaded from `config/services.php`:

```php
'bitgo' => [
    'url' => env('BITGO_URL'),
    'express_url' => env('BITGO_EXPRESS_URL'),
    'token' => env('BITGO_TOKEN'),
    'enterprise_id' => env('BITGO_ENTERPRISE_ID'),
],
```

After changing environment variables, clear Laravel's cached configuration:

```bash
php artisan optimize:clear
```

## BitGo Express

CryptoBag uses BitGo Express for wallet operations that require local cryptographic functionality.

BitGo Express should be available at:

```text
http://localhost:3080
```

The Laravel application communicates with Express using:

```env
BITGO_EXPRESS_URL=http://localhost:3080
```

BitGo Express is used for operations such as:

- Generating wallets
- Signing transactions
- Sending transactions

Normal wallet information can be retrieved directly from the BitGo REST API.

## Running the Application

Start the Laravel development environment using the project's configured development command, for example:

```bash
composer dev
```

Or run Laravel and Vite separately:

```bash
php artisan serve
```

```bash
npm run dev
```

Make sure BitGo Express is also running before using wallet-generation or transaction functionality.

## Wallet Balance

Wallet balances are retrieved from BitGo.

BitGo returns Bitcoin values in satoshis.

```text
1 BTC = 100,000,000 satoshis
```

For example:

```text
100000 satoshis
÷ 100000000
=
0.001 BTC
```

CryptoBag converts these values before displaying them to the user.

## Receive Addresses

Users can generate receive addresses from the dashboard.

All generated addresses belong to the same BitGo wallet.

Bitcoin wallets may also contain internal/change addresses created automatically during transactions.

## Sending Transactions

Users provide:

- Recipient address
- TBTC amount

CryptoBag validates the transaction before sending it to BitGo.

The application currently enforces a minimum transaction amount to avoid Bitcoin dust transactions.

Example:

```text
Minimum: 0.00001 TBTC
```

The amount is converted to satoshis before being sent to BitGo.

```text
0.001 TBTC
× 100,000,000
=
100,000 satoshis
```

BitGo Express signs and broadcasts the transaction.

## Transaction Status

New transactions may initially appear as:

```text
unconfirmed
```

This means the transaction has been broadcast but has not yet been included in a Bitcoin Testnet4 block.

After miners include it in a block, confirmations begin increasing.

```text
Unconfirmed
     │
     ▼
1 Confirmation
     │
     ▼
Additional Confirmations
```

## Transaction History

Transaction history is retrieved directly from BitGo rather than duplicated in the local database.

The dashboard displays information such as:

- Date
- Transaction type
- TBTC amount
- USD amount
- BTC/USD exchange rate
- Transaction state

CryptoBag uses BitGo's cursor-based pagination for transaction history.

```text
Page 1
   │
   │ nextBatchPrevId
   ▼
Page 2
   │
   │ nextBatchPrevId
   ▼
Page 3
```

## Security

Cryptocurrency applications require careful secret management.

Never commit:

```text
.env
BitGo access tokens
OTP codes
Wallet passphrases
Private keys
bitgo-token-response.json
```

Ensure sensitive files are included in `.gitignore`.

For example:

```gitignore
.env
bitgo-token-response.json
```

Access tokens should use only the permissions required by the application.

For transaction-capable long-lived BitGo tokens, spending limits should be configured appropriately.

Production applications should also avoid storing wallet passphrases as plaintext in the database. Use an appropriate secure key-management/encryption strategy before handling real funds.

## Code Quality

Check the project before committing:

```bash
composer ci:check
```

Fix frontend/project formatting issues with:

```bash
npm run check -- --fix
```

Then run the CI check again:

```bash
composer ci:check
```

PHP formatting can also be checked with Laravel Pint:

```bash
./vendor/bin/pint --test
```

and fixed with:

```bash
./vendor/bin/pint
```

Run the Laravel test suite with:

```bash
php artisan test
```

## Development Status

CryptoBag is currently a development/testnet project.

It uses:

```text
Bitcoin Testnet4 (tbtc4)
```

and does **not** currently operate with real Bitcoin.

Before adapting the project for production/mainnet use, additional work should be done around:

- Secure wallet key management
- Passphrase protection
- Access-token permission reduction
- Spending limits
- Transaction fee estimation
- Maximum spendable balance calculation
- Error handling
- Rate limiting
- Monitoring and logging
- Transaction confirmation handling
- Automated testing
- Production infrastructure

## Disclaimer

CryptoBag is currently intended for educational and development purposes.

Bitcoin Testnet4 coins have no monetary value. The current implementation should not be used to custody or transfer real cryptocurrency without a thorough security review.
