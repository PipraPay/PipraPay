<?php
    declare(strict_types=1);

    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    if (date_default_timezone_get() !== 'UTC') {
        date_default_timezone_set('UTC');
    }

    $pp_functions_loaded = true;
    
    function pp_site_url($type = "Full") {
        // Detect protocol
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                    || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

        // Full host with subdomain
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Request URI (path after domain)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // Extract main domain
        $hostParts = explode('.', $host);
        $numParts = count($hostParts);

        if ($numParts >= 2) {
            // Handles domains like example.com or sub.example.com
            $mainDomain = $hostParts[$numParts - 2] . '.' . $hostParts[$numParts - 1];
        } else {
            $mainDomain = $host; // fallback
        }

        switch (strtolower($type)) {
            case "fulldomain":
                return $protocol.$host; // subdomain + main domain
            case "maindomain":
                return $mainDomain; // main domain only
            case "full":
            default:
                return $protocol . $host . $requestUri; // full URL
        }
    }

    function getAdminPath($url) {
        // Remove query string
        $url = explode('?', $url)[0];

        // Find position of admin/
        $pos = strpos($url, 'admin/');
        if ($pos === false) return ''; // admin/ not found

        // Get everything after admin/
        $path = substr($url, $pos + strlen('admin/'));

        // Remove leading/trailing slashes
        $path = trim($path, '/');

        return $path;
    }

    function getAuthorizationHeader() {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['MHS-PIPRAPAY-API-KEY'])) {
                return trim($headers['MHS-PIPRAPAY-API-KEY']);
            }
        }
    
        foreach ($_SERVER as $key => $value) {
            if (stripos($key, 'HTTP_MHS_PIPRAPAY_API_KEY') !== false) {
                return trim($value);
            }
        }
    
        return null;
    }

    function connectDatabase() {
        global $db_host, $db_port, $db_user, $db_pass, $db_name;
        $db_port = $db_port ?? 3306; // fallback

    try {
            // Build DSN
            $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";

            // Create PDO instance
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // Throw exceptions on error
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch associative arrays
                PDO::ATTR_EMULATE_PREPARES => false               // Use native prepared statements
            ]);

            return $pdo;
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    function timeAgo($datetime) {
        global $global_response_brand;

        // Determine user timezone or default to Dhaka
        $userTimezone = (!empty($global_response_brand['response'][0]['timezone']) && $global_response_brand['response'][0]['timezone'] !== '--')
            ? $global_response_brand['response'][0]['timezone']
            : 'Asia/Dhaka';

        // Create DateTime objects in the user's timezone
        $tz = new DateTimeZone($userTimezone);

        // Convert the input datetime (assumed UTC) to user's timezone
        $past = new DateTime($datetime, new DateTimeZone('UTC'));
        $past->setTimezone($tz);

        // Get current time in user's timezone
        $now = new DateTime('now', $tz);

        // Calculate difference
        $diff = $now->diff($past);

        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        } elseif ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        } elseif ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }

    function getCurrentDatetime($format = 'Y-m-d H:i:s') {
        $currentDatetime = new DateTime();

        return $currentDatetime->format($format);
    }   

    function getUserDeviceInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
        if (preg_match('/mobile/i', $userAgent)) {
            $deviceType = "Mobile";
        } elseif (preg_match('/tablet/i', $userAgent)) {
            $deviceType = "Tablet";
        } else {
            $deviceType = "Desktop";
        }
    
        if (preg_match('/Windows/i', $userAgent)) {
            $os = "Windows";
        } elseif (preg_match('/Mac/i', $userAgent)) {
            $os = "Mac OS";
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = "Linux";
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = "Android";
        } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
            $os = "iOS";
        } else {
            $os = "Unknown OS";
        }
    
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = "Internet Explorer";
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = "Firefox";
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = "Chrome";
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = "Safari";
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = "Opera";
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = "Edge";
        } else {
            $browser = "Unknown Browser";
        }
    
        return [
            'ip_address' => $ipAddress,
            'device' => $deviceType,
            'os' => $os,
            'browser' => $browser
        ];
    }

    // Set a cookie securely (supports all panels)
    function setsCookie($cookieName, $cookieValue, $days = 365) {
        $expiryTime = time() + ($days * 24 * 60 * 60);
    
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    
        setcookie($cookieName, $cookieValue, [
            'expires' => $expiryTime,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax', // Use 'None' if cross-domain needed (and use HTTPS)
        ]);
    }
    
    // Get the value of a cookie
    function getCookie($cookieName) {
        return $_COOKIE[$cookieName] ?? null;
    }
    
    // Logout: clear all cookies and destroy session
    function logoutCookie() {
        // Expire all cookies
        foreach ($_COOKIE as $name => $value) {
            setcookie($name, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    
        // Clear session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    function escape_string($value) {
        /*$conn = connectDatabase();
        $value = mysqli_real_escape_string($conn, $value);*/

        return $value;
    }   

    function getData($tableName, $coloum_name, $type = "* FROM", $params = []) {
        $pdo = connectDatabase(); // PDO connection

        // Build SQL
        $sql = "SELECT $type `$tableName` $coloum_name";

        try {
            $stmt = $pdo->prepare($sql); // prepare statement

            // Bind parameters if any
            foreach ($params as $key => $value) {
                // Detect integer for proper PDO type
                $pdoType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value, $pdoType);
            }

            $stmt->execute(); // execute

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$row) {
                foreach ($row as $col => $val) {
                    if (is_null($val)) {
                        $row[$col] = '--';
                    }
                }
            }

            if ($data) {
                return json_encode(['status' => true, 'response' => $data]);
            } else {
                return json_encode(['status' => false, 'response' => []]);
            }

        } catch (PDOException $e) {
            error_log("getData PDO Error: " . $e->getMessage());
            return json_encode(['status' => false, 'response' => []]);
        }
    }

    function insertData($tableName, $columns, $values) {
        $pdo = connectDatabase(); 

        try {
            $stmtColumns = $pdo->prepare("SHOW COLUMNS FROM `$tableName`");
            $stmtColumns->execute();
            $tableCols = $stmtColumns->fetchAll(PDO::FETCH_ASSOC);

            $finalColumns = [];
            $finalValues = [];
            $placeholders = [];

            $userData = array_combine($columns, $values);

            foreach ($tableCols as $col) {
                $colName = $col['Field'];

                if (strpos(strtolower($col['Extra']), 'auto_increment') !== false && !isset($userData[$colName])) {
                    continue;
                }

                $finalColumns[] = $colName;
                $placeholders[] = ":val_$colName";

                if (isset($userData[$colName])) {
                    $finalValues[$colName] = $userData[$colName];
                } else {
                    if ($col['Default'] !== null) {
                        $finalValues[$colName] = $col['Default'];
                    } else {
                        $finalValues[$colName] = "--";
                    }
                }
            }

            $sql = "INSERT INTO `$tableName` (" . implode(", ", $finalColumns) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $pdo->prepare($sql);

            foreach ($finalValues as $colName => $val) {
                $stmt->bindValue(":val_$colName", $val);
            }

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Insert failed: " . $e->getMessage());
            return false;
        }
    }

    function updateData($tableName, $columns, $values, $condition) {
        $pdo = connectDatabase(); 

        $setClauses = [];
        foreach ($columns as $index => $col) {
            $setClauses[] = "$col = :val$index";
        }
        $setString = implode(", ", $setClauses);

        $sql = "UPDATE `$tableName` SET $setString WHERE $condition";

        try {
            $stmt = $pdo->prepare($sql);

            foreach ($values as $index => $value) {
                if ($value === "" || is_null($value)) {
                    $value = "--";
                }

                $stmt->bindValue(":val$index", $value);
            }

            return $stmt->execute(); 
        } catch (PDOException $e) {
            error_log("updateData PDO Error: " . $e->getMessage());
            return false;
        }
    }

    function deleteData($tableName, $condition) {
        $pdo = connectDatabase(); // PDO connection

        $sql = "DELETE FROM `$tableName` WHERE $condition";

        try {
            $stmt = $pdo->prepare($sql);
            return $stmt->execute(); // returns true/false
        } catch (PDOException $e) {
            error_log("deleteData PDO Error: " . $e->getMessage());
            return false;
        }
    }

    function limit_checker($tableName, $db_prefix) {
        $count = 1;

        if($tableName == "transactions"){
            $response_limit = json_decode(getData($db_prefix.'transaction',' WHERE status = "completed"'),true);
            if($response_limit['status'] == true){
                foreach($response_limit['response'] as $row){
                    $count = $count+1;
                }
            }
        }else{
            $response_limit = json_decode(getData($db_prefix.'domain',' '),true);
            if($response_limit['status'] == true){
                foreach($response_limit['response'] as $row){
                    $count = $count+1;
                }
            }
        }

        return $count; 
    }

    function generateStrongPassword($length = 8) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@#$%&!';
        return substr(str_shuffle(str_repeat($chars, 5)), 0, $length);
    }

    function generateItemID($length = 10, $maxLength = 10)
    {
        // Ensure length does not exceed max
        $length = ($length > $maxLength) ? $maxLength : $length;

        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= mt_rand(0, 9);
        }

        return $id;
    }

    function getNameChars(string $fullName, int $length = 2): string
    {
        $fullName = trim($fullName);

        if ($fullName === '' || $length <= 0) {
            return '';
        }

        // Split name by spaces (remove extra spaces)
        $parts = array_values(array_filter(explode(' ', $fullName)));

        // If multiple words, use first + last
        if (count($parts) > 1) {
            $first = $parts[0];
            $last  = end($parts);

            $result = strtoupper(
                substr($first, 0, 1) .
                substr($last, 0, max(0, $length - 1))
            );
        } else {
            // Single name
            $result = strtoupper(substr($parts[0], 0, $length));
        }

        return $result;
    }

    function moneyToInt(string $amount, int $decimals = 2): int {
        $amount = money_sanitize($amount);
        $multiplier = bcpow("10", (string)$decimals);
        return (int) bcmul($amount, $multiplier, 0);
    }

    function intToMoney(int $amount, int $decimals = 2): string {
        $divisor = bcpow("10", (string)$decimals);
        return bcdiv((string)$amount, $divisor, $decimals);
    }

    function money_sanitize(string|int|float|null $value): string {
        if (is_numeric($value)) {
            return (string)$value;
        }
        return "0";
    }

    function money_add($a, $b, int $scale = 8): string {
        $a = money_sanitize($a);
        $b = money_sanitize($b);
        return bcadd($a, $b, $scale);
    }

    function money_sub($a, $b, int $scale = 8): string {
        $a = money_sanitize($a);
        $b = money_sanitize($b);
        return bcsub($a, $b, $scale);
    }

    function money_mul($a, $b, int $scale = 8): string {
        $a = money_sanitize($a);
        $b = money_sanitize($b);
        return bcmul($a, $b, $scale);
    }

    function money_div($a, $b, int $scale = 8): string {
        $a = money_sanitize($a);
        $b = money_sanitize($b);
        if (bccomp($b, '0', $scale) === 0) {
            return "0";
        }
        return bcdiv($a, $b, $scale);
    }

    function money_round($amount, int $decimals = 2): string {
        $amount = money_sanitize($amount);
        $factor = bcpow('10', (string)($decimals + 1));
        $tmp = bcmul($amount, $factor, 0);
        $tmp = bcdiv($tmp, '10', 0); 
        return bcdiv($tmp, bcpow('10', (string)$decimals), $decimals);
    }

    function pp_get_gateway_options($gateway_id = '', $brand_id = ''){
        global $db_prefix;

        $options = [];

        if ($gateway_id === '' || $brand_id === '') {
            return $options;
        }

        $params = [ ':gateway_id' => $gateway_id, ':brand_id' => $brand_id ];
        $response_gateways_parameter = json_decode(getData($db_prefix.'gateways_parameter','WHERE gateway_id = :gateway_id AND brand_id = :brand_id', '* FROM', $params),true);

        if ($response_gateways_parameter['status'] == true) {
            foreach($response_gateways_parameter['response'] as $field){
                $value = $field['value'];

                if(!empty($field['multiple']) && !empty($value)){
                    $value = is_array($value) ? $value : json_decode($value, true);
                }

                $options[$field['option_name']] = $value;
            }
        }

        return $options;
    }

    function pp_bkash_tokenized_refund($transaction = [], $refund = []){
        $gateway_id = $transaction['gateway_id'] ?? '';
        $brand_id = $transaction['brand_id'] ?? '';

        if ($gateway_id === '' || $brand_id === '') {
            return [
                'status' => false,
                'message' => 'Gateway or brand not found.',
            ];
        }

        $options = $refund['options'] ?? pp_get_gateway_options($gateway_id, $brand_id);

        if (empty($options)) {
            return [
                'status' => false,
                'message' => 'bKash configuration is missing.',
            ];
        }

        if (isset($options['auto_refund']) && $options['auto_refund'] === 'off') {
            return [
                'status' => false,
                'message' => 'Auto refund is disabled for this gateway.',
            ];
        }

        $gateway_path = __DIR__ . '/../pp-modules/pp-gateways/bkash-api-tokenized/class.php';
        if (!file_exists($gateway_path)) {
            return [
                'status' => false,
                'message' => 'bKash gateway not installed.',
            ];
        }

        require_once $gateway_path;

        if (!class_exists('BkashApiTokenizedGateway')) {
            return [
                'status' => false,
                'message' => 'bKash gateway class not found.',
            ];
        }

        $gateway = new BkashApiTokenizedGateway();

        if (!method_exists($gateway, 'refund')) {
            return [
                'status' => false,
                'message' => 'Refund not supported by this gateway.',
            ];
        }

        $payload = [
            'transaction' => $transaction,
            'options' => $options,
            'refund' => $refund,
        ];

        return $gateway->refund($payload);
    }


    function verifyPaymentTolerance(string $checkout, string $paid, string $tolerance): bool{
        $checkout  = money_round($checkout);
        $paid      = money_round($paid);
        $tolerance = money_round($tolerance);

        if (bccomp($checkout, "0", 8) <= 0 || bccomp($paid, "0", 8) <= 0) {
            return false;
        }

        // max allowed = checkout + tolerance
        $maxAllowed = money_add($checkout, $tolerance);

        return (
            bccomp($paid, $checkout, 8) >= 0 &&
            bccomp($paid, $maxAllowed, 8) <= 0
        );
    }

    function dateformat($date, $format = 'd/m/Y') {
        $d = DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    function convertUTCtoUserTZ($utc_time, $user_tz = 'UTC', $format = 'Y-m-d H:i:s') {
        $dt = new DateTime($utc_time, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($user_tz));
        return $dt->format($format);
    }

    function isExpired($expires_at){
        if (empty($expires_at) || $expires_at === '--') {
            return false; 
        }

        $timestamp = strtotime($expires_at);

        if ($timestamp === false) {
            return true;
        }

        if (preg_match('/^\d{1,4}[-\/]\d{1,2}[-\/]\d{1,4}$/', $expires_at)) {
            $timestamp = strtotime(date('Y-m-d 23:59:59', $timestamp));
        }

        return time() > $timestamp;
    }

    function getParam(array $params, string $key): ?string {
        if (!isset($params[$key]) || !is_string($params[$key])) {
            return null;
        }

        $value = trim($params[$key]);
        if ($value === '') return null;

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) {
            return null;
        }

        return escape_string($value);
    }

    function getDomainValue($input) {
        $input = trim($input);

        if ($input === '') {
            return false;
        }

        if (!preg_match('#^https?://#i', $input)) {
            $input = 'http://' . $input;
        }

        $host = parse_url($input, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        $host = preg_replace('/^www\./i', '', $host);

        if (!preg_match('/^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,}$/i', $host)) {
            return false;
        }

        return strtolower($host);
    }

    function sendIPN(string $url, array $payload): int {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $json,
            CURLOPT_HTTPHEADER      => [
                'Content-Type: application/json',
                'Connection: close'
            ],
            CURLOPT_RETURNTRANSFER  => false,
            CURLOPT_HEADER          => false,
            CURLOPT_CONNECTTIMEOUT  => 3,
            CURLOPT_TIMEOUT         => 5,
            CURLOPT_FORBID_REUSE    => true,
            CURLOPT_NOSIGNAL        => true,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_WRITEFUNCTION   => function($ch, $data) { return strlen($data); },
        ]);

        $result = curl_exec($ch); 
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result === false) {
            $httpCode = 0; 
        }

        curl_close($ch);

        return $httpCode;
    }

    function sendIPNMulti(array $jobs): array{
        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($jobs as $job) {
            $json = json_encode($job['payload'], JSON_UNESCAPED_UNICODE);

            $ch = curl_init($job['url']);
            curl_setopt_array($ch, [
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => $json,
                CURLOPT_HTTPHEADER      => [
                    'Content-Type: application/json',
                    'Connection: close'
                ],
                CURLOPT_RETURNTRANSFER  => false,
                CURLOPT_CONNECTTIMEOUT  => 3,
                CURLOPT_TIMEOUT         => 5,
                CURLOPT_FORBID_REUSE    => true,
                CURLOPT_NOSIGNAL        => true,
                CURLOPT_SSL_VERIFYPEER  => true,
                CURLOPT_SSL_VERIFYHOST  => 2,
                CURLOPT_WRITEFUNCTION   => fn($ch, $data) => strlen($data),
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[(int)$ch] = [
                'handle' => $ch,
                'id'     => $job['id']
            ];
        }

        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        foreach ($handles as $item) {
            $ch = $item['handle'];
            $id = $item['id'];

            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code === 0) {
                $code = 0;
            }

            $results[$id] = $code;

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        return $results; 
    }

    function senderWhitelist(?string $sender = null, ?string $providerKey = null, string $mode = 'provider', ?string $providerName = null) {
        $providers = [
            'bkash' => [
                'name'     => 'bKash',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['bkash'],
            ],
            'nagad' => [
                'name'     => 'Nagad',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['nagad'],
            ],
            'rocket' => [
                'name'     => 'Rocket',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['16216'],
            ],
            'upay' => [
                'name'     => 'Upay',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['upay'],
            ],
            'tap' => [
                'name'     => 'Tap',
                'currency' => 'USD',
                'balance_verify' => 'true',
                'senders'  => ['tap.'],
            ],
            'cellfin' => [
                'name'     => 'Cellfin',
                'currency' => 'BDT',
                'balance_verify' => 'false',
                'senders'  => ['ibbl .'],
            ],
            'okwallet' => [
                'name'     => 'Ok Wallet',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['01847-348685'],
            ],
            'mcash' => [
                'name'     => 'mCash',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['16259'],
            ],
            'pathaopay' => [
                'name'     => 'Pathao Pay',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['pathaopay'],
            ],
            'telecash' => [
                'name'     => 'TeleCash',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['telecash'],
            ],            
            'ipay' => [
                'name'     => 'Ipay',
                'currency' => 'BDT',
                'balance_verify' => 'true',
                'senders'  => ['09638-900800'],
            ],
        ];

        if ($mode === 'senders') {
            $allSenders = [];
            foreach ($providers as $provider) {
                $allSenders = array_merge($allSenders, $provider['senders']);
            }
            $allSenders = array_values(array_unique($allSenders));
            return $allSenders;
        }

        if ($sender !== null) {
            $sender = strtolower(trim($sender));
            foreach ($providers as $key => $provider) {
                foreach ($provider['senders'] as $s) {
                    if (strtolower($s) === $sender) {
                        return [
                            'provider_key'   => $key,
                            'name'           => $provider['name'],
                            'currency'       => $provider['currency'],
                            'balance_verify'       => $provider['balance_verify'],
                            'sender'         => $sender,
                        ];
                    }
                }
            }
            return false; 
        }

        if ($providerKey !== null) {
            return $providers[$providerKey] ?? false;
        }

        if ($providerName !== null) {
            $providerName = strtolower(trim($providerName));
            foreach ($providers as $key => $provider) {
                if (strtolower($provider['name']) === $providerName) {
                    return [
                        'provider_key' => $key,
                        'name'         => $provider['name'],
                        'currency'     => $provider['currency'],
                        'balance_verify'     => $provider['balance_verify'],
                        'senders'      => $provider['senders'],
                    ];
                }
            }
            return false;
        }

        return $providers;
    }


    function MFSMessageVerified(string $mfs, string $message){
        $message = trim(preg_replace('/\s+/', ' ', $message));

        $formats = [
            'bkash' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/You have received Tk ([\d,.]+) from (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Fee Tk ([\d,.]+)\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'fee', 'balance', 'trxid', 'datetime']
                ],
                [
                    'type'     => 'Personal',
                    'priority' => 90,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Fee Tk ([\d,.]+)\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'fee', 'balance', 'trxid', 'datetime']
                ],
                [
                    'type'     => 'Merchant',
                    'priority' => 80,
                    'pattern'  => '/You have received payment Tk ([\d,.]+) from (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Fee Tk ([\d,.]+)\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'fee', 'balance', 'trxid', 'datetime']
                ],

                /*
                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'nagad' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Money Received\. Amount: Tk ([\d,.]+) Sender: (\d+)(?:\s*Ref[:\-]?\s*(\S+))? TxnID: ([A-Z0-9]+) Balance: Tk ([\d,.]+) ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'trxid', 'balance', 'datetime']
                ],
                [
                    'type'     => 'Personal',
                    'priority' => 90,
                    'pattern'  => '/Cash In Received\. Amount: Tk ([\d,.]+) Uddokta: (\d+) TxnID: ([A-Z0-9]+) Balance: ([\d,.]+) ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'balance', 'datetime']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'rocket' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Tk([\d,.]+) received from A\/C:([*\d]+) Fee:Tk([\d,.]+)\, Your A\/C Balance: Tk([\d,.]+) TxnId:([A-Z0-9]+)(?: Date:([\w\-:\s]+))?/i',
                    'map'      => ['amount', 'sender', 'fee', 'balance', 'trxid', 'datetime']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'upay' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Tk\. ([\d,.]+) has been received from (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Balance Tk\. ([\d,.]+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)\./i',
                    'map'      => ['amount', 'sender', 'ref', 'balance', 'trxid', 'datetime']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'tap' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Received Tk ([\d,.]+) from (\d+)\. Balance Tk\. ([\d,.]+)\. TxID: ([A-Z0-9]+)\./i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'cellfin' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/Islami Bank CellFin Received ([\d,.]+) Tk From CellFin: (\d+) To CellFin: (\d+) TrxId: ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'receiver', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'okwallet' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/\(OK Wallet\) Successfully received Tk ([\d,.]+) from A\/C (\d+)\.(?:\s*Ref[:\-]?\s*(\S+))? Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'balance', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'mcash' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/IBBL mCash You have received Tk: ([\d,.]+) From: (\d+)(?:\s*Reference:\s*(\S*))? Balance Tk: ([\d,.]+) TrxID: ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'ref', 'balance', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],
            'pathaopay' => [
                // 🔹 PERSONAL (Most specific first)
                [
                    'type'     => 'Personal',
                    'priority' => 100,
                    'pattern'  => '/You have received BDT ([\d,.]+) from (\+?\d+)\. Balance BDT ([\d,.]+) TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],

                /*
                [
                    'type'     => 'Merchant',
                    'priority' => 70,
                    'pattern'  => '/received a payment of Tk ([\d,.]+) from (\d+)\. TrxID ([A-Z0-9]+) at ([\d\/:\s]+)/i',
                    'map'      => ['amount', 'sender', 'trxid', 'datetime']
                ],

                // 🔹 AGENT
                [
                    'type'     => 'Agent',
                    'priority' => 60,
                    'pattern'  => '/Cash In Tk ([\d,.]+) from (\d+) successful\. Balance Tk ([\d,.]+)\. TrxID ([A-Z0-9]+)/i',
                    'map'      => ['amount', 'sender', 'balance', 'trxid']
                ],*/
            ],




        ];

        if (!isset($formats[strtolower($mfs)])) {
            return false;
        }

        // 🔥 Sort by priority (DESC)
        usort($formats[strtolower($mfs)], fn($a, $b) => $b['priority'] <=> $a['priority']);

        foreach ($formats[strtolower($mfs)] as $format) {
            if (preg_match($format['pattern'], $message, $matches)) {

                $data = [
                    'mfs'  => strtolower($mfs),
                    'type' => $format['type'],
                    'raw'  => $message,
                ];

                // Map values safely
                foreach ($format['map'] as $i => $key) {
                    $data[$key] = $matches[$i + 1] ?? null;
                }

                // Normalize numbers
                foreach (['amount', 'balance', 'fee'] as $field) {
                    if (isset($data[$field]) && $data[$field] !== null) {
                        $data[$field] = str_replace(',', '', $data[$field]);
                    }
                }

                return $data;
            }
        }

        return false;
    }

    function reconcileByLongestChain($device_id, $sender_key, $type){
        global $db_prefix;

        $resBalance = json_decode(getData($db_prefix.'balance_verification', 'WHERE device_id="'.$device_id.'" AND sender_key="'.$sender_key.'" AND type="'.$type.'"'),true);

        $canonicalBalanceInt = 0;

        if (!empty($resBalance['response'][0]['current_balance'])) {
            $canonicalBalanceInt = moneyToInt($resBalance['response'][0]['current_balance']);
        }

        $res = json_decode(getData($db_prefix.'sms_data','WHERE device_id="'.$device_id.'" AND sender_key="'.$sender_key.'" AND type="'.$type.'" AND status IN ("approved","awaiting-review") AND source IN ("app") ORDER BY id ASC'),true);

        $smsList = $res['response'] ?? [];
        if (count($smsList) < 1) return;

        foreach ($smsList as &$s) {
            $amountInt  = moneyToInt($s['amount'] ?? "0");
            $balanceInt = moneyToInt($s['balance'] ?? "0");

            if ($amountInt <= 0 || $balanceInt <= 0) continue;

            $s['amount_int']  = $amountInt;
            $s['balance_int'] = $balanceInt;

            $s['prev'] = $balanceInt - $amountInt;
            $s['bal']  = $balanceInt;
        }
        unset($s);

        $next = [];

        foreach ($smsList as $s) {
            if (!isset($s['prev'])) continue;
            $next[$s['prev']][] = $s;
        }

        $bestChain = [];
        $queue = [$canonicalBalanceInt];

        while (!empty($queue)) {

            $current = array_shift($queue);

            if (!isset($next[$current])) continue;

            foreach ($next[$current] as $sms) {
                $chain = [];
                $tempCurrent = $current;
                $tempNext = $next;

                while (isset($tempNext[$tempCurrent]) && count($tempNext[$tempCurrent]) > 0) {

                    $smsInChain = array_shift($tempNext[$tempCurrent]);

                    $chain[] = $smsInChain;
                    $tempCurrent = $smsInChain['bal'];
                }

                if (count($chain) > count($bestChain)) {
                    $bestChain = $chain;
                }
            }
        }

        if (count($bestChain) < 1) return;

        $idsToApprove = array_column($bestChain, 'id');

        if (!empty($idsToApprove)) {

            updateData($db_prefix.'sms_data',['status','reason','updated_date'],['approved','--',getCurrentDatetime('Y-m-d H:i:s')],'id IN ('.implode(',', $idsToApprove).')');
        }

        $last = end($bestChain);
        $finalBalanceInt = $last['bal'];

        $finalBalance = intToMoney($finalBalanceInt, 2);

        updateData($db_prefix.'balance_verification', ['current_balance','updated_date'], [$finalBalance, getCurrentDatetime('Y-m-d H:i:s')], 'device_id="'.$device_id.'" AND sender_key="'.$sender_key.'" AND type="'.$type.'"');
    }

    function permissionSchema(){
        $permissionSchema = [
            'resources' => [
                'customers' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'transaction' => [
                    'edit'      => true,
                    'delete'    => true,
                    'approve'   => true,
                    'cancel'   => true,
                    'refund'    => true,
                    'send_ipn'  => true
                ],
                'invoice' => [
                    'create'    => true,
                    'edit'      => true,
                    'delete'    => true
                ],
                'payment_link' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'gateways' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'addons' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'brand_settings' => [
                    'view' => true,
                    'edit'   => true
                ],
                'api_settings' => [
                    'view' => true,
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'theme_settings' => [
                    'view' => true,
                    'edit'   => true
                ],
                'faq_settings' => [
                    'view' => true,
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'currency_settings' => [
                    'view' => true,
                    'sync_rate' => true,
                    'import'   => true,
                    'edit'   => true
                ],
                'sms_data' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'device' => [
                    'connect' => true,
                    'delete'  => true,
                    'balance_verification_for'  => true
                ],
                'brands' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'staff' => [
                    'create' => true,
                    'edit'   => true,
                    'delete' => true,
                    'assign_brand_to' => true,
                    'edit_permission' => true,
                    'view_permission_list' => true,
                    'delete_permission_of' => true
                ],
                'domains' => [
                    'whitelist' => true,
                    'edit'   => true,
                    'delete' => true
                ],
                'system_settings' => [
                    'manage_general' => true,
                    'manage_cron' => true,
                    'manage_update'   => true,
                    'manage_import'   => true
                ],
            ],
            'pages' => [
                'dashboard' => true,
                'reports' => true,
                'customers' => true,
                'transaction' => true,
                'invoice' => true,
                'payment_link' => true,
                'gateways' => true,
                'addons' => true,
                'brand_settings' => true,
                'sms_data' => true,
                'device' => true,
                'brands' => true,
                'staff_management' => true,
                'domains' => true,
                'system_settings' => true,
            ]
        ];

        return $permissionSchema ?? [];
    }

    function countPermissions($tabKey, $tabData) {
        $count = 0;

        if ($tabKey === 'resources') {
            foreach ($tabData as $module => $actions) {
                $count += count($actions);
            }
        }

        if ($tabKey === 'pages') {
            $count = count($tabData);
        }

        return $count;
    }
    function hasPermission($permissions, $module, $action = 'view', $adminType = 'staff') {
        if ($adminType === 'admin') {
            return true;
        }

        return isset($permissions['resources'][$module][$action])
            && $permissions['resources'][$module][$action] === true;
    }

    function canAccessPage($permissions, $page, $adminType = 'staff') {
        if ($adminType === 'admin') {
            return true;
        }

        return !empty($permissions['pages'][$page]);
    }

    function get_env($option_name, $brand_id = 'both') {
        global $db_prefix;

        $option_name = escape_string($option_name);
        $brand_id = escape_string($brand_id);

        $params = [ ':brand_id' => $brand_id, ':option_name' => $option_name ];

        $response_env = json_decode(getData($db_prefix.'env','WHERE brand_id = :brand_id AND option_name = :option_name', '* FROM', $params),true);
        if($response_env['status'] == true){
            $value = $response_env['response'][0]['value'];

            if($value == '--'){
                $value = '';
            }
        }else{
            $columns = ['brand_id', 'option_name', 'value', 'created_date', 'updated_date'];
            $values = [$brand_id, $option_name, '--', getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

            insertData($db_prefix.'env', $columns, $values);

            $value = '';
        }

        return $value;
    }

    function set_env($option_name, $value, $brand_id = 'both') {
        global $db_prefix;

        $option_name = escape_string($option_name);
        $value = escape_string($value);
        $brand_id = escape_string($brand_id);

        $params = [ ':brand_id' => $brand_id, ':option_name' => $option_name ];

        $response_env = json_decode(getData($db_prefix.'env','WHERE brand_id = :brand_id AND option_name = :option_name', '* FROM', $params),true);
        if($response_env['status'] == true){
            $columns = ['brand_id', 'value', 'updated_date'];
            $values = [$brand_id, $value, getCurrentDatetime('Y-m-d H:i:s')];
            $condition = "id = '".$response_env['response'][0]['id']."'"; 
            
            updateData($db_prefix.'env', $columns, $values, $condition);
        }else{
            $columns = ['brand_id', 'option_name', 'value', 'created_date', 'updated_date'];
            $values = [$brand_id, $option_name, $value, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

            insertData($db_prefix.'env', $columns, $values);
        }

        return $value;
    }

    function generateRandomFilename($extension) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        for ($i = 0; $i < 30; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString . "." . $extension;
    }

    function uploadImage($file, $max_file_size) {
        if (!is_dir(__DIR__.'/../../pp-media/storage')) {
            if (mkdir(__DIR__.'/../../pp-media/storage', 0755, true)) {
                $upload_directory = __DIR__ . '/../../pp-media/storage/';
            } else {
                return json_encode(['status' => false, 'message' => 'Failed to create folder!']);
            }
        }else{
            $upload_directory = __DIR__ . '/../../pp-media/storage/';
        }

        // ─────────── VALIDATION ───────────
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return json_encode(['status' => false, 'message' => 'No file uploaded or upload failed.']);
        }
    
        if ($file['size'] > $max_file_size) {
            return json_encode(['status' => false, 'message' => 'File size exceeds maximum allowed.']);
        }
    
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_info          = pathinfo($file['name']);
        $file_extension     = strtolower($file_info['extension']);
    
        if (!in_array($file_extension, $allowed_extensions)) {
            return json_encode(['status' => false, 'message' => 'Only JPG, PNG, GIF, and WEBP files are allowed.']);
        }
    
        // ─────────── FILE NAME ───────────
        $random_filename = generateRandomFilename($file_extension);
        $full_path       = $upload_directory . $random_filename;
    
        // ─────────── TRY IMAGICK ───────────
        try {
            if (!extension_loaded('imagick')) {
                throw new Exception('Imagick extension not installed.');
            }
    
            $img = new Imagick($file['tmp_name']);
    
            $hasAlpha = $img->getImageAlphaChannel();
    
            if ($hasAlpha && Imagick::queryFormats('WEBP')) {
                $img->setImageFormat('webp');
                $img->setOption('webp:lossless', 'true');
                $img->setImageCompressionQuality(85);
                $random_filename = generateRandomFilename('webp');
            } elseif (!$hasAlpha && Imagick::queryFormats('JPEG')) {
                $img->setImageFormat('jpeg');
                $img->setImageCompression(Imagick::COMPRESSION_JPEG);
                $img->setImageCompressionQuality(75);
                $random_filename = generateRandomFilename('jpg');
            } else {
                throw new Exception('Required format not supported by Imagick.');
            }
    
            $full_path = $upload_directory . $random_filename;
    
            $img->stripImage();
            $img->writeImage($full_path);
            $img->clear();
            $img->destroy();
    
            return json_encode(['status' => true, 'file' => $random_filename]);
    
        } catch (Exception $e) {
            // ───── FALLBACK: MOVE FILE DIRECTLY ─────
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                return json_encode([
                    'status' => true,
                    'file'   => $random_filename,
                    'note'   => 'Imagick not used. File uploaded without processing.'
                ]);
            } else {
                return json_encode(['status' => false, 'message' => 'File upload failed without Imagick: ' . $e->getMessage()]);
            }
        }
    }
    
    function deleteImage($file) {
        // Define the local image directory path
        $upload_directory = __DIR__ . '/../../pp-media/storage/'; // Update path if different
    
        // Sanitize the filename to prevent directory traversal attacks
        $filename = basename($file);
        $full_path = $upload_directory . $filename;
    
        // Check if the file exists
        if (!file_exists($full_path)) {
            return json_encode(["status" => false, "message" => "File not found."]);
        }
    
        // Attempt to delete the file
        if (unlink($full_path)) {
            return json_encode(["status" => true, "message" => "File deleted successfully!"]);
        } else {
            return json_encode(["status" => false, "message" => "Error deleting file."]);
        }
    }


    function deleteFolder($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.','..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? deleteFolder($path) : unlink($path);
        }
        rmdir($dir);
    }

    function copyFolder($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);

        while(false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $srcPath = $src . '/' . $file;
                $dstPath = $dst . '/' . $file;

                if (is_dir($srcPath)) {
                    copyFolder($srcPath, $dstPath);
                } else {
                    copy($srcPath, $dstPath);
                }
            }
        }
        closedir($dir);
    }

    function zipFolder($source, $zipFile) {
        $zip = new ZipArchive;
        $zip->open($zipFile, ZipArchive::CREATE);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source)
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $zip->addFile($file, substr($file, strlen($source) + 1));
            }
        }
        $zip->close();
    }

    function runSql($file) {
        $pdo = connectDatabase();

        if (!file_exists($file)) {
            throw new Exception("SQL file not found");
        }

        $sql = file_get_contents($file);

        try {
            $pdo->beginTransaction();

            // Split SQL safely
            $queries = array_filter(array_map('trim', explode(";\n", $sql)));

            foreach ($queries as $query) {
                if ($query !== '') {
                    $pdo->exec($query);
                }
            }

            $pdo->commit();
            return true;

        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Update SQL failed: ' . $e->getMessage());
            throw new Exception('Database update failed');
        }
    }

    function backupDatabasePDO($backupPath) {
        $pdo = connectDatabase();
        $pdo->exec("SET NAMES utf8mb4");

        $fh = fopen($backupPath, 'w');

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {

            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
            fwrite($fh, "DROP TABLE IF EXISTS `$table`;\n$create;\n\n");

            $stmt = $pdo->query("SELECT * FROM `$table`", PDO::FETCH_ASSOC);
            foreach ($stmt as $row) {
                $vals = [];
                foreach ($row as $val) {
                    $vals[] = ($val === null) ? "NULL" : $pdo->quote($val);
                }
                fwrite($fh, "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n");
            }

            fwrite($fh, "\n");
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    function extractUpdate($zipFile, $destination) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile) !== true) {
            throw new Exception("Cannot open ZIP file");
        }

        // Detect top-level folder in zip
        $topFolder = '';
        if ($zip->numFiles > 0) {
            $firstFile = $zip->getNameIndex(0);
            $parts = explode('/', $firstFile);
            if (count($parts) > 1) $topFolder = $parts[0] . '/';
        }

        // Extract each file manually to remove top-level folder
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            // Remove top folder prefix
            if ($topFolder && str_starts_with($entry, $topFolder)) {
                $entryNew = substr($entry, strlen($topFolder));
            } else {
                $entryNew = $entry;
            }

            if ($entryNew === '') continue; // skip folder itself

            $targetPath = $destination . '/' . $entryNew;

            if (substr($entry, -1) === '/') { // folder
                @mkdir($targetPath, 0755, true);
            } else { // file
                @mkdir(dirname($targetPath), 0755, true);
                copy("zip://$zipFile#$entry", $targetPath);
            }
        }

        $zip->close();
    }

    function addQueryParams($url, $params = []) {
        // Parse existing URL
        $parsedUrl = parse_url($url);

        // Get existing query params (if any)
        $existingParams = [];
        if (!empty($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $existingParams);
        }

        // Merge new params
        $finalParams = array_merge($existingParams, $params);

        // Rebuild query string
        $queryString = http_build_query($finalParams);

        // Rebuild full URL
        $baseUrl =
            ($parsedUrl['scheme'] ?? '') . ($parsedUrl['scheme'] ? '://' : '') .
            ($parsedUrl['host'] ?? '') .
            ($parsedUrl['path'] ?? '');

        return $baseUrl . '?' . $queryString;
    }































    function pp_set_lang($lang){
        $_SESSION['ui_language'] = preg_replace('/[^a-z]/', '', $lang);
    }

    function pp_site_address(){
        global $site_url;

        return $site_url;
    }

    function pp_callback_url(){
        $url = pp_site_url();

        $separator = (parse_url($url, PHP_URL_QUERY) ? '&' : '?');
        $url .= $separator . 'pp_callback';

        return $url;
    }

    function pp_ipn_url($gatewayid){
        global $site_url;

        return $site_url.'ipn/'.$gatewayid;
    }

    function pp_check_transaction($ppid = ''){
        global $db_prefix;

        $params = [ ':ref' => $ppid ];

        $response_transaciton = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref','* FROM',$params),true);

        if ($response_transaciton['status'] === true) {
            return true;
        }else{
            return false;
        }
    }

    function pp_check_transaction_id($trxid = ''){
        global $db_prefix;

        $params = [ ':trx_id' => $trxid ];

        $response_transaciton = json_decode(getData($db_prefix.'transaction','WHERE trx_id = :trx_id','* FROM',$params),true);

        if ($response_transaciton['status'] === true) {
            return true;
        }else{
            return false;
        }
    }

    function pp_set_transaction_status($transactionid, $status = '', $gateway_id = '', $trxid = '', $source_info = []){
        global $db_prefix;

        $params = [ ':ref' => $transactionid, ':status' => 'initiated' ];

        $response_transaciton = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref AND status = :status','* FROM',$params),true);

        if ($response_transaciton['status'] === true) {
            if($status == "canceled"){
                $columns = ['status', 'updated_date'];
                $values = ['canceled', getCurrentDatetime('Y-m-d H:i:s')];
                $condition = 'id ="'.$response_transaciton['response'][0]['id'].'"'; 

                updateData($db_prefix.'transaction', $columns, $values, $condition);
                                                                
                return true;
            }

            if($status == "completed"){
                $final_source_info = '--';

                if (is_array($source_info) && !empty($source_info)) {
                    $valid = true;

                    foreach ($source_info as $item) {
                        if (
                            !is_array($item) ||
                            empty($item['label']) ||
                            empty($item['value'])
                        ) {
                            $valid = false;
                            break;
                        }
                    }

                    if ($valid) {
                        $final_source_info = json_encode($source_info, JSON_UNESCAPED_UNICODE);
                    }
                }

                $params = [ ':gateway_id' => $gateway_id, ':brand_id' => $response_transaciton['response'][0]['brand_id'] ];

                $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id AND brand_id = :brand_id  AND status = "active"', '* FROM', $params),true);
                if($response_gateway['status'] == true){
                    $currencyRates = [];

                    $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$response_gateway['response'][0]['brand_id'].'"'), true);

                    if (!empty($currencyRes['response'])) {
                        foreach ($currencyRes['response'] as $c) {
                            $currencyRates[$c['code']] = money_sanitize($c['rate']);
                        }
                    }

                    $txnAmount  = money_sanitize($response_transaciton['response'][0]['amount']);
                    $txnCurrency = $response_transaciton['response'][0]['currency'];
                    $gatewayCurrency = $response_gateway['response'][0]['currency'];

                    if ($txnCurrency === $gatewayCurrency) {
                        $convertedAmount = $txnAmount;
                    } else {
                        if (isset($currencyRates[$gatewayCurrency])) {
                            $convertedAmount = money_div($txnAmount, $currencyRates[$gatewayCurrency]);
                        } else {
                            $convertedAmount = "0";
                        }
                    }

                    $fixed_discount = money_sanitize( $response_gateway['response'][0]['fixed_discount']);
                    $percentage_discount = money_sanitize($response_gateway['response'][0]['percentage_discount']);

                    $fixed_charge = money_sanitize($response_gateway['response'][0]['fixed_charge']);
                    $percentage_charge = money_sanitize($response_gateway['response'][0]['percentage_charge']);

                    $percentageDiscountAmount = money_div(money_mul($convertedAmount, $percentage_discount, 8), "100", 8);
                    $totalDiscount = money_add($fixed_discount, $percentageDiscountAmount, 8);

                    $percentageChargeAmount = money_div(money_mul($convertedAmount, $percentage_charge, 8), "100", 8);
                    $totalProcessingFee = money_add($fixed_charge, $percentageChargeAmount, 8);

                    $convertedAmount = money_add(money_sub($convertedAmount, $totalDiscount, 8), $totalProcessingFee, 8);

                    if ($txnCurrency !== $gatewayCurrency && isset($currencyRates[$gatewayCurrency])) {
                        $totalDiscount = money_mul($totalDiscount, $currencyRates[$gatewayCurrency]);
                        $totalProcessingFee = money_mul($totalProcessingFee, $currencyRates[$gatewayCurrency]);
                    }
                }else{
                    return false;
                }

                $columns = ['processing_fee', 'discount_amount', 'local_net_amount', 'local_currency', 'gateway_id', 'status', 'trx_id', 'source_info', 'updated_date'];
                $values = [$totalProcessingFee, $totalDiscount, $convertedAmount, $response_gateway['response'][0]['currency'], $gateway_id, 'completed', $trxid, $final_source_info, getCurrentDatetime('Y-m-d H:i:s')];
                $condition = 'id ="'.$response_transaciton['response'][0]['id'].'"'; 

                updateData($db_prefix.'transaction', $columns, $values, $condition);

                $params = [ ':ref' => $response_transaciton['response'][0]['ref'], ':status' => 'completed' ];

                $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref AND status = :status ', '* FROM', $params),true);

                $metadata = json_decode($response_transaction['response'][0]['metadata'], true) ?: [];

                $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$response_transaction['response'][0]['brand_id'].'" AND gateway_id = "'.$gateway_id.'"'),true);

                $gateway = $response_gateway['response'][0]['display'] ?? '';

                $customer_info = json_decode($response_transaction['response'][0]['customer_info'], true) ?: [];

                $response_brand = json_decode(getData($db_prefix.'brands',' WHERE brand_id ="'.$response_transaction['response'][0]['brand_id'].'"'),true);

                $net = money_sub(money_add($response_transaction['response'][0]['amount'], $response_transaction['response'][0]['processing_fee']), $response_transaction['response'][0]['discount_amount']);
                
                $all_transactions = [];

                $all_transactions[] = [
                    "pp_id" => $response_transaction['response'][0]['ref'],
                    "full_name" => $customer_info['name'] ?? 'N/A',
                    "email_address" => $customer_info['email'] ?? 'N/A',
                    "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                    "gateway" => $gateway,
                    "amount" => money_round($response_transaction['response'][0]['amount']),
                    "fee" => money_round($response_transaction['response'][0]['processing_fee']),
                    "discount_amount" => money_round($response_transaction['response'][0]['discount_amount']),
                    "total" => money_round($net),
                    "local_net_amount" => money_round($response_transaction['response'][0]['local_net_amount']),
                    "currency" => $response_transaction['response'][0]['currency'],
                    "local_currency" => $response_transaction['response'][0]['local_currency'],
                    "metadata" => $metadata, // ← AS-IS
                    "sender" => $response_transaction['response'][0]['sender'],
                    "transaction_id" => $response_transaction['response'][0]['trx_id'],
                    "status" => $response_transaction['response'][0]['status'],
                    "date" => convertUTCtoUserTZ($response_transaction['response'][0]['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                ];

                if($response_transaction['response'][0]['webhook_url'] == "" || $response_transaction['response'][0]['webhook_url'] == "--"){

                }else{
                    $ipnData = [
                        "pp_id" => $response_transaction['response'][0]['ref'],
                        "full_name" => $customer_info['name'] ?? 'N/A',
                        "email_address" => $customer_info['email'] ?? 'N/A',
                        "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                        "gateway" => $gateway,
                        "amount" => money_round($response_transaction['response'][0]['amount']),
                        "fee" => money_round($response_transaction['response'][0]['processing_fee']),
                        "discount_amount" => money_round($response_transaction['response'][0]['discount_amount']),
                        "total" => money_round($net),
                        "local_net_amount" => money_round($response_transaction['response'][0]['local_net_amount']),
                        "currency" => $response_transaction['response'][0]['currency'],
                        "local_currency" => $response_transaction['response'][0]['local_currency'],
                        "metadata" => $metadata, // ← AS-IS
                        "sender" => $response_transaction['response'][0]['sender'],
                        "transaction_id" => $response_transaction['response'][0]['trx_id'],
                        "status" => $response_transaction['response'][0]['status'],
                        "date" => convertUTCtoUserTZ($response_transaction['response'][0]['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                    ];

                    $payload = json_encode($ipnData, JSON_UNESCAPED_UNICODE);

                    $jobs = [[
                        'id'      => rand(),
                        'url'     => $response_transaction['response'][0]['webhook_url'],
                        'payload' => json_decode($payload, true),
                    ]];

                    $results = sendIPNMulti($jobs);

                    foreach ($jobs as $job) {
                        $code = $results[$job['id']] ?? 0;
                        $status = ($code === 200) ? 'completed' : 'pending';

                        if($status == 'completed'){

                        }else{
                            $columns = ['ref', 'brand_id', 'payload', 'url', 'created_date', 'updated_date'];
                            $values = [rand(), $response_brand['response'][0]['brand_id'], $payload, $response_transaction['response'][0]['webhook_url'], getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'webhook_log', $columns, $values);
                        }
                    }
                }

                if (!empty($all_transactions)) {
                    do_action('transactions.updated', $all_transactions);
                }

                return true;
            }

        }else{
            return false;
        }
    }

    function pp_checkout_address($paymentid = ''){
        global $path_payment, $paymentID124123412;

        if($paymentid !== ""){
            $paymentID124123412 = $paymentid ?? '';
        }else{
           $paymentID124123412 = $paymentID124123412 ?? '';
        }

        return pp_site_address().$path_payment.'/'.$paymentID124123412;
    }

    function pp_hexToRgba($hex, $opacity = 1) {
        $hex = str_replace('#','',$hex);
        if(strlen($hex) == 3){
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        return "rgba($r,$g,$b,$opacity)";
    }

    function pp_assets($position = ''){
        global $site_url;

        if($position == "head"){
            echo '
                <link rel="stylesheet" href="'.$site_url.'assets/css/tabler.min.css?v=1.7" />
                <link rel="stylesheet" href="'.$site_url.'assets/css/choices.min.css">

                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-flags.min.css" />
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-payments.min.css" />
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-socials.min.css" />
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-vendors.min.css" />

                <style>
                    @import url("'.$site_url.'assets/css/inter.css");
                </style>
            ';
        }else{
            echo '
                <script src="'.$site_url.'assets/js/tabler.min.js"></script>
                <script src="'.$site_url.'assets/js/jquery-3.6.4.min.js"></script>
                <script src="'.$site_url.'assets/js/custom-toast.js?v=1.2"></script>
                <script src="'.$site_url.'assets/js/choices.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/hugerte@1/hugerte.min.js"></script>
            ';
        }
    }

    function pp_downloadReceiptPDF($data = []){

        if (!$data) {
            die('Invalid transaction');
        }

        $tx = $data['transaction'];
        $brand = $data['brand'];

        $amountPaid = money_add(money_sub($tx['amount'], $tx['discount_amount']), $tx['processing_fee']);

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        if (!empty($brand['logo'])) {
            $pdf->Image($brand['logo'], 10, 10, 35);
        }

        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetXY(50, 12);
        $pdf->Cell(0, 8, $brand['name'], 0, 1);

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetX(50);
        $pdf->Cell(0, 6, $brand['address']['city'].', '.$brand['address']['country'], 0, 1);

        $pdf->Ln(10);

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Payment Receipt', 0, 1, 'C');

        $status = strtoupper($tx['status']);

        $statusColors = [
            'COMPLETED' => [46,204,113],
            'PENDING'   => [241,196,15],
            'REFUNDED'  => [52,152,219],
            'CANCELED'  => [231,76,60],
        ];

        $color = $statusColors[$status] ?? [120,120,120];

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->Cell(0, 8, 'STATUS: '.$status, 0, 1, 'C');
        $pdf->SetTextColor(0,0,0);

        $pdf->Ln(6);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 6, 'Amount Paid', 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 22);
        $pdf->Cell(0, 12, money_round($amountPaid, 2), 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 6, 'Local Net Amount: '.money_round($tx['local_net_amount'], 2).' '.$tx['local_currency'], 0, 1, 'C');

        $pdf->Ln(6);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(6);

        sectionTitle($pdf, 'Transaction Details');
        infoRow($pdf, 'Transaction Ref', $tx['ref']);
        infoRow($pdf, 'Payment Method', $tx['payment_method']);
        infoRow($pdf, 'Created Date', convertUTCtoUserTZ($tx['created_date'], ($brand['locale']['timezone'] === '--' || $brand['locale']['timezone'] === '') ? 'Asia/Dhaka' : $brand['locale']['timezone'], "M d, Y h:i A"));

        $pdf->Ln(3);
        sectionTitle($pdf, 'Customer Details');
        infoRow($pdf, 'Name', $tx['customer']['name']);
        infoRow($pdf, 'Email', $tx['customer']['email']);
        infoRow($pdf, 'Mobile', $tx['customer']['mobile']);

        $pdf->Ln(3);
        sectionTitle($pdf, 'Payment Breakdown');
        infoRow($pdf, 'Amount', money_round($tx['amount'], 2).' '.$tx['currency']);
        infoRow($pdf, 'Discount', money_round($tx['discount_amount'], 2).' '.$tx['currency']);
        infoRow($pdf, 'Processing Fee', money_round($tx['processing_fee'], 2).' '.$tx['currency']);


        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 6, 'This is a system generated receipt.', 0, 1, 'C');

        $pdf->Output('D', 'Receipt-'.$tx['ref'].'.pdf');
    }

    function sectionTitle($pdf, $title)
    {
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 8, $title, 0, 1);
    }

    function infoRow($pdf, $label, $value)
    {
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(60, 8, $label, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, $value, 0, 1);
    }

    function resolveModuleLanguage($brandLanguage, array $supportedLanguages)
    {
        if (!empty($_SESSION['ui_language'])) {
            $sessionLang = $_SESSION['ui_language'];
            if (isset($supportedLanguages[$sessionLang])) {
                return $sessionLang;
            }
        }

        if (isset($supportedLanguages[$brandLanguage])) {
            return $brandLanguage;
        }

        return array_key_first($supportedLanguages);
    }

    function buildLangArray(array $langText, ?string $language = 'en') {
        $lang = [];

        foreach ($langText as $key => $translations) {
            $lang[$key] = $translations[$language]
                ?? reset($translations);
        }

        return $lang;
    }

    function pp_gateways($tab = '', $data = []){
        global $db_prefix;

        $params = [ ':tab' => $tab, ':brand_id' => $data['brand']['id'] ];

        $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE tab = :tab AND brand_id = :brand_id AND status = "active"','* FROM',$params),true);

        $gatewayList = [];

        if ($response_gateway['status'] === true) {
            $currencyRates = [];

            $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$data['brand']['id'].'"'), true);

            if (!empty($currencyRes['response'])) {
                foreach ($currencyRes['response'] as $c) {
                    $currencyRates[$c['code']] =$c['rate'];
                }
            }

            foreach ($response_gateway['response'] as $row) {
                $txnAmount  = money_sanitize($data['transaction']['amount']);
                $txnCurrency = $data['transaction']['currency'];

                if ($txnCurrency === $row['currency']) {
                    $convertedAmount = $txnAmount;
                } else {
                    if (isset($currencyRates[$row['currency']])) {
                        $convertedAmount = money_div($txnAmount, $currencyRates[$row['currency']]);
                    } else {
                        $convertedAmount = "0";
                    }
                }

                $fixed_discount = money_sanitize( $response_gateway['response'][0]['fixed_discount']);
                $percentage_discount = money_sanitize($response_gateway['response'][0]['percentage_discount']);

                $fixed_charge = money_sanitize($response_gateway['response'][0]['fixed_charge']);
                $percentage_charge = money_sanitize($response_gateway['response'][0]['percentage_charge']);

                $percentageDiscountAmount = money_div(money_mul($convertedAmount, $percentage_discount, 8), "100", 8);
                $totalDiscount = money_add($fixed_discount, $percentageDiscountAmount, 8);

                $percentageChargeAmount = money_div(money_mul($convertedAmount, $percentage_charge, 8), "100", 8);
                $totalProcessingFee = money_add($fixed_charge, $percentageChargeAmount, 8);

                $convertedAmount = money_add(money_sub($convertedAmount, $totalDiscount, 8), $totalProcessingFee, 8);

                $min = money_sanitize($row['min_allow']);
                $max = money_sanitize($row['max_allow']);

                $hasNoMax = bccomp($max, '0', 2) <= 0 || $max === '' || $max === '--';

                $isAboveMin = bccomp(money_round($convertedAmount), $min, 2) >= 0;
                $isBelowMax = $hasNoMax ? true : (bccomp(money_round($convertedAmount), $max, 2) <= 0);

                if ($isAboveMin && $isBelowMax) {
                    $gatewayList[] = [
                        'gateway_id'           => $row['gateway_id'],
                        'slug'                 => $row['slug'],
                        'name'                 => $row['name'],
                        'display'              => $row['display'],
                        'logo'                 => $row['logo'],
                        'currency'             => $row['currency'],
                        'min_allow'            => money_round($row['min_allow']),
                        'max_allow'            => money_round($row['max_allow']),
                        'fixed_discount'       => money_round($row['fixed_discount']),
                        'percentage_discount'  => money_round($row['percentage_discount']),
                        'fixed_charge'         => money_round($row['fixed_charge']),
                        'percentage_charge'    => money_round($row['percentage_charge']),
                        'primary_color'        => $row['primary_color'],
                        'text_color'           => $row['text_color'],
                        'btn_color'            => $row['btn_color'],
                        'btn_text_color'       => $row['btn_text_color'],
                    ];
                }
            }

            return [
                'status'   => true,
                'gateway'  => $gatewayList
            ];
        }

        return [
            'status'  => false,
            'gateway' => []
        ];
    }

    function pp_gateway_info($gateway_id = '', $data = []){
        global $db_prefix;

        $params = [ ':gateway_id' => $gateway_id, ':brand_id' => $data['brand']['id'] ];

        $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id AND brand_id = :brand_id AND status = "active"','* FROM',$params),true);

        if ($response_gateway['status'] === true) {
            $row = $response_gateway['response'][0];
            $currencyRates = [];

            $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$data['brand']['id'].'"'), true);

            if (!empty($currencyRes['response'])) {
                foreach ($currencyRes['response'] as $c) {
                    $currencyRates[$c['code']] =$c['rate'];
                }
            }

            $txnAmount  = money_sanitize($data['transaction']['amount']);
            $txnCurrency = $data['transaction']['currency'];

            if ($txnCurrency === $row['currency']) {
                $convertedAmount = $txnAmount;
            } else {
                if (isset($currencyRates[$row['currency']])) {
                    $convertedAmount = money_div($txnAmount, $currencyRates[$row['currency']]);
                } else {
                    $convertedAmount = "0";
                }
            }

            $fixed_discount = money_sanitize( $response_gateway['response'][0]['fixed_discount']);
            $percentage_discount = money_sanitize($response_gateway['response'][0]['percentage_discount']);

            $fixed_charge = money_sanitize($response_gateway['response'][0]['fixed_charge']);
            $percentage_charge = money_sanitize($response_gateway['response'][0]['percentage_charge']);

            $percentageDiscountAmount = money_div(money_mul($convertedAmount, $percentage_discount, 8), "100", 8);
            $totalDiscount = money_add($fixed_discount, $percentageDiscountAmount, 8);

            $percentageChargeAmount = money_div(money_mul($convertedAmount, $percentage_charge, 8), "100", 8);
            $totalProcessingFee = money_add($fixed_charge, $percentageChargeAmount, 8);

            $convertedAmount = money_add(money_sub($convertedAmount, $totalDiscount, 8), $totalProcessingFee, 8);

            $min = money_sanitize($row['min_allow']);
            $max = money_sanitize($row['max_allow']);

            $hasNoMax = bccomp($max, '0', 2) <= 0 || $max === '' || $max === '--';

            $isAboveMin = bccomp(money_round($convertedAmount), $min, 2) >= 0;
            $isBelowMax = $hasNoMax ? true : (bccomp(money_round($convertedAmount), $max, 2) <= 0);

            if ($isAboveMin && $isBelowMax) {
                if(file_exists(__DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php')){
                    require_once __DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php';

                    $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $response_gateway['response'][0]['slug']))) . 'Gateway';

                    $gateway = new $class();

                    $gateway_info = $gateway->info();

                    if (method_exists($gateway, 'supported_languages')) {
                        $supported_languages = $gateway->supported_languages();
                    }else{
                        $supported_languages = [];
                    }
                }else{
                    if($response_gateway['response'][0]['tab'] == 'bank'){
                        $supported_languages = [
                            'en' => 'English',
                            'bn' => 'বাংলা',
                            'hi' => 'हिन्दी',
                            'ur' => 'اردو',
                            'ar' => 'العربية',
                        ];
                    }else{
                        $supported_languages = [];
                    }
                }

                $gatewayList = [
                    'gateway_id'           => $row['gateway_id'],
                    'slug'                 => $row['slug'],
                    'name'                 => $row['name'],
                    'display'              => $row['display'],
                    'logo'                 => $row['logo'],
                    'currency'             => $row['currency'],
                    'min_allow'            => money_round($row['min_allow']),
                    'max_allow'            => money_round($row['max_allow']),
                    'fixed_discount'       => money_round($row['fixed_discount']),
                    'percentage_discount'  => money_round($row['percentage_discount']),
                    'fixed_charge'         => money_round($row['fixed_charge']),
                    'percentage_charge'    => money_round($row['percentage_charge']),
                    'primary_color'        => $row['primary_color'],
                    'text_color'           => $row['text_color'],
                    'btn_color'            => $row['btn_color'],
                    'btn_text_color'       => $row['btn_text_color'],
                ];

                return [
                    'status'   => true,
                    'gateway'  => $gatewayList,
                    'supported_languages'  => $supported_languages
                ];
            }else{
                return [
                    'status'   => false,
                    'gateway'  => []
                ];
            }
        }

        return [
            'status'  => false,
            'gateway' => []
        ];
    }

    function pp_gateway_render($gateway_id = '', $data = []){
        global $db_prefix;

        unset($data['options'], $data['lang']);

        $params = [ ':gateway_id' => $gateway_id, ':brand_id' => $data['brand']['id'] ];

        $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id AND brand_id = :brand_id  AND status = "active"', '* FROM', $params),true);
        if($response_gateway['status'] == true){

            $options = [];

            $params = [ ':gateway_id' => $gateway_id ];
            $response_gateways_parameter = json_decode(getData($db_prefix.'gateways_parameter','WHERE gateway_id = :gateway_id', '* FROM', $params),true);
            foreach($response_gateways_parameter['response'] as $field){
                $value = $field['value'];

                if(!empty($field['multiple']) && !empty($value)){
                    $value = is_array($value) ? $value : json_decode($value, true);
                }

                $options[$field['option_name']] = $value;
            }

            $data['options'] = $options;

            $gatewayInfo = [
                'gateway_id'     => $response_gateway['response'][0]['gateway_id'],
                'slug'     => $response_gateway['response'][0]['slug'],
                'name'     => $response_gateway['response'][0]['name'],
                'display'     => $response_gateway['response'][0]['display'],
                'logo'     => $response_gateway['response'][0]['logo'],
                'currency'     => $response_gateway['response'][0]['currency'],
                'min_allow'     => money_round($response_gateway['response'][0]['min_allow']),
                'max_allow'     => money_round($response_gateway['response'][0]['max_allow']),

                'fixed_discount'     => money_round($response_gateway['response'][0]['fixed_discount']),
                'percentage_discount'     => money_round($response_gateway['response'][0]['percentage_discount']),
                'fixed_charge'     => money_round($response_gateway['response'][0]['fixed_charge']),
                'percentage_charge'     => money_round($response_gateway['response'][0]['percentage_charge']),

                'primary_color'     => $response_gateway['response'][0]['primary_color'],
                'text_color'     => $response_gateway['response'][0]['text_color'],
                'btn_color'     => $response_gateway['response'][0]['btn_color'],
                'btn_text_color'     => $response_gateway['response'][0]['btn_text_color'],
            ];

            $data['gateway'] = $gatewayInfo;

            $currencyRates = [];

            $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$response_gateway['response'][0]['brand_id'].'"'), true);

            if (!empty($currencyRes['response'])) {
                foreach ($currencyRes['response'] as $c) {
                    $currencyRates[$c['code']] =$c['rate'];
                }
            }

            $txnAmount  = money_sanitize($data['transaction']['amount']);
            $txnCurrency = $data['transaction']['currency'];
            $gatewayCurrency = $response_gateway['response'][0]['currency'];

            if ($txnCurrency === $gatewayCurrency) {
                $convertedAmount = $txnAmount;
            } else {
                if (isset($currencyRates[$gatewayCurrency])) {
                    $convertedAmount = money_div($txnAmount, $currencyRates[$gatewayCurrency]);
                } else {
                    $convertedAmount = "0";
                }
            }

            $fixed_discount = money_sanitize($response_gateway['response'][0]['fixed_discount']);
            $percentage_discount = money_sanitize($response_gateway['response'][0]['percentage_discount']);

            $fixed_charge = money_sanitize($response_gateway['response'][0]['fixed_charge']);
            $percentage_charge = money_sanitize($response_gateway['response'][0]['percentage_charge']);

            $percentageDiscountAmount = money_div(money_mul($convertedAmount, $percentage_discount, 8), "100", 8);
            $totalDiscount = money_add($fixed_discount, $percentageDiscountAmount, 8);

            $percentageChargeAmount = money_div(money_mul($convertedAmount, $percentage_charge, 8), "100", 8);
            $totalProcessingFee = money_add($fixed_charge, $percentageChargeAmount, 8);

            $convertedAmount = money_add(money_sub($convertedAmount, $totalDiscount, 8), $totalProcessingFee, 8);

            if ($txnCurrency !== $gatewayCurrency && isset($currencyRates[$gatewayCurrency])) {
                $totalDiscount = money_mul($totalDiscount, $currencyRates[$gatewayCurrency], 8);
                $totalProcessingFee = money_mul($totalProcessingFee, $currencyRates[$gatewayCurrency], 8);
            }

            $data['transaction']['amount'] = money_round($txnAmount, 2);
            $data['transaction']['processing_fee'] = money_round($totalProcessingFee, 2);
            $data['transaction']['discount_amount'] = money_round($totalDiscount, 2);
            $data['transaction']['local_net_amount'] = money_round($convertedAmount, 2);
            $data['transaction']['local_currency'] = $gatewayCurrency;

            if(file_exists(__DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php')){
                require_once __DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php';

                $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $response_gateway['response'][0]['slug']))) . 'Gateway';

                $gateway = new $class();

                $gateway_info = $gateway->info();

                if (method_exists($gateway, 'supported_languages')) {
                    $supported_languages = $gateway->supported_languages();
                }else{
                    $supported_languages = [];
                }

                if (method_exists($gateway, 'lang_text')) {
                     $lang_text = $gateway->lang_text();
                }else{
                    $lang_text = [];
                }
            }else{
                if($response_gateway['response'][0]['tab'] == 'bank'){
                    $gateway = '';

                    $supported_languages = [
                        'en' => 'English',
                        'bn' => 'বাংলা',
                        'hi' => 'हिन्दी',
                        'ur' => 'اردو',
                        'ar' => 'العربية',
                    ];

                    $lang_text = [
                        'bank_step_bank_name' => [
                            'en' => 'Bank Name: {bank_name}',
                            'bn' => 'ব্যাংকের নাম: {bank_name}',
                            'hi' => 'बैंक का नाम: {bank_name}',
                            'ur' => 'بینک کا نام: {bank_name}',
                            'ar' => 'اسم البنك: {bank_name}',
                        ],

                        'bank_step_account_name' => [
                            'en' => 'Account Name: {account_holder_name}',
                            'bn' => 'অ্যাকাউন্টের নাম: {account_holder_name}',
                            'hi' => 'खाते का नाम: {account_holder_name}',
                            'ur' => 'اکاؤنٹ کا نام: {account_holder_name}',
                            'ar' => 'اسم الحساب: {account_holder_name}',
                        ],

                        'bank_step_account_number' => [
                            'en' => 'Account Number: {account_number}',
                            'bn' => 'অ্যাকাউন্ট নম্বর: {account_number}',
                            'hi' => 'खाता संख्या: {account_number}',
                            'ur' => 'اکاؤنٹ نمبر: {account_number}',
                            'ar' => 'رقم الحساب: {account_number}',
                        ],

                        'bank_step_branch_name' => [
                            'en' => 'Branch Name: {branch_name}',
                            'bn' => 'শাখার নাম: {branch_name}',
                            'hi' => 'शाखा का नाम: {branch_name}',
                            'ur' => 'برانچ کا نام: {branch_name}',
                            'ar' => 'اسم الفرع: {branch_name}',
                        ],

                        'bank_step_routing_number' => [
                            'en' => 'Routing Number: {routing_number}',
                            'bn' => 'রাউটিং নম্বর: {routing_number}',
                            'hi' => 'रूटिंग नंबर: {routing_number}',
                            'ur' => 'روٹنگ نمبر: {routing_number}',
                            'ar' => 'رقم التوجيه: {routing_number}',
                        ],

                        'bank_step_swift_code' => [
                            'en' => 'Swift Code: {swift_code}',
                            'bn' => 'সুইফট কোড: {swift_code}',
                            'hi' => 'स्विफ्ट कोड: {swift_code}',
                            'ur' => 'سوئفٹ کوڈ: {swift_code}',
                            'ar' => 'رمز السويفت: {swift_code}',
                        ],

                        'bank_step_amount' => [
                            'en' => 'Amount: {amount} {currency}',
                            'bn' => 'পরিমাণ: {amount} {currency}',
                            'hi' => 'राशि: {amount} {currency}',
                            'ur' => 'رقم: {amount} {currency}',
                            'ar' => 'المبلغ: {amount} {currency}',
                        ],

                        'bank_step_slip' => [
                            'en' => 'Upload the Payment Slip in the box below and press Submit',
                            'bn' => 'নিচের বক্সে পেমেন্ট স্লিপ আপলোড করুন এবং জমা দিন চাপুন।',
                            'hi' => 'नीचे दिए गए बॉक्स में भुगतान रसीद अपलोड करें और "सबमिट" दबाएँ।',
                            'ur' => 'نیچے دیے گئے باکس میں ادائیگی کی رسید اپ لوڈ کریں اور "Submit" دبائیں۔',
                            'ar' => 'قم برفع إيصال الدفع في المربع أدناه ثم اضغط على "إرسال".',
                        ],
                    ];

                    $instructions = [
                        [
                            'icon' => '',
                            'text' => 'bank_step_bank_name',
                            'copy' => true,
                            'value' => $data['options']['bank_name'],
                            'vars' => [
                                '{bank_name}' => $data['options']['bank_name']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_account_name',
                            'copy' => true,
                            'value' => $data['options']['account_holder_name'],
                            'vars' => [
                                '{account_holder_name}' => $data['options']['account_holder_name']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_account_number',
                            'copy' => true,
                            'value' => $data['options']['account_number'],
                            'vars' => [
                                '{account_number}' => $data['options']['account_number']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_branch_name',
                            'copy' => true,
                            'value' => $data['options']['branch_name'],
                            'vars' => [
                                '{branch_name}' => $data['options']['branch_name']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_routing_number',
                            'copy' => true,
                            'value' => $data['options']['routing_number'],
                            'vars' => [
                                '{routing_number}' => $data['options']['routing_number']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_swift_code',
                            'copy' => true,
                            'value' => $data['options']['swift_code'],
                            'vars' => [
                                '{swift_code}' => $data['options']['swift_code']
                            ]
                        ],                        
                        [
                            'icon' => '',
                            'text' => 'bank_step_amount',
                            'copy' => true,
                            'value' => $data['transaction']['local_net_amount'],
                            'vars' => [
                                '{amount}' => number_format((float)$data['transaction']['local_net_amount'], 2),
                                '{currency}' => $data['transaction']['local_currency']
                            ]
                        ],
                        [
                            'icon' => '',
                            'text' => 'bank_step_slip',
                            'copy' => false,
                        ],
                    ];

                    $gateway_info = [
                        'gateway_type'        => 'manual',
                        'verify_by'        => 'slip',
                    ];
                }else{
                    return false;
                }
            }

            $lang_text['verify'] = [
                'en' => 'Verify',
                'bn' => 'যাচাই করুন',
                'hi' => 'सत्यापित करें',
                'ur' => 'تصدیق کریں',
                'ar' => 'تحقق',
            ];
            
            $lang_text['transaction_id'] = [
                'en' => 'Transaction ID',
                'bn' => 'ট্রানজ্যাকশন আইডি',
                'hi' => 'लेन-देन आईडी',
                'ur' => 'لین دین آئی ڈی',
                'ar' => 'معرّف المعاملة',
            ];

            $lang_text['enter_transaction_id'] = [
                'en' => 'Enter transaction ID',
                'bn' => 'ট্রানজ্যাকশন আইডি লিখুন',
                'hi' => 'लेन-देन आईडी दर्ज करें',
                'ur' => 'لین دین آئی ڈی درج کریں',
                'ar' => 'أدخل معرّف المعاملة',
            ];
            
            $lang_text['upload_slip'] = [
                'en' => 'Upload Payment Slip',
                'bn' => 'পেমেন্ট স্লিপ আপলোড করুন',
                'hi' => 'भुगतान स्लिप अपलोड करें',
                'ur' => 'ادائیگی سلپ اپ لوڈ کریں',
                'ar' => 'ارفع إيصال الدفع',
            ];            
            
            $lang_text['mobile_number'] = [
                'en' => 'Mobile Number',
                'bn' => 'মোবাইল নম্বর',
                'hi' => 'मोबाइल नंबर',
                'ur' => 'موبائل نمبر',
                'ar' => 'رقم الجوال',
            ];

            $lang_text['submit'] = [
                'en' => 'Submit',
                'bn' => 'জমা দিন',
                'hi' => 'जमा करें',
                'ur' => 'جمع کریں',
                'ar' => 'إرسال',
            ];
            
            $language = resolveModuleLanguage($data['brand']['locale']['language'],$supported_languages);

            // Build $lang array for developer
            $lang = buildLangArray($lang_text, $language);

            $data['lang']    = $lang; // or whatever new value

            // If you also want to keep discount in sync (optional)
            //$data['transaction']['discount_amount'] = number_format((float)$data['transaction']['discount_amount'],2,'.','');

            if (is_callable([$gateway, 'instructions'])) {
                $instructions = $gateway->instructions($data);
            }

            if(isset($instructions)){
                echo '<ol class="payment-instructions">';

                $rowli = 0;

                foreach ($instructions as $step) {
                    $rowli = $rowli+1;

                    // Resolve language directly
                    $text = $lang[$step['text']] ?? $step['text'];

                    // Replace variables
                    if (!empty($step['vars'])) {
                        foreach ($step['vars'] as $k => $v) {
                            $text = str_replace($k, '<span class="dynamic-value">'.$v.'</span>', $text);
                        }
                    }

                    echo '<li class="li-'.$rowli.'">';
                    echo ($step['icon'] == "") ? '<div class="dot"></div>' : $step['icon'];

                    echo '<p>';
                    echo $text;

                    /* Copy button */
                    if (!empty($step['copy']) && isset($step['value'])) {
                        echo ' <span class="button-icon"
                            onclick="copy_value(\'' . htmlspecialchars($step['value'], ENT_QUOTES) . '\')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666"/>
                                <path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"/>
                            </svg>
                        </span>';
                    }

                    /* Action button */
                    if (!empty($step['action'])) {
                        $action = $step['action'];

                        if ($action['type'] === 'image' && !empty($action['value'])) {
                            echo ' <span class="button-icon"
                                onclick="pp_show_image(\''.htmlspecialchars($action['value'], ENT_QUOTES).'\')">
                                '.$action['label'].'
                            </span>';
                        }else{
                            echo '<style>.li-'.$rowli.'{display: none !important;}</style>';
                        }
                    }

                    echo '</p>';
                    echo '</li>';

                }

                echo '</ol>';

                echo '
                    <div id="pp-image-modal" class="pp-modal" style="display:none;">
                        <div class="pp-modal-content">
                            <span class="pp-close" onclick="pp_close_image()">&times;</span>
                            <div class="pp-model-image-b"><img id="pp-modal-image" src="" alt="Preview"></div>
                        </div>
                    </div>

                    <script data-cfasync="false">
                        function pp_show_image(src) {
                            const modal = document.getElementById("pp-image-modal");
                            const img = document.getElementById("pp-modal-image");

                            img.src = src;
                            modal.style.display = "flex";
                        }

                        function pp_close_image() {
                            document.getElementById("pp-image-modal").style.display = "none";
                        }
                    </script>
                ';
            }

            if(isset($gateway_info)){
                if(isset($gateway_info['gateway_type']) && $gateway_info['gateway_type'] == "automation"){
                    echo '
                        <form class="payment-form-submit" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action-v2" value="transaction-verify">
                            <input type="hidden" name="gateway-id" value="'.$data['gateway']['gateway_id'].'">
                            <input type="hidden" name="transaction-id" value="'.$data['transaction']['ref'].'">

                            <div class="form-group  mt-3" style="display: none">
                                <label class="form-label">'.$data['lang']['mobile_number'].'</label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" name="mobile_number" placeholder="'.$data['lang']['mobile_number'].'"> 
                                </div>
                            </div>

                            <div class="form-group  mt-3">
                                <label class="form-label">'.$data['lang']['transaction_id'].'</label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" name="trxid" placeholder="'.$data['lang']['enter_transaction_id'].'" required=""> 
                                </div>
                            </div>

                            <button class="btn btn-primary w-100 payment-form-btn mt-3" type="submit">'.$data['lang']['verify'].'</button>
                        </form>

                        <script data-cfasync="false">
                            document.addEventListener("DOMContentLoaded", function() {
                                const form = document.querySelector(".payment-form-submit");
                                const mobileWrapper = form.querySelector(`.form-group[style*="display: none"]`);
                                const submitBtn = form.querySelector(".payment-form-btn");

                                form.addEventListener("submit", function(e) {
                                    e.preventDefault();

                                    const formData = new FormData(form);

                                    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

                                    fetch("", { // replace "" with your PHP AJAX URL if needed
                                        method: "POST",
                                        body: formData
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        submitBtn.innerHTML = `'.$data['lang']['verify'].'`;

                                        if(data.status === "true") {
                                            // Verified successfully
                                            success(data); // pass data if needed
                                        } else if(data.status === "false") {
                                            // Failed verification
                                            if(data.visible_number && data.visible_number === "true") {
                                                mobileWrapper.style.display = "block";
                                            }
                                            // Call failed handler with title & message
                                            failed(data.title, data.message);
                                        } else {
                                            // Unexpected response
                                            failed("Unexpected Response", "Please try again later.");
                                        }
                                    })
                                    .catch(err => {
                                        submitBtn.innerHTML = `'.$data['lang']['verify'].'`;
                                        console.error(err);
                                        failed("Request Error", "Something went wrong. Please try again.");
                                    });
                                });
                            });

                        </script>

                    ';
                }
                if(isset($gateway_info['gateway_type']) && $gateway_info['gateway_type'] == "manual"){
                    if(isset($gateway_info['verify_by']) && $gateway_info['verify_by'] == "trxid"){
                        echo '
                            <form class="payment-form-submit" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action-v2" value="transaction-verify">
                                <input type="hidden" name="gateway-id" value="'.$data['gateway']['gateway_id'].'">
                                <input type="hidden" name="transaction-id" value="'.$data['transaction']['ref'].'">

                                <div class="form-group  mt-3">
                                    <label class="form-label">'.$data['lang']['transaction_id'].'</label>
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" name="trxid" placeholder="'.$data['lang']['enter_transaction_id'].'" required=""> 
                                    </div>
                                </div>

                                <button class="btn btn-primary w-100 payment-form-btn mt-3" type="submit">'.$data['lang']['submit'].'</button>
                            </form>

                            <script data-cfasync="false">
                                document.addEventListener("DOMContentLoaded", function() {
                                    const form = document.querySelector(".payment-form-submit");
                                    const mobileWrapper = form.querySelector(`.form-group[style*="display: none"]`);
                                    const submitBtn = form.querySelector(".payment-form-btn");

                                    form.addEventListener("submit", function(e) {
                                        e.preventDefault();

                                        const formData = new FormData(form);

                                        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

                                        fetch("", { // replace "" with your PHP AJAX URL if needed
                                            method: "POST",
                                            body: formData
                                        })
                                        .then(res => res.json())
                                        .then(data => {
                                            submitBtn.innerHTML = `'.$data['lang']['verify'].'`;

                                            if(data.status === "true") {
                                                // Verified successfully
                                                success(data); // pass data if needed
                                            } else if(data.status === "false") {
                                                // Call failed handler with title & message
                                                failed(data.title, data.message);
                                            } else {
                                                // Unexpected response
                                                failed("Unexpected Response", "Please try again later.");
                                            }
                                        })
                                        .catch(err => {
                                            submitBtn.innerHTML = `'.$data['lang']['verify'].'`;
                                            console.error(err);
                                            failed("Request Error", "Something went wrong. Please try again.");
                                        });
                                    });
                                });

                            </script>
                        ';
                    }else{
                        if(isset($gateway_info['verify_by']) && $gateway_info['verify_by'] == "slip"){
                            echo '
                                <form class="payment-form-submit" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action-v2" value="transaction-verify">
                                    <input type="hidden" name="gateway-id" value="'.$data['gateway']['gateway_id'].'">
                                    <input type="hidden" name="transaction-id" value="'.$data['transaction']['ref'].'">

                                    <div class="form-group  mt-3">
                                        <label class="form-label">'.$data['lang']['upload_slip'].'</label>
                                        <div class="form-control-wrap">
                                            <input type="file" class="form-control" name="slip" accept = "image/*" placeholder="'.$data['lang']['upload_slip'].'" required=""> 
                                        </div>
                                    </div>

                                    <button class="btn btn-primary w-100 payment-form-btn mt-3" type="submit">'.$data['lang']['submit'].'</button>
                                </form>

                                <script data-cfasync="false">
                                    document.addEventListener("DOMContentLoaded", function() {
                                        const form = document.querySelector(".payment-form-submit");
                                        const mobileWrapper = form.querySelector(`.form-group[style*="display: none"]`);
                                        const submitBtn = form.querySelector(".payment-form-btn");

                                        form.addEventListener("submit", function(e) {
                                            e.preventDefault();

                                            const formData = new FormData(form);

                                            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

                                            fetch("", { // replace "" with your PHP AJAX URL if needed
                                                method: "POST",
                                                body: formData
                                            })
                                            .then(res => res.json())
                                            .then(data => {
                                                submitBtn.innerHTML = `'.$data['lang']['verify'].'`;

                                                if(data.status === "true") {
                                                    // Verified successfully
                                                    success(data); // pass data if needed
                                                } else if(data.status === "false") {
                                                    // Call failed handler with title & message
                                                    failed(data.title, data.message);
                                                } else {
                                                    // Unexpected response
                                                    failed("Unexpected Response", "Please try again later.");
                                                }
                                            })
                                            .catch(err => {
                                                submitBtn.innerHTML = `'.$data['lang']['verify'].'`;
                                                console.error(err);
                                                failed("Request Error", "Something went wrong. Please try again.");
                                            });
                                        });
                                    });

                                </script>
                            ';
                        }
                    }
                }
            }

            if(isset($_GET['pp_callback'])){
                if (is_callable([$gateway, 'callback'])) {
                    $gateway->callback($data);
                }
            }else{
                if (is_callable([$gateway, 'process_payment'])) {
                    $gateway->process_payment($data);
                }
            }
        }else{
            return false;
        }
    }

    function pp_renderFormFields(string $type = '', array $data = [])
    {
        if($type == "payment-link"){
            $paymentLinkID = $data['paymentLink']['pid'] ?? '';
            $fields = $data['paymentLink']['fields'] ?? '';

            echo "<input type='hidden' name='action-v2' value='payment-link'>";
            echo "<input type='hidden' name='itemid' value='".$paymentLinkID."'>";

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='full-name'>".$data['lang']['full_name']." <span class='text-danger'>*</span></label>";
                echo "<input type='text' name='full-name' id='full-name' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='email-address'>".$data['lang']['email_address']." <span class='text-danger'>*</span></label>";
                echo "<input type='email' name='email-address' id='email-address' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='mobile-number'>".$data['lang']['mobile_number']." <span class='text-danger'>*</span></label>";
                echo "<input type='text' name='mobile-number' id='mobile-number' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='mobile-number'>".$data['lang']['amount']." <span class='text-danger'>*</span></label>";
                echo '
                    <div class="input-group mb-2">
                        <span class="input-group-text"> '.$data['paymentLink']['currency'].' </span>
                        <input type="text" class="form-control" placeholder="Amount" value="'.money_round($data['paymentLink']['total'], 2).'" autocomplete="off" readonly>
                    </div>';
            echo '</div>';

            foreach ($fields as $field) {
                $name     = htmlspecialchars($field['name']);
                $label    = htmlspecialchars($field['label']);
                $type     = $field['type'];
                $required = (!empty($field['required']) && $field['required'] !== 'false') ? 'required' : '';

                echo '<div class="mb-3">';

                // Show label for all except checkbox (we put label inside input for checkbox)
                if ($type == 'checkbox') {
                    echo "<label class='form-label' for='{$name}'>{$label}";
                    if ($required) echo ' <span class="text-danger">*</span>';
                    echo "</label>";
                }

                switch ($type) {

                    case 'text':
                        echo "<input type='text' name='{$name}' id='{$name}' class='form-control' {$required}>";
                        break;

                    case 'textarea':
                        echo "<textarea name='{$name}' id='{$name}' class='form-control' {$required}></textarea>";
                        break;

                    case 'select':
                        echo "<select name='{$name}' id='{$name}' class='form-control' {$required}>";
                        if (!empty($field['options'])) {
                            foreach ($field['options'] as $opt) {
                                echo "<option value='".htmlspecialchars($opt)."'>".htmlspecialchars($opt)."</option>";
                            }
                        }
                        echo "</select>";
                        break;

                    case 'checkbox':
                        if (!empty($field['options'])) {
                            foreach ($field['options'] as $opt) {

                                $optValue = htmlspecialchars($opt);
                                $optId    = $name . '_' . preg_replace('/\s+/', '_', strtolower($opt));

                                echo "<div class='form-check'>";
                                echo "<input 
                                        type='checkbox'
                                        name='{$name}[]'
                                        id='{$optId}'
                                        class='form-check-input'
                                        value='{$optValue}'
                                        {$required}
                                    >";
                                echo "<label class='form-check-label' for='{$optId}'>{$optValue}</label>";
                                echo "</div>";
                            }
                        }
                        break;

                    case 'radio':
                        if (!empty($field['options'])) {
                            foreach ($field['options'] as $opt) {
                                $radioId = $name.'_'.preg_replace('/\s+/', '_', strtolower($opt));
                                echo "<div class='form-check'>";
                                echo "<input type='radio' name='{$name}' id='{$radioId}' class='form-check-input' value='".htmlspecialchars($opt)."' {$required}>";
                                echo "<label class='form-check-label' for='{$radioId}'>".htmlspecialchars($opt)."</label>";
                                echo "</div>";
                            }
                        }
                        break;

                    case 'file':
                        $accept = '';
                        if (!empty($field['options'])) {
                            $exts = array_map(function($e){ return ".".$e; }, $field['options']);
                            $accept = 'accept="'.implode(',', $exts).'"';
                        }
                        echo "<input type='file' name='{$name}' id='{$name}' class='form-control' {$accept} {$required}>";
                        break;

                    default:
                        echo "<input type='text' name='{$name}' id='{$name}' class='form-control' {$required}>";
                        break;
                }

                // Optional context hint (for invoice, payment_link, etc.)
                if (!empty($field['hint'])) {
                    echo "<small class='form-text text-muted'>{$field['hint']}</small>";
                }

                echo '</div>';
            }
        }

        if($type == "payment-link-default"){
            $paymentLinkID = $data['paymentLink']['pid'] ?? '';
            $currency = $data['paymentLink']['currency'] ?? '';

            echo "<input type='hidden' name='action-v2' value='payment-link-default'>";
            echo "<input type='hidden' name='itemid' value='".$paymentLinkID."'>";

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='full-name'>".$data['lang']['full_name']." <span class='text-danger'>*</span></label>";
                echo "<input type='text' name='full-name' id='full-name' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='email-address'>".$data['lang']['email_address']." <span class='text-danger'>*</span></label>";
                echo "<input type='email' name='email-address' id='email-address' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='mobile-number'>".$data['lang']['mobile_number']." <span class='text-danger'>*</span></label>";
                echo "<input type='text' name='mobile-number' id='mobile-number' class='form-control' required>";
            echo '</div>';

            echo '<div class="mb-3">';
                echo "<label class='form-label' for='mobile-number'>".$data['lang']['amount']." <span class='text-danger'>*</span></label>";
                echo '
                    <div class="input-group mb-2">
                        <span class="input-group-text"> '.$data['paymentLink']['currency'].' </span>
                        <input type="number" name="amount" class="form-control" placeholder="Amount" value="0" autocomplete="off">
                    </div>';
            echo '</div>';
        }

        if($type == "invoice"){
            $invoiceID = $data['invoice']['iid'] ?? '';

            echo "<input type='hidden' name='action-v2' value='invoice'>";
            echo "<input type='hidden' name='itemid' value='".$invoiceID."'>";
        }
    }

    $GLOBALS['__actions'] = [];
    $GLOBALS['__filters'] = [];

    function add_action(string $hook, callable $callback, int $priority = 10)
    {
        $GLOBALS['__actions'][$hook][$priority][] = $callback;
    }

    function do_action(string $hook, ...$args)
    {
        if (empty($GLOBALS['__actions'][$hook])) {
            return;
        }

        ksort($GLOBALS['__actions'][$hook]);

        foreach ($GLOBALS['__actions'][$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    call_user_func_array($callback, $args);
                } catch (Throwable $e) {
                    // prevent plugin crash
                    error_log('Action error ['.$hook.']: '.$e->getMessage());
                }
            }
        }
    }

    function add_filter(string $hook, callable $callback, int $priority = 10)
    {
        $GLOBALS['__filters'][$hook][$priority][] = $callback;
    }

    function apply_filters(string $hook, $value, ...$args)
    {
        if (empty($GLOBALS['__filters'][$hook])) {
            return $value;
        }

        ksort($GLOBALS['__filters'][$hook]);

        foreach ($GLOBALS['__filters'][$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $value = call_user_func($callback, $value, ...$args);
                } catch (Throwable $e) {
                    error_log('Filter error ['.$hook.']: '.$e->getMessage());
                }
            }
        }

        return $value;
    }

    /*
    add_filter('invoice.total', function ($total, $invoice) {
        return $total + 10;
    });
    add_action('invoice.updated', function ($invoice) {
        error_log('Wallet credited for invoice '.$invoice['id']);
    });
    */

    class DB
    {
        protected static ?PDO $pdo = null;

        protected static function pdo(): PDO
        {
            if (!self::$pdo) {
                self::$pdo = connectDatabase(); // your existing function
            }
            return self::$pdo;
        }

        public static function table(string $table): QueryBuilder
        {
            global $db_prefix;
            return new QueryBuilder($db_prefix . $table, self::pdo());
        }

        /* ========================
        TRANSACTIONS
        ======================== */

        public static function beginTransaction(): void
        {
            self::pdo()->beginTransaction();
        }

        public static function commit(): void
        {
            self::pdo()->commit();
        }

        public static function rollBack(): void
        {
            self::pdo()->rollBack();
        }
    }
    class QueryBuilder
    {
        protected PDO $pdo;
        protected string $table;

        protected array $wheres = [];
        protected array $bindings = [];
        protected array $orders = [];

        protected ?int $limit = null;
        protected ?int $offset = null;

        public function __construct(string $table, PDO $pdo)
        {
            $this->table = $table;
            $this->pdo   = $pdo;
        }

        /* ========================
        WHERE
        ======================== */

        public function where(string $column, $operator, $value = null): self
        {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }

            $this->wheres[] = ['AND', "$column $operator ?"];
            $this->bindings[] = $value;

            return $this;
        }

        public function orWhere(string $column, $operator, $value = null): self
        {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }

            $this->wheres[] = ['OR', "$column $operator ?"];
            $this->bindings[] = $value;

            return $this;
        }

        public function whereIn(string $column, array $values): self
        {
            $placeholders = implode(',', array_fill(0, count($values), '?'));

            $this->wheres[] = ['AND', "$column IN ($placeholders)"];
            $this->bindings = array_merge($this->bindings, $values);

            return $this;
        }

        /* ========================
        ORDER / LIMIT
        ======================== */

        public function orderBy(string $column, string $direction = 'ASC'): self
        {
            $this->orders[] = "$column " . strtoupper($direction);
            return $this;
        }

        public function limit(int $limit): self
        {
            $this->limit = $limit;
            return $this;
        }

        public function offset(int $offset): self
        {
            $this->offset = $offset;
            return $this;
        }

        /* ========================
        READ
        ======================== */

        public function get(): array
        {
            $sql = $this->buildSelect();
            $rows = $this->run($sql, true, true);
            $this->reset();

            return $rows;
        }

        public function first(): ?object
        {
            $this->limit = 1;
            $sql = $this->buildSelect();

            $row = $this->run($sql, true, false);
            $this->reset();

            return $row ?: null;
        }

        public function count(): int
        {
            $sql = "SELECT COUNT(*) AS total FROM {$this->table}";
            $sql .= $this->compileWhere();

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            $this->reset();
            return (int) $stmt->fetch(PDO::FETCH_OBJ)->total;
        }

        public function exists(): bool
        {
            return $this->count() > 0;
        }

        /* ========================
        INSERT
        ======================== */

        public function insert(array $data): bool
        {
            $columns = array_keys($data);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));

            $sql = "INSERT INTO {$this->table} (" .
                implode(',', $columns) .
                ") VALUES ($placeholders)";

            return $this->run($sql, false, false, array_values($data));
        }

        /* ========================
        UPDATE
        ======================== */

        public function update(array $data): bool
        {
            $sets = [];

            foreach ($data as $col => $val) {
                $sets[] = "$col = ?";
                $this->bindings[] = $val;
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
            $sql .= $this->compileWhere();

            $result = $this->run($sql, false, false);
            $this->reset();

            return $result;
        }

        /* ========================
        DELETE
        ======================== */

        public function delete(): bool
        {
            $sql = "DELETE FROM {$this->table}";
            $sql .= $this->compileWhere();

            $result = $this->run($sql, false, false);
            $this->reset();

            return $result;
        }

        /* ========================
        INTERNAL
        ======================== */

        protected function buildSelect(): string
        {
            $sql = "SELECT * FROM {$this->table}";
            $sql .= $this->compileWhere();

            if ($this->orders) {
                $sql .= ' ORDER BY ' . implode(', ', $this->orders);
            }

            if ($this->limit !== null) {
                $sql .= " LIMIT {$this->limit}";
            }

            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }

            return $sql;
        }

        protected function compileWhere(): string
        {
            if (!$this->wheres) {
                return '';
            }

            $sql = ' WHERE ';
            foreach ($this->wheres as $i => [$type, $condition]) {
                $sql .= ($i === 0 ? '' : " $type ") . $condition;
            }

            return $sql;
        }

        protected function run(
            string $sql,
            bool $fetch,
            bool $fetchAll,
            array $bindings = []
        ) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings ?: $this->bindings);

            if (!$fetch) {
                return true;
            }

            return $fetchAll
                ? $stmt->fetchAll(PDO::FETCH_OBJ)
                : $stmt->fetch(PDO::FETCH_OBJ);
        }

        protected function reset(): void
        {
            $this->wheres = [];
            $this->bindings = [];
            $this->orders = [];
            $this->limit = null;
            $this->offset = null;
        }
    }
