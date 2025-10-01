<?php

/**
 * Mutasibank API - Usage Examples
 *
 * This file demonstrates how to use the MutasibankAPI helper class
 * for common operations
 */

require_once 'Helper.php';

// Initialize API
$apiToken = 'YOUR_API_TOKEN_HERE'; // Get from dashboard
$api = new MutasibankAPI($apiToken);

// ============================================================================
// Example 1: Get User Info
// ============================================================================

echo "=== Example 1: Get User Info ===\n";
$user = $api->getUser();

if (!$user['error']) {
    echo "Name: " . $user['data']['name'] . "\n";
    echo "Email: " . $user['data']['email'] . "\n";
    echo "Balance: Rp " . number_format($user['data']['saldo']) . "\n\n";
}

// ============================================================================
// Example 2: List All Bank Accounts
// ============================================================================

echo "=== Example 2: List Bank Accounts ===\n";
$accounts = $api->getAccounts();

if (!$accounts['error']) {
    foreach ($accounts['data'] as $account) {
        echo "ID: {$account['id']} | ";
        echo "Bank: {$account['bank']} | ";
        echo "Account: {$account['account_no']} | ";
        echo "Balance: Rp " . number_format($account['balance']) . " | ";
        echo "Status: " . ($account['is_active'] ? 'Active' : 'Inactive') . "\n";
    }
    echo "\n";
}

// ============================================================================
// Example 3: Get Transactions for Specific Account
// ============================================================================

echo "=== Example 3: Get Transactions ===\n";
$accountId = 1; // Change to your account ID
$dateFrom = '2025-01-01';
$dateTo = '2025-01-31';

$statements = $api->getStatements($accountId, $dateFrom, $dateTo);

if (!$statements['error']) {
    echo "Account: {$statements['account_name']} ({$statements['account_number']})\n";
    echo "Current Balance: Rp " . number_format($statements['balance']) . "\n";
    echo "Transactions found: " . count($statements['data']) . "\n\n";

    foreach ($statements['data'] as $tx) {
        echo "Date: {$tx['transaction_date']} | ";
        echo "Type: {$tx['type']} | ";
        echo "Amount: Rp " . number_format($tx['amount']) . " | ";
        echo "Description: {$tx['description']}\n";
    }
    echo "\n";
}

// ============================================================================
// Example 4: Match Transaction by Amount
// ============================================================================

echo "=== Example 4: Match Transaction ===\n";
$accountId = 1;
$amount = 100000; // Amount to find

$match = $api->matchTransaction($accountId, $amount);

if (!$match['error'] && $match['valid']) {
    echo "✅ Transaction found!\n";
    echo "ID: {$match['data']['id']}\n";
    echo "Amount: Rp " . number_format($match['data']['amount']) . "\n";
    echo "Description: {$match['data']['description']}\n";
    echo "Date: {$match['data']['transaction_date']}\n\n";
} else {
    echo "❌ Transaction not found\n\n";
}

// ============================================================================
// Example 5: Validate Transaction by ID
// ============================================================================

echo "=== Example 5: Validate Transaction ===\n";
$transactionId = '12345'; // Get from match or webhook

$validation = $api->validateTransaction($transactionId);

if (!$validation['error'] && $validation['valid']) {
    echo "✅ Transaction is valid!\n";
    echo "Amount: Rp " . number_format($validation['data']['amount']) . "\n";
    echo "Type: {$validation['data']['type']}\n\n";
} else {
    echo "❌ Transaction not valid or not found\n\n";
}

// ============================================================================
// Example 6: List Supported Banks
// ============================================================================

echo "=== Example 6: List Supported Banks ===\n";
$banks = $api->getListBank();

if (!$banks['error']) {
    foreach ($banks['data'] as $bank) {
        $status = $bank['is_active'] ? '✅' : '❌';
        $maintenance = $bank['is_maintenance'] ? '[MAINTENANCE]' : '';
        echo "{$status} {$bank['bank_name']} ({$bank['module_name']}) {$maintenance}\n";
    }
    echo "\n";
}

// ============================================================================
// Example 7: Create New Bank Account
// ============================================================================

echo "=== Example 7: Create Bank Account ===\n";
$newAccount = [
    'bank_id' => 1, // BCA
    'account_name' => 'John Doe',
    'account_no' => '1234567890',
    'user_banking' => 'username',
    'password_banking' => 'password',
    'schedule_minutes' => 15,
    'url_callback' => 'https://example.com/webhook',
    'note' => 'My BCA account'
];

// Uncomment to actually create
// $result = $api->createAccount($newAccount);
// echo "Account created: " . $result['message'] . "\n\n";

echo "⚠️  Uncomment code to actually create account\n\n";

// ============================================================================
// Example 8: Create Webhook
// ============================================================================

echo "=== Example 8: Create Webhook ===\n";

// Uncomment to create webhook
// $webhook = $api->createWebhook(
//     'https://yoursite.com/webhook',
//     1, // bank_account_id
//     'Main webhook for notifications'
// );
//
// if (!$webhook['error']) {
//     echo "✅ Webhook created!\n";
//     echo "Webhook ID: {$webhook['data']['id']}\n";
//     echo "⚠️  IMPORTANT: Save your webhook secret securely!\n";
//     echo "Secret: {$webhook['data']['secret']}\n\n";
// }

echo "⚠️  Uncomment code to create webhook\n\n";

// ============================================================================
// Example 9: Get Categories
// ============================================================================

echo "=== Example 9: List Categories ===\n";
$categories = $api->getCategories();

if (!$categories['error']) {
    foreach ($categories['data'] as $cat) {
        echo "- {$cat['category']} ({$cat['type']})\n";
    }
    echo "\n";
}

// ============================================================================
// Example 10: Top Up Balance
// ============================================================================

echo "=== Example 10: Top Up Balance ===\n";

// Uncomment to create topup
// $topup = $api->topupKredit(100000, 1); // 100k via BCA
//
// if (!$topup['error']) {
//     echo "✅ Topup created!\n";
//     echo "Trans ID: {$topup['data']['trans_id']}\n";
//     echo "Amount: Rp " . number_format($topup['data']['jumlah']) . "\n";
//     echo "Unique Code: {$topup['data']['kode_unik']}\n";
//     echo "Total Transfer: Rp " . number_format($topup['data']['total']) . "\n";
//     echo "\nTransfer to:\n";
//     echo "Bank: {$topup['data']['payment']['name']}\n";
//     echo "Account: {$topup['data']['payment']['account_number']}\n";
//     echo "Name: {$topup['data']['payment']['account_name']}\n\n";
// }

echo "⚠️  Uncomment code to create topup\n\n";

// ============================================================================
// Example 11: Error Handling
// ============================================================================

echo "=== Example 11: Error Handling ===\n";
$result = $api->getAccount(99999); // Non-existent account

if ($result['error']) {
    echo "❌ Error occurred: {$result['message']}\n";
} else {
    echo "✅ Success!\n";
}
