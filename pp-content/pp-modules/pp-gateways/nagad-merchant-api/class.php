<?php
/**
 * Nagad Merchant API Gateway - Direct API Implementation
 */

    class NagadMerchantApiGateway
    {
        public function info()
        {
            return [
                'title'       => 'Nagad Merchant Api',
                'logo'        => 'assets/logo.jpg',
                'currency'        => 'BDT',
                'tab'        => 'mfs',

                'gateway_type'        => 'api',
            ];
        }

        public function color()
        {
            return [
                'primary_color'        => '#ed1c24',
                'text_color'        => '#FFFFFF',
                'btn_color'        => '#ed1c24',
                'btn_text_color'        => '#FFFFFF',
            ];
        }

        public function fields()
        {
            return [
                [
                    'name'  => 'merchant_id',
                    'label' => 'Merchant ID',
                    'type'  => 'text',
                    'required' => true,
                ],
                [
                    'name'  => 'private_key',
                    'label' => 'Private Key',
                    'type'  => 'text',
                    'required' => true,
                ],
                [
                    'name'  => 'public_key',
                    'label' => 'Public Key',
                    'type'  => 'text',
                    'required' => true,
                ],
                [
                    'name'  => 'mode',
                    'label' => 'Mode',
                    'type'  => 'select',
                    'options' => [
                        'live'  => 'Live',
                        'sandbox' => 'Sandbox',
                    ],
                    'value' => 'live',
                    'required' => true,
                    'multiple' => false,
                ],
                [
                    'name'  => 'invoice_prefix',
                    'label' => 'Invoice Prefix',
                    'type'  => 'text',
                    'value' => 'INV',
                    'required' => false,
                    'hint' => 'Example: INV (max 15)',
                ],
            ];
        }

        function process_payment($data = []){
            // Validate input data
            if (!is_array($data) || empty($data)) {
                echo '<div class="alert alert-danger" role="alert">Invalid payment data</div><style>.loading-123412341234{display: none;}</style>';
                exit();
            }

            echo '<center><div class="spinner-border text-primary m-3 loading-123412341234" role="status"><span class="visually-hidden">Loading...</span></div></center>';

            // Get and validate settings
            $merchant_id  = trim($data['options']['merchant_id'] ?? '');
            $private_key  = trim($data['options']['private_key'] ?? '');
            $public_key   = trim($data['options']['public_key'] ?? '');
            $mode         = in_array($data['options']['mode'] ?? 'live', ['live', 'sandbox']) ? $data['options']['mode'] : 'live';
            $prefix       = preg_replace('/[^A-Z0-9]/', '', strtoupper($data['options']['invoice_prefix'] ?? 'INV'));
            $ref          = trim($data['transaction']['ref'] ?? '');
            $amount       = floatval($data['transaction']['local_net_amount'] ?? 0);
            $callback_url = filter_var(pp_callback_url(), FILTER_SANITIZE_URL);
            $site_url     = filter_var(pp_site_url(), FILTER_SANITIZE_URL);

            // Validate required fields
            if (empty($merchant_id) || empty($private_key) || empty($public_key)) {
                echo '<div class="alert alert-danger" role="alert">Gateway not configured properly. Missing Merchant ID, Private Key or Public Key.</div><style>.loading-123412341234{display: none;}</style>';
                exit();
            }

            // Validate amount
            if ($amount <= 0) {
                echo '<div class="alert alert-danger" role="alert">Invalid payment amount</div><style>.loading-123412341234{display: none;}</style>';
                exit();
            }

            // Nagad API Base URL
            $baseUrl = ($mode === 'sandbox') 
                ? 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs/'
                : 'https://api.mynagad.com/api/dfs/';

            // Check if this is callback (has payment_ref_id and status)
            $parts = explode('?', $site_url, 2);
            $queryString = isset($parts[1]) ? $parts[1] : '';
            $queryString = str_replace('/?', '&', $queryString);
            parse_str($queryString, $params);

            $payment_ref_id = $params['payment_ref_id'] ?? '';
            $status         = $params['status'] ?? '';

            // Handle Callback/Return
            if (!empty($payment_ref_id) && !empty($status)) {
                try {
                    $this->handleCallback($data, $baseUrl, $merchant_id, $public_key, $private_key, $payment_ref_id, $status);
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger" role="alert">Callback Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div><style>.loading-123412341234{display: none;}</style>';
                }
                return;
            }

            // Initiate Payment
            try {
                $this->initiatePayment($ref, $amount, $prefix, $baseUrl, $merchant_id, $public_key, $private_key, $callback_url);
            } catch (Exception $e) {
                echo '<div class="alert alert-danger" role="alert">Payment Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div><style>.loading-123412341234{display: none;}</style>';
                exit();
            }
        }

        /**
         * Initiate Payment Request
         */
        private function initiatePayment($ref, $amount, $prefix, $baseUrl, $merchantId, $publicKey, $privateKey, $callbackUrl)
        {
            // Order ID must be max 20 chars for Nagad
            $short_ref = substr($ref, -8);
            $random = substr(time(), -4);
            $invoice_id = $prefix . $short_ref . $random;
            
            if (strlen($invoice_id) > 20) {
                $invoice_id = substr($invoice_id, 0, 20);
            }

            set_env('nagad_pp_' . $invoice_id, $ref);

            date_default_timezone_set('Asia/Dhaka');
            $DateTime = date('YmdHis');
            $random   = $this->generateRandomString();

            $SensitiveData = [
                'merchantId' => $merchantId,
                'datetime'   => $DateTime,
                'orderId'    => $invoice_id,
                'challenge'  => $random
            ];

            $PostData = [
                'dateTime'      => $DateTime,
                'sensitiveData' => $this->encryptData(json_encode($SensitiveData), $publicKey),
                'signature'     => $this->generateSignature(json_encode($SensitiveData), $privateKey)
            ];

            $PostURL = $baseUrl . 'check-out/initialize/' . $merchantId . '/' . $invoice_id;
            $Result_Data = $this->httpPost($PostURL, $PostData);

            if (!isset($Result_Data['sensitiveData']) || !isset($Result_Data['signature'])) {
                $error = $Result_Data['message'] ?? 'Failed to initialize payment';
                echo '<div class="alert alert-danger" role="alert">' . $error . '</div><style>.loading-123412341234{display: none;}</style>';
                exit();
            }

            $PlainResponse = json_decode($this->decryptData($Result_Data['sensitiveData'], $privateKey), true);

            if (!isset($PlainResponse['paymentReferenceId']) || !isset($PlainResponse['challenge'])) {
                echo '<div class="alert alert-danger" role="alert">Invalid response from Nagad</div><style>.loading-123412341234{display: none;}</style>';
                exit();
            }

            $paymentReferenceId = $PlainResponse['paymentReferenceId'];
            $randomServer       = $PlainResponse['challenge'];

            $SensitiveDataOrder = [
                'merchantId'   => $merchantId,
                'orderId'      => $invoice_id,
                'currencyCode' => '050',
                'amount'       => (string) round($amount),
                'challenge'    => $randomServer
            ];

            $PostDataOrder = [
                'sensitiveData'         => $this->encryptData(json_encode($SensitiveDataOrder), $publicKey),
                'signature'             => $this->generateSignature(json_encode($SensitiveDataOrder), $privateKey),
                'merchantCallbackURL'   => $callbackUrl,
                'additionalMerchantInfo' => json_decode('{"callback": "' . $callbackUrl . '"}')
            ];

            $OrderSubmitUrl = $baseUrl . 'check-out/complete/' . $paymentReferenceId;
            $Result_Order   = $this->httpPost($OrderSubmitUrl, $PostDataOrder);

            if (isset($Result_Order['status']) && $Result_Order['status'] === 'Success') {
                $redirectUrl = $Result_Order['callBackUrl'];
                echo '<script>window.location.href = "' . htmlspecialchars($redirectUrl) . '";</script>';
                echo '<div class="text-center mt-3"><a href="' . htmlspecialchars($redirectUrl) . '" class="btn btn-primary">Click here if not redirected</a></div>';
                exit;
            }

            $error = $Result_Order['message'] ?? 'Failed to complete order';
            echo '<div class="alert alert-danger" role="alert">' . $error . '</div><style>.loading-123412341234{display: none;}</style>';
            exit();
        }

        /**
         * Handle Callback/Return
         */
        private function handleCallback($data, $baseUrl, $merchantId, $publicKey, $privateKey, $paymentRefId, $status)
        {
            // Validate callback parameters
            $paymentRefId = preg_replace('/[^A-Za-z0-9]/', '', $paymentRefId);
            $status = preg_replace('/[^A-Za-z]/', '', $status);
            
            if ($status === 'Aborted') {
                echo '<div class="alert alert-danger" role="alert">Transaction Canceled</div><style>.loading-123412341234{display: none;}</style>';
                return;
            }

            if ($status !== 'Success' && isset($_REQUEST['message'])) {
                $message = htmlspecialchars($_REQUEST['message'], ENT_QUOTES, 'UTF-8');
                echo '<div class="alert alert-danger" role="alert">' . $message . '</div><style>.loading-123412341234{display: none;}</style>';
                return;
            }

            $verifyUrl = $baseUrl . 'verify/payment/' . urlencode($paymentRefId);
            $verifyResult = $this->httpGet($verifyUrl);
            $result = json_decode($verifyResult, true);

            if (!isset($result['status']) || $result['status'] !== 'Success') {
                $error = $result['message'] ?? 'Payment verification failed';
                echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div><style>.loading-123412341234{display: none;}</style>';
                return;
            }

            $order_id = preg_replace('/[^A-Za-z0-9]/', '', $result['orderId'] ?? '');
            $transaction_ref = 0;

            if (get_env('nagad_pp_' . $order_id) !== '') {
                $transaction_ref = get_env('nagad_pp_' . $order_id);
            }

            if (empty($transaction_ref)) {
                $transaction_ref = $data['transaction']['ref'];
            }

            // Verify merchant ID matches
            if ($result['merchantId'] !== $merchantId) {
                echo '<div class="alert alert-danger" role="alert">Invalid Merchant</div><style>.loading-123412341234{display: none;}</style>';
                return;
            }

            // Sanitize response data
            $moreinfo = [
                ['label' => 'Client Mobile', 'value' => preg_replace('/[^0-9]/', '', $result['clientMobileNo'] ?? 'N/A')],
                ['label' => 'Service Type', 'value' => htmlspecialchars($result['serviceType'] ?? 'N/A', ENT_QUOTES, 'UTF-8')],
                ['label' => 'Issuer Ref', 'value' => preg_replace('/[^A-Za-z0-9]/', '', $result['issuerPaymentRefNo'] ?? 'N/A')],
            ];

            pp_set_transaction_status($data['transaction']['ref'], 'completed', $data['gateway']['gateway_id'], $paymentRefId, $moreinfo);

            global $site_url, $path_payment;
            $redirectUrl = rtrim($site_url, '/') . '/' . trim($path_payment, '/') . '/' . $data['transaction']['ref'];
            echo '<script>window.location.href = "' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '";</script>';
            echo '<div class="text-center mt-3"><a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary">Click here if not redirected</a></div>';
        }

        // ============== HELPER METHODS ==============

        private function generateRandomString($length = 40)
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            $maxIndex = strlen($characters) - 1;
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $maxIndex)];
            }
            return $randomString;
        }

        private function formatKey($key, $type)
        {
            // Remove existing headers/footers and whitespace
            $key = preg_replace('/-----BEGIN (PUBLIC KEY|RSA PRIVATE KEY)-----/', '', $key);
            $key = preg_replace('/-----END (PUBLIC KEY|RSA PRIVATE KEY)-----/', '', $key);
            $key = preg_replace('/\s+/', '', $key);
            
            // Add proper headers based on type
            if ($type === 'public') {
                return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($key, 64, "\n") . "-----END PUBLIC KEY-----";
            } else {
                return "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($key, 64, "\n") . "-----END RSA PRIVATE KEY-----";
            }
        }

        private function encryptData($data, $publicKey)
        {
            $key = $this->formatKey($publicKey, 'public');
            $resource = openssl_get_publickey($key);
            if (!$resource) {
                throw new Exception('Invalid public key');
            }
            openssl_public_encrypt($data, $encrypted, $resource);
            return base64_encode($encrypted);
        }

        private function generateSignature($data, $privateKey)
        {
            $key = $this->formatKey($privateKey, 'private');
            $resource = openssl_get_privatekey($key);
            if (!$resource) {
                throw new Exception('Invalid private key');
            }
            openssl_sign($data, $signature, $resource, OPENSSL_ALGO_SHA256);
            return base64_encode($signature);
        }

        private function decryptData($data, $privateKey)
        {
            $key = $this->formatKey($privateKey, 'private');
            $resource = openssl_get_privatekey($key);
            if (!$resource) {
                throw new Exception('Invalid private key for decrypt');
            }
            openssl_private_decrypt(base64_decode($data), $decrypted, $resource);
            return $decrypted;
        }

        private function httpPost($url, $data)
        {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid URL');
            }
            
            $ch = curl_init($url);
            if (!$ch) {
                throw new Exception('Failed to initialize cURL');
            }
            
            $payload = json_encode($data);
            if ($payload === false) {
                curl_close($ch);
                throw new Exception('Failed to encode JSON data');
            }
            
            $headers = [
                'Content-Type: application/json',
                'X-KM-Api-Version: v-0.2.0',
                'X-KM-IP-V4: ' . $this->getClientIp(),
                'X-KM-Client-Type: PC_WEB'
            ];

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $result = curl_exec($ch);
            if ($result === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception('cURL Error: ' . $error);
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('HTTP Error: ' . $httpCode);
            }

            $decoded = json_decode($result, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response');
            }
            
            return $decoded ?: [];
        }

        private function httpGet($url)
        {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid URL');
            }
            
            $ch = curl_init($url);
            if (!$ch) {
                throw new Exception('Failed to initialize cURL');
            }
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
            $result = curl_exec($ch);
            if ($result === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception('cURL Error: ' . $error);
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('HTTP Error: ' . $httpCode);
            }
            
            return $result;
        }

        private function getClientIp()
        {
            $ipHeaders = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            ];
            
            foreach ($ipHeaders as $header) {
                $ip = $_SERVER[$header] ?? '';
                if (!empty($ip)) {
                    // Validate IP address
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
            
            return '127.0.0.1'; // Default fallback
        }
    }
