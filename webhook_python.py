"""
Mutasibank Webhook Handler - Python/Flask

Secure webhook receiver with HMAC-SHA256 signature verification
"""

from flask import Flask, request, jsonify
import hmac
import hashlib
import time
import json
import os
from datetime import datetime

app = Flask(__name__)

# Configuration
WEBHOOK_SECRET = os.getenv('WEBHOOK_SECRET', 'your_webhook_secret_here')
API_TOKEN = os.getenv('API_TOKEN', 'your_api_token_here')
TIMESTAMP_TOLERANCE = 300  # 5 minutes


@app.route('/webhook/mutasibank', methods=['POST'])
def mutasibank_webhook():
    """
    Webhook endpoint with signature verification
    """
    try:
        # Step 1: Extract headers
        signature = request.headers.get('X-Mutasibank-Signature', '')
        timestamp = int(request.headers.get('X-Mutasibank-Timestamp', 0))
        webhook_id = request.headers.get('X-Mutasibank-Webhook-Id', '')

        # Step 2: Validate headers
        if not signature or not timestamp:
            app.logger.error('Missing signature headers')
            return jsonify({
                'error': 'Missing signature headers'
            }), 401

        # Step 3: Check timestamp (prevent replay attacks)
        current_time = int(time.time())
        time_diff = abs(current_time - timestamp)

        if time_diff > TIMESTAMP_TOLERANCE:
            app.logger.error(f'Timestamp expired. Diff: {time_diff}s')
            return jsonify({
                'error': 'Request expired',
                'timestamp_received': timestamp,
                'current_time': current_time,
                'difference_seconds': time_diff
            }), 401

        # Step 4: Get raw payload
        payload = request.get_data()

        if not payload:
            app.logger.error('Empty payload')
            return jsonify({'error': 'Empty payload'}), 400

        # Step 5: Verify signature (HMAC-SHA256)
        expected_signature = hmac.new(
            WEBHOOK_SECRET.encode(),
            payload,
            hashlib.sha256
        ).hexdigest()

        # Use constant-time comparison
        if not hmac.compare_digest(signature, expected_signature):
            app.logger.error('Invalid signature', extra={'webhook_id': webhook_id})
            return jsonify({
                'error': 'Invalid signature',
                'message': 'Webhook signature verification failed'
            }), 401

        app.logger.info(f'✅ Signature verified: {webhook_id}')

        # Step 6: Parse and validate data
        data = request.get_json()

        # Verify API token (double authentication)
        if data.get('api_key') != API_TOKEN:
            app.logger.error('Invalid API token')
            return jsonify({'error': 'Invalid API token'}), 401

        # Step 7: Process webhook data
        account_id = data['account_id']
        account_number = data['account_number']
        module = data['module']  # bca, bri, mandiri, bni
        balance = data['balance']
        transactions = data['data_mutasi']

        app.logger.info(f'Processing webhook for account: {account_number}', extra={
            'account_id': account_id,
            'module': module,
            'transaction_count': len(transactions),
            'balance': balance
        })

        # Process each transaction
        for transaction in transactions:
            transaction_id = transaction['id']
            transaction_date = transaction['transaction_date']
            description = transaction['description']
            tx_type = transaction['type']  # CR (credit) or DB (debit)
            amount = transaction['amount']
            tx_balance = transaction['balance']

            app.logger.info(f'Processing transaction: {transaction_id}', extra={
                'date': transaction_date,
                'type': tx_type,
                'amount': amount
            })

            # ================================================================
            # YOUR BUSINESS LOGIC HERE
            # ================================================================

            # Example 1: Auto-confirm order
            if tx_type == 'CR':
                order_id = extract_order_id(description)
                if order_id:
                    confirm_order(order_id, amount, transaction_id)

            # Example 2: Save to database
            # save_transaction(transaction)

        # Step 8: Return success response
        app.logger.info(f'✅ Webhook processed successfully: {webhook_id}', extra={
            'transactions_processed': len(transactions)
        })

        return jsonify({
            'success': True,
            'webhook_id': webhook_id,
            'transactions_processed': len(transactions),
            'timestamp': datetime.now().isoformat()
        }), 200

    except Exception as e:
        app.logger.error(f'Webhook processing error: {str(e)}')
        return jsonify({
            'error': 'Internal server error',
            'message': str(e)
        }), 500


# ============================================================================
# HELPER FUNCTIONS
# ============================================================================

def extract_order_id(description):
    """Extract order ID from transaction description"""
    import re

    patterns = [
        r'ORDER[:\-\s]*(\d+)',
        r'TRF\s+(\d+)',
        r'INV[:\-\s]*(\d+)'
    ]

    for pattern in patterns:
        match = re.search(pattern, description, re.IGNORECASE)
        if match:
            return match.group(1)

    return None


def confirm_order(order_id, amount, transaction_id):
    """Confirm order (example)"""
    # Your database logic here
    app.logger.info(f'Order confirmed: {order_id}', extra={
        'amount': amount,
        'transaction_id': transaction_id
    })

    # Example:
    # db.orders.update_one(
    #     {'_id': order_id},
    #     {
    #         '$set': {
    #             'status': 'paid',
    #             'payment_amount': amount,
    #             'transaction_id': transaction_id,
    #             'paid_at': datetime.now()
    #         }
    #     }
    # )


def save_transaction(transaction):
    """Save transaction to database (example)"""
    # Example using SQLAlchemy or MongoDB
    # db.transactions.insert_one({
    #     'transaction_id': transaction['id'],
    #     'date': datetime.fromisoformat(transaction['transaction_date']),
    #     'description': transaction['description'],
    #     'type': transaction['type'],
    #     'amount': transaction['amount'],
    #     'balance': transaction['balance'],
    #     'processed_at': datetime.now()
    # })

    app.logger.info(f'Transaction saved: {transaction["id"]}')


if __name__ == '__main__':
    PORT = int(os.getenv('PORT', 3000))
    print(f'🚀 Webhook server running on port {PORT}')
    print(f'📝 Endpoint: POST http://localhost:{PORT}/webhook/mutasibank')
    app.run(host='0.0.0.0', port=PORT, debug=False)
