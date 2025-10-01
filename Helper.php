<?php

/**
 * Mutasibank API Helper Class
 *
 * Comprehensive helper class for Mutasibank API integration
 * Documentation: https://mutasibank.co.id/api/docs
 *
 * @version 2.0
 * @updated 2025-01-10
 */
class MutasibankAPI
{
    const API_URL = "https://mutasibank.co.id/api/v1";
    private $apiKey;

    /**
     * Constructor
     * @param string $apiKey Your API token from Mutasibank dashboard
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    // ========================================================================
    // AUTHENTICATION & USER
    // ========================================================================

    /**
     * Get current user information
     * @return array User data with balance and package info
     */
    public function getUser()
    {
        return $this->httpGet("/user");
    }

    // ========================================================================
    // BANK OPERATIONS
    // ========================================================================

    /**
     * Get list of supported banks
     * @return array List of all supported banks with their configuration
     */
    public function getListBank()
    {
        return $this->httpGet("/list_bank");
    }

    // ========================================================================
    // ACCOUNT OPERATIONS
    // ========================================================================

    /**
     * Get all bank accounts
     * @return array List of all registered bank accounts
     */
    public function getAccounts()
    {
        return $this->httpGet("/accounts");
    }

    /**
     * Get account by ID
     * @param int $accountId Account ID
     * @return array Account details
     */
    public function getAccount($accountId)
    {
        return $this->httpGet("/account/{$accountId}");
    }

    /**
     * Create new bank account
     * @param array $data Account configuration
     * @return array Created account details
     *
     * Required fields:
     * - bank_id: Bank ID from /list_bank
     * - account_name: Account holder name
     * - account_no: Bank account number
     * - user_banking: Internet banking username (if applicable)
     * - password_banking: Internet banking password (if applicable)
     *
     * Optional fields:
     * - api_key, api_secret, client_id, client_secret, corp_id: For API-based banks
     * - schedule_minutes: Bot check interval (default varies by bank)
     * - url_callback: Webhook URL for notifications
     * - email_notification, wa_number, telegram_id: Notification channels
     * - note: Account notes
     */
    public function createAccount($data)
    {
        return $this->httpPostJson("/account/create", $data);
    }

    /**
     * Update bank account
     * @param string $accountId Account UUID or ID
     * @param array $data Updated account data
     * @return array Response message
     */
    public function updateAccount($accountId, $data)
    {
        return $this->httpPut("/account/{$accountId}", $data);
    }

    /**
     * Delete bank account
     * @param string $accountId Account UUID or ID
     * @return array Response message
     */
    public function deleteAccount($accountId)
    {
        return $this->httpDelete("/account/{$accountId}");
    }

    /**
     * Toggle account status (on/off)
     * @param int $accountId Account ID
     * @param int $status 0 = off, 1 = on
     * @return array Response message
     */
    public function toggleAccountStatus($accountId, $status)
    {
        $data = ['status' => $status];
        return $this->httpPost("/on_off/{$accountId}", $data);
    }

    /**
     * Toggle account status (alternative endpoint)
     * @param string $accountId Account UUID
     * @return array Response message
     */
    public function toggleAccount($accountId)
    {
        return $this->httpPostJson("/account/{$accountId}/toggle", []);
    }

    /**
     * Input BCA token (for BCA accounts requiring token)
     * @param int $accountId Account ID
     * @param string $token1 First token from BCA KeyBCA
     * @param string $token2 Second token from BCA KeyBCA
     * @return array Response message
     */
    public function inputToken($accountId, $token1, $token2)
    {
        $data = [
            'token_1' => $token1,
            'token_2' => $token2
        ];
        return $this->httpPost("/input_token/{$accountId}", $data);
    }

    /**
     * Rerun bot check for account
     * @param int $accountId Account ID
     * @return array Response with next schedule time
     */
    public function rerunCheck($accountId)
    {
        return $this->httpGet("/rerun/{$accountId}");
    }

    /**
     * Get bot activity logs
     * @param int $accountId Account ID
     * @return array Bot activity logs and history
     */
    public function getLogBot($accountId)
    {
        return $this->httpGet("/log_bot/{$accountId}");
    }

    // ========================================================================
    // TRANSACTION & STATEMENT OPERATIONS
    // ========================================================================

    /**
     * Get account statements/transactions
     * @param int $accountId Account ID
     * @param string $dateFrom Start date (YYYY-MM-DD)
     * @param string $dateTo End date (YYYY-MM-DD)
     * @return array Transaction data
     */
    public function getStatements($accountId, $dateFrom, $dateTo)
    {
        $data = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
        return $this->httpPost("/statements/{$accountId}", $data);
    }

    /**
     * Match transaction by amount (returns first match)
     * @param int $accountId Account ID
     * @param int $amount Transaction amount to match
     * @param string $dateFrom Optional start date (YYYY-MM-DD)
     * @param string $dateTo Optional end date (YYYY-MM-DD)
     * @return array Matched transaction or empty if not found
     */
    public function matchTransaction($accountId, $amount, $dateFrom = null, $dateTo = null)
    {
        $data = ['amount' => $amount];
        if ($dateFrom) $data['date_from'] = $dateFrom;
        if ($dateTo) $data['date_to'] = $dateTo;

        return $this->httpPost("/match/{$accountId}", $data, 'application/x-www-form-urlencoded');
    }

    /**
     * Match multiple transactions by amount
     * @param int $accountId Account ID
     * @param int $amount Transaction amount to match
     * @return array All matched transactions
     */
    public function matchTransactions($accountId, $amount)
    {
        $data = ['amount' => $amount];
        return $this->httpPost("/matchs/{$accountId}", $data, 'application/x-www-form-urlencoded');
    }

    /**
     * Validate transaction by statement ID
     * @param string $transactionId Statement ID (UUID or integer)
     * @return array Transaction validation result
     */
    public function validateTransaction($transactionId)
    {
        return $this->httpGet("/validate/{$transactionId}");
    }

    // ========================================================================
    // CATEGORY MANAGEMENT
    // ========================================================================

    /**
     * Get all categories
     * @return array List of transaction categories
     */
    public function getCategories()
    {
        return $this->httpGet("/categories");
    }

    /**
     * Get category by ID
     * @param string $categoryId Category UUID
     * @return array Category details
     */
    public function getCategory($categoryId)
    {
        return $this->httpGet("/category/{$categoryId}");
    }

    /**
     * Create new category
     * @param string $name Category name
     * @param string $type Transaction type (CR or DB)
     * @param string $description Category description (optional)
     * @return array Created category
     */
    public function createCategory($name, $type, $description = '')
    {
        $data = [
            'name' => $name,
            'type' => $type,
            'description' => $description
        ];
        return $this->httpPostJson("/category", $data);
    }

    /**
     * Update category
     * @param string $categoryId Category UUID
     * @param array $data Updated category data
     * @return array Response message
     */
    public function updateCategory($categoryId, $data)
    {
        return $this->httpPut("/category/{$categoryId}", $data);
    }

    /**
     * Delete category
     * @param string $categoryId Category UUID
     * @return array Response message
     */
    public function deleteCategory($categoryId)
    {
        return $this->httpDelete("/category/{$categoryId}");
    }

    // ========================================================================
    // WEBHOOK MANAGEMENT
    // ========================================================================

    /**
     * Get all webhooks
     * @return array List of configured webhooks
     */
    public function getWebhooks()
    {
        return $this->httpGet("/webhooks");
    }

    /**
     * Get webhook by ID
     * @param int $webhookId Webhook ID
     * @return array Webhook details (secret is hidden for security)
     */
    public function getWebhook($webhookId)
    {
        return $this->httpGet("/webhook/{$webhookId}");
    }

    /**
     * Create new webhook
     * @param string $url Webhook URL (must be HTTPS)
     * @param int $bankAccountId Bank account ID
     * @param string $description Webhook description
     * @return array Created webhook with secret for signature verification
     */
    public function createWebhook($url, $bankAccountId, $description = '')
    {
        $data = [
            'url' => $url,
            'bank_account_id' => $bankAccountId,
            'description' => $description
        ];
        return $this->httpPostJson("/webhook", $data);
    }

    /**
     * Update webhook
     * @param string $webhookId Webhook UUID or ID
     * @param array $data Updated webhook data
     * @return array Response message
     */
    public function updateWebhook($webhookId, $data)
    {
        return $this->httpPut("/webhook/{$webhookId}", $data);
    }

    /**
     * Delete webhook
     * @param string $webhookId Webhook UUID or ID
     * @return array Response message
     */
    public function deleteWebhook($webhookId)
    {
        return $this->httpDelete("/webhook/{$webhookId}");
    }

    // ========================================================================
    // WALLET & BILLING
    // ========================================================================

    /**
     * Create top-up transaction
     * @param int $amount Amount to top up (min: 10000)
     * @param int $paymentId Payment method (1=BCA, 2=Mandiri)
     * @return array Top-up details with payment instructions
     */
    public function topupKredit($amount, $paymentId)
    {
        $data = [
            'jumlah' => $amount,
            'payment_id' => $paymentId
        ];
        return $this->httpPost("/topup_kredit", $data);
    }

    // ========================================================================
    // HTTP REQUEST METHODS (PRIVATE)
    // ========================================================================

    /**
     * HTTP GET request
     */
    private function httpGet($endpoint)
    {
        $headers = ["Authorization: " . $this->apiKey];
        return $this->request('GET', self::API_URL . $endpoint, null, $headers);
    }

    /**
     * HTTP POST request (form-urlencoded)
     */
    private function httpPost($endpoint, $data = [], $contentType = 'application/x-www-form-urlencoded')
    {
        $headers = [
            "Authorization: " . $this->apiKey,
            "Content-Type: " . $contentType
        ];

        if ($contentType === 'application/json') {
            $postData = json_encode($data);
        } else {
            $postData = http_build_query($data);
        }

        return $this->request('POST', self::API_URL . $endpoint, $postData, $headers);
    }

    /**
     * HTTP POST request (JSON)
     */
    private function httpPostJson($endpoint, $data = [])
    {
        $headers = [
            "Authorization: " . $this->apiKey,
            "Content-Type: application/json"
        ];

        return $this->request('POST', self::API_URL . $endpoint, json_encode($data), $headers);
    }

    /**
     * HTTP PUT request
     */
    private function httpPut($endpoint, $data = [])
    {
        $headers = [
            "Authorization: " . $this->apiKey,
            "Content-Type: application/json"
        ];

        return $this->request('PUT', self::API_URL . $endpoint, json_encode($data), $headers);
    }

    /**
     * HTTP DELETE request
     */
    private function httpDelete($endpoint)
    {
        $headers = ["Authorization: " . $this->apiKey];
        return $this->request('DELETE', self::API_URL . $endpoint, null, $headers);
    }

    /**
     * Generic HTTP request handler
     */
    private function request($method, $url, $data = null, $headers = [])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // SECURITY: Enable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_USERAGENT, "MutasibankAPI-PHP/2.0");

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'error' => true,
                'message' => 'cURL Error: ' . $error,
                'http_code' => $httpCode
            ];
        }

        return json_decode($result, true);
    }
}

// ============================================================================
// BACKWARD COMPATIBILITY - OLD Helper class
// ============================================================================

/**
 * Legacy Helper class (for backward compatibility)
 * @deprecated Use MutasibankAPI class instead
 */
class Helper
{
    const API_URL = "https://mutasibank.co.id/api/v1";
    const API_KEY = "YOUR_API_TOKEN_HERE";

    /**
     * Get all accounts (legacy method)
     * @deprecated Use MutasibankAPI::getAccounts()
     */
    public static function GetAccount()
    {
        $api = new MutasibankAPI(self::API_KEY);
        return $api->getAccounts();
    }

    /**
     * Get user info (legacy method)
     * @deprecated Use MutasibankAPI::getUser()
     */
    public static function GetUser()
    {
        $api = new MutasibankAPI(self::API_KEY);
        return $api->getUser();
    }

    /**
     * Get account statements (legacy method)
     * @deprecated Use MutasibankAPI::getStatements()
     */
    public static function GetAccountStatement($accId, $from, $to)
    {
        $api = new MutasibankAPI(self::API_KEY);
        return $api->getStatements($accId, $from, $to);
    }

    /**
     * Legacy HTTP POST method
     * @deprecated Use MutasibankAPI methods
     */
    public static function http_post($url, $param = [], $headers = [])
    {
        $fieldsString = http_build_query($param);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // SECURITY: Enable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Legacy HTTP GET method
     * @deprecated Use MutasibankAPI methods
     */
    public static function http_get($url, $headers = [])
    {
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // SECURITY: Enable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($ch, CURLOPT_TIMEOUT, 240);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}
