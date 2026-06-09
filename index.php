<?php
    declare(strict_types=1);
    bcscale(8);

    define('PipraPay_INIT', true);

    if (date_default_timezone_get() !== 'UTC') {
        date_default_timezone_set('UTC');
    }

    if(file_exists(__DIR__ . '/pp-content/pp-include/pp-functions.php')){
        if (isset($pp_functions_loaded)) {

        }else{
            require __DIR__ . '/pp-content/pp-include/pp-functions.php';

            if (isset($pp_functions_loaded)) {

            }else{
                if(file_exists(__DIR__ . '/pp-404.php')){
                    http_response_code(404);
                    require __DIR__ . '/pp-404.php';
                    exit();
                }else{
                    http_response_code(403);
                    exit('Direct access not allowed');
                }
            }
        }
    }else{
        if(file_exists(__DIR__ . '/pp-404.php')){
            http_response_code(404);
            require __DIR__ . '/pp-404.php';
            exit();
        }else{
            http_response_code(403);
            exit('Direct access not allowed');
        }
    }

    if(file_exists(__DIR__ . '/pp-content/pp-include/pp-adapter.php')){
        if (isset($pp_adapter_loaded)) {

        }else{
            require __DIR__ . '/pp-content/pp-include/pp-adapter.php';

            if (isset($pp_adapter_loaded)) {

            }else{
                if(file_exists(__DIR__ . '/pp-404.php')){
                    http_response_code(404);
                    require __DIR__ . '/pp-404.php';
                    exit();
                }else{
                    http_response_code(403);
                    exit('Direct access not allowed');
                }
            }
        }
    }else{
        if(file_exists(__DIR__ . '/pp-404.php')){
            http_response_code(404);
            require __DIR__ . '/pp-404.php';
            exit();
        }else{
            http_response_code(403);
            exit('Direct access not allowed');
        }

    }

    /*
    |--------------------------------------------------------------------------
    | Basic Security Headers
    |--------------------------------------------------------------------------
    */
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    /*
    |--------------------------------------------------------------------------
    | Get Requested Page
    |--------------------------------------------------------------------------
    */
    $page = $_GET['page'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Fallback router (nginx / PHP built-in server / no .htaccess)
    |--------------------------------------------------------------------------
    | The .htaccess RewriteRule that maps "/login" to "index.php?page=login"
    | only runs under Apache mod_rewrite. On servers that front-control to
    | index.php without rewriting (e.g. Laravel Valet's nginx), $_GET['page']
    | is empty, so derive it from the request path instead.
    */
    if ($page === '') {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($base !== '/' && $base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        $page = rawurldecode($uri);
    }

    $page = trim($page, '/');

    /*
    |--------------------------------------------------------------------------
    | SECURITY: Prevent traversal & illegal chars
    |--------------------------------------------------------------------------
    */
    if (strpos($page, '..') !== false || !preg_match('/^[a-zA-Z0-9\/_-]+$/', $page)) {
        $page = 'homepageRedirect';
    }

    /*
    |--------------------------------------------------------------------------
    | Explode path for dynamic values
    |--------------------------------------------------------------------------
    */
    $segments = explode('/', $page);

    /*
    |--------------------------------------------------------------------------
    | Example:
    | /payment/2134124123
    |--------------------------------------------------------------------------
    */
    $route = $segments[0] ?? '';
    $param1 = $segments[1] ?? null;

    /*
    |--------------------------------------------------------------------------
    | Router
    |--------------------------------------------------------------------------
    */

    if(!file_exists(__DIR__ . '/.maintenance')){
        if(file_exists(__DIR__ . '/pp-config.php')){
            if (isset($requriemntnoneedchecked) && $requriemntnoneedchecked === true) {
                switch ($route) {
                    case '404':
                        if(file_exists(__DIR__ . '/pp-404.php')){
                            http_response_code(404);
                            require __DIR__ . '/pp-404.php';
                        }else{
                            http_response_code(403);
                            exit('Direct access not allowed');
                        }
                        break;

                    case 'login':
                    case 'forgot':
                    case '2fa':
                        if(file_exists(__DIR__ . '/pp-content/pp-admin/'.$route.'.php')){
                            require __DIR__ . '/pp-content/pp-admin/'.$route.'.php';
                        }else{
                            if(file_exists(__DIR__ . '/pp-404.php')){
                                http_response_code(404);
                                require __DIR__ . '/pp-404.php';
                            }else{
                                http_response_code(403);
                                exit('Direct access not allowed');
                            }
                        }
                        break;

                    case 'ipn':
                        $gateway_id = $segments[1] ?? null;

                        $params = [ ':gateway_id' => $gateway_id ];

                        $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id', '* FROM', $params),true);
                        if($response_gateway['status'] == false){
                            http_response_code(400);

                            echo json_encode([
                                'error' => [
                                    'code'    => 'INVALID_GATEWAY',
                                    'message' => 'The Gateway provided is incorrect or invalid.'
                                ]
                            ]);
                            exit;
                        }else{
                            $params = [ ':brand_id' => $response_gateway['response'][0]['brand_id'] ];

                            $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                            if($response_brand['status'] == true){
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

                                if(file_exists(__DIR__.'/pp-content/pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php')){
                                    require_once __DIR__.'/pp-content/pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php';

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

                                    $language = resolveModuleLanguage($response_brand['response'][0]['language'],$supported_languages);

                                    // Build $lang array for developer
                                    $lang = buildLangArray($lang_text, $language);

                                    $brandRow = $response_brand['response'][0];

                                    $brandInfo = [
                                        'id'            => $brandRow['brand_id'],
                                        'name'          => ($brandRow['name'] == "--") ? $brandRow['identify_name'] : $brandRow['name'],
                                        'identifyName'  => $brandRow['identify_name'],
                                        'logo'          => $brandRow['logo'] !== '--' ? $brandRow['logo'] : 'https://help.piprapay.com/storage/branding_media/8a5c6ee4-8eba-401d-bffb-c43006d5f65d.png',
                                        'favicon'       => $brandRow['favicon'] !== '--' ? $brandRow['favicon'] : 'https://help.piprapay.com/favicon/icon-144x144.png',

                                        'support' => [
                                            'email'   => $brandRow['support_email_address'],
                                            'phone'   => $brandRow['support_phone_number'],
                                            'website' => $brandRow['support_website'],
                                            'whatsapp'=> $brandRow['whatsapp_number'],
                                            'telegram'=> 'https://t.me/'.$brandRow['telegram'],
                                            'messenger'=> 'https://m.me/'.$brandRow['facebook_messenger'],
                                            'fb_page'=> 'https://facebook.com/'.$brandRow['facebook_page'],
                                        ],

                                        'address' => [
                                            'street'  => $brandRow['street_address'],
                                            'city'    => $brandRow['city_town'],
                                            'postal'  => $brandRow['postal_code'],
                                            'country' => $brandRow['country'],
                                        ],

                                        'locale' => [
                                            'timezone' => $brandRow['timezone'],
                                            'language' => $language,
                                            'currency' => $brandRow['currency_code'],
                                        ],
                                    ];

                                    $response = [
                                        'gateway' => [
                                            'gateway_id'            => $response_gateway['response'][0]['gateway_id'],
                                            'slug'                  => $response_gateway['response'][0]['slug'],
                                            'name'                  => $response_gateway['response'][0]['name'],
                                            'display'               => $response_gateway['response'][0]['display'],
                                            'logo'                  => $response_gateway['response'][0]['logo'],
                                            'currency'              => $response_gateway['response'][0]['currency'],

                                            'min_allow'     => money_round($response_gateway['response'][0]['min_allow']),
                                            'max_allow'     => money_round($response_gateway['response'][0]['max_allow']),

                                            'fixed_discount'     => money_round($response_gateway['response'][0]['fixed_discount']),
                                            'percentage_discount'     => money_round($response_gateway['response'][0]['percentage_discount']),
                                            'fixed_charge'     => money_round($response_gateway['response'][0]['fixed_charge']),
                                            'percentage_charge'     => money_round($response_gateway['response'][0]['percentage_charge']),

                                            'primary_color'         => $response_gateway['response'][0]['primary_color'],
                                            'text_color'            => $response_gateway['response'][0]['text_color'],
                                            'btn_color'             => $response_gateway['response'][0]['btn_color'],
                                            'btn_text_color'        => $response_gateway['response'][0]['btn_text_color'],

                                            'options' => $options
                                        ],

                                        'brand' => [
                                            'id'            => $brandRow['brand_id'],
                                            'name'          => $brandRow['name'],
                                            'identifyName'  => $brandRow['identify_name'],
                                            'logo'          => $brandRow['logo'] !== '--' ? $brandRow['logo'] : null,
                                            'favicon'       => $brandRow['favicon'] !== '--' ? $brandRow['favicon'] : null,

                                            'support' => [
                                                'email'     => $brandRow['support_email_address'],
                                                'phone'     => $brandRow['support_phone_number'],
                                                'website'   => $brandRow['support_website'],
                                                'whatsapp'  => $brandRow['whatsapp_number'],
                                                'telegram'  => 'https://t.me/'.$brandRow['telegram'],
                                                'messenger' => 'https://m.me/'.$brandRow['facebook_messenger'],
                                                'fb_page'   => 'https://facebook.com/'.$brandRow['facebook_page'],
                                            ],

                                            'address' => [
                                                'street'  => $brandRow['street_address'],
                                                'city'    => $brandRow['city_town'],
                                                'postal'  => $brandRow['postal_code'],
                                                'country' => $brandRow['country'],
                                            ],

                                            'locale' => [
                                                'timezone' => $brandRow['timezone'],
                                                'language' => $language,
                                                'currency' => $brandRow['currency_code'],
                                            ],
                                        ],

                                        'lang' => $lang
                                    ];

                                    if (is_callable([$gateway, 'ipn'])) {
                                        $gateway->ipn($response);
                                    }
                                }else{
                                    http_response_code(400);

                                    echo json_encode([
                                        'error' => [
                                            'code'    => 'INVALID_GATEWAY',
                                            'message' => 'The Gateway provided is incorrect or invalid.'
                                        ]
                                    ]);
                                    exit;
                                }
                            }else{
                                http_response_code(400);

                                echo json_encode([
                                    'error' => [
                                        'code'    => 'INVALID_GATEWAY',
                                        'message' => 'The Gateway provided is incorrect or invalid.'
                                    ]
                                ]);
                                exit;
                            }
                        }

                        break;

                    case 'api':
                        $api_type = $segments[1] ?? null;

                        header('Content-Type: application/json');

                        $apiKey = getAuthorizationHeader();

                        $params = [ ':api_key' => $apiKey ];

                        $response_api = json_decode(getData($db_prefix.'api','WHERE api_key = :api_key AND status = "active"', '* FROM', $params),true);
                        if($response_api['status'] == false){
                            http_response_code(400);

                            echo json_encode([
                                'error' => [
                                    'code'    => 'INVALID_API_KEY',
                                    'message' => 'The API key provided is incorrect or invalid.'
                                ]
                            ]);
                            exit;
                        }

                        if(isExpired($response_api['response'][0]['expired_date'])){
                            http_response_code(400);

                            echo json_encode([
                                'error' => [
                                    'code'    => 'INVALID_API_KEY',
                                    'message' => 'The API key provided is incorrect or expired.'
                                ]
                            ]);
                            exit;
                        }

                        $rawInput = file_get_contents("php://input");

                        $data = json_decode($rawInput, true);

                        if (!$data) {
                            http_response_code(400);
                            echo json_encode([
                                'error' => [
                                    'code'    => 'INVALID_JSON_PAYLOAD',
                                    'message' => 'The JSON payload is invalid or malformed.'
                                ]
                            ]);
                            exit;
                        }

                        if($api_type == "checkout"){
                            $api_scopes = $response_api['response'][0]['api_scopes'] ?? [];
                            if (is_string($api_scopes)) {
                                $api_scopes = json_decode($api_scopes, true);
                            }

                            if (!in_array("create_payment", $api_scopes)) {
                                $requiredScope = 'Create Payment';

                                http_response_code(400);
                                echo json_encode([
                                    'error' => [
                                        'code'    => 'INSUFFICIENT_SCOPE',
                                        'message' => "The API key does not have the required permission: {$requiredScope}"
                                    ]
                                ]);
                                exit;
                            }

                            $checkout_type = $segments[2] ?? null;

                            if($checkout_type == "redirect"){
                                $fullName      = $data['full_name'] ?? '';
                                $email         = $data['email_address'] ?? '';
                                $mobile        = $data['mobile_number'] ?? '';
                                $amount        = $data['amount'] ?? '0';
                                $currency      = $data['currency'] ?? 'BDT';
                                $returnUrl     = $data['return_url'] ?? '';
                                $webhookUrl    = $data['webhook_url'] ?? '';
                                $metadataRaw   = $data['metadata'] ?? '{}';

                                function getDomainFromUrl($url) {
                                    // Check if it's a valid URL
                                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                                        // Parse the URL to get host
                                        $parsed = parse_url($url, PHP_URL_HOST);
                                        return $parsed;
                                    }
                                    return false; // Invalid URL
                                }

                                if($returnUrl == ""){
                                    $returnUrl = '--';
                                }else{
                                    $returnDomain  = getDomainFromUrl($returnUrl);

                                    if (!$returnDomain) {
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_URL',
                                                'message' => 'Return URL is invalid.'
                                            ]
                                        ]);
                                        exit;
                                    }else{
                                        $params = [ ':domain' => $returnDomain ];

                                        $response_urlCheck = json_decode(getData($db_prefix.'domain','WHERE domain = :domain', '* FROM', $params),true);
                                        if($response_urlCheck['status'] == true){
                                            if($response_urlCheck['response'][0]['status'] !== "active"){
                                                http_response_code(400);
                                                echo json_encode([
                                                    'error' => [
                                                        'code' => 'INVALID_URL',
                                                        'message' => 'The Return URL ("'.$returnDomain.'") is whitelisted but not active. Please activate this domain in the "Domains" section to proceed.'
                                                    ]
                                                ]);
                                                exit;
                                            }
                                        }else{
                                            http_response_code(400);
                                            echo json_encode([
                                                'error' => [
                                                    'code' => 'INVALID_URL',
                                                    'message' => 'The provided Return URL ("'.$returnDomain.'") is not whitelisted. Please add this domain in the "Domains" section to continue.'
                                                ]
                                            ]);
                                            exit;
                                        }
                                    }
                                }

                                if($webhookUrl == ""){
                                    $webhookUrl = '--';
                                }else{
                                    $webhookDomain = getDomainFromUrl($webhookUrl);

                                    if (!$webhookDomain) {
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_URL',
                                                'message' => 'Webhook URL is invalid.'
                                            ]
                                        ]);
                                        exit;
                                    }else{
                                        $params = [ ':domain' => $webhookDomain ];

                                        $response_urlCheck = json_decode(getData($db_prefix.'domain','WHERE domain = :domain', '* FROM', $params),true);
                                        if($response_urlCheck['status'] == true){
                                            if($response_urlCheck['response'][0]['status'] !== "active"){
                                                http_response_code(400);
                                                echo json_encode([
                                                    'error' => [
                                                        'code' => 'INVALID_URL',
                                                        'message' => 'The Webhook URL ("'.$webhookDomain.'") is whitelisted but not active. Please activate this domain in the "Domains" section to proceed.'
                                                    ]
                                                ]);
                                                exit;
                                            }
                                        }else{
                                            http_response_code(400);
                                            echo json_encode([
                                                'error' => [
                                                    'code' => 'INVALID_URL',
                                                    'message' => 'The provided Webhook URL ("'.$webhookDomain.'") is not whitelisted. Please add this domain in the "Domains" section to continue.'
                                                ]
                                            ]);
                                            exit;
                                        }
                                    }
                                }

                                if (is_string($metadataRaw)) {
                                    $metadata = json_decode($metadataRaw, true);
                                    if ($metadata === null && json_last_error() !== JSON_ERROR_NONE) {
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_JSON',
                                                'message' => 'The metadata JSON is invalid.'
                                            ]
                                        ]);
                                        exit;
                                    }
                                } elseif (is_array($metadataRaw)) {
                                    $metadata = $metadataRaw;
                                } else {
                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code' => 'INVALID_METADATA',
                                            'message' => 'Metadata must be an array or valid JSON string.'
                                        ]
                                    ]);
                                    exit;
                                }

                                if (empty($fullName)) {
                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code' => 'MISSING_FIELD',
                                            'message' => 'Full name is required.'
                                        ]
                                    ]);
                                    exit;
                                }

                                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code' => 'INVALID_EMAIL',
                                            'message' => 'A valid email address is required.'
                                        ]
                                    ]);
                                    exit;
                                }

                                if (empty($mobile)) {
                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code' => 'MISSING_FIELD',
                                            'message' => 'Mobile number is required.'
                                        ]
                                    ]);
                                    exit;
                                }

                                if (!is_numeric($amount) || $amount <= 0) {
                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code' => 'INVALID_AMOUNT',
                                            'message' => 'Amount must be a positive number.'
                                        ]
                                    ]);
                                    exit;
                                }

                                $params = [ ':brand_id' => $response_api['response'][0]['brand_id'], ':code' => $currency ];

                                $response_currency = json_decode(getData($db_prefix.'currency','WHERE brand_id = :brand_id AND code = :code', '* FROM', $params),true);
                                if($response_currency['status'] == true){
                                    $params = [ ':brand_id' => $response_api['response'][0]['brand_id'], ':email' => $email, ':status' => 'suspend' ];

                                    $check_customer = json_decode(getData($db_prefix.'customer','WHERE brand_id = :brand_id AND email = :email AND status = :status', '* FROM', $params),true);
                                    if($check_customer['status'] == true){
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_CUSTOMER',
                                                'message' => $check_customer['response'][0]['suspend_reason'] == "--" ? 'Customer is already suspended by the admin.' : 'Customer is already suspended by the admin. Reason: '.$check_customer['response'][0]['suspend_reason']
                                            ]
                                        ]);
                                        exit;
                                    }

                                    $payment_id = generateItemID(27, 27);

                                    $customerInfoJson = json_encode([
                                        'name'   => $fullName,
                                        'email'  => $email,
                                        'mobile' => $mobile
                                    ], JSON_UNESCAPED_UNICODE);

                                    $columns = ['brand_id', 'ref', 'customer_info', 'amount', 'currency', 'metadata', 'return_url', 'webhook_url', 'created_date', 'updated_date'];
                                    $values = [$response_api['response'][0]['brand_id'], $payment_id, $customerInfoJson, money_sanitize($amount), $currency, json_encode($metadata), $returnUrl, $webhookUrl, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                    insertData($db_prefix.'transaction', $columns, $values);

                                    $params = [ ':brand_id' => $response_api['response'][0]['brand_id'], ':email' => $email ];

                                    $response_customer = json_decode(getData($db_prefix.'customer','WHERE brand_id = :brand_id AND email = :email', '* FROM', $params),true);
                                    if($response_customer['status'] == false){
                                        $ref = generateItemID();

                                        $columns = ['ref', 'brand_id', 'name', 'email', 'mobile', 'created_date', 'updated_date'];
                                        $values = [$ref, $response_api['response'][0]['brand_id'], $fullName, $email, $mobile, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                        insertData($db_prefix.'customer', $columns, $values);
                                    }

                                    echo json_encode(['pp_id' => $payment_id, 'pp_url' => $site_url.$path_payment.'/'.$payment_id]);
                                }else{
                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code' => 'INVALID_CURRENCY',
                                            'message' => 'Currency not supported.'
                                        ]
                                    ]);
                                    exit;
                                }
                            }else{
                                if($checkout_type == "popup"){
                                    $fullName      = $data['full_name'] ?? '';
                                    $email         = $data['email_address'] ?? '';
                                    $mobile        = $data['mobile_number'] ?? '';
                                    $amount        = $data['amount'] ?? '0';
                                    $currency      = $data['currency'] ?? 'BDT';
                                    $webhookUrl    = $data['webhook_url'] ?? '--';
                                    $metadataRaw   = $data['metadata'] ?? '{}';

                                    if($webhookUrl == ""){
                                        $webhookUrl = '--';
                                    }

                                    if (is_string($metadataRaw)) {
                                        $metadata = json_decode($metadataRaw, true);
                                        if ($metadata === null && json_last_error() !== JSON_ERROR_NONE) {
                                            http_response_code(400);
                                            echo json_encode([
                                                'error' => [
                                                    'code' => 'INVALID_JSON',
                                                    'message' => 'The metadata JSON is invalid.'
                                                ]
                                            ]);
                                            exit;
                                        }
                                    } elseif (is_array($metadataRaw)) {
                                        $metadata = $metadataRaw;
                                    } else {
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_METADATA',
                                                'message' => 'Metadata must be an array or valid JSON string.'
                                            ]
                                        ]);
                                        exit;
                                    }

                                    if (empty($fullName)) {
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'MISSING_FIELD',
                                                'message' => 'Full name is required.'
                                            ]
                                        ]);
                                        exit;
                                    }

                                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_EMAIL',
                                                'message' => 'A valid email address is required.'
                                            ]
                                        ]);
                                        exit;
                                    }

                                    if (empty($mobile)) {
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'MISSING_FIELD',
                                                'message' => 'Mobile number is required.'
                                            ]
                                        ]);
                                        exit;
                                    }

                                    if (!is_numeric($amount) || $amount <= 0) {
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_AMOUNT',
                                                'message' => 'Amount must be a positive number.'
                                            ]
                                        ]);
                                        exit;
                                    }

                                    $params = [ ':brand_id' => $response_api['response'][0]['brand_id'], ':code' => $currency ];

                                    $response_currency = json_decode(getData($db_prefix.'currency','WHERE brand_id = :brand_id AND code = :code', '* FROM', $params),true);
                                    if($response_currency['status'] == true){
                                        $params = [ ':brand_id' => $response_api['response'][0]['brand_id'], ':email' => $email, ':status' => 'suspend' ];

                                        $check_customer = json_decode(getData($db_prefix.'customer','WHERE brand_id = :brand_id AND email = :email AND status = :status', '* FROM', $params),true);
                                        if($check_customer['status'] == true){
                                            http_response_code(400);
                                            echo json_encode([
                                                'error' => [
                                                    'code' => 'INVALID_CUSTOMER',
                                                    'message' => $check_customer['response'][0]['suspend_reason'] == "--" ? 'Customer is already suspended by the admin.' : 'Customer is already suspended by the admin. Reason: '.$check_customer['response'][0]['suspend_reason']
                                                ]
                                            ]);
                                            exit;
                                        }


                                        $payment_id = generateItemID(27, 27);

                                        $customerInfoJson = json_encode([
                                            'name'   => $fullName,
                                            'email'  => $email,
                                            'mobile' => $mobile
                                        ], JSON_UNESCAPED_UNICODE);

                                        $columns = ['brand_id', 'ref', 'customer_info', 'amount', 'currency', 'metadata', 'webhook_url', 'created_date', 'updated_date'];
                                        $values = [$response_api['response'][0]['brand_id'], $payment_id, $customerInfoJson, money_sanitize($amount), $currency, json_encode($metadata), $webhookUrl, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                        insertData($db_prefix.'transaction', $columns, $values);

                                        $params = [ ':brand_id' => $response_api['response'][0]['brand_id'], ':email' => $email ];

                                        $response_customer = json_decode(getData($db_prefix.'customer','WHERE brand_id = :brand_id AND email = :email', '* FROM', $params),true);
                                        if($response_customer['status'] == false){
                                            $ref = generateItemID();

                                            $columns = ['ref', 'brand_id', 'name', 'email', 'mobile', 'created_date', 'updated_date'];
                                            $values = [$ref, $response_api['response'][0]['brand_id'], $fullName, $email, $mobile, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                            insertData($db_prefix.'customer', $columns, $values);
                                        }

                                        echo json_encode(['pp_id' => $payment_id, 'pp_url' => $site_url.$path_payment.'/'.$payment_id]);
                                    }else{
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_CURRENCY',
                                                'message' => 'Currency not supported.'
                                            ]
                                        ]);
                                        exit;
                                    }
                                }else{
                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code'    => 'INVALID_JSON_PAYLOAD',
                                            'message' => 'The JSON payload is invalid or malformed.'
                                        ]
                                    ]);
                                }
                            }
                        }else{
                            if($api_type == "verify-payment"){
                                $api_scopes = $response_api['response'][0]['api_scopes'] ?? [];
                                if (is_string($api_scopes)) {
                                    $api_scopes = json_decode($api_scopes, true);
                                }

                                if (!in_array("verify_payment", $api_scopes)) {
                                    $requiredScope = 'Verify Payment';

                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code'    => 'INSUFFICIENT_SCOPE',
                                            'message' => "The API key does not have the required permission: {$requiredScope}"
                                        ]
                                    ]);
                                    exit;
                                }

                                $pp_id = $data['pp_id'] ?? '';

                                if($pp_id == ""){
                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code' => 'INVALID_PP_ID',
                                            'message' => 'A valid bp id is required.'
                                        ]
                                    ]);
                                    exit;
                                }else{
                                    $params = [ ':ref' => $pp_id ];

                                    $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref', '* FROM', $params),true);
                                    if($response_transaction['status'] == true){
                                            $metadata = json_decode($response_transaction['response'][0]['metadata'], true) ?: [];

                                            $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$response_transaction['response'][0]['brand_id'].'" AND gateway_id = "'.$response_transaction['response'][0]['gateway_id'].'"'),true);

                                            $gateway = $response_gateway['response'][0]['display'] ?? '';

                                            $customer_info = json_decode($response_transaction['response'][0]['customer_info'], true) ?: [];

                                            $params = [ ':brand_id' => $response_transaction['response'][0]['brand_id'] ];

                                            $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);

                                            $net = money_sub(money_add($response_transaction['response'][0]['amount'], $response_transaction['response'][0]['processing_fee']), $response_transaction['response'][0]['discount_amount']);

                                            $transactions = [
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

                                            echo json_encode($transactions);
                                    }else{
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_PP_ID',
                                                'message' => 'A valid bp id is required.'
                                            ]
                                        ]);
                                        exit;
                                    }
                                }
                            }else{
                                if($api_type == "refund-payment"){
                                    $api_scopes = $response_api['response'][0]['api_scopes'] ?? [];
                                    if (is_string($api_scopes)) {
                                        $api_scopes = json_decode($api_scopes, true);
                                    }

                                    if (!in_array("refund_payment", $api_scopes)) {
                                        $requiredScope = 'Refund Payment';

                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code'    => 'INSUFFICIENT_SCOPE',
                                                'message' => "The API key does not have the required permission: {$requiredScope}"
                                            ]
                                        ]);
                                        exit;
                                    }

                                    $pp_id = $data['pp_id'] ?? '';

                                    if($pp_id == ""){
                                        http_response_code(400);
                                        echo json_encode([
                                            'error' => [
                                                'code' => 'INVALID_PP_ID',
                                                'message' => 'A valid bp id is required.'
                                            ]
                                        ]);
                                        exit;
                                    }else{
                                        $params = [ ':ref' => $pp_id ];

                                        $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref', '* FROM', $params),true);
                                        if($response_transaction['status'] == true){
                                            if (($response_transaction['response'][0]['status'] ?? '') !== 'completed') {
                                                http_response_code(400);
                                                echo json_encode([
                                                    'error' => [
                                                        'code' => 'INVALID_STATUS',
                                                        'message' => 'Only completed transactions can be refunded.'
                                                    ]
                                                ]);
                                                exit;
                                            }

                                            $transactionRow = $response_transaction['response'][0];
                                            $refundResult = null;

                                            $params = [ ':gateway_id' => $transactionRow['gateway_id'], ':brand_id' => $transactionRow['brand_id'] ];
                                            $response_gateway_info = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id AND brand_id = :brand_id', '* FROM', $params),true);
                                            $gateway_slug = $response_gateway_info['response'][0]['slug'] ?? '';

                                            if ($gateway_slug === 'bkash-api-tokenized') {
                                                $refundPayload = [
                                                    'amount' => money_round($transactionRow['local_net_amount']),
                                                    'sku' => $transactionRow['ref'],
                                                    'reason' => 'API refund'
                                                ];

                                                $refundResult = pp_bkash_tokenized_refund($transactionRow, $refundPayload);

                                                if (empty($refundResult['status'])) {
                                                    http_response_code(400);
                                                    echo json_encode([
                                                        'error' => [
                                                            'code' => 'REFUND_FAILED',
                                                            'message' => $refundResult['message'] ?? 'Refund failed.'
                                                        ]
                                                    ]);
                                                    exit;
                                                }
                                            }

                                            $source_info = json_decode($transactionRow['source_info'], true) ?: [];
                                            $source_info_changed = false;

                                            if (!empty($refundResult['data'])) {
                                                $refundTrxId = $refundResult['data']['refundTrxId'] ?? '';
                                                $refundAmount = $refundResult['data']['refundAmount'] ?? '';
                                                $completedTime = $refundResult['data']['completedTime'] ?? '';

                                                if ($refundTrxId !== '') {
                                                    $source_info[] = ['label' => 'Refund TrxID', 'value' => $refundTrxId];
                                                    $source_info_changed = true;
                                                }
                                                if ($refundAmount !== '') {
                                                    $source_info[] = ['label' => 'Refund Amount', 'value' => $refundAmount];
                                                    $source_info_changed = true;
                                                }
                                                if ($completedTime !== '') {
                                                    $source_info[] = ['label' => 'Refund Time', 'value' => $completedTime];
                                                    $source_info_changed = true;
                                                }
                                            }

                                            $columns = ['status',  'updated_date'];
                                            $values = ['refunded', getCurrentDatetime('Y-m-d H:i:s')];
                                            $condition = 'id ="'.$response_transaction['response'][0]['id'].'"'; 

                                            if ($source_info_changed) {
                                                $columns[] = 'source_info';
                                                $values[] = json_encode($source_info, JSON_UNESCAPED_UNICODE);
                                                $response_transaction['response'][0]['source_info'] = json_encode($source_info, JSON_UNESCAPED_UNICODE);
                                            }

                                            updateData($db_prefix.'transaction', $columns, $values, $condition);

                                            $response_transaction['response'][0]['status'] = 'refunded';


                                            $metadata = json_decode($response_transaction['response'][0]['metadata'], true) ?: [];

                                            $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$response_transaction['response'][0]['brand_id'].'" AND gateway_id = "'.$response_transaction['response'][0]['gateway_id'].'"'),true);

                                            $gateway = $response_gateway['response'][0]['display'] ?? '';

                                            $customer_info = json_decode($response_transaction['response'][0]['customer_info'], true) ?: [];

                                            $params = [ ':brand_id' => $response_transaction['response'][0]['brand_id'] ];

                                            $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);

                                            $net = money_sub(money_add($response_transaction['response'][0]['amount'], $response_transaction['response'][0]['processing_fee']), $response_transaction['response'][0]['discount_amount']);

                                            $transactions = [
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
                                                "status" => 'refunded',
                                                "date" => convertUTCtoUserTZ($response_transaction['response'][0]['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                            ];

                                            echo json_encode($transactions);
                                        }else{
                                            http_response_code(400);
                                            echo json_encode([
                                                'error' => [
                                                    'code' => 'INVALID_PP_ID',
                                                    'message' => 'A valid bp id is required.'
                                                ]
                                            ]);
                                            exit;
                                        }
                                    }
                                }else{
                                    http_response_code(400);
                                    echo json_encode([
                                        'error' => [
                                            'code'    => 'INVALID_JSON_PAYLOAD',
                                            'message' => 'The JSON payload is invalid or malformed.'
                                        ]
                                    ]);
                                }
                            }
                        }

                        break;

                    case $path_payment:
                        $paymentID = $param1;
                        $paymentID124123412 = $param1;

                        $params = [ ':ref' => $paymentID ];

                        $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref', '* FROM', $params),true);
                        if($response_transaction['status'] == true){
                            $params = [ ':brand_id' => $response_transaction['response'][0]['brand_id'] ];

                            $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                            if($response_brand['status'] == true){
                                if(file_exists(__DIR__.'/pp-content/pp-modules/pp-themes/'.$response_brand['response'][0]['theme'].'/class.php')){
                                    require_once __DIR__.'/pp-content/pp-modules/pp-themes/'.$response_brand['response'][0]['theme'].'/class.php';

                                    $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $response_brand['response'][0]['theme']))) . 'Theme';

                                    $theme = new $class();

                                    $fields = $theme->fields();

                                    $supported_languages = $theme->supported_languages();
                                    $lang_text = $theme->lang_text();

                                    $language = resolveModuleLanguage($response_brand['response'][0]['language'],$supported_languages);

                                    // Build $lang array for developer
                                    $lang = buildLangArray($lang_text, $language);

                                    $options = [];
                                    foreach($fields as $field){
                                        $optionName = $response_brand['response'][0]['theme'] . '-' . $field['name'];
                                        $value = get_env($optionName, $response_brand['response'][0]['brand_id']);

                                        // Handle multi-select stored as JSON
                                        if(!empty($field['multiple']) && !empty($value)){
                                            $value = is_array($value) ? $value : json_decode($value, true);
                                        }

                                        $options[$field['name']] = $value;
                                    }

                                    $transactionRow = $response_transaction['response'][0];
                                    
                                    $customer = json_decode($transactionRow['customer_info'], true) ?? [];

                                    $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$response_brand['response'][0]['brand_id'].'" AND gateway_id = "'.$response_transaction['response'][0]['gateway_id'].'"'),true);

                                    $gateway = $response_gateway['response'][0]['display'] ?? '';

                                    if($transactionRow['status'] == "initiated"){
                                        $finalUrl = '--';
                                    }else{
                                        if($transactionRow['return_url'] == "" || $transactionRow['return_url'] == "--"){
                                            $finalUrl = '--';
                                        }else{
                                            $finalUrl = addQueryParams($transactionRow['return_url'], ['pp_status' => $transactionRow['status'], 'transaction_ref' => $transactionRow['ref']]);
                                        }
                                    }

                                    $response_faq = json_decode(getData($db_prefix.'faq',' WHERE brand_id ="'.$response_brand['response'][0]['brand_id'].'" AND status ="active" ORDER BY 1 DESC'),true);
                                    
                                    /* Clean Transaction Info */
                                    $transactionInfo = [
                                        'ref'     => $transactionRow['ref'],
                                        'customer' => [
                                            'id'     => $customer['id']     ?? null,
                                            'name'   => $customer['name']   ?? null,
                                            'email'  => $customer['email']  ?? null,
                                            'mobile' => $customer['mobile'] ?? null,
                                        ],
                                        'payment_method'        => $gateway,
                                        'currency'        => $transactionRow['currency'],
                                        'amount'        => money_round($transactionRow['amount']),
                                        'discount_amount'       => money_round($transactionRow['discount_amount']),
                                        'processing_fee'       => money_round($transactionRow['processing_fee']),
                                        'local_net_amount'       => money_round($transactionRow['local_net_amount']),
                                        'local_currency'          => $transactionRow['local_currency'],
                                        'return_url'          => $finalUrl,
                                        'created_date'          => $transactionRow['created_date'],
                                        'updated_date'          => $transactionRow['updated_date'],
                                        'status'          => $transactionRow['status'],
                                        'brandId' => $transactionRow['brand_id'],
                                    ];

                                    $brandRow = $response_brand['response'][0];

                                    $brandInfo = [
                                        'id'            => $brandRow['brand_id'],
                                        'name'          => ($brandRow['name'] == "--") ? $brandRow['identify_name'] : $brandRow['name'],
                                        'identifyName'  => $brandRow['identify_name'],
                                        'logo'          => $brandRow['logo'] !== '--' ? $brandRow['logo'] : 'https://help.piprapay.com/storage/branding_media/8a5c6ee4-8eba-401d-bffb-c43006d5f65d.png',
                                        'favicon'       => $brandRow['favicon'] !== '--' ? $brandRow['favicon'] : 'https://help.piprapay.com/favicon/icon-144x144.png',

                                        'support' => [
                                            'email'   => $brandRow['support_email_address'],
                                            'phone'   => $brandRow['support_phone_number'],
                                            'website' => $brandRow['support_website'],
                                            'whatsapp'=> $brandRow['whatsapp_number'],
                                            'telegram'=> 'https://t.me/'.$brandRow['telegram'],
                                            'messenger'=> 'https://m.me/'.$brandRow['facebook_messenger'],
                                            'fb_page'=> 'https://facebook.com/'.$brandRow['facebook_page'],
                                        ],

                                        'address' => [
                                            'street'  => $brandRow['street_address'],
                                            'city'    => $brandRow['city_town'],
                                            'postal'  => $brandRow['postal_code'],
                                            'country' => $brandRow['country'],
                                        ],

                                        'locale' => [
                                            'timezone' => $brandRow['timezone'],
                                            'language' => $language,
                                            'currency' => $brandRow['currency_code'],
                                        ],
                                    ];

                                    $faqs = [];

                                    foreach ($response_faq['response'] as $faq) {
                                        $faqs[] = [
                                            'title'       => $faq['title'],
                                            'description' => $faq['description']
                                        ];
                                    }

                                    $pageData = [
                                        'transaction' => $transactionInfo,
                                        'brand'   => $brandInfo,
                                        'faqs'   => $faqs,
                                        'options'   => $options,
                                        'lang'   => $lang,
                                    ];

                                    // Pass to theme to render checkout page
                                    $theme->renderCheckout($pageData);
                                }else{
                                    http_response_code(403);
                                    exit('Invalid theme slug');
                                }
                            }else{
                                if(file_exists(__DIR__ . '/pp-404.php')){
                                    http_response_code(404);
                                    require __DIR__ . '/pp-404.php';
                                }else{
                                    http_response_code(403);
                                    exit('Direct access not allowed');
                                }
                            }
                        }else{
                            if(file_exists(__DIR__ . '/pp-404.php')){
                                http_response_code(404);
                                require __DIR__ . '/pp-404.php';
                            }else{
                                http_response_code(403);
                                exit('Direct access not allowed');
                            }
                        }
                        break;

                    case $path_invoice:
                        $invoiceID = $param1;

                        if($invoiceID == "webhook"){
                            // 1️⃣ Read raw JSON
                            $raw = file_get_contents('php://input');

                            if ($raw === '' || $raw === false) {
                                http_response_code(400);
                                exit('No payload received');
                            }

                            // 2️⃣ Decode JSON
                            $data = json_decode($raw, true);

                            if (json_last_error() !== JSON_ERROR_NONE) {
                                http_response_code(400);
                                exit('Invalid JSON');
                            }

                            // 3️⃣ Access data (EXACT match to sender)
                            $pp_id = $data['pp_id'] ?? null;

                            $params = [ ':ref' => $pp_id ];

                            $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref', '* FROM', $params),true);
                            if($response_transaction['status'] == true){

                                $metadata_decode = json_decode($response_transaction['response'][0]['metadata'], true);

                                $invoiceIDD  = $metadata_decode['invoice_id'] ?? '';

                                $params = [ ':ref' => $invoiceIDD ];

                                $response_invoice = json_decode(getData($db_prefix.'invoice','WHERE ref = :ref', '* FROM', $params),true);
                                if($response_invoice['status'] == true){
                                    if($response_transaction['response'][0]['status'] == "completed"){
                                        $columns = ['gateway_id', 'status', 'updated_date'];
                                        $values = [$response_transaction['response'][0]['gateway_id'], 'paid', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = 'id ="'.$response_invoice['response'][0]['id'].'"'; 

                                        updateData($db_prefix.'invoice', $columns, $values, $condition);
                                    }

                                    if($response_transaction['response'][0]['status'] == "refunded"){
                                        $columns = ['gateway_id', 'status', 'updated_date'];
                                        $values = [$response_transaction['response'][0]['gateway_id'], 'refunded', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = 'id ="'.$response_invoice['response'][0]['id'].'"'; 

                                        updateData($db_prefix.'invoice', $columns, $values, $condition);
                                    }

                                    $params = [ ':ref' => $invoiceIDD ];

                                    $response_invoice = json_decode(getData($db_prefix.'invoice','WHERE ref = :ref', '* FROM', $params),true);
                                    
                                    $params = [ ':brand_id' => $response_invoice['response'][0]['brand_id'] ];
                                    $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                            
                                    $invoice_items_array = [];

                                    $response_items = json_decode(getData($db_prefix.'invoice_items','WHERE brand_id ="'.$response_invoice['response'][0]['brand_id'].'" AND ref ="'.$response_invoice['response'][0]['ref'].'"'),true);
                                    foreach($response_items['response'] as $rowItem){
                                        $invoice_items_array[] = [
                                            'description' => $rowItem['description'],
                                            'amount'      => money_round($rowItem['amount']),
                                            'quantity'    => money_round($rowItem['quantity']),
                                            'discount'    => money_round($rowItem['discount']),
                                            'vat'         => money_round($rowItem['vat'])
                                        ];
                                    }

                                    $all_invoices= [
                                        'customer_info'  => $response_invoice['response'][0]['customer_info'],
                                        'invoice_info'   => [
                                            'invoice_id'   => $response_invoice['response'][0]['ref'],
                                            'brand_id'     => $response_invoice['response'][0]['brand_id'],
                                            'currency'     => $response_invoice['response'][0]['currency'],
                                            'due_date'     => $response_invoice['response'][0]['expired_date'],
                                            'shipping'     => money_round($response_invoice['response'][0]['shipping']),
                                            'status'       => $response_invoice['response'][0]['status'],
                                            'note'         => $response_invoice['response'][0]['note'],
                                            'private_note' => $response_invoice['response'][0]['private_note'],
                                            'created_date' => convertUTCtoUserTZ($response_invoice['response'][0]['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                            'updated_date' => convertUTCtoUserTZ(getCurrentDatetime('Y-m-d H:i:s'), ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                        ],
                                        'invoice_items'  => $invoice_items_array
                                    ];
                                    if(!empty($all_invoices)){
                                        do_action('invoices.updated.status', $all_invoices);
                                    }
                                }
                            }

                            // 6️⃣ IMPORTANT: Return 200 OK
                            http_response_code(200);
                            echo 'OK';
                            exit();
                        }

                        $params = [ ':ref' => $invoiceID ];

                        $response_invoice = json_decode(getData($db_prefix.'invoice','WHERE ref = :ref', '* FROM', $params),true);
                        if($response_invoice['status'] == true){
                            $params = [ ':brand_id' => $response_invoice['response'][0]['brand_id'] ];

                            $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                            if($response_brand['status'] == true){
                                if(file_exists(__DIR__.'/pp-content/pp-modules/pp-themes/'.$response_brand['response'][0]['theme'].'/class.php')){
                                    require_once __DIR__.'/pp-content/pp-modules/pp-themes/'.$response_brand['response'][0]['theme'].'/class.php';

                                    $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $response_brand['response'][0]['theme']))) . 'Theme';

                                    $theme = new $class();

                                    $fields = $theme->fields();

                                    $supported_languages = $theme->supported_languages();
                                    $lang_text = $theme->lang_text();

                                    $language = resolveModuleLanguage($response_brand['response'][0]['language'],$supported_languages);

                                    // Build $lang array for developer
                                    $lang = buildLangArray($lang_text, $language);

                                    $options = [];
                                    foreach($fields as $field){
                                        $optionName = $response_brand['response'][0]['theme'] . '-' . $field['name'];
                                        $value = get_env($optionName, $response_brand['response'][0]['brand_id']);

                                        // Handle multi-select stored as JSON
                                        if(!empty($field['multiple']) && !empty($value)){
                                            $value = is_array($value) ? $value : json_decode($value, true);
                                        }

                                        $options[$field['name']] = $value;
                                    }

                                    $invoiceRow = $response_invoice['response'][0];
                                    
                                    $customer = json_decode($invoiceRow['customer_info'], true) ?? [];

                                    $params = [ ':gateway_id' => $response_invoice['response'][0]['gateway_id'] ];

                                    $response_gateway = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id', '* FROM', $params),true);

                                    /* Clean Invoice Info */
                                    $invoiceInfo = [
                                        'iid'     => $invoiceRow['ref'],
                                        'gateway'        => $response_gateway['response'][0]['display'] ?? '',
                                        'status'        => $invoiceRow['status'],
                                        'currency'      => $invoiceRow['currency'],
                                        'due_date'       => $invoiceRow['due_date'] !== '--' ? convertUTCtoUserTZ($invoiceRow['due_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y") : null,
                                        'shippingFee'   => money_round($invoiceRow['shipping']),
                                        'note'          => $invoiceRow['note'] !== '--' ? $invoiceRow['note'] : null,
                                        'privateNote'   => $invoiceRow['private_note'] !== '--' ? $invoiceRow['private_note'] : null,
                                        'created_date'     => convertUTCtoUserTZ($invoiceRow['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y"),
                                        'updated_date'     => convertUTCtoUserTZ($invoiceRow['updated_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y"),

                                        'customer' => [
                                            'id'     => $customer['id']     ?? null,
                                            'name'   => $customer['name']   ?? null,
                                            'email'  => $customer['email']  ?? null,
                                            'mobile' => $customer['mobile'] ?? null,
                                        ],

                                        'brandId' => $invoiceRow['brand_id'],
                                    ];

                                    $invoiceItems = [];
                                    $subTotal = "0";
                                    $totalDiscount = "0";
                                    $totalVat = "0";

                                    $params = [ ':invoice_id' => $invoiceRow['ref'], ':brand_id' => $invoiceRow['brand_id'] ];

                                    $response_invoiceItem = json_decode(getData($db_prefix.'invoice_items','WHERE invoice_id = :invoice_id AND brand_id = :brand_id', '* FROM', $params), true);

                                    if($response_invoiceItem['status'] == true) {
                                        foreach($response_invoiceItem['response'] as $row) {

                                            $amount   = money_sanitize($row['amount']);
                                            $quantity = money_sanitize($row['quantity']);
                                            $discount = money_sanitize($row['discount']);
                                            $vat      = money_sanitize($row['vat']);

                                            $lineTotal = money_add(money_sub(money_mul($amount, $quantity), $discount), $vat);

                                            $invoiceItems[] = [
                                                'description' => $row['description'],
                                                'unitPrice'   => money_round($amount, 2),
                                                'quantity'    => $quantity,
                                                'discount'    => money_round($discount, 2),
                                                'vat'         => money_round($vat, 2),
                                                'total'       => money_round($lineTotal, 2),
                                            ];

                                            $subTotal      = money_add($subTotal, money_mul($amount, $quantity));
                                            $totalDiscount = money_add($totalDiscount, $discount);
                                            $totalVat      = money_add($totalVat, $vat);
                                        }
                                    }

                                    $shippingFee = money_sanitize($invoiceInfo['shippingFee']);

                                    $grandTotal = money_add(money_add(money_sub($subTotal, $totalDiscount), $totalVat), $shippingFee);

                                    $invoiceTotals = [
                                        'subTotal'   => money_round($subTotal, 2),
                                        'discount'   => money_round($totalDiscount, 2),
                                        'vat'        => money_round($totalVat, 2),
                                        'shipping'   => money_round($shippingFee, 2),
                                        'grandTotal' => money_round($grandTotal, 2),
                                    ];

                                    $brandRow = $response_brand['response'][0];

                                    $brandInfo = [
                                        'id'            => $brandRow['brand_id'],
                                        'name'          => ($brandRow['name'] == "--") ? $brandRow['identify_name'] : $brandRow['name'],
                                        'identifyName'  => $brandRow['identify_name'],
                                        'logo'          => $brandRow['logo'] !== '--' ? $brandRow['logo'] : 'https://help.piprapay.com/storage/branding_media/8a5c6ee4-8eba-401d-bffb-c43006d5f65d.png',
                                        'favicon'       => $brandRow['favicon'] !== '--' ? $brandRow['favicon'] : 'https://help.piprapay.com/favicon/icon-144x144.png',

                                        'support' => [
                                            'email'   => $brandRow['support_email_address'],
                                            'phone'   => $brandRow['support_phone_number'],
                                            'website' => $brandRow['support_website'],
                                            'whatsapp'=> $brandRow['whatsapp_number'],
                                            'telegram'=> $brandRow['telegram'],
                                        ],

                                        'address' => [
                                            'street'  => $brandRow['street_address'],
                                            'city'    => $brandRow['city_town'],
                                            'postal'  => $brandRow['postal_code'],
                                            'country' => $brandRow['country'],
                                        ],

                                        'locale' => [
                                            'timezone' => $brandRow['timezone'],
                                            'language' => $language,
                                            'currency' => $brandRow['currency_code'],
                                        ],
                                    ];

                                    $pageData = [
                                        'invoice' => $invoiceInfo,
                                        'items'   => $invoiceItems,
                                        'totals'  => $invoiceTotals,
                                        'brand'   => $brandInfo,
                                        'options'   => $options,
                                        'lang'   => $lang,
                                    ];

                                    // Pass to theme to render checkout page
                                    $theme->renderInvoice($pageData);
                                }else{
                                    http_response_code(403);
                                    exit('Invalid theme slug');
                                }
                            }else{
                                if(file_exists(__DIR__ . '/pp-404.php')){
                                    http_response_code(404);
                                    require __DIR__ . '/pp-404.php';
                                }else{
                                    http_response_code(403);
                                    exit('Direct access not allowed');
                                }
                            }
                        }else{
                            if(file_exists(__DIR__ . '/pp-404.php')){
                                http_response_code(404);
                                require __DIR__ . '/pp-404.php';
                            }else{
                                http_response_code(403);
                                exit('Direct access not allowed');
                            }
                        }
                        break;

                    case $path_payment_link:
                        $paymentLinkID = $param1;

                        if($paymentLinkID == "default"){
                            $brandID = $segments[2] ?? null;

                            $params = [ ':brand_id' => $brandID ];

                            $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                            if($response_brand['status'] == true){
                                if(file_exists(__DIR__.'/pp-content/pp-modules/pp-themes/'.$response_brand['response'][0]['theme'].'/class.php')){
                                    require_once __DIR__.'/pp-content/pp-modules/pp-themes/'.$response_brand['response'][0]['theme'].'/class.php';

                                    $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $response_brand['response'][0]['theme']))) . 'Theme';

                                    $theme = new $class();

                                    $fields = $theme->fields();

                                    $supported_languages = $theme->supported_languages();
                                    $lang_text = $theme->lang_text();

                                    $language = resolveModuleLanguage($response_brand['response'][0]['language'],$supported_languages);

                                    $lang = buildLangArray($lang_text, $language);

                                    $options = [];
                                    foreach($fields as $field){
                                        $optionName = $response_brand['response'][0]['theme'] . '-' . $field['name'];
                                        $value = get_env($optionName, $response_brand['response'][0]['brand_id']);

                                        if(!empty($field['multiple']) && !empty($value)){
                                            $value = is_array($value) ? $value : json_decode($value, true);
                                        }

                                        $options[$field['name']] = $value;
                                    }

                                    $paymentLinkInfo = [
                                        'pid' => $response_brand['response'][0]['brand_id'],
                                        'currency'    => (($v = get_env('payment-link-default-currency', $response_brand['response'][0]['brand_id'])) && $v !== '--') ? $v : $brandRow['currency_code'],
                                        'brandId' => $response_brand['response'][0]['brand_id'],
                                    ];

                                    $brandRow = $response_brand['response'][0];
                                    
                                    $brandInfo = [
                                        'id'            => $brandRow['brand_id'],
                                        'name'          => ($brandRow['name'] == "--") ? $brandRow['identify_name'] : $brandRow['name'],
                                        'identifyName'  => $brandRow['identify_name'],
                                        'logo'          => $brandRow['logo'] !== '--' ? $brandRow['logo'] : 'https://help.piprapay.com/storage/branding_media/8a5c6ee4-8eba-401d-bffb-c43006d5f65d.png',
                                        'favicon'       => $brandRow['favicon'] !== '--' ? $brandRow['favicon'] : 'https://help.piprapay.com/favicon/icon-144x144.png',

                                        'support' => [
                                            'email'   => $brandRow['support_email_address'],
                                            'phone'   => $brandRow['support_phone_number'],
                                            'website' => $brandRow['support_website'],
                                            'whatsapp'=> $brandRow['whatsapp_number'],
                                            'telegram'=> $brandRow['telegram'],
                                        ],

                                        'address' => [
                                            'street'  => $brandRow['street_address'],
                                            'city'    => $brandRow['city_town'],
                                            'postal'  => $brandRow['postal_code'],
                                            'country' => $brandRow['country'],
                                        ],

                                        'locale' => [
                                            'timezone' => $brandRow['timezone'],
                                            'language' => $language,
                                            'currency' => $brandRow['currency_code'],
                                        ],
                                    ];

                                    $pageData = [
                                        'paymentLink' => $paymentLinkInfo,
                                        'brand'   => $brandInfo,
                                        'options'   => $options,
                                        'lang'   => $lang,
                                    ];

                                    // Pass to theme to render checkout page
                                    $theme->renderPaymentLinkDefault($pageData);
                                }else{
                                    http_response_code(403);
                                    exit('Invalid theme slug');
                                }
                            }else{
                                if(file_exists(__DIR__ . '/pp-404.php')){
                                    http_response_code(404);
                                    require __DIR__ . '/pp-404.php';
                                }else{
                                    http_response_code(403);
                                    exit('Direct access not allowed');
                                }
                            }
                        }else{
                            $params = [ ':ref' => $paymentLinkID ];

                            $response_payment_link = json_decode(getData($db_prefix.'payment_link','WHERE ref = :ref', '* FROM', $params),true);
                            if($response_payment_link['status'] == true){
                                $params = [ ':brand_id' => $response_payment_link['response'][0]['brand_id'] ];

                                $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                                if($response_brand['status'] == true){
                                    if(file_exists(__DIR__.'/pp-content/pp-modules/pp-themes/'.$response_brand['response'][0]['theme'].'/class.php')){
                                        require_once __DIR__.'/pp-content/pp-modules/pp-themes/'.$response_brand['response'][0]['theme'].'/class.php';

                                        $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $response_brand['response'][0]['theme']))) . 'Theme';

                                        $theme = new $class();

                                        $fields = $theme->fields();

                                        $supported_languages = $theme->supported_languages();
                                        $lang_text = $theme->lang_text();

                                        $language = resolveModuleLanguage($response_brand['response'][0]['language'],$supported_languages);

                                        // Build $lang array for developer
                                        $lang = buildLangArray($lang_text, $language);

                                        $options = [];
                                        foreach($fields as $field){
                                            $optionName = $response_brand['response'][0]['theme'] . '-' . $field['name'];
                                            $value = get_env($optionName, $response_brand['response'][0]['brand_id']);

                                            // Handle multi-select stored as JSON
                                            if(!empty($field['multiple']) && !empty($value)){
                                                $value = is_array($value) ? $value : json_decode($value, true);
                                            }

                                            $options[$field['name']] = $value;
                                        }

                                        $paymentRow = $response_payment_link['response'][0];

                                        $product_info = json_decode($paymentRow['product_info'], true);
                                                    
                                        if($paymentRow['expired_date'] == "--"){
                                            $status = $paymentRow['status'];
                                        }else{
                                            if (isExpired($paymentRow['expired_date'])) {
                                                $status = 'expired';
                                            } else {
                                                $status = $paymentRow['status'];
                                            }
                                        }

                                        $paymentLinkInfo = [
                                            'pid' => $paymentRow['ref'],
                                            'status'    => $status,
                                            'currency'  => $paymentRow['currency'],
                                            'total'  => money_round($paymentRow['amount']),
                                            'quantity'  => money_sanitize($paymentRow['quantity']),
                                            'expired_date'=> ($paymentRow['expired_date'] == "" || $paymentRow['expired_date'] == "--") ? '--' : convertUTCtoUserTZ($paymentRow['expired_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y"),
                                            'created_date'     => convertUTCtoUserTZ($paymentRow['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y"),
                                            'updated_date'     => convertUTCtoUserTZ($paymentRow['updated_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y"),

                                            'product' => [
                                                'title'       => $product_info['title'] ?? 'Product',
                                                'description' => $product_info['description'] ?? null,
                                            ],

                                            'brandId' => $paymentRow['brand_id'],
                                        ];

                                        $customFields = [];

                                        $params = [ ':paymentLinkID' => $paymentRow['ref'] ];

                                        $response_PaymentLinkItem = json_decode(getData($db_prefix.'payment_link_field','WHERE paymentLinkID = :paymentLinkID', '* FROM', $params),true);
                                        if($response_PaymentLinkItem['status'] == true){
                                            foreach($response_PaymentLinkItem['response'] as $row){
                                                $Inputoptions = [];
                                                if ($row['formType'] === 'select' && $row['value'] !== '--' || $row['formType'] === 'file' && $row['value'] !== '--' || $row['formType'] === 'checkbox' && $row['value'] !== '--' || $row['formType'] === 'radio' && $row['value'] !== '--') {
                                                    $Inputoptions = array_map('trim', explode(',', $row['value']));
                                                }

                                                $customFields[] = [
                                                    'type'        => $row['formType'],              // text, textarea, select
                                                    'name'        => strtolower(preg_replace('/[^a-z0-9_]/i', '_', $row['fieldName'])),                              // customer_name
                                                    'label'       => $row['fieldName'],             // Customer Name
                                                    'options'     => $Inputoptions,                      // for select
                                                    'required'    => $row['required'],                          // future extend
                                                ];
                                            }
                                        }


                                        $paymentLinkInfo['fields'] = $customFields;


                                        $brandRow = $response_brand['response'][0];
                                        
                                        $brandInfo = [
                                            'id'            => $brandRow['brand_id'],
                                            'name'          => ($brandRow['name'] == "--") ? $brandRow['identify_name'] : $brandRow['name'],
                                            'identifyName'  => $brandRow['identify_name'],
                                            'logo'          => $brandRow['logo'] !== '--' ? $brandRow['logo'] : 'https://help.piprapay.com/storage/branding_media/8a5c6ee4-8eba-401d-bffb-c43006d5f65d.png',
                                            'favicon'       => $brandRow['favicon'] !== '--' ? $brandRow['favicon'] : 'https://help.piprapay.com/favicon/icon-144x144.png',
                                                
                                            'support' => [
                                                'email'   => $brandRow['support_email_address'],
                                                'phone'   => $brandRow['support_phone_number'],
                                                'website' => $brandRow['support_website'],
                                                'whatsapp'=> $brandRow['whatsapp_number'],
                                                'telegram'=> $brandRow['telegram'],
                                            ],

                                            'address' => [
                                                'street'  => $brandRow['street_address'],
                                                'city'    => $brandRow['city_town'],
                                                'postal'  => $brandRow['postal_code'],
                                                'country' => $brandRow['country'],
                                            ],

                                            'locale' => [
                                                'timezone' => $brandRow['timezone'],
                                                'language' => $language,
                                                'currency' => $brandRow['currency_code'],
                                            ],
                                        ];

                                        $pageData = [
                                            'paymentLink' => $paymentLinkInfo,
                                            'brand'   => $brandInfo,
                                            'options'   => $options,
                                            'lang'   => $lang,
                                        ];

                                        // Pass to theme to render checkout page
                                        $theme->renderPaymentLink($pageData);
                                    }else{
                                        http_response_code(403);
                                        exit('Invalid theme slug');
                                    }
                                }else{
                                    if(file_exists(__DIR__ . '/pp-404.php')){
                                        http_response_code(404);
                                        require __DIR__ . '/pp-404.php';
                                    }else{
                                        http_response_code(403);
                                        exit('Direct access not allowed');
                                    }
                                }
                            }else{
                                if(file_exists(__DIR__ . '/pp-404.php')){
                                    http_response_code(404);
                                    require __DIR__ . '/pp-404.php';
                                }else{
                                    http_response_code(403);
                                    exit('Direct access not allowed');
                                }
                            }
                        }
                        break;

                    case $path_admin:
                        $_GET['page_name'] = $param1;

                        if(file_exists(__DIR__ . '/pp-content/pp-admin/index.php')){
                            require __DIR__ . '/pp-content/pp-admin/index.php';
                        }else{
                            if(file_exists(__DIR__ . '/pp-404.php')){
                                http_response_code(404);
                                require __DIR__ . '/pp-404.php';
                            }else{
                                http_response_code(403);
                                exit('Direct access not allowed');
                            }
                        }
                        break;

                    case $path_cron:
                        if($param1 == ""){
                            if(file_exists(__DIR__ . '/pp-404.php')){
                                http_response_code(404);
                                require __DIR__ . '/pp-404.php';
                            }else{
                                http_response_code(403);
                                exit('Direct access not allowed');
                            }
                        }else{
                            if(escape_string($param1) == get_env('cron-job')){
                                $lockFile = __DIR__ . '/pp-media/storage/cron.lock';
                                $maxLockTime = 60 * 10;

                                header('Content-Type: application/json');

                                echo json_encode(['status' => 'true', "message" => "Cron run executed."]);

                                if (file_exists($lockFile) && (time() - filemtime($lockFile)) < $maxLockTime) {
                                    exit; 
                                }

                                file_put_contents($lockFile, time());

                                set_env('last-cron-invocation', getCurrentDatetime('Y-m-d H:i:s'));

                                //auto system update
                                //auto system update
                                $automatic_update = get_env('system-settings-automatic_update') === '--' || (get_env('system-settings-automatic_update') === '') ? '' : get_env('system-settings-automatic_update');

                                if($automatic_update == "yes"){
                                    if (strtotime(getCurrentDatetime('Y-m-d H:i:s')) - strtotime(get_env('last-auto-update-check') ?: getCurrentDatetime('Y-m-d H:i:s')) >= 10*3600) {
                                        set_env('last-auto-update-check', getCurrentDatetime('Y-m-d H:i:s'));

                                        $manifest = json_decode(file_get_contents('https://updates.piprapay.com/manifest.json'), true);

                                        $current_code = $piprapay_current_version['version_code'];
                                        $current_name = $piprapay_current_version['version_name'];

                                        if(get_env('system-settings-update_channel') == "" || get_env('system-settings-update_channel') == "--" || get_env('system-settings-update_channel') == "stable"){
                                            $update_channel = 'stable';
                                        }else{
                                            $update_channel = 'beta';
                                        }

                                        $channel_data = $manifest['channels'][$update_channel] ?? null;

                                        $update_available = false;
                                        $latest_name = null;
                                        $latest_code = null;

                                        if ($channel_data) {
                                            $latest_name = $channel_data['latest_version_name'];
                                            $latest_code = $channel_data['latest_version_code'];

                                            if (version_compare($latest_code, $current_code, '>')) {
                                                $update_available = true;
                                            }
                                        }

                                        if($update_available == true){
                                            do_action('system.update.available', [
                                                'current_version_name'  => $current_name,
                                                'current_version_code'  => $current_code,
                                                'latest_version_name'  => $latest_name,
                                                'latest_version_code'  => $latest_code,
                                            ]);

                                            set_env('last-update-version-name', $latest_name);
                                            set_env('last-update-version', $latest_code);
                                        }else{
                                            set_env('last-update-version-name', $current_name);
                                            set_env('last-update-version', $current_code);
                                        }
                                    }else{
                                        if(get_env('last-auto-update-check') == "--" || get_env('last-auto-update-check') == ""){
                                            set_env('last-auto-update-check', getCurrentDatetime('Y-m-d H:i:s'));
                                        }
                                    }
                                }
                                //auto system update
                                //auto system update


                                //verify pending against sms data
                                //verify pending against sms data
                                $response_pending_transaciton = json_decode(getData($db_prefix.'transaction','WHERE status = "pending" AND sender_key NOT IN ("--", "") ORDER BY 1 DESC'), true);
                                $all_transactions = [];
                                foreach($response_pending_transaciton['response'] as $row){
                                    $params = [ ':sender_key' => $row['sender_key'], ':type' => $row['sender_type'], ':trx_id' => $row['trx_id'], ':status' => 'approved' ];

                                    $response_pending_SMSTransaction = json_decode(getData($db_prefix.'sms_data','WHERE sender_key = :sender_key AND type = :type AND trx_id = :trx_id AND status = :status', '* FROM', $params), true);
                                    if($response_pending_SMSTransaction['status'] == true){

                                        $response_brand = json_decode(getData($db_prefix.'brands',' WHERE brand_id ="'.$row['brand_id'].'"'),true);
                                        if($response_brand['status'] == true){
                                            if (verifyPaymentTolerance($row['local_net_amount'], $response_pending_SMSTransaction['response'][0]['amount'], $response_brand['response'][0]['payment_tolerance'])) {
                                                    $columns = ['status', 'updated_date'];
                                                    $values = ['used', getCurrentDatetime('Y-m-d H:i:s')];
                                                    $condition = 'id ="'.$response_pending_SMSTransaction['response'][0]['id'].'"'; 
                                                    
                                                    updateData($db_prefix.'sms_data', $columns, $values, $condition);

                                                    $columns = ['status', 'sender', 'trx_id', 'updated_date'];
                                                    $values = ['completed', $response_pending_SMSTransaction['response'][0]['number'], $row['trx_id'], getCurrentDatetime('Y-m-d H:i:s')];
                                                    $condition = 'id ="'.$row['id'].'"'; 

                                                    updateData($db_prefix.'transaction', $columns, $values, $condition);


                                                    $metadata = json_decode($row['metadata'], true) ?: [];

                                                    $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$response_brand['response'][0]['brand_id'].'" AND gateway_id = "'.$row['gateway_id'].'"'),true);

                                                    $gateway = $response_gateway['response'][0]['display'] ?? '';

                                                    $customer_info = json_decode($row['customer_info'], true) ?: [];

                                                    $net = money_sub(money_add($row['amount'], $row['processing_fee']), $row['discount_amount']);

                                                    $all_transactions[] = [
                                                        "pp_id" => $row['ref'],
                                                        "full_name" => $customer_info['name'] ?? 'N/A',
                                                        "email_address" => $customer_info['email'] ?? 'N/A',
                                                        "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                                                        "gateway" => $gateway,
                                                        "amount" => money_round($row['amount']),
                                                        "fee" => money_round($row['processing_fee']),
                                                        "discount_amount" => money_round($row['discount_amount']),
                                                        "total" => money_round($net),
                                                        "local_net_amount" => money_round($row['local_net_amount']),
                                                        "currency" => $row['currency'],
                                                        "local_currency" => $row['local_currency'],
                                                        "metadata" => $metadata, // ← AS-IS
                                                        "sender" => $response_pending_SMSTransaction['response'][0]['number'],
                                                        "transaction_id" => $row['trx_id'],
                                                        "status" => $row['status'],
                                                        "date" => convertUTCtoUserTZ($row['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                                    ];

                                                    if($row['webhook_url'] == "" || $row['webhook_url'] == "--"){

                                                    }else{
                                                        $ipnData = [
                                                            "pp_id" => $row['ref'],
                                                            "full_name" => $customer_info['name'] ?? 'N/A',
                                                            "email_address" => $customer_info['email'] ?? 'N/A',
                                                            "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                                                            "gateway" => $gateway,
                                                            "amount" => money_round($row['amount']),
                                                            "fee" => money_round($row['processing_fee']),
                                                            "discount_amount" => money_round($row['discount_amount']),
                                                            "total" => money_round($net),
                                                            "local_net_amount" => money_round($row['local_net_amount']),
                                                            "currency" => $row['currency'],
                                                            "local_currency" => $row['local_currency'],
                                                            "metadata" => $metadata, // ← AS-IS
                                                            "sender" => $response_pending_SMSTransaction['response'][0]['number'],
                                                            "transaction_id" => $row['trx_id'],
                                                            "status" => $row['status'],
                                                            "date" => convertUTCtoUserTZ($row['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                                        ];

                                                        $payload = json_encode($ipnData, JSON_UNESCAPED_UNICODE);

                                                        $columns = ['ref', 'brand_id', 'payload', 'url', 'created_date', 'updated_date'];
                                                        $values = [$row['ref'], $row['brand_id'], $payload, $row['webhook_url'], getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                                        insertData($db_prefix.'webhook_log', $columns, $values);
                                                    }
                                            }
                                        }
                                    }
                                }

                                if (!empty($all_transactions)) {
                                    do_action('transactions.updated', $all_transactions);
                                }
                                //verify pending against sms data
                                //verify pending against sms data


                                //auto update currency
                                //auto update currency
                                $response_currency_auto_update = json_decode(getData($db_prefix.'brands','WHERE autoExchange = "enabled"'), true);

                                $multiHandle = curl_multi_init();
                                $curlHandles = [];
                                $brandMap = []; 

                                foreach ($response_currency_auto_update['response'] as $row) {
                                    if (strtotime(getCurrentDatetime('Y-m-d H:i:s')) - strtotime(get_env('last-auto-exchange', $row['brand_id']) ?: getCurrentDatetime('Y-m-d H:i:s')) >= 5*3600) {
                                        set_env('last-auto-exchange', getCurrentDatetime('Y-m-d H:i:s'), $row['brand_id']);

                                        $url = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/' . strtolower($row['currency_code']) . '.json';

                                        $ch = curl_init($url);
                                        curl_setopt_array($ch, [
                                            CURLOPT_RETURNTRANSFER => true,
                                            CURLOPT_TIMEOUT => 10,
                                            CURLOPT_SSL_VERIFYPEER => false
                                        ]);

                                        curl_multi_add_handle($multiHandle, $ch);
                                        $curlHandles[] = $ch;
                                        $brandMap[(int)$ch] = $row; 
                                    }else{
                                        if(get_env('last-auto-exchange', $row['brand_id']) == "--" || get_env('last-auto-exchange', $row['brand_id']) == ""){
                                            set_env('last-auto-exchange', getCurrentDatetime('Y-m-d H:i:s'), $row['brand_id']);
                                        }
                                    }
                                }

                                $running = null;
                                do {
                                    curl_multi_exec($multiHandle, $running);
                                    curl_multi_select($multiHandle);
                                } while ($running > 0);

                                foreach ($curlHandles as $ch) {
                                    $row = $brandMap[(int)$ch]; 
                                    $response = curl_multi_getcontent($ch);
                                    curl_multi_remove_handle($multiHandle, $ch);
                                    curl_close($ch);

                                    if (!$response) continue;

                                    $data = json_decode($response, true);
                                    if (!isset($data[strtolower($row['currency_code'])])) continue;

                                    $rates = $data[strtolower($row['currency_code'])];

                                    foreach ($rates as $currency => $rate) {
                                        if ($currency === strtolower($row['currency_code'])) continue;
                                        if ($rate <= 0) continue;

                                        $converted = number_format(1 / $rate, 4);
                                        $columns = ['rate', 'updated_date'];
                                        $values = [$converted, getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = 'brand_id ="'.$row['brand_id'].'" AND code = "'.$currency.'"'; 
                                        updateData($db_prefix.'currency', $columns, $values, $condition);
                                    }
                                }

                                curl_multi_close($multiHandle);
                                //auto update currency
                                //auto update currency


                                //balance verification
                                //balance verification
                                $response_balance_verification = json_decode(getData($db_prefix.'balance_verification','WHERE status = "active"'),true);
                                foreach($response_balance_verification['response'] as $row){
                                    reconcileByLongestChain($row['device_id'], $row['sender_key'], $row['type']);
                                }
                                //balance verification
                                //balance verification


                                //webhook pending
                                //webhook pending
                                $limit = get_env('geneal-application-settings-webhook_attempts_limit');
                                $limit = ($limit === '' || $limit === '--') ? 1 : (int)$limit;

                                $response = json_decode(getData($db_prefix.'webhook_log','WHERE status="pending" AND attempts < '.$limit.' ORDER BY id ASC LIMIT 15'),true);

                                $jobs = [];

                                foreach ($response['response'] as $row) {
                                    updateData($db_prefix.'webhook_log',['attempts', 'updated_date'],[$row['attempts'] + 1, getCurrentDatetime('Y-m-d H:i:s')],"id = '".$row['id']."'");

                                    $jobs[] = [
                                        'id'      => $row['id'],
                                        'url'     => $row['url'],
                                        'payload' => json_decode($row['payload'], true),
                                        'attempts'=> $row['attempts'] + 1
                                    ];
                                }

                                $results = sendIPNMulti($jobs);

                                foreach ($jobs as $job) {
                                    $code = $results[$job['id']] ?? 0;
                                    $status = ($code === 200) ? 'completed' : 'pending';

                                    if ($job['attempts'] >= $limit && $code !== 200) {
                                        $status = 'canceled';
                                    }

                                    updateData($db_prefix.'webhook_log',['status', 'http_code', 'updated_date'],[$status, $code, getCurrentDatetime('Y-m-d H:i:s')],"id = '".$job['id']."'");
                                }
                                //webhook pending
                                //webhook pending
                                unlink($lockFile);
                            }else{
                                if(file_exists(__DIR__ . '/pp-404.php')){
                                    http_response_code(404);
                                    require __DIR__ . '/pp-404.php';
                                }else{
                                    http_response_code(403);
                                    exit('Direct access not allowed');
                                }
                            }
                        }
                        break;

                    case 'homepageRedirect':
                        if($path_homepageRedirect == ""){
                            echo '<script>location.href="login";</script>';
                        }else{
                            echo '<script>location.href="https://'.$path_homepageRedirect.'";</script>';
                        }
                        break;
                    default:
                        if(file_exists(__DIR__ . '/pp-404.php')){
                            http_response_code(404);
                            require __DIR__ . '/pp-404.php';
                        }else{
                            http_response_code(403);
                            exit('Direct access not allowed');
                        }
                        break;
                }
            }else{
                if(file_exists(__DIR__ . '/pp-requirement.php')){
                    require __DIR__ . '/pp-requirement.php';
                }else{
                    if(file_exists(__DIR__ . '/pp-404.php')){
                        http_response_code(404);
                        require __DIR__ . '/pp-404.php';
                    }else{
                        http_response_code(403);
                        exit('Direct access not allowed');
                    }
                }
            }
        }else{
            if(file_exists(__DIR__ . '/pp-content/pp-install/index.php')){
                require __DIR__ . '/pp-content/pp-install/index.php';
            }else{
                if(file_exists(__DIR__ . '/pp-404.php')){
                    http_response_code(404);
                    require __DIR__ . '/pp-404.php';
                }else{
                    http_response_code(403);
                    exit('Direct access not allowed');
                }
            }
        }
    }else{
        if(file_exists(__DIR__ . '/pp-maintenance.php')){
            require __DIR__ . '/pp-maintenance.php';
        }else{
            if(file_exists(__DIR__ . '/pp-404.php')){
                http_response_code(404);
                require __DIR__ . '/pp-404.php';
            }else{
                http_response_code(403);
                exit('Direct access not allowed');
            }
        }
    }
