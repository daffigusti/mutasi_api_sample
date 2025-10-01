<?php
/**
 * Mutasibank Webhook Handler with Signature Verification
 *
 * This example shows how to securely receive and verify webhooks from Mutasibank
 * with HMAC-SHA256 signature authentication
 *
 * SECURITY: Always verify webhook signatures to prevent spoofing attacks
 */

// ============================================================================
// CONFIGURATION
// ============================================================================

// Get your webhook secret from Mutasibank Dashboard > Pengaturan > Webhook
define('WEBHOOK_SECRET', 'your_64_character_webhook_secret_here');

// Your API token from Mutasibank Dashboard > Developer API
define('API_TOKEN', 'your_api_token_here');

// Timestamp tolerance (in seconds) - prevent replay attacks
define('TIMESTAMP_TOLERANCE', 300); // 5 minutes

// Enable logging (for debugging)
define('ENABLE_LOGGING', true);

// ============================================================================
// STEP 1: EXTRACT HEADERS
// ============================================================================

$signature = $_SERVER['HTTP_X_MUTASIBANK_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_MUTASIBANK_TIMESTAMP'] ?? 0;
$webhookId = $_SERVER['HTTP_X_MUTASIBANK_WEBHOOK_ID'] ?? '';

// ============================================================================
// STEP 2: VALIDATE HEADERS EXIST
// ============================================================================

if (empty($signature) || empty($timestamp)) {
    logMessage('ERROR: Missing signature headers');
    http_response_code(401);
    die(json_encode([
        'error' => 'Missing signature headers',
        'message' => 'X-Mutasibank-Signature and X-Mutasibank-Timestamp required'
    ]));
}

// ============================================================================
// STEP 3: PREVENT REPLAY ATTACKS (Check Timestamp)
// ============================================================================

$currentTime = time();
$timeDiff = abs($currentTime - $timestamp);

if ($timeDiff > TIMESTAMP_TOLERANCE) {
    logMessage("ERROR: Timestamp expired. Diff: {$timeDiff}s");
    http_response_code(401);
    die(json_encode([
        'error' => 'Request expired',
        'message' => 'Timestamp outside acceptable window',
        'timestamp_received' => $timestamp,
        'current_time' => $currentTime,
        'difference_seconds' => $timeDiff
    ]));
}

// ============================================================================
// STEP 4: GET RAW PAYLOAD
// ============================================================================

// IMPORTANT: Use raw input, NOT $_POST!
// Signature is calculated on the exact JSON string
$payload = file_get_contents('php://input');

if (empty($payload)) {
    logMessage('ERROR: Empty payload');
    http_response_code(400);
    die(json_encode(['error' => 'Empty payload']));
}

// ============================================================================
// STEP 5: VERIFY SIGNATURE (HMAC-SHA256)
// ============================================================================

// Calculate expected signature
$expectedSignature = hash_hmac('sha256', $payload, WEBHOOK_SECRET);

// Use constant-time comparison to prevent timing attacks
if (!hash_equals($expectedSignature, $signature)) {
    logMessage('ERROR: Invalid signature', [
        'expected' => substr($expectedSignature, 0, 10) . '...',
        'received' => substr($signature, 0, 10) . '...',
        'webhook_id' => $webhookId
    ]);

    http_response_code(401);
    die(json_encode([
        'error' => 'Invalid signature',
        'message' => 'Webhook signature verification failed'
    ]));
}

logMessage('SUCCESS: Signature verified', ['webhook_id' => $webhookId]);

// ============================================================================
// STEP 6: PARSE AND VALIDATE DATA
// ============================================================================

$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    logMessage('ERROR: Invalid JSON - ' . json_last_error_msg());
    http_response_code(400);
    die(json_encode(['error' => 'Invalid JSON payload']));
}

// Verify API token (double authentication)
$receivedToken = $data['api_key'] ?? '';
if ($receivedToken !== API_TOKEN) {
    logMessage('ERROR: Invalid API token');
    http_response_code(401);
    die(json_encode(['error' => 'Invalid API token']));
}

// ============================================================================
// STEP 7: PROCESS WEBHOOK DATA
// ============================================================================

$accountId = $data['account_id'];
$accountName = $data['account_name'];
$accountNumber = $data['account_number'];
$module = $data['module']; // bca, bri, mandiri, bni
$balance = $data['balance'];
$transactions = $data['data_mutasi'];

logMessage("Processing webhook for account: {$accountNumber}", [
    'account_id' => $accountId,
    'module' => $module,
    'transaction_count' => count($transactions),
    'balance' => $balance
]);

// Process each transaction
foreach ($transactions as $transaction) {
    $transactionId = $transaction['id'];
    $transactionDate = $transaction['transaction_date'];
    $description = $transaction['description'];
    $type = $transaction['type']; // CR (credit) or DB (debit)
    $amount = $transaction['amount'];
    $balance = $transaction['balance'];

    logMessage("Processing transaction: {$transactionId}", [
        'date' => $transactionDate,
        'type' => $type,
        'amount' => $amount
    ]);

    // ========================================================================
    // YOUR BUSINESS LOGIC HERE
    // ========================================================================

    // Example 1: Auto-confirm order for e-commerce
    if ($type === 'CR') { // Credit (incoming payment)
        // Match transaction with pending orders
        $orderId = extractOrderIdFromDescription($description);

        if ($orderId) {
            // Verify amount matches order
            // confirmOrder($orderId, $amount, $transactionId);
            logMessage("Order matched: {$orderId}");
        }
    }

    // Example 2: Top-up processing for gaming/pulsa
    // if ($type === 'CR' && $amount >= 10000) {
    //     processTopUp($description, $amount, $transactionId);
    // }

    // Example 3: Invoice payment tracking
    // matchInvoicePayment($description, $amount, $transactionDate);

    // Example 4: Save to your database
    // saveTransactionToDatabase($transaction);
}

// ============================================================================
// STEP 8: RETURN SUCCESS RESPONSE
// ============================================================================

logMessage('Webhook processed successfully', [
    'webhook_id' => $webhookId,
    'transactions_processed' => count($transactions)
]);

http_response_code(200);
echo json_encode([
    'success' => true,
    'webhook_id' => $webhookId,
    'transactions_processed' => count($transactions),
    'timestamp' => date('Y-m-d H:i:s')
]);

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Extract order ID from transaction description
 * Customize based on your order ID format
 */
function extractOrderIdFromDescription($description) {
    // Example: Extract order ID like "ORDER-12345" from description
    if (preg_match('/ORDER-(\d+)/i', $description, $matches)) {
        return $matches[1];
    }

    // Example: Extract from format "TRF 12345"
    if (preg_match('/TRF\s+(\d+)/i', $description, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Log message to file (for debugging)
 */
function logMessage($message, $context = []) {
    if (!ENABLE_LOGGING) {
        return;
    }

    $logFile = __DIR__ . '/webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = $context ? ' | ' . json_encode($context) : '';

    $logEntry = "[{$timestamp}] {$message}{$contextStr}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Example: Save transaction to your database
 */
function saveTransactionToDatabase($transaction) {
    // Example using PDO
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=your_db', 'username', 'password');

        $stmt = $pdo->prepare("
            INSERT INTO transactions
            (transaction_id, transaction_date, description, type, amount, balance, processed_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE processed_at = NOW()
        ");

        $stmt->execute([
            $transaction['id'],
            $transaction['transaction_date'],
            $transaction['description'],
            $transaction['type'],
            $transaction['amount'],
            $transaction['balance']
        ]);

        return true;
    } catch (PDOException $e) {
        logMessage('Database error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Example: Confirm order (e-commerce)
 */
function confirmOrder($orderId, $amount, $transactionId) {
    // Check if order exists and amount matches
    // $order = getOrderById($orderId);
    // if ($order && $order['total'] == $amount) {
    //     updateOrderStatus($orderId, 'paid', $transactionId);
    //     sendConfirmationEmail($orderId);
    // }

    logMessage("Order confirmed: {$orderId}");
}

/**
 * Example: Process top-up
 */
function processTopUp($description, $amount, $transactionId) {
    // Extract customer phone/ID from description
    // $customerId = extractCustomerIdFromDescription($description);
    // if ($customerId) {
    //     addBalance($customerId, $amount);
    //     sendTopUpNotification($customerId, $amount);
    // }

    logMessage("Top-up processed: {$amount}");
}
