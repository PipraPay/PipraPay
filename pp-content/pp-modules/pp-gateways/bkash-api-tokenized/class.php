<?php
    class BkashApiTokenizedGateway
    {
        public function info()
        {
            return [
                'title'       => 'Bkash Api (Tokenized)',
                'logo'        => 'assets/logo.jpg',
                'currency'        => 'BDT',
                'tab'        => 'mfs',

                'gateway_type'        => 'api',
            ];
        }

        public function color()
        {
            return [
                'primary_color'        => '#D12053',
                'text_color'        => '#FFFFFF',
                'btn_color'        => '#D12053',
                'btn_text_color'        => '#FFFFFF',
            ];
        }

        public function fields()
        {
            return [
                [
                    'name'  => 'username',
                    'label' => 'Username',
                    'type'  => 'text',
                ],
                [
                    'name'  => 'password',
                    'label' => 'Password',
                    'type'  => 'text',
                ],
                [
                    'name'  => 'app_key',
                    'label' => 'App Key',
                    'type'  => 'text',
                ],
                [
                    'name'  => 'app_secret_key',
                    'label' => 'App Secret Key',
                    'type'  => 'text',
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
                    'name'  => 'auto_refund',
                    'label' => 'Auto Refund API',
                    'type'  => 'select',
                    'options' => [
                        'on'  => 'On',
                        'off' => 'Off',
                    ],
                    'value' => 'off',
                    'required' => false,
                    'multiple' => false,
                ],
            ];
        }

        function process_payment($data = []){
            echo '<center><div class="spinner-border text-primary m-3 loading-123412341234" role="status"><span class="visually-hidden">Loading...</span></div></center>';

            $base_url = (($data['options']['mode'] ?? 'sandbox') === 'live') ? 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized' : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';

            $request_data = array(
                'app_key'=> ($data['options']['app_key'] ?? ''),
                'app_secret'=> ($data['options']['app_secret_key'] ?? '')
            );	

            $url = curl_init($base_url.'/checkout/token/grant');
            $request_data_json=json_encode($request_data);
            $header = array(
                'Content-Type:application/json',
                'username:'.($data['options']['username'] ?? ''),				
                'password:'.($data['options']['password'] ?? '')
            );	

            curl_setopt($url,CURLOPT_HTTPHEADER, $header);
            curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url,CURLOPT_POSTFIELDS, $request_data_json);
            curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($url, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $token_grand =  json_decode(curl_exec($url), true);
            curl_close($url);

            $token_grand_bk = ($token_grand['id_token'] ?? '');

            if($token_grand_bk !== ""){
                $_SESSION['bk-token'] = $token_grand_bk;
            }

            $requestbody = array(
                'mode' => '0011',
                'amount' => $data['transaction']['local_net_amount'],
                'currency' => $data['transaction']['local_currency'],
                'intent' => 'sale',
                'payerReference' => 'BillPax',
                'merchantInvoiceNumber' => rand().'-BP-'.$data['transaction']['ref'],
                'callbackURL' => pp_callback_url()
            );
            $url = curl_init($base_url.'/checkout/create');                     
            $requestbodyJson = json_encode($requestbody);
            
            $header = array(
                'Content-Type:application/json',
                "accept: application/json",
                'Authorization:' . ($token_grand['id_token'] ?? ''),
                'X-APP-Key:' . ($data['options']['app_key'] ?? '')
            );

            curl_setopt($url, CURLOPT_HTTPHEADER, $header);
            curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url, CURLOPT_POSTFIELDS, $requestbodyJson);
            curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($url, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $resultdata = json_decode(curl_exec($url), true);
            curl_close($url);

            if(isset($resultdata['bkashURL'])){
                echo '<script>location.href="' . $resultdata['bkashURL'] . '";</script>';
            }else{
                echo "<center>Bkash Initialize Error</center> <style>.loading-123412341234{display: none;}</style>";
            }
        }

        function callback($data = []){
            echo '<center><div class="spinner-border text-primary m-3 loading-123412341234" role="status"><span class="visually-hidden">Loading...</span></div></center>';

            $base_url = (($data['options']['mode'] ?? 'sandbox') === 'live') ? 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized' : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';

            $status = $_GET['status'] ?? '';

            if($status == "success"){
                $paymentID = $_GET['paymentID'] ?? '';
                $auth = $_SESSION['bk-token'] ?? '';

                $post_token = array('paymentID' => $paymentID);

                $url = curl_init($base_url.'/checkout/execute');       
                $posttoken = json_encode($post_token);

                $header = array(
                    'Content-Type:application/json',
                    'Authorization:' . $auth,
                    'X-APP-Key:'.($data['options']['app_key'] ?? '')
                );
                curl_setopt($url, CURLOPT_HTTPHEADER, $header);
                curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($url, CURLOPT_POSTFIELDS, $posttoken);
                curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($url, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                $resultdata = curl_exec($url);

                curl_close($url);

                $obj = json_decode($resultdata, true);

                if(isset($obj['statusMessage'])){
                    if($obj['statusMessage'] == 'Successful'){
                        $merchantInvoiceNumber = $obj['merchantInvoiceNumber'];

                        $parts = explode('-BP-', $merchantInvoiceNumber);

                        $afterBP = $parts[1] ?? null;

                        if($afterBP == $data['transaction']['ref']){
                            $moreinfo = [
                                [
                                    'label' => 'Payment ID',
                                    'value' => $obj['paymentID']
                                ]
                            ];

                            pp_set_transaction_status($data['transaction']['ref'], 'completed', $data['gateway']['gateway_id'], $obj['trxID'], $moreinfo);

                            echo "<script>location.reload();</script>";
                        }else{
                            echo '<div class="alert alert-danger" role="alert">Transaction not valid or not found.</div><style>.loading-123412341234{display: none;}</style>';
                        }
                    }else{
                        echo '<div class="alert alert-danger" role="alert">Transaction not valid or not found.</div><style>.loading-123412341234{display: none;}</style>';
                    }
                }else{
                    echo '<div class="alert alert-danger" role="alert">Transaction not valid or not found.</div><style>.loading-123412341234{display: none;}</style>';
                }
            }else{
                if($status == "cancel"){
                    echo '<script>location.href="'.pp_checkout_address().'";</script>';
                }else{
                    echo '<div class="alert alert-danger" role="alert">Transaction not valid or not found.</div><style>.loading-123412341234{display: none;}</style>';
                }
            }
        }

        public function refund($data = []){
            $transaction = $data['transaction'] ?? [];
            $options = $data['options'] ?? [];
            $refund = $data['refund'] ?? [];

            $paymentId = $refund['paymentId'] ?? '';
            if ($paymentId === '') {
                $paymentId = $this->extractPaymentId($transaction['source_info'] ?? '');
            }

            $trxId = $transaction['trx_id'] ?? '';
            $refundAmount = $refund['amount'] ?? ($transaction['local_net_amount'] ?? '');
            $refundAmount = money_round($refundAmount);

            $sku = $refund['sku'] ?? ($transaction['ref'] ?? 'refund');
            $reason = $refund['reason'] ?? 'Admin refund';

            if ($paymentId === '') {
                return [
                    'status' => false,
                    'message' => 'Payment ID not found for this transaction.',
                ];
            }

            if ($trxId === '' || $refundAmount === '' || $refundAmount === '0') {
                return [
                    'status' => false,
                    'message' => 'Missing required refund fields.',
                ];
            }

            $base_url = (($options['mode'] ?? 'sandbox') === 'live') ? 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized' : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';

            $tokenData = $this->grantToken($base_url, $options);
            $token = $tokenData['token'] ?? '';
            if ($token === '') {
                return [
                    'status' => false,
                    'message' => 'Failed to obtain bKash token.',
                ];
            }

            $headers = [
                'Authorization:' . $token,
                'X-APP-Key:' . ($options['app_key'] ?? ''),
            ];

            $refundPayload = [
                'paymentId' => $paymentId,
                'trxId' => $trxId,
                'refundAmount' => $refundAmount,
                'sku' => $sku,
                'reason' => $reason,
            ];

            $refundRes = $this->postJsonWithFallback('/v2/tokenized-checkout/refund/payment/transaction', $refundPayload, $headers, $base_url);
            $refundBaseUrl = $refundRes['used_base'] ?? $base_url;
            $refundData = $refundRes['data'] ?? [];
            if (empty($refundData) && !empty($refundRes['raw'])) {
                $refundData = ['raw' => $refundRes['raw']];
            }

            if (!empty($refundData['refundTransactionStatus']) && strtolower($refundData['refundTransactionStatus']) === 'completed') {
                return [
                    'status' => true,
                    'message' => 'Refund completed.',
                    'data' => $refundData,
                ];
            }

            if ($refundRes['ok'] !== true) {
                $err = $refundRes['error'] ?? '';
                $code = $refundRes['http_code'] ?? 0;
                $msg = $err !== '' ? $err : 'Refund request failed.';
                if ($code) {
                    $msg .= ' (HTTP '.$code.')';
                }

                return [
                    'status' => false,
                    'message' => $msg,
                    'data' => $refundData,
                ];
            }

            $statusPayload = [
                'paymentId' => $paymentId,
                'trxId' => $trxId,
            ];

            $statusRes = $this->postJsonWithFallback('/v2/tokenized-checkout/refund/payment/status', $statusPayload, $headers, $refundBaseUrl);
            $statusData = $statusRes['data'] ?? [];
            if (empty($statusData) && !empty($statusRes['raw'])) {
                $statusData = ['raw' => $statusRes['raw']];
            }

            $matched = $this->findCompletedRefund($statusData, $refundAmount);
            if (!empty($matched)) {
                return [
                    'status' => true,
                    'message' => 'Refund completed.',
                    'data' => $matched,
                    'status_data' => $statusData,
                ];
            }

            $errorMessage = $refundData['errorMessageEn'] ?? ($statusData['errorMessageEn'] ?? '');
            $externalCode = $refundData['externalCode'] ?? ($statusData['externalCode'] ?? '');
            $statusMessage = $refundData['statusMessage'] ?? ($statusData['statusMessage'] ?? '');

            if ($errorMessage === '') {
                $raw = $refundData['raw'] ?? ($statusData['raw'] ?? '');
                if ($raw !== '') {
                    $errorMessage = substr($raw, 0, 300);
                }
            }

            if ($errorMessage === '' && $statusMessage !== '') {
                $errorMessage = $statusMessage;
            }

            if ($errorMessage === '') {
                $errorMessage = 'Refund failed.';
            }

            if ($externalCode !== '') {
                $errorMessage = $errorMessage.' (Code '.$externalCode.')';
            }

            return [
                'status' => false,
                'message' => $errorMessage,
                'data' => $refundData,
                'status_data' => $statusData,
            ];
        }

        private function grantToken($base_url, $options = []){
            $request_data = array(
                'app_key'=> ($options['app_key'] ?? ''),
                'app_secret'=> ($options['app_secret_key'] ?? '')
            );

            $headers = array(
                'username:'.($options['username'] ?? ''),
                'password:'.($options['password'] ?? '')
            );

            $response = $this->postJson($base_url.'/checkout/token/grant', $request_data, $headers, 30);

            $token = $response['data']['id_token'] ?? '';
            if ($token !== '') {
                $_SESSION['bk-token'] = $token;
            }

            return [
                'token' => $token,
                'response' => $response,
            ];
        }

        private function postJson($url, $payload, $headers = [], $timeout = 30){
            $ch = curl_init($url);
            $body = json_encode($payload);

            $defaultHeaders = array(
                'Content-Type:application/json',
                'Accept: application/json'
            );

            $mergedHeaders = array_merge($defaultHeaders, $headers);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $mergedHeaders);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $raw = curl_exec($ch);
            $error = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($raw, true);

            return [
                'ok' => ($error === '' && $raw !== false),
                'http_code' => $code,
                'raw' => $raw,
                'data' => $data,
                'error' => $error,
                'url' => $url,
            ];
        }

        private function extractPaymentId($sourceInfo){
            if (is_string($sourceInfo)) {
                $sourceInfo = json_decode($sourceInfo, true);
            }

            if (!is_array($sourceInfo)) {
                return '';
            }

            foreach ($sourceInfo as $item) {
                $label = $item['label'] ?? '';
                $value = $item['value'] ?? '';

                if ($label === '' || $value === '') {
                    continue;
                }

                $labelLower = strtolower($label);
                if (strpos($labelLower, 'payment') !== false && strpos($labelLower, 'id') !== false) {
                    return $value;
                }

                if ($labelLower === 'paymentid' || $labelLower === 'payment id') {
                    return $value;
                }
            }

            return '';
        }

        private function findCompletedRefund($statusData, $amount){
            $refundTransactions = $statusData['refundTransactions'] ?? [];
            if (!is_array($refundTransactions)) {
                return [];
            }

            $targetAmount = money_round($amount);

            foreach ($refundTransactions as $item) {
                $status = strtolower($item['refundTransactionStatus'] ?? '');
                $refundAmount = money_round($item['refundAmount'] ?? '0');

                if ($status === 'completed' && ($targetAmount === '0' || $refundAmount === $targetAmount)) {
                    return $item;
                }
            }

            return [];
        }

        private function postJsonWithFallback($path, $payload, $headers, $baseUrl){
            $candidates = $this->getRefundBaseCandidates($baseUrl);
            $lastResponse = null;
            $usedBase = $baseUrl;

            foreach ($candidates as $candidate) {
                $usedBase = $candidate;
                $resp = $this->postJson($candidate.$path, $payload, $headers, 30);
                $lastResponse = $resp;

                if (!$this->isAwsAuthError($resp)) {
                    $resp['used_base'] = $candidate;
                    return $resp;
                }

            }

            if (is_array($lastResponse)) {
                $lastResponse['used_base'] = $usedBase;
                return $lastResponse;
            }

            return [
                'ok' => false,
                'http_code' => 0,
                'raw' => '',
                'data' => [],
                'error' => 'No response',
                'url' => $usedBase.$path,
                'used_base' => $usedBase,
            ];
        }

        private function getRefundBaseCandidates($baseUrl){
            $base = rtrim($baseUrl, '/');
            $candidates = [];

            $candidates[] = $base;

            if (substr($base, -10) === '/tokenized') {
                $candidates[] = substr($base, 0, -10);
            }

            $parts = parse_url($base);
            if (!empty($parts['scheme']) && !empty($parts['host'])) {
                $root = $parts['scheme'].'://'.$parts['host'];
                $candidates[] = $root;
                $candidates[] = $root.'/v1.2.0-beta';
                $candidates[] = $root.'/v1.2.0-beta/tokenized';
            }

            $unique = [];
            foreach ($candidates as $c) {
                if ($c !== '' && !in_array($c, $unique, true)) {
                    $unique[] = $c;
                }
            }

            return $unique;
        }

        private function isAwsAuthError($response){
            $message = '';
            if (is_array($response)) {
                $message = $response['data']['message'] ?? ($response['raw'] ?? '');
            }
            if (!is_string($message)) {
                return false;
            }
            return (strpos($message, 'Authorization header requires') !== false
                && strpos($message, 'Credential') !== false);
        }
    }
