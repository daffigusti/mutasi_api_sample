# Sample API Mutasibank.co.id

Mutasibank API memungkinkan Anda untuk melakukan integrasi mutasibank.co.id dengan sistem Anda. API ini dibangun berdasarkan REST API dengan format response data dalam bentuk JSON.

## 📋 Daftar File

### API Library & Examples
- **`Helper.php`** - ⭐ Complete API wrapper class (MutasibankAPI)
- **`example_usage.php`** - Contoh lengkap penggunaan API
- **`api.php`** - Legacy API example

### Webhook Examples (dengan Signature Verification)
- **`webhook_with_signature.php`** - ✅ PHP webhook handler (RECOMMENDED)
- **`webhook_nodejs.js`** - ✅ Node.js/Express webhook handler
- **`webhook_python.py`** - ✅ Python/Flask webhook handler
- **`callback.php`** - Legacy webhook (tanpa signature - NOT RECOMMENDED)

---

## 🚀 Quick Start - REST API

### Install & Setup

```php
<?php
require_once 'Helper.php';

// Initialize API with your token
$api = new MutasibankAPI('YOUR_API_TOKEN_HERE');

// Get your accounts
$accounts = $api->getAccounts();
print_r($accounts);

// Get transactions
$statements = $api->getStatements(
    $accountId = 1,
    $dateFrom = '2025-01-01',
    $dateTo = '2025-01-31'
);

// Match transaction
$match = $api->matchTransaction($accountId = 1, $amount = 100000);

// More examples in example_usage.php
?>
```

### Available Methods

**User & Auth:**
- `getUser()` - Get current user info

**Bank Operations:**
- `getListBank()` - List supported banks

**Account Operations:**
- `getAccounts()` - List all accounts
- `getAccount($id)` - Get account details
- `createAccount($data)` - Create new account
- `updateAccount($id, $data)` - Update account
- `deleteAccount($id)` - Delete account
- `toggleAccountStatus($id, $status)` - Enable/disable account
- `inputToken($id, $token1, $token2)` - Input BCA token
- `rerunCheck($id)` - Manually trigger bot
- `getLogBot($id)` - Get bot activity logs

**Transaction Operations:**
- `getStatements($id, $from, $to)` - Get transactions
- `matchTransaction($id, $amount)` - Find transaction by amount
- `matchTransactions($id, $amount)` - Find all matching transactions
- `validateTransaction($txId)` - Validate transaction by ID

**Category Operations:**
- `getCategories()` - List categories
- `getCategory($id)` - Get category details
- `createCategory($name, $type, $desc)` - Create category
- `updateCategory($id, $data)` - Update category
- `deleteCategory($id)` - Delete category

**Webhook Operations:**
- `getWebhooks()` - List webhooks
- `getWebhook($id)` - Get webhook details
- `createWebhook($url, $accountId, $desc)` - Create webhook
- `updateWebhook($id, $data)` - Update webhook
- `deleteWebhook($id)` - Delete webhook

**Billing:**
- `topupKredit($amount, $paymentId)` - Create topup transaction

---

## 🔒 Webhook Security (PENTING!)

Sejak 2025, semua webhook dari Mutasibank dilengkapi dengan **HMAC-SHA256 signature** untuk keamanan.

### Kenapa Harus Verifikasi Signature?
- ❌ **Tanpa verifikasi:** Siapa saja bisa kirim fake webhook → fraud!
- ✅ **Dengan verifikasi:** Hanya webhook asli dari Mutasibank yang diterima

### Quick Start

**1. Dapatkan Webhook Secret**
- Login ke dashboard Mutasibank
- Buka **Pengaturan → Webhook**
- Copy **Webhook Secret** (64 karakter)

**2. Pilih Contoh Sesuai Bahasa Anda**
- PHP → `webhook_with_signature.php`
- Node.js → `webhook_nodejs.js`
- Python → `webhook_python.py`

**3. Set Environment Variables**
```bash
# .env atau environment variables
WEBHOOK_SECRET=your_64_char_secret_from_dashboard
API_TOKEN=your_api_token_from_dashboard
```

**4. Deploy & Test**
- Upload ke server Anda
- Daftarkan URL webhook di dashboard
- Test dengan trigger transaksi

---

## 📖 Webhook Headers

Setiap webhook dari Mutasibank menyertakan headers berikut:

| Header | Deskripsi |
|--------|-----------|
| `X-Mutasibank-Signature` | HMAC-SHA256 signature (64 chars hex) |
| `X-Mutasibank-Timestamp` | Unix timestamp (untuk prevent replay attack) |
| `X-Mutasibank-Webhook-Id` | Unique UUID untuk webhook ini |
| `Content-Type` | `application/json` |
| `User-Agent` | `Mutasibank-Webhook/1.0` |

---

## 🚀 Cara Menggunakan

### Option 1: PHP (Recommended)

```bash
# 1. Copy file
cp webhook_with_signature.php /path/to/your/webserver/

# 2. Edit konfigurasi
nano webhook_with_signature.php
# Update: WEBHOOK_SECRET dan API_TOKEN

# 3. Set URL di dashboard Mutasibank
# https://yoursite.com/webhook_with_signature.php
```

### Option 2: Node.js

```bash
# 1. Install dependencies
npm init -y
npm install express

# 2. Set environment variables
export WEBHOOK_SECRET="your_secret"
export API_TOKEN="your_token"

# 3. Run server
node webhook_nodejs.js

# 4. Set URL di dashboard
# https://yoursite.com/webhook/mutasibank
```

### Option 3: Python

```bash
# 1. Install dependencies
pip install flask

# 2. Set environment variables
export WEBHOOK_SECRET="your_secret"
export API_TOKEN="your_token"

# 3. Run server
python webhook_python.py

# 4. Set URL di dashboard
# https://yoursite.com/webhook/mutasibank
```

---

## 🧪 Testing Webhook

### Test Signature Verification Locally

```bash
# PHP
php -S localhost:8000
# Akses: http://localhost:8000/webhook_with_signature.php

# Node.js
node webhook_nodejs.js
# Akses: http://localhost:3000/webhook/mutasibank

# Python
python webhook_python.py
# Akses: http://localhost:3000/webhook/mutasibank
```

### Test dengan cURL

```bash
# Generate signature untuk testing
SECRET="your_webhook_secret"
TIMESTAMP=$(date +%s)
PAYLOAD='{"api_key":"test","account_id":1,"data_mutasi":[]}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

# Send test webhook
curl -X POST http://localhost:8000/webhook_with_signature.php \
  -H "Content-Type: application/json" \
  -H "X-Mutasibank-Signature: $SIGNATURE" \
  -H "X-Mutasibank-Timestamp: $TIMESTAMP" \
  -H "X-Mutasibank-Webhook-Id: test-123" \
  -d "$PAYLOAD"
```

---

## 📚 Webhook Data Format

```json
{
  "api_key": "your_api_token",
  "account_id": 123,
  "module": "bca",
  "account_name": "PT Example",
  "account_number": "1234567890",
  "balance": 5000000,
  "data_mutasi": [
    {
      "id": "uuid-transaction-id",
      "transaction_date": "2025-01-10 14:30:00",
      "description": "TRANSFER FROM JOHN DOE ORDER-12345",
      "type": "CR",
      "amount": 100000,
      "balance": 5000000
    }
  ]
}
```

### Field Explanation
- **`type`**: `CR` = Credit (uang masuk), `DB` = Debit (uang keluar)
- **`amount`**: Nominal transaksi
- **`balance`**: Saldo setelah transaksi
- **`description`**: Keterangan dari bank (bisa extract order ID dari sini)

---

## ⚠️ Security Best Practices

### ✅ DO:
- ✅ **Selalu verifikasi signature** - Jangan skip!
- ✅ **Check timestamp** - Prevent replay attacks
- ✅ **Gunakan HTTPS** - URL webhook HARUS https://
- ✅ **Simpan secret di environment** - Jangan hardcode
- ✅ **Log webhook ID** - Untuk debugging
- ✅ **Gunakan constant-time comparison** - `hash_equals()`, `crypto.timingSafeEqual()`

### ❌ DON'T:
- ❌ **Jangan pakai HTTP** - Harus HTTPS
- ❌ **Jangan skip signature verification** - Risiko fraud
- ❌ **Jangan log webhook secret** - Keep it confidential
- ❌ **Jangan pakai `==` untuk compare signature** - Vulnerable to timing attacks
- ❌ **Jangan commit secret ke git** - Use environment variables

---

## 🐛 Troubleshooting

### "Invalid signature" Error
**Kemungkinan penyebab:**
1. Webhook secret salah → Cek di dashboard
2. Pakai `$_POST` bukan raw payload → Gunakan `file_get_contents('php://input')`
3. Pakai `==` bukan `hash_equals()` → Gunakan constant-time comparison

### "Request expired" Error
**Solusi:**
- Sync waktu server dengan NTP
- Tingkatkan `TIMESTAMP_TOLERANCE` jadi 600 (10 menit)

### Webhook tidak terkirim
**Check:**
1. URL webhook di dashboard benar?
2. URL publicly accessible (bukan localhost)?
3. SSL certificate valid?
4. Firewall tidak block IP Mutasibank?

---

## 📞 Support

**Dokumentasi Lengkap:**
https://mutasibank.co.id/api/docs

**Butuh Bantuan?**
- Email: support@mutasibank.co.id
- WhatsApp: +62856 120 5976
- Website: https://mutasibank.co.id

---

## 📝 Changelog

**2025-01-10:**
- ✅ Added webhook signature verification examples
- ✅ Added Node.js and Python examples
- ✅ Updated security best practices

**2018:**
- Initial release with basic API examples

---

**⚡ Start dengan file `webhook_with_signature.php` untuk implementasi paling aman!**
