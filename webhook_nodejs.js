/**
 * Mutasibank Webhook Handler - Node.js/Express
 *
 * Secure webhook receiver with HMAC-SHA256 signature verification
 */

const express = require('express');
const crypto = require('crypto');
const app = express();

// Configuration
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET || 'your_webhook_secret_here';
const API_TOKEN = process.env.API_TOKEN || 'your_api_token_here';
const TIMESTAMP_TOLERANCE = 300; // 5 minutes

// IMPORTANT: Use raw body for signature verification
app.use(express.json({
    verify: (req, res, buf) => {
        req.rawBody = buf.toString('utf8');
    }
}));

// Webhook endpoint
app.post('/webhook/mutasibank', async (req, res) => {
    try {
        // Step 1: Extract headers
        const signature = req.headers['x-mutasibank-signature'];
        const timestamp = parseInt(req.headers['x-mutasibank-timestamp']);
        const webhookId = req.headers['x-mutasibank-webhook-id'];

        // Step 2: Validate headers
        if (!signature || !timestamp) {
            console.error('Missing signature headers');
            return res.status(401).json({
                error: 'Missing signature headers'
            });
        }

        // Step 3: Check timestamp (prevent replay attacks)
        const currentTime = Math.floor(Date.now() / 1000);
        const timeDiff = Math.abs(currentTime - timestamp);

        if (timeDiff > TIMESTAMP_TOLERANCE) {
            console.error(`Timestamp expired. Diff: ${timeDiff}s`);
            return res.status(401).json({
                error: 'Request expired',
                timestamp_received: timestamp,
                current_time: currentTime,
                difference_seconds: timeDiff
            });
        }

        // Step 4: Verify signature
        const payload = req.rawBody || JSON.stringify(req.body);
        const expectedSignature = crypto
            .createHmac('sha256', WEBHOOK_SECRET)
            .update(payload)
            .digest('hex');

        // Use timing-safe comparison
        if (!crypto.timingSafeEqual(
            Buffer.from(signature),
            Buffer.from(expectedSignature)
        )) {
            console.error('Invalid signature', { webhookId });
            return res.status(401).json({
                error: 'Invalid signature'
            });
        }

        console.log('✅ Signature verified', { webhookId });

        // Step 5: Validate API token (double authentication)
        const data = req.body;
        if (data.api_key !== API_TOKEN) {
            console.error('Invalid API token');
            return res.status(401).json({
                error: 'Invalid API token'
            });
        }

        // Step 6: Process webhook data
        const accountId = data.account_id;
        const accountNumber = data.account_number;
        const module = data.module; // bca, bri, mandiri, bni
        const balance = data.balance;
        const transactions = data.data_mutasi;

        console.log(`Processing webhook for account: ${accountNumber}`, {
            accountId,
            module,
            transactionCount: transactions.length,
            balance
        });

        // Process each transaction
        for (const transaction of transactions) {
            const {
                id: transactionId,
                transaction_date,
                description,
                type, // CR (credit) or DB (debit)
                amount,
                balance: txBalance
            } = transaction;

            console.log(`Processing transaction: ${transactionId}`, {
                date: transaction_date,
                type,
                amount
            });

            // ================================================================
            // YOUR BUSINESS LOGIC HERE
            // ================================================================

            // Example 1: Auto-confirm order
            if (type === 'CR') {
                const orderId = extractOrderId(description);
                if (orderId) {
                    await confirmOrder(orderId, amount, transactionId);
                }
            }

            // Example 2: Process top-up
            // await processTopUp(description, amount, transactionId);

            // Example 3: Save to database
            // await saveTransaction(transaction);
        }

        // Step 7: Return success response
        console.log('✅ Webhook processed successfully', {
            webhookId,
            transactionsProcessed: transactions.length
        });

        res.json({
            success: true,
            webhook_id: webhookId,
            transactions_processed: transactions.length,
            timestamp: new Date().toISOString()
        });

    } catch (error) {
        console.error('Webhook processing error:', error);
        res.status(500).json({
            error: 'Internal server error',
            message: error.message
        });
    }
});

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Extract order ID from transaction description
 */
function extractOrderId(description) {
    // Match patterns like "ORDER-12345" or "TRF 12345"
    const patterns = [
        /ORDER[:\-\s]*(\d+)/i,
        /TRF\s+(\d+)/i,
        /INV[:\-\s]*(\d+)/i
    ];

    for (const pattern of patterns) {
        const match = description.match(pattern);
        if (match) {
            return match[1];
        }
    }

    return null;
}

/**
 * Confirm order (example)
 */
async function confirmOrder(orderId, amount, transactionId) {
    // Your database logic here
    console.log(`Order confirmed: ${orderId}`, { amount, transactionId });

    // Example:
    // await db.orders.update(
    //     { id: orderId },
    //     {
    //         status: 'paid',
    //         payment_amount: amount,
    //         transaction_id: transactionId,
    //         paid_at: new Date()
    //     }
    // );
}

/**
 * Save transaction to database (example)
 */
async function saveTransaction(transaction) {
    // Example using Mongoose (MongoDB)
    // await Transaction.create({
    //     transactionId: transaction.id,
    //     date: new Date(transaction.transaction_date),
    //     description: transaction.description,
    //     type: transaction.type,
    //     amount: transaction.amount,
    //     balance: transaction.balance,
    //     processedAt: new Date()
    // });

    console.log('Transaction saved:', transaction.id);
}

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Webhook server running on port ${PORT}`);
    console.log(`📝 Endpoint: POST http://localhost:${PORT}/webhook/mutasibank`);
});

module.exports = app;
