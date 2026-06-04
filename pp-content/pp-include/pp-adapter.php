<?php
    declare(strict_types=1);

    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    session_start();

    if (date_default_timezone_get() !== 'UTC') {
        date_default_timezone_set('UTC');
    }

    $phpVersion = PHP_VERSION;

    $requirements = [
        [
            'name'     => 'PHP Version',
            'required' => '8.1.x - 8.3.x',
            'current'  => PHP_VERSION,
            'check'    => version_compare(PHP_VERSION, '8.1.0', '>=') && version_compare(PHP_VERSION, '8.4.0', '<')
        ],
        [
            'name'     => 'cURL',
            'required' => 'Enabled',
            'current'  => function_exists('curl_init') ? 'Enabled' : 'Disabled',
            'check'    => function_exists('curl_init')
        ],
        [
            'name'     => 'cURL Multi',
            'required' => 'Enabled',
            'current'  => function_exists('curl_multi_init') ? 'Enabled' : 'Disabled',
            'check'    => function_exists('curl_multi_init')
        ],
        [
            'name'     => 'PDO',
            'required' => 'Enabled',
            'current'  => extension_loaded('pdo') && class_exists('PDO') ? 'Enabled' : 'Disabled',
            'check'    => extension_loaded('pdo') && class_exists('PDO')
        ],
        [
            'name'     => 'GD Library',
            'required' => 'Enabled',
            'current'  => extension_loaded('gd') && function_exists('gd_info') ? 'Enabled' : 'Disabled',
            'check'    => extension_loaded('gd') && function_exists('gd_info')
        ],
        [
            'name'     => 'Fileinfo',
            'required' => 'Enabled',
            'current'  => function_exists('finfo_open') ? 'Enabled' : 'Disabled',
            'check'    => function_exists('finfo_open')
        ],
        [
            'name'     => 'Imagick',
            'required' => 'Enabled',
            'current'  => extension_loaded('imagick') ? 'Enabled' : 'Disabled',
            'check'    => extension_loaded('imagick')
        ],
        [
            'name'     => 'OpenSSL',
            'required' => 'Enabled',
            'current'  => extension_loaded('openssl') ? 'Enabled' : 'Disabled',
            'check'    => extension_loaded('openssl')
        ],
        [
            'name'     => 'ZipArchive',
            'required' => 'Enabled',
            'current'  => (extension_loaded('zip') && class_exists('ZipArchive')) ? 'Enabled' : 'Disabled',
            'check'    => (extension_loaded('zip') && class_exists('ZipArchive'))
        ],
        [
            'name'     => 'Mbstring',
            'required' => 'Enabled',
            'current'  => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
            'check'    => extension_loaded('mbstring')
        ],
        [
            'name'     => 'Tokenizer',
            'required' => 'Enabled',
            'current'  => extension_loaded('tokenizer') ? 'Enabled' : 'Disabled',
            'check'    => extension_loaded('tokenizer')
        ],
        [
            'name'     => 'JSON',
            'required' => 'Enabled',
            'current'  => extension_loaded('json') ? 'Enabled' : 'Disabled',
            'check'    => extension_loaded('json')
        ],
        [
            'name'     => 'allow_url_fopen',
            'required' => 'Enabled',
            'current'  => ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled',
            'check'    => ini_get('allow_url_fopen')
        ],
        [
            'name'     => 'file_uploads',
            'required' => 'Enabled',
            'current'  => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
            'check'    => ini_get('file_uploads')
        ],
        [
            'name'     => 'bcmath',
            'required' => 'Enabled',
            'current'  => extension_loaded('bcmath') ? 'Enabled' : 'Disabled',
            'check'    => extension_loaded('bcmath')
        ]
    ];

    $requriemntnoneedchecked = true;

    foreach ($requirements as $req) {
        if (!$req['check']) {
            $requriemntnoneedchecked = false;
        }
    }

    $path_payment = 'payment';
    $path_invoice = 'invoice';
    $path_payment_link = 'payment-link';
    $path_admin = 'admin';
    $path_cron = 'cron';
    $path_homepageRedirect = '';

    if(file_exists(__DIR__ . '/pp-functions.php')){
        if (isset($pp_functions_loaded)) {

        }else{
            require __DIR__ . '/pp-functions.php';

            if (isset($pp_functions_loaded)) {

            }else{
                if(file_exists(__DIR__ . '/../../pp-404.php')){
                    http_response_code(404);
                    require __DIR__ . '/../../pp-404.php';
                    exit();
                }else{
                    http_response_code(403);
                    exit('Direct access not allowed');
                }
            }
        }
    }else{
        if(file_exists(__DIR__ . '/../../pp-404.php')){
            http_response_code(404);
            require __DIR__ . '/../../pp-404.php';
            exit();
        }else{
            http_response_code(403);
            exit('Direct access not allowed');
        }
    }

    if(file_exists(__DIR__ . '/../../pp-config.php')){
        require __DIR__ . '/../../pp-config.php';

        if($requriemntnoneedchecked == true){
            $path_payment = ($value = get_env('geneal-application-settings-paymentPath')) && $value !== '--' ? $value : 'payment';
            $path_invoice = ($value = get_env('geneal-application-settings-invoicePath')) && $value !== '--' ? $value : 'invoice';
            $path_payment_link = ($value = get_env('geneal-application-settings-paymentLinkPath')) && $value !== '--' ? $value : 'payment-link';
            $path_admin = ($value = get_env('geneal-application-settings-adminPath')) && $value !== '--' ? $value : 'admin';
            $path_cron = ($value = get_env('geneal-application-settings-cronPath')) && $value !== '--' ? $value : 'cron';
            $path_homepageRedirect = ($value = get_env('geneal-application-settings-homepageRedirect')) && $value !== '--' ? $value : '';

            $response_addonLoader = json_decode(getData($db_prefix.'addon',' WHERE status = "active" ORDER BY 1 DESC '),true);
            foreach ($response_addonLoader['response'] as $row) {
                $addonPath = __DIR__ . '/../pp-modules/pp-addons/' . $row['slug'] . '/';

                if (!is_dir($addonPath)) {
                    continue;
                }

                if(file_exists($addonPath . 'class.php')){
                    require_once $addonPath . 'class.php';

                    $slug = str_replace(['-', '_'], ' ', strtolower($row['slug']));
                    $slug = str_replace(' ', '', ucwords($slug));

                    $className = $slug . 'Addon';

                    if (class_exists($className)) {
                        $options = [];

                        $response_addonOptionLoader = json_decode(getData($db_prefix.'addon_parameter',' WHERE addon_id = "'.$row['addon_id'].'"'),true);
                        foreach ($response_addonOptionLoader['response'] as $rowOption) {
                            $value = $rowOption['value'];
                            if (in_array($value[0] ?? '', ['[','{'])) {
                                $decoded = json_decode($value, true);
                                if ($decoded !== null) $value = $decoded;
                            }

                            $options[$rowOption['option_name']] = $value;
                        }

                        new $className($options);
                    }
                }
            }
        }
    }else{
        if(file_exists(__DIR__ . '/../../pp-temp-config.php')){
            require __DIR__ . '/../../pp-temp-config.php';
        }
    }

    if(file_exists(__DIR__ . '/../../pp-media/sdk/GoogleAuthenticator.php')){
        require __DIR__ . '/../../pp-media/sdk/GoogleAuthenticator.php';
    }else{
        http_response_code(403);
        exit('SDK Missing');
    }

    if(file_exists(__DIR__ . '/../../pp-media/sdk/fpdf/fpdf.php')){
        require __DIR__ . '/../../pp-media/sdk/fpdf/fpdf.php';
    }else{
        http_response_code(403);
        exit('SDK Missing');
    }

    $pp_adapter_loaded = true;

    $piprapay_current_version = [
        'version_name' => 'v3.0.0-beta',
        'version_code' => '3.0.0',
        'version_hash' => '6b6f7c62e34e3680398387720dbd44a036d1a574860d5f90a3bd5d9b6280bea1
c9515853f1fbf61175dd3dbce6eb011e4cf29fc43949ed4b562f6421b88c8773
c0dc07a71b29a9da279310f2247affb16089334cc3da60fa0b4b4f06f78594cb
29668acba982d4706c0b8827b5cb9c85ecd24ba899f62116a5dcb7dea121d451
83bfac44e905e37a7ff65776b378988011d407fe308d57af204d1d88093ba733
3ef016b79259331703a1a3db6d1b886e38226d9d619673c81ab13d6ee53bdd99
46e4e9bd74065d7e87ad545cba46957bc5d695290c6b2a4786710de8785bbb48
46cc094590e12b359b3f8c429b75c7771164d64b4ee77c3783b304a6757f1dcb
aa021689e729dc2302b47e9bdc7d1a9f8b72f95f01530da35bf3b848b188d5b1
09a03d6d70021d1c0dd64cefd6e400b18d0e43d00d821b8f52e2e9370908779e',
        'version_channel' => 'beta'
    ];

    $piprapay_favicon= 'https://piprapay.com/assets/images/favicon.png';
    $piprapay_logo_light = 'https://cdn.piprapay.com/media/logo.png';

    $directory = (pp_site_url('fulldomain') == 'http://localhost') ? 'piprapay-panel/' : '';
    $site_url = pp_site_url('fulldomain').'/'.$directory;

    if(isset($_GET['logout'])){
        logoutCookie();
?>
        <script>
           location.href = '<?php echo $site_url.'login'?>';
        </script>
<?php
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrf_token = $_SESSION['csrf_token'];

    $global_user_login = false;
    $global_user_2fa = false;
    $global_two_fector_validate = false;

    if(file_exists(__DIR__ . '/../../pp-config.php')){
        if(getCookie('pp_admin') !== null){
            $pp_admin = escape_string(getCookie('pp_admin'));

            $params = [ ':cookie' => $pp_admin, ':status' => 'active' ];

            $global_cookie_response = json_decode(getData($db_prefix.'browser_log', 'WHERE cookie= :cookie AND status= :status', '* FROM', $params), true);
            if($global_cookie_response['status'] == true){
                $params = [ ':a_id' => $global_cookie_response['response'][0]['a_id'] ];

                $global_user_response = json_decode(getData($db_prefix.'admin', 'WHERE a_id= :a_id', '* FROM', $params), true);
                if($global_user_response['status'] == true){
                    if($global_user_response['response'][0]['status'] == "active"){
                        if(getCookie('pp_brand') !== null){
                            $pp_brand = escape_string(getCookie('pp_brand'));

                            $params = [ ':a_id' => $global_user_response['response'][0]['a_id'], ':status' => 'active', ':brand_id' => $pp_brand ];

                            $global_response_permission = json_decode(getData($db_prefix.'permission','WHERE a_id = :a_id AND status = :status AND brand_id = :brand_id', '* FROM', $params),true);
                            if($global_response_permission['status'] == true){
                                $params = [ ':brand_id' => $global_response_permission['response'][0]['brand_id'] ];
                                
                                $global_response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                                if($global_response_brand['status'] == true){
                                    $global_user_login = true;

                                    $global_permissions = json_decode($global_response_permission['response'][0]['permission'], true);
                                }else{
                                    $global_user_login = false;
                                }
                            }else{
                                $params = [ ':a_id' => $global_user_response['response'][0]['a_id'], ':status' => 'active' ];

                                $global_response_permission = json_decode(getData($db_prefix.'permission','WHERE a_id = :a_id AND status = :status LIMIT 1', '* FROM', $params),true);
                                if($global_response_permission['status'] == true){
                                    $params = [ ':brand_id' => $global_response_permission['response'][0]['brand_id'] ];

                                    $global_response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                                    if($global_response_brand['status'] == true){
                                        setsCookie('pp_brand', $global_response_permission['response'][0]['brand_id']);
                                        $global_user_login = true;

                                        $global_permissions = json_decode($global_response_permission['response'][0]['permission'], true);
                                    }else{
                                        $global_user_login = false;
                                    }
                                }else{
                                    $global_user_login = false;
                                }
                            }

                        }else{
                            $global_user_login = false;
                        }
                    }else{
                        $global_user_login = true;
                    }
                }else{
                    $global_user_login = false;
                }
            }else{
                $global_user_login = false;
            }
        }else{
            if(getCookie('pp_2fa') !== null){
                $params = [ ':cookie' => getCookie('pp_2fa'), ':status' => 'active' ];

                $global_cookie_response = json_decode(getData($db_prefix.'browser_log', 'WHERE cookie= :cookie AND status= :status', '* FROM', $params), true);
                if($global_cookie_response['status'] == true){
                    $params = [ ':a_id' => $global_cookie_response['response'][0]['a_id'], ':2fa_status' => 'enable' ];

                    $global_user_response = json_decode(getData($db_prefix.'admin', 'WHERE a_id= :a_id AND 2fa_status= :2fa_status', '* FROM', $params), true);
                    if($global_user_response['status'] == true){
                        if($global_user_response['response'][0]['status'] == "active"){
                            if(getCookie('pp_brand') !== null){
                                $pp_brand = escape_string(getCookie('pp_brand'));

                                $params = [ ':a_id' => $global_user_response['response'][0]['a_id'], ':status' => 'active', ':brand_id' => $pp_brand ];

                                $global_response_permission = json_decode(getData($db_prefix.'permission','WHERE a_id = :a_id AND status = :status AND brand_id = :brand_id', '* FROM', $params),true);
                                if($global_response_permission['status'] == true){
                                    $params = [ ':brand_id' => $global_response_permission['response'][0]['brand_id'] ];

                                    $global_response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                                    if($global_response_brand['status'] == true){
                                        $global_user_2fa = true;
                                    }else{
                                        $global_user_2fa = false;
                                    }
                                }else{
                                    $params = [ ':a_id' => $global_user_response['response'][0]['a_id'], ':status' => 'active' ];

                                    $global_response_permission = json_decode(getData($db_prefix.'permission','WHERE a_id = :a_id AND status = :status LIMIT 1', '* FROM', $params),true);
                                    if($global_response_permission['status'] == true){
                                        $params = [ ':brand_id' => $global_response_permission['response'][0]['brand_id'] ];

                                        $global_response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                                        if($global_response_brand['status'] == true){
                                            setsCookie('pp_brand', $global_response_permission['response'][0]['brand_id']);
                                            $global_user_2fa = true;
                                        }else{
                                            $global_user_2fa = false;
                                        }
                                    }else{
                                        $global_user_2fa = false;
                                    }
                                }

                            }else{
                                $global_user_2fa = false;
                            }
                        }else{
                            $global_user_2fa = true;
                        }
                    }else{
                        $global_user_2fa = false;
                    }
                }else{
                    $global_user_2fa = false;
                }
            }else{
                $global_user_login = false;
            }
        }
    }

    if($global_user_login == true){
        $global_brand_currency_code = $global_response_brand['response'][0]['currency_code'];
        $global_brand_currency_symbol = $global_response_brand['response'][0]['currency_code'];
        $global_brand_currency_rate = 1;
        $params = [ ':brand_id' => $global_response_brand['response'][0]['brand_id'], ':code' => $global_brand_currency_code ];

        $global_response_currency_symbol = json_decode(getData($db_prefix.'currency','WHERE brand_id = :brand_id AND code = :code', '* FROM', $params),true);
        if($global_response_currency_symbol['status'] == true){
            $global_brand_currency_symbol = $global_response_currency_symbol['response'][0]['symbol'];
        }
    }

    if(isset($_POST['action'])){
        $action = escape_string($_POST['action'] ?? '');
        $pp_app_token = escape_string($_POST['pp-token'] ?? '');

        if($action == ""){
            echo json_encode(['status' => "false", 'title' => 'Oops! Something went wrong', 'message' => 'Your request could not be processed. Please try again.']);
        }else{
            if($pp_app_token == ''){
                if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $new_csrf_token = $_SESSION['csrf_token'];

                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request token' , 'csrf_token' => $new_csrf_token]);
                    exit;
                }
            }else{
                $new_csrf_token = '';

                $pp_app_id = escape_string($_POST['pp-app-id'] ?? '');
                $pp_app_timestamp = escape_string($_POST['pp-app-timestamp'] ?? '');

                $data = $pp_app_id . '|' . $pp_app_timestamp;
                $expectedSignature = hash_hmac('sha256', $data, '698b7520-c604-8323-a04d-dc519bb3e1d3');

                if (!hash_equals($expectedSignature, $pp_app_token)) {
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request token' , 'csrf_token' => $new_csrf_token]);
                    exit;
                }
            }

            //$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $new_csrf_token = $_SESSION['csrf_token'];
            
            if(isset($_POST['my-two-step-verify-code'])){
                $auth_code = escape_string($_POST['my-two-step-verify-code'] ?? '');

                if($global_user_response['response'][0]['2fa_status'] == "enable"){
                    $ga = new PHPGangsta_GoogleAuthenticator();

                    $check = $ga->verifyCode($global_user_response['response'][0]['2fa_secret'], $auth_code, 2);

                    if ($check) {
                        $global_two_fector_validate = true;
                    } else {
                        echo json_encode(['status' => "false", 'title' => 'Verification Failed', 'message' => 'The code you entered is incorrect. Please try again.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    if (password_verify($auth_code, $global_user_response['response'][0]['password'])) {
                        $global_two_fector_validate = true;
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Verification Failed', 'message' => 'The password you entered is incorrect. Please try again.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }
            }
            
            if($action == "login"){
                $email_username = escape_string($_POST['username'] ?? '');
                $password = escape_string($_POST['password'] ?? '');
        
                if($email_username == "" || $password == ""){
                    echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                }else{
                    if (filter_var($email_username, FILTER_VALIDATE_EMAIL)) {
                        $params = [ ':email' => $email_username ];

                        $sql_email_username = 'email = :email';
                    }else{
                        $params = [ ':username' => $email_username ];

                        $sql_email_username = 'username = :username';
                    }
                    
                    $response = json_decode(getData($db_prefix.'admin','WHERE '.$sql_email_username, '* FROM', $params),true);
        
                    if($response['status'] == true){
                        if (password_verify($password, $response['response'][0]['password'])) {
                            if ($response['response'][0]['status'] == "active") {
                                $cookie = bin2hex(random_bytes(16)); 
                                $userInfo = getUserDeviceInfo();

                                $params = [ ':a_id' => $response['response'][0]['a_id'], ':status' => 'active' ];

                                $response_brand = json_decode(getData($db_prefix.'permission','WHERE a_id = :a_id AND status = :status LIMIT 1', '* FROM', $params),true);
                                if($response_brand['status'] == true){
                                    setsCookie('pp_brand', $response_brand['response'][0]['brand_id']);
                                }else{
                                    echo json_encode(['status' => "false", 'title' => 'Login Failed', 'message' => 'You don’t have permission to manage brands. Contact your admin.', 'csrf_token' => $new_csrf_token]);
                                    exit();
                                }

                                if($response['response'][0]['2fa_status'] == "enable"){
                                    setsCookie('pp_2fa', $cookie);

                                    $target = "2fa";
                                }else{
                                    setsCookie('pp_admin', $cookie);

                                    $target = $path_admin."/dashboard";
                                }

                                if($response['response'][0]['2fa_secret'] == '--' || $response['response'][0]['2fa_secret'] == ''){
                                    $ga = new PHPGangsta_GoogleAuthenticator();
                                    $secret = $ga->createSecret();

                                    $columns = ['2fa_secret'];
                                    $values = [$secret];
                                    $condition = "id = '".$response['response'][0]['id']."'"; 
                                    
                                    updateData($db_prefix.'admin', $columns, $values, $condition);
                                }
                                
                                $columns = ['a_id', 'cookie', 'browser', 'device', 'ip', 'created_date', 'updated_date'];
                                $values = [$response['response'][0]['a_id'], $cookie, $userInfo['browser'], $userInfo['device'], $userInfo['ip_address'], getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];
                
                                insertData($db_prefix.'browser_log', $columns, $values);
                                
                                echo json_encode(['status' => "true", 'target' => $target, 'session_token' => $cookie, 'csrf_token' => $new_csrf_token]);
                            }else{
                                echo json_encode(['status' => "false", 'title' => 'Login Failed', 'message' => 'Your account has been suspended. Please contact with your admin.', 'csrf_token' => $new_csrf_token]);
                            }
                        }else{
                            if (password_verify($password, $response['response'][0]['temp_password'])) {
                                if ($response['response'][0]['status'] == "active") {
                                    $cookie = bin2hex(random_bytes(16)); 
                                    $userInfo = getUserDeviceInfo();

                                    $params = [ ':a_id' => $response['response'][0]['a_id'], ':status' => 'active' ];

                                    $response_brand = json_decode(getData($db_prefix.'permission','WHERE a_id = :a_id AND status = :status LIMIT 1', '* FROM', $params),true);
                                    if($response_brand['status'] == true){
                                        setsCookie('pp_brand', $response_brand['response'][0]['brand_id']);
                                    }else{
                                        echo json_encode(['status' => "false", 'title' => 'Login Failed', 'message' => 'You don’t have permission to manage brands. Contact your admin.', 'csrf_token' => $new_csrf_token]);
                                        exit();
                                    }

                                    if($response['response'][0]['2fa_status'] == "enable"){
                                        setsCookie('pp_2fa', $cookie);

                                        $target = "2fa";
                                    }else{
                                        setsCookie('pp_admin', $cookie);

                                        $target = $path_admin."/dashboard";
                                    }

                                    if($response['response'][0]['2fa_secret'] == '--' || $response['response'][0]['2fa_secret'] == ''){
                                        $ga = new PHPGangsta_GoogleAuthenticator();
                                        $secret = $ga->createSecret();

                                        $columns = ['2fa_secret'];
                                        $values = [$secret];
                                        $condition = "id = '".$response['response'][0]['id']."'"; 
                                        
                                        updateData($db_prefix.'admin', $columns, $values, $condition);
                                    }
                                    
                                    $columns = ['a_id', 'cookie', 'browser', 'device', 'ip', 'created_date', 'updated_date'];
                                    $values = [$response['response'][0]['a_id'], $cookie, $userInfo['browser'], $userInfo['device'], $userInfo['ip_address'], getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];
                    
                                    insertData($db_prefix.'browser_log', $columns, $values);
                                    
                                    echo json_encode(['status' => "true", 'target' => $target, 'session_token' => $cookie, 'csrf_token' => $new_csrf_token]);
                                }else{
                                    echo json_encode(['status' => "false", 'title' => 'Login Failed', 'message' => 'Your account has been suspended. Please contact with your admin.', 'csrf_token' => $new_csrf_token]);
                                }
                            }else{
                                echo json_encode(['status' => "false", 'title' => 'Login Failed', 'message' => 'The email or password you entered is incorrect.', 'csrf_token' => $new_csrf_token]);
                            }
                        }
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Login Failed', 'message' => 'The email or password you entered is incorrect.', 'csrf_token' => $new_csrf_token]);
                    }
                }
            }


            if($action == "2fa-verify"){
                $code_one = escape_string($_POST['code_one'] ?? '');
                $code_two = escape_string($_POST['code_two'] ?? '');
                $code_three = escape_string($_POST['code_three'] ?? '');
                $code_four = escape_string($_POST['code_four'] ?? '');
                $code_five = escape_string($_POST['code_five'] ?? '');
                $code_six = escape_string($_POST['code_six'] ?? '');

                if($code_one == "" || $code_two == "" || $code_three == "" || $code_four == "" || $code_five == "" || $code_six == ""){
                    echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                }else{
                    if($global_user_2fa == true){
                        $params = [ ':a_id' => $global_user_response['response'][0]['a_id'] ];

                        $response = json_decode(getData($db_prefix.'admin','WHERE a_id = :a_id', '* FROM', $params),true);
            
                        if($response['status'] == true){
                            $ga = new PHPGangsta_GoogleAuthenticator();

                            $check = $ga->verifyCode($response['response'][0]['2fa_secret'], $code_one.$code_two.$code_three.$code_four.$code_five.$code_six, 2);

                            if ($check) {
                                logoutCookie();

                                setsCookie('pp_brand', $global_response_brand['response'][0]['brand_id']);
                                setsCookie('pp_admin', $global_cookie_response['response'][0]['cookie']);

                                echo json_encode(['status' => "true", 'target' => $path_admin.'/dashboard', 'session_token' => $global_cookie_response['response'][0]['cookie'], 'csrf_token' => $new_csrf_token]);
                            } else {
                                echo json_encode(['status' => "false", 'title' => 'Verification Failed', 'message' => 'The code you entered is incorrect. Please try again.', 'csrf_token' => $new_csrf_token]);
                            }
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Login Failed', 'message' => 'You do not have access to this account. Please check your credentials.', 'csrf_token' => $new_csrf_token]);
                        }
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Oops! Something went wrong', 'message' => 'Your request could not be processed. Please try again.', 'csrf_token' => $new_csrf_token]);
                    }
                }
            }


            if($action == "forgot-password"){
                $email_address = escape_string($_POST['email-address'] ?? '');

                if($email_address == ""){
                    echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                }else{
                    if (filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
                        $params = [ ':email' => $email_address, ':status' => 'active' ];

                        $response = json_decode(getData($db_prefix.'admin','WHERE email = :email AND status = :status', '* FROM', $params),true);
                
                        if($response['status'] == true){
                            
                            if($response['response'][0]['reset_limit'] > 0){

                                $new_temp_password = generateStrongPassword(8);
                                $reset_limit = $response['response'][0]['reset_limit']-1;
                                $temp_password = password_hash($new_temp_password, PASSWORD_BCRYPT);

                                $columns = ['temp_password', 'reset_limit'];
                                $values = [$temp_password, $reset_limit];
                                $condition = "id = '".$response['response'][0]['id']."'"; 
                                
                                updateData($db_prefix.'admin', $columns, $values, $condition);
                                
                                $action_data = [
                                    'full_name'    => $response['response'][0]['full_name'],
                                    'new_password' => $new_temp_password,
                                    'email'        => $response['response'][0]['email'],
                                ];

                                do_action('forgot.password', $action_data);
                        
                                echo json_encode(['status' => "true", 'title' => 'We have emailed your new password.', 'message' => "If your account doesn't exist, you will not receive the email.", 'csrf_token' => $new_csrf_token]);

                            }else{
                                echo json_encode(['status' => "false", 'title' => 'Forgot Failed', 'message' => 'You have reached the maximum number of reset attempts.', 'csrf_token' => $new_csrf_token]);
                            }

                        }else{
                            echo json_encode(['status' => "true", 'title' => 'We have emailed your new password.', 'message' => "If your account doesn't exist, you will not receive the email.", 'csrf_token' => $new_csrf_token]);
                        }
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Invalid Email', 'message' => 'Please enter a valid email address.', 'csrf_token' => $new_csrf_token]);
                    }
                }
            }


            if($action == "set-default-brand"){
                if($global_user_login == true){
                    $brand_id = escape_string($_POST['brand_id'] ?? '');

                    if($brand_id == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $params = [ ':a_id' => $global_user_response['response'][0]['a_id'], ':status' => 'active', ':brand_id' => $brand_id ];

                        $response = json_decode(getData($db_prefix.'permission','WHERE a_id = :a_id AND status = :status AND brand_id = :brand_id', '* FROM', $params),true);
                        if($response['status'] == true){
                            setsCookie('pp_brand', $brand_id);

                            echo json_encode(['status' => "true", 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Brand Access Failed', 'message' => 'You don’t have permission to manage brands. Contact your admin.', 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "my-account-profile-information"){
                if($global_user_login == true){
                    if (!empty($pp_demo_mode)) {
                        echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $fullname = escape_string($_POST['fullname'] ?? '');
                        $username = escape_string($_POST['username'] ?? '');
                        $email_address = escape_string($_POST['email-address'] ?? '');
                        $password = escape_string($_POST['password'] ?? '');

                        if($fullname == "" || $username == "" || $email_address == ""){
                            echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            if (filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
                                if($global_two_fector_validate == false){
                                    echo json_encode(['status' => "false", 'title' => 'Verification Failed', 'message' => 'The code/password you entered is incorrect. Please try again.', 'csrf_token' => $new_csrf_token]);
                                    exit();
                                }

                                if($fullname == ""){
                                    $fullname = $global_user_response['response'][0]['full_name'];
                                }

                                if($username !== $global_user_response['response'][0]['username']){
                                    $params = [ ':username' => $username ];

                                    $response = json_decode(getData($db_prefix.'admin','WHERE username = :username', '* FROM', $params),true);
                                    if($response['status'] == true){
                                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Username already exits.', 'csrf_token' => $new_csrf_token]);
                                        exit();
                                    }
                                }

                                if($email_address !== $global_user_response['response'][0]['email']){
                                    $params = [ ':email' => $email_address ];

                                    $response = json_decode(getData($db_prefix.'admin','WHERE email = :email', '* FROM', $params),true);
                                    if($response['status'] == true){
                                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Email Address already exits.', 'csrf_token' => $new_csrf_token]);
                                        exit();
                                    }
                                }

                                if($password == ""){
                                    $password = $global_user_response['response'][0]['password'];
                                    $temp_password = $global_user_response['response'][0]['temp_password'];
                                }else{
                                    $new_temp_password = generateStrongPassword(8);
                                    $password = password_hash($password, PASSWORD_BCRYPT);
                                    $temp_password = password_hash($new_temp_password, PASSWORD_BCRYPT);
                                }

                                $columns = ['full_name', 'username', 'email', 'password', 'temp_password', 'updated_date'];
                                $values = [$fullname, $username, $email_address, $password, $temp_password, getCurrentDatetime('Y-m-d H:i:s')];
                                $condition = "id = '".$global_user_response['response'][0]['id']."'"; 
                                
                                updateData($db_prefix.'admin', $columns, $values, $condition);

                                echo json_encode(['status' => 'true', 'title' => 'Profile Updated', 'message' => 'Your profile information has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                            }else{
                                echo json_encode(['status' => "false", 'title' => 'Invalid Email', 'message' => 'Please enter a valid email address.', 'csrf_token' => $new_csrf_token]);
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "my-account-account-browser-sessions"){
                if($global_user_login == true){
                    if (!empty($pp_demo_mode)) {
                        echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if($global_two_fector_validate == false){
                            echo json_encode(['status' => "false", 'title' => 'Verification Failed', 'message' => 'The code/password you entered is incorrect. Please try again.', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $columns = ['status', 'updated_date'];
                        $values = ['expired', getCurrentDatetime('Y-m-d H:i:s')];
                        $condition = "a_id = '".$global_user_response['response'][0]['a_id']."' AND cookie NOT IN ('".$pp_admin."')"; 
                        
                        updateData($db_prefix.'browser_log', $columns, $values, $condition);

                        echo json_encode(['status' => 'true', 'title' => 'Logged Out Successfully', 'message' => 'You have been logged out of all other browser sessions.', 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "my-account-account-two-factor-authentication"){
                if($global_user_login == true){
                    if (!empty($pp_demo_mode)) {
                        echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $auth_code = escape_string($_POST['auth-code'] ?? '');

                        if($auth_code == ""){
                            echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            $ga = new PHPGangsta_GoogleAuthenticator();

                            $check = $ga->verifyCode($global_user_response['response'][0]['2fa_secret'], $auth_code, 2);

                            if ($check) {
                                if($global_user_response['response'][0]['2fa_status'] == "enable"){
                                    $fa_status = 'disable';
                                }else{
                                    $fa_status = 'enable';
                                }

                                $columns = ['2fa_status', 'updated_date'];
                                $values = [$fa_status, getCurrentDatetime('Y-m-d H:i:s')];
                                $condition = "a_id = '".$global_user_response['response'][0]['a_id']."'"; 
                                
                                updateData($db_prefix.'admin', $columns, $values, $condition);

                                if($fa_status == "disable"){
                                    echo json_encode(['status' => 'true', 'title' => 'Two-Factor Authentication Disabled', 'message' => 'Two-factor authentication has been successfully disabled for your account.', 'csrf_token' => $new_csrf_token]);
                                }else{
                                   echo json_encode(['status' => 'true', 'title' => 'Two-Factor Authentication Enabled', 'message' => 'Two-factor authentication has been successfully enabled for your account.', 'csrf_token' => $new_csrf_token]);
                                }
                            } else {
                                echo json_encode(['status' => "false", 'title' => 'Verification Failed', 'message' => 'The code you entered is incorrect. Please try again.', 'csrf_token' => $new_csrf_token]);
                                exit();
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "activities-list"){
                if($global_user_login == true){
                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( browser LIKE '%$search_input%' OR device LIKE '%$search_input%' OR ip LIKE '%$search_input%')";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'browser_log','WHERE '.$where_sql.' a_id = "'.$global_user_response['response'][0]['a_id'].'" '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $isequal = '';
                            if($row['cookie'] == $pp_admin){
                                $isequal = 'matched';
                            }

                            $response[] = [
                                "id"   => $row['id'],
                                "browser"   => $row['browser'],
                                "device"   => $row['device'],
                                "ip"     => $row['ip'],
                                "status"     => $row['status'],
                                "isequal"     => $isequal,
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'browser_log','WHERE '.$where_sql.' a_id = "'.$global_user_response['response'][0]['a_id'].'" '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "staff-management-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( full_name LIKE '%$search_input%' OR email LIKE '%$search_input%' OR username LIKE '%$search_input%')";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'admin','WHERE '.$where_sql.'  role = "staff" AND a_id NOT IN ("'.$global_user_response['response'][0]['a_id'].'") '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $response[] = [
                                "id"   => $row['a_id'],
                                "name"   => $row['full_name'],
                                "username"   => $row['username'],
                                "email"     => $row['email'],
                                "status"     => $row['status'],
                                "role"     => $row['role'],
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'admin','WHERE '.$where_sql.' role="staff" AND a_id NOT IN ("'.$global_user_response['response'][0]['a_id'].'") '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "staff-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_staff = json_decode(getData($db_prefix.'admin','WHERE role = "staff" AND a_id = "'.$itemID.'" '),true);
                            if($response_staff['status'] == true){
                                if($itemID == $global_user_response['response'][0]['a_id']){

                                }else{
                                    if($actionID == "deleted"){
                                        if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'delete', $global_user_response['response'][0]['role'])) {
                        
                                            $condition = "a_id = '".$response_staff['response'][0]['a_id']."'"; 
                                            
                                            deleteData($db_prefix.'permission', $condition);

                                            $condition = "a_id = '".$response_staff['response'][0]['a_id']."'"; 
                                            
                                            deleteData($db_prefix.'browser_log', $condition);

                                            $condition = "a_id = '".$response_staff['response'][0]['a_id']."'"; 
                                            
                                            deleteData($db_prefix.'admin', $condition);

                                        }
                                    }

                                    if($actionID == "activated"){
                                        if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'edit', $global_user_response['response'][0]['role'])) {
                                                
                                            $columns = ['status', 'updated_date'];
                                            $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                            $condition = "a_id = '".$itemID."'"; 
                                            
                                            updateData($db_prefix.'admin', $columns, $values, $condition);

                                        }
                                    }

                                    if($actionID == "suspended"){
                                        if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'edit', $global_user_response['response'][0]['role'])) {
                                                
                                            $columns = ['status', 'updated_date'];
                                            $values = ['suspend', getCurrentDatetime('Y-m-d H:i:s')];
                                            $condition = "a_id = '".$itemID."'"; 
                                            
                                            updateData($db_prefix.'admin', $columns, $values, $condition);

                                        }
                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Staff '.$actionID, 'message' => 'The selected staff members have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No Staff selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "staff-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_staff = json_decode(getData($db_prefix.'admin','WHERE role = "staff" AND a_id = "'.$ItemID.'" '),true);
                    if($response_staff['status'] == true){
                        if($ItemID == $global_user_response['response'][0]['a_id']){
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'You cannot delete your own account.' , 'csrf_token' => $new_csrf_token]);
                        }else{
                            $condition = "a_id = '".$response_staff['response'][0]['a_id']."'"; 
                            
                            deleteData($db_prefix.'permission', $condition);

                            $condition = "a_id = '".$response_staff['response'][0]['a_id']."'"; 
                            
                            deleteData($db_prefix.'browser_log', $condition);

                            $condition = "id = '".$response_staff['response'][0]['id']."'"; 
                            
                            deleteData($db_prefix.'admin', $condition);

                            echo json_encode(['status' => 'true', 'title' => 'Staff Deleted', 'message' => 'The staff member have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                        }
                    }else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No Staff selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "staff-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $fullname = escape_string($_POST['full-name'] ?? '');
                    $username = escape_string($_POST['username'] ?? '');
                    $email_address = escape_string($_POST['email-address'] ?? '');
                    $password = escape_string($_POST['password'] ?? '');
                    $brands = $_POST['brands'] ?? [];

                    if($fullname == "" || $username == "" || $email_address == "" || $password == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if (filter_var($email_address, FILTER_VALIDATE_EMAIL)) {

                            $count_brand = 0;

                            foreach ($brands as $count) {
                                    $count_brand = $count_brand+1;
                            }

                            if($count_brand == 0){
                                echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'You need to allow minimum 1 brand to create a staff', 'csrf_token' => $new_csrf_token]);
                                exit();
                            }

                            $response = json_decode(getData($db_prefix.'admin','WHERE username = "'.$username.'"'),true);
                            if($response['status'] == true){
                                echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Username already exits.', 'csrf_token' => $new_csrf_token]);
                                exit();
                            }

                            $response = json_decode(getData($db_prefix.'admin','WHERE email = "'.$email_address.'"'),true);
                            if($response['status'] == true){
                                echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Email Address already exits.', 'csrf_token' => $new_csrf_token]);
                                exit();
                            }

                            $new_temp_password = generateStrongPassword(8);
                            $password = password_hash($password, PASSWORD_BCRYPT);
                            $temp_password = password_hash($new_temp_password, PASSWORD_BCRYPT);

                            $a_id = generateItemID();

                            $columns = ['a_id', 'full_name', 'username', 'email', 'password', 'temp_password', 'role', 'created_date', 'updated_date'];
                            $values = [$a_id, $fullname, $username, $email_address, $password, $temp_password, 'staff', getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'admin', $columns, $values);

                            $schema = permissionSchema();

                            $inputPermissions = json_decode($_POST['permissions_json'] ?? '{}', true);

                            $newPermissions = [
                                'resources' => [],
                                'pages' => []
                            ];

                            foreach ($schema['resources'] as $module => $actions) {
                                foreach ($actions as $action => $_) {
                                    $newPermissions['resources'][$module][$action] =
                                        !empty($inputPermissions['resources'][$module][$action]);
                                }
                            }

                            foreach ($schema['pages'] as $page => $_) {
                                $newPermissions['pages'][$page] =
                                    !empty($inputPermissions['pages'][$page]);
                            }

                            $permission_json = json_encode($newPermissions);

                            foreach ($brands as $brand_id) {
                                $brand_id = escape_string($brand_id);

                                $response = json_decode(getData($db_prefix.'brands','WHERE brand_id = "'.$brand_id.'"'),true);
                                if($response['status'] == true){

                                    $columns = ['brand_id', 'a_id', 'permission', 'created_date', 'updated_date'];
                                    $values = [$brand_id, $a_id, $permission_json, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                    insertData($db_prefix.'permission', $columns, $values);

                                }
                            }

                            echo json_encode(['status' => 'true', 'title' => 'Staff Created', 'message' => 'The staff account has been created successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Invalid Email', 'message' => 'Please enter a valid email address.', 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "staff-update"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $fullname = escape_string($_POST['full-name'] ?? '');
                    $username = escape_string($_POST['username'] ?? '');
                    $email_address = escape_string($_POST['email-address'] ?? '');
                    $password = escape_string($_POST['password'] ?? '');
                    $itemID = escape_string($_POST['itemID'] ?? '');

                    $response_staff = json_decode(getData($db_prefix.'admin','WHERE role = "staff" AND a_id = "'.$itemID.'"'),true);
                    if($response_staff['status'] == true){
                        if($global_user_response['response'][0]['a_id'] == $itemID){
                            echo json_encode(['status' => "false", 'title' => 'Edit Staff Failed', 'message' => 'You are not allowed to edit your own staff information.', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        if($fullname == "" || $username == "" || $email_address == ""){
                            echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            if (filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
                                if($fullname == ""){
                                    $fullname = $response_staff['response'][0]['full_name'];
                                }

                                if($username !== $response_staff['response'][0]['username']){
                                    $response = json_decode(getData($db_prefix.'admin','WHERE username = "'.$username.'"'),true);
                                    if($response['status'] == true){
                                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Username already exits.', 'csrf_token' => $new_csrf_token]);
                                        exit();
                                    }
                                }

                                if($email_address !== $response_staff['response'][0]['email']){
                                    $response = json_decode(getData($db_prefix.'admin','WHERE email = "'.$email_address.'"'),true);
                                    if($response['status'] == true){
                                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Email Address already exits.', 'csrf_token' => $new_csrf_token]);
                                        exit();
                                    }
                                }

                                if($password == ""){
                                    $password = $response_staff['response'][0]['password'];
                                    $temp_password = $response_staff['response'][0]['temp_password'];
                                }else{
                                    $new_temp_password = generateStrongPassword(8);
                                    $password = password_hash($password, PASSWORD_BCRYPT);
                                    $temp_password = password_hash($new_temp_password, PASSWORD_BCRYPT);
                                }

                                $columns = ['full_name', 'username', 'email', 'password', 'temp_password', 'updated_date'];
                                $values = [$fullname, $username, $email_address, $password, $temp_password, getCurrentDatetime('Y-m-d H:i:s')];
                                $condition = "a_id = '".$response_staff['response'][0]['a_id']."'"; 
                                
                                updateData($db_prefix.'admin', $columns, $values, $condition);

                                echo json_encode(['status' => 'true', 'title' => 'Staff Profile Updated', 'message' => 'Staff profile information has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                            }else{
                                echo json_encode(['status' => "false", 'title' => 'Invalid Email', 'message' => 'Please enter a valid email address.', 'csrf_token' => $new_csrf_token]);
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "staff-permissions"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'view_permission_list', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $show_limit = escape_string($_POST['show_limit'] ?? 5);
                    $a_id = escape_string($_POST['a_id'] ?? '');

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_staff = json_decode(getData($db_prefix.'admin','WHERE a_id = "'.$a_id.'" AND id NOT IN ("'.$global_user_response['response'][0]['id'].'") AND role = "staff"'),true);
                    if($response_staff['status'] == true){
                        if($global_user_response['response'][0]['a_id'] == $a_id){
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => "You can't edit your info" , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => "Invalid Staff ID" , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $response_result = json_decode(getData($db_prefix.'permission','WHERE '.$where_sql.' a_id = "'.$response_staff['response'][0]['a_id'].'" ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = "'.$row['brand_id'].'"'),true);
                            if($response_brand['status'] == true){
                                $response[] = [
                                    "id"   => $row['id'],
                                    "identify_name"   => $response_brand['response'][0]['identify_name'],
                                    "brandname"   => $response_brand['response'][0]['name'],
                                    "status"     => $row['status'],
                                    "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                    "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                ];
                            }
                        }

                        $count_data = json_decode(getData($db_prefix.'permission','WHERE a_id = "'.$response_staff['response'][0]['a_id'].'"'),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "staff-permission-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'permission','WHERE id = "'.$itemID.'"'),true);
                            if($response_brand['status'] == true){
                                if($response_brand['response'][0]['a_id'] == $global_user_response['response'][0]['a_id']){

                                }else{
                                    $response_admin = json_decode(getData($db_prefix.'admin','WHERE role = "admin" AND a_id = "'.$response_brand['response'][0]['a_id'].'" '),true);
                                    if($response_admin['status'] == true){

                                    }else{
                                        if($actionID == "deleted"){
                                            if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'delete_permission_of', $global_user_response['response'][0]['role'])) {
                                                $condition = "id = '".$itemID."'"; 
                                                
                                                deleteData($db_prefix.'permission', $condition);
                                            }
                                        }

                                        if($actionID == "activated"){
                                            if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'edit_permission', $global_user_response['response'][0]['role'])) {
                                                $columns = ['status', 'updated_date'];
                                                $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                                $condition = "id = '".$itemID."'"; 
                                                
                                                updateData($db_prefix.'permission', $columns, $values, $condition);
                                            }
                                        }

                                        if($actionID == "suspended"){
                                            if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'edit_permission', $global_user_response['response'][0]['role'])) {
                                                $columns = ['status', 'updated_date'];
                                                $values = ['suspend', getCurrentDatetime('Y-m-d H:i:s')];
                                                $condition = "id = '".$itemID."'"; 
                                                
                                                updateData($db_prefix.'permission', $columns, $values, $condition);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Staff Permissions '.$actionID, 'message' => 'The selected staff permissions have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No Staff selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "staff-permission-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'delete_permission', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_permision = json_decode(getData($db_prefix.'permission','WHERE id = "'.$ItemID.'"'),true);
                    if($response_permision['status'] == true){
                        $response_staff = json_decode(getData($db_prefix.'admin','WHERE role = "staff" AND a_id = "'.$response_permision['response'][0]['a_id'].'" '),true);
                        if($response_staff['status'] == true){
                            if($response_staff['response'][0]['id'] == $global_user_response['response'][0]['id']){
                                echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'You cannot delete your own permission.' , 'csrf_token' => $new_csrf_token]);
                            }else{
                                $condition = "id = '".$ItemID."'"; 
                                
                                deleteData($db_prefix.'permission', $condition);

                                echo json_encode(['status' => 'true', 'title' => 'Staff Permission Deleted', 'message' => 'The staff member permission have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                            }
                        }else {
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No Staff selected.' , 'csrf_token' => $new_csrf_token]);
                        }
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid Permission ID' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "staff-brand-add"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'assign_brand_to', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $staffID = escape_string($_POST['staff_id'] ?? '');
                    $brands =  $_POST['brands'] ?? [];

                    $count_brand = 0;

                    foreach ($brands as $count) {
                            $count_brand = $count_brand+1;
                    }

                    if($count_brand == 0){
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'You need to allow minimum 1 brand to create a permission', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $response_staff = json_decode(getData($db_prefix.'admin','WHERE role = "staff" AND a_id = "'.$staffID.'"'),true);
                    if($response_staff['status'] == true){
                        if($global_user_response['response'][0]['a_id'] == $staffID){
                            echo json_encode(['status' => "false", 'title' => 'Edit Staff Failed', 'message' => 'You are not allowed to edit your own permissions.', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        foreach ($brands as $brandid) {
                            $response_brand = json_decode(getData($db_prefix . 'brands', ' WHERE brand_id = "'.$brandid.'"'), true);
                            if ($response_brand['status'] == true) {
                                foreach ($response_brand['response'] as $row) {
                                    $response_permission = json_decode(getData($db_prefix . 'permission', ' WHERE a_id = "'.$response_staff['response'][0]['a_id'].'" AND brand_id = "'.$row['brand_id'].'"'), true);
                                    
                                    if($response_permission['status'] == true){

                                    }else{

                                        $columns = ['brand_id', 'a_id', 'permission', 'created_date', 'updated_date'];
                                        $values = [$brandid, $response_staff['response'][0]['a_id'], json_encode(permissionSchema()), getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                        insertData($db_prefix.'permission', $columns, $values);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Brand Assigned Successfully', 'message' => 'The brand has been successfully assigned to the staff member.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "staff-update-permission"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'staff_management', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'staff', 'edit_permission', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $permission_id = escape_string($_POST['staff_id'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');

                    $schema = permissionSchema();

                    $inputPermissions = json_decode($_POST['permissions_json'] ?? '{}', true);

                    $newPermissions = [
                        'resources' => [],
                        'pages' => []
                    ];

                    foreach ($schema['resources'] as $module => $actions) {
                        foreach ($actions as $action => $_) {
                            $newPermissions['resources'][$module][$action] =
                                !empty($inputPermissions['resources'][$module][$action]);
                        }
                    }

                    foreach ($schema['pages'] as $page => $_) {
                        $newPermissions['pages'][$page] =
                            !empty($inputPermissions['pages'][$page]);
                    }

                    $permission_json = json_encode($newPermissions);

                    $response = json_decode(getData($db_prefix.'permission','WHERE id = "'.$permission_id.'"'),true);
                    if($response['status'] == true){
                        $response_staff = json_decode(getData($db_prefix.'admin','WHERE role = "staff" AND a_id = "'.$response['response'][0]['a_id'].'"'),true);
                        if($response_staff['status'] == true){
                            if($global_user_response['response'][0]['a_id'] == $response['response'][0]['a_id']){
                                echo json_encode(['status' => "false", 'title' => 'Edit Staff Failed', 'message' => 'You are not allowed to edit your own permissions.', 'csrf_token' => $new_csrf_token]);
                                exit();
                            }

                            $columns = ['permission', 'updated_date', 'status'];
                            $values = [$permission_json, getCurrentDatetime('Y-m-d H:i:s'), $status];

                            $condition = "id = '".$permission_id."'"; 
                            
                            updateData($db_prefix.'permission', $columns, $values, $condition);

                            echo json_encode(['status' => 'true', 'title' => 'Permissions Updated', 'message' => 'The staff brand permissions has been created successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);                            
                        }
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "create-new-brand"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $brand_name = escape_string($_POST['brand-name'] ?? '');

                    if($brand_name == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $response = json_decode(getData($db_prefix.'brands','WHERE identify_name = "'.$brand_name.'"'),true);
                        if($response['status'] == false){
                            $brand_id = generateItemID();

                            $columns = ['brand_id', 'identify_name', 'created_date', 'updated_date'];
                            $values = [$brand_id, $brand_name, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'brands', $columns, $values);

                            $columns = ['brand_id', 'a_id', 'permission', 'created_date', 'updated_date'];
                            $values = [$brand_id, $global_user_response['response'][0]['a_id'], json_encode(permissionSchema()), getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'permission', $columns, $values);

                            $columns = ['brand_id', 'code', 'symbol', 'created_date', 'updated_date'];
                            $values = [$brand_id, 'BDT', '৳', getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'currency', $columns, $values);

                            if($global_user_response['response'][0]['role'] !== 'admin'){
                                $response_admin = json_decode(getData($db_prefix.'admin','WHERE role = "admin"'),true);
                                foreach($response_admin['response'] as $admins){
                                    $columns = ['brand_id', 'a_id', 'permission', 'created_date', 'updated_date'];
                                    $values = [$brand_id, $admins['a_id'], json_encode(permissionSchema()), getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                    insertData($db_prefix.'permission', $columns, $values);
                                }
                            }

                            setsCookie('pp_brand', $brand_id);

                            echo json_encode(['status' => 'true', 'title' => 'Brand Created', 'message' => 'The brand has been created successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Duplicate Brand', 'message' => 'A brand with this name already exists. Please choose a different name.' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "all-brand-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( identify_name LIKE '%$search_input%' OR name LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'brands',' WHERE '.$where_sql.' identify_name NOT IN ("") '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $deleteable = 'true';

                            if($row['id'] == 1 || $row['brand_id'] == $global_response_brand['response'][0]['brand_id']){
                                $deleteable = 'false';
                            }

                            $response[] = [
                                "id"   => $row['brand_id'],
                                "deleteable"   => $deleteable,
                                "identify_name"   => $row['identify_name'],
                                "name"   => $row['name'],
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'brands','  '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "brand-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = "'.$itemID.'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if($response_brand['response'][0]['id'] == 1 || $response_brand['response'][0]['brand_id'] == $global_response_brand['response'][0]['brand_id']){

                                    }else{
                                        if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', 'delete', $global_user_response['response'][0]['role'])) {
                                        
                                            $condition = "brand_id = '".$itemID."'"; 
                                            
                                            deleteData($db_prefix.'brands', $condition);


                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'api', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'currency', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'customer', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'env', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'faq', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'gateways', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'gateways_parameter', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'invoice', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'invoice_items', $condition);

                                            $response_payment_link_filed = json_decode(getData($db_prefix.'payment_link','WHERE brand_id = "'.$response_brand['response'][0]['brand_id'].'"'),true);
                                            foreach($response_payment_link_filed['response'] as $row_paymentfiled){
                                                $condition = "paymentLinkID = '".$row_paymentfiled['ref']."'"; 
                                                
                                                deleteData($db_prefix.'payment_link_field', $condition);
                                            }

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'payment_link', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'permission', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'transaction', $condition);

                                            $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                            
                                            deleteData($db_prefix.'webhook_log', $condition);
                                        }
                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Brands '.$actionID, 'message' => 'The selected brands have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No brands selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "brand-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = "'.$ItemID.'" '),true);
                    if($response_brand['status'] == true){
                        if($response_brand['response'][0]['id'] == 1 || $response_brand['response'][0]['brand_id'] == $global_response_brand['response'][0]['brand_id']){
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        }else{
                            if($response_brand['response'][0]['id'] == 1 || $response_brand['response'][0]['brand_id'] == $global_response_brand['response'][0]['brand_id']){
                                echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                            }else{
                                $condition = "brand_id = '".$ItemID."'"; 
                                
                                deleteData($db_prefix.'brands', $condition);


                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'api', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'currency', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'customer', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'env', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'faq', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'gateways', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'gateways_parameter', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'invoice', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'invoice_items', $condition);

                                $response_payment_link_filed = json_decode(getData($db_prefix.'payment_link','WHERE brand_id = "'.$response_brand['response'][0]['brand_id'].'"'),true);
                                foreach($response_payment_link_filed['response'] as $row_paymentfiled){
                                    $condition = "paymentLinkID = '".$row_paymentfiled['ref']."'"; 
                                    
                                    deleteData($db_prefix.'payment_link_field', $condition);
                                }

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'payment_link', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'permission', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'transaction', $condition);

                                $condition = "brand_id = '".$response_brand['response'][0]['brand_id']."'"; 
                                
                                deleteData($db_prefix.'webhook_log', $condition);

                                echo json_encode(['status' => 'true', 'title' => 'Brands Deleted', 'message' => 'The selected brand have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "edit-brand"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $brand_name = escape_string($_POST['brand-name'] ?? '');
                    $brand_id = escape_string($_POST['b_id'] ?? '');

                    if($brand_name == "" || $brand_id == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $response = json_decode(getData($db_prefix.'brands','WHERE brand_id = "'.$brand_id.'"'),true);
                        if($response['status'] == true){
                            if($response['response'][0]['id'] == 1 || $response['response'][0]['brand_id'] == $global_response_brand['response'][0]['brand_id']){
                                echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                                exit();
                            }

                            if($response['response'][0]['identify_name'] !== $brand_name){
                                $responseNameCheck = json_decode(getData($db_prefix.'brands','WHERE identify_name = "'.$brand_name.'"'),true);
                                if($responseNameCheck['status'] == true){
                                    echo json_encode(['status' => 'false', 'title' => 'Duplicate Brand', 'message' => 'A brand with this name already exists. Please choose a different name.' , 'csrf_token' => $new_csrf_token]);
                                    exit();
                                }
                            }

                            $columns = ['identify_name', 'updated_date'];
                            $values = [$brand_name, getCurrentDatetime('Y-m-d H:i:s')];
                            $condition = "brand_id = '".$brand_id."'"; 
                            
                            updateData($db_prefix.'brands', $columns, $values, $condition);

                            echo json_encode(['status' => 'true', 'title' => 'Brand Updated', 'message' => 'The brand has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid brand id' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "all-domain-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= "AND ( domain LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'domain',' WHERE '.$where_sql.' status NOT IN ("") '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $response[] = [
                                "id"   => $row['id'],
                                "domain"   => $row['domain'],
                                "status"   => $row['status'],
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'domain','  '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "domains-info-byID"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'domain','WHERE id = "'.$ItemID.'"'),true);
                    if($response_brand['status'] == true){
                        echo json_encode(['status' => 'true', 'domain' => $response_brand['response'][0]['domain'], 'istatus' => $response_brand['response'][0]['status'], 'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "create-domains"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', 'whitelist', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $domain_name = escape_string($_POST['domain_name'] ?? '');
                    $domain_status = escape_string($_POST['domain_status'] ?? '');

                    if($domain_name == "" || $domain_status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $domain_name = getDomainValue($domain_name);

                        if ($domain_name === false) {
                            echo json_encode(['status' => "false", 'title' => 'Invalid Domain', 'message' => 'Please enter a valid domain or domain URL.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            $response = json_decode(getData($db_prefix.'domain','WHERE domain = "'.$domain_name.'"'),true);
                            if($response['status'] == false){
                                $columns = ['domain', 'status', 'created_date', 'updated_date'];
                                $values = [$domain_name, $domain_status, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                insertData($db_prefix.'domain', $columns, $values);

                                echo json_encode(['status' => 'true', 'title' => 'Domain Whitelisted', 'message' => 'The domain has been whitelisted successfully.', 'csrf_token' => $new_csrf_token]);
                            }else{
                                echo json_encode(['status' => 'false', 'title' => 'Duplicate Domain', 'message' => 'A domain with this name already exists. Please choose a different name.' , 'csrf_token' => $new_csrf_token]);
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "domains-edit"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $domain_id = escape_string($_POST['domain_id'] ?? '');
                    $domain_name = escape_string($_POST['domain_name'] ?? '');
                    $domain_status = escape_string($_POST['domain_status'] ?? '');

                    if($domain_id == "" || $domain_name == "" || $domain_status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $domain_name = getDomainValue($domain_name);

                        if ($domain_name === false) {
                            echo json_encode(['status' => "false", 'title' => 'Invalid Domain', 'message' => 'Please enter a valid domain or domain URL.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            $response = json_decode(getData($db_prefix.'domain','WHERE id = "'.$domain_id.'"'),true);
                            if($response['status'] == false){
                                echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                            }else{
                                $response = json_decode(getData($db_prefix.'domain','WHERE domain = "'.$domain_name.'"'),true);
                                if($response['status'] == true){
                                    if($response['response'][0]['id'] == $domain_id){

                                    }else{
                                        echo json_encode(['status' => 'false', 'title' => 'Duplicate Domain', 'message' => 'A domain with this name already exists. Please choose a different name.' , 'csrf_token' => $new_csrf_token]);
                                        exit();
                                    }
                                }

                                $columns = ['domain', 'status', 'updated_date'];
                                $values = [$domain_name, $domain_status, getCurrentDatetime('Y-m-d H:i:s')];
                                $condition = "id = '".$domain_id."'"; 
                                
                                updateData($db_prefix.'domain', $columns, $values, $condition);

                                echo json_encode(['status' => 'true', 'title' => 'Domain Updated', 'message' => 'The domain has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "domains-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'domain','WHERE id = "'.$ItemID.'" '),true);
                    if($response_brand['status'] == true){
                        $condition = "id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'domain', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Domain Deleted', 'message' => 'The selected domain have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "domain-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'domain','WHERE id = "'.$itemID.'" '),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', 'delete', $global_user_response['response'][0]['role'])) {
                                    
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'domain', $condition);

                                    }
                                }
                                if($actionID == "activated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'domain', $columns, $values, $condition);

                                    }
                                }

                                if($actionID == "inactive"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'domains', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['inactive', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'domain', $columns, $values, $condition);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Domains '.$actionID, 'message' => 'The selected domains have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No domains selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "cron-job-command-generate"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_cron', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $cron_command = bin2hex(random_bytes(8)); 

                    set_env('cron-job', $cron_command);

                    echo json_encode(['status' => 'true', 'title' => 'Cron Command Generated', 'message' => 'Your cron command has been updated. You can now copy it or use it immediately.', 'cron_command' => $cron_command, 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "dashboard-transaction-statistics"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'dashboard', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $date  = escape_string($_POST['date'] ?? 'this_year');
                    $start = escape_string($_POST['start'] ?? '');
                    $end   = escape_string($_POST['end'] ?? '');

                    $labels = [];
                    $keys   = [];

                    // If user sends a custom start/end date
                    if ($start || $end) {

                        // fallback to today if start or end missing
                        if (!$start) $start = $end;
                        if (!$end) $end = $start;

                        $start_ts = strtotime($start);
                        $end_ts   = strtotime($end);

                        // loop from start to end by day
                        for ($ts = $start_ts; $ts <= $end_ts; $ts = strtotime('+1 day', $ts)) {
                            $labels[] = date('d M', $ts);       // e.g. 08 Jan
                            $keys[]   = date('Y-m-d', $ts);     // use for mapping DB
                        }

                    } else {

                        // Your existing switch for presets
                        switch ($date) {
                            case 'today':
                                for ($i = 6; $i >= 0; $i--) {
                                    $labels[] = date('h A', strtotime("-$i hour"));
                                    $keys[]   = date('Y-m-d H', strtotime("-$i hour"));
                                }
                                break;

                            case 'yesterday':
                                for ($i = 0; $i < 7; $i++) {
                                    $labels[] = date('h A', strtotime("yesterday +$i hour"));
                                    $keys[]   = date('Y-m-d H', strtotime("yesterday +$i hour"));
                                }
                                break;

                            case 'this_week':
                            case 'last_week':
                                $start = ($date === 'this_week') ? strtotime('monday this week') : strtotime('monday last week');
                                for ($i = 0; $i < 7; $i++) {
                                    $labels[] = date('D', strtotime("+$i day", $start));
                                    $keys[]   = date('Y-m-d', strtotime("+$i day", $start));
                                }
                                break;

                            case 'this_month':
                            case 'last_month':
                                $start = ($date === 'this_month') ? strtotime(date('Y-m-01')) : strtotime('first day of last month');
                                $days = date('t', $start);
                                for ($i = 0; $i < $days; $i++) {
                                    $labels[] = date('d', strtotime("+$i day", $start));
                                    $keys[]   = date('Y-m-d', strtotime("+$i day", $start));
                                }
                                break;

                            case 'previous_year':
                                for ($i = 11; $i >= 0; $i--) {
                                    $labels[] = date('M', strtotime("-$i month", strtotime('first day of january last year')));
                                    $keys[]   = date('Y-m', strtotime("-$i month", strtotime('first day of january last year')));
                                }
                                break;

                            case 'this_year':
                            default:
                                for ($i = 11; $i >= 0; $i--) {
                                    $labels[] = date('M', strtotime("-$i month"));
                                    $keys[]   = date('Y-m', strtotime("-$i month"));
                                }
                                break;
                        }

                    }

                    // Prepare empty arrays
                    $total    = array_fill(0, count($keys), 0);
                    $complete = array_fill(0, count($keys), 0);
                    $pending  = array_fill(0, count($keys), 0);

                    $keyMap = array_flip($keys);

                    // Fetch transactions
                    $response_transaction = json_decode(getData($db_prefix.'transaction',' WHERE brand_id = "'.$global_response_brand['response'][0]['brand_id'].'" AND status NOT IN ("initiated")'), true);

                    foreach ($response_transaction['response'] as $row) {

                        if ($start || $end) {
                            // For custom date, group by day
                            $trxKey = date('Y-m-d', strtotime($row['created_date']));
                        } elseif (in_array($date, ['today','yesterday'])) {
                            $trxKey = date('Y-m-d H', strtotime($row['created_date']));
                        } elseif (in_array($date, ['this_week','last_week','this_month','last_month'])) {
                            $trxKey = date('Y-m-d', strtotime($row['created_date']));
                        } else {
                            $trxKey = date('Y-m', strtotime($row['created_date']));
                        }

                        if (isset($keyMap[$trxKey])) {
                            $i = $keyMap[$trxKey];
                            $total[$i]++;

                            if ($row['status'] === 'completed') $complete[$i]++;
                            if ($row['status'] === 'pending')   $pending[$i]++;
                        }
                    }

                    echo json_encode([
                        'status'   => 'true',
                        'labels'   => $labels,
                        'total'    => $total,
                        'complete' => $complete,
                        'pending'  => $pending,
                        'csrf_token' => $new_csrf_token
                    ]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "dashboard-gateway-statistics"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'dashboard', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $date = escape_string($_POST['date'] ?? 'this_year');
                    $start = escape_string($_POST['start'] ?? '');
                    $end   = escape_string($_POST['end'] ?? '');

                    $labels = [];
                    $keys   = [];

                    $isCustomRange = (!empty($start) && !empty($end));

                    if ($isCustomRange) {

                        $startTs = strtotime($start);
                        $endTs   = strtotime($end);

                        // safety: swap if reversed
                        if ($startTs > $endTs) {
                            [$startTs, $endTs] = [$endTs, $startTs];
                        }

                        $labels = [];
                        $keys   = [];

                        while ($startTs <= $endTs) {
                            $labels[] = date('d M', $startTs);   // UI label
                            $keys[]   = date('Y-m-d', $startTs); // matching key
                            $startTs = strtotime('+1 day', $startTs);
                        }

                    } else {
                        switch ($date) {

                            case 'today':
                                for ($i = 6; $i >= 0; $i--) {
                                    $labels[] = date('h A', strtotime("-$i hour"));
                                    $keys[]   = date('Y-m-d H', strtotime("-$i hour"));
                                }
                                break;

                            case 'yesterday':
                                for ($i = 0; $i < 7; $i++) {
                                    $labels[] = date('h A', strtotime("yesterday +$i hour"));
                                    $keys[]   = date('Y-m-d H', strtotime("yesterday +$i hour"));
                                }
                                break;

                            case 'this_week':
                            case 'last_week':
                                $start = ($date === 'this_week') ? strtotime('monday this week') : strtotime('monday last week');
                                for ($i = 0; $i < 7; $i++) {
                                    $labels[] = date('D', strtotime("+$i day", $start));
                                    $keys[]   = date('Y-m-d', strtotime("+$i day", $start));
                                }
                                break;

                            case 'this_month':
                            case 'last_month':
                                $start = ($date === 'this_month') ? strtotime(date('Y-m-01')) : strtotime('first day of last month');
                                $days = date('t', $start);
                                for ($i = 0; $i < $days; $i++) {
                                    $labels[] = date('d', strtotime("+$i day", $start));
                                    $keys[]   = date('Y-m-d', strtotime("+$i day", $start));
                                }
                                break;

                            case 'previous_year':
                                for ($i = 11; $i >= 0; $i--) {
                                    $labels[] = date('M', strtotime("-$i month", strtotime('first day of january last year')));
                                    $keys[]   = date('Y-m', strtotime("-$i month", strtotime('first day of january last year')));
                                }
                                break;

                            case 'this_year':
                            default:
                                for ($i = 6; $i >= 0; $i--) {
                                    $labels[] = date('M', strtotime("-$i month"));
                                    $keys[]   = date('Y-m', strtotime("-$i month"));
                                }
                                break;
                        }
                    }

                    $keyMap = array_flip($keys);

                    // Initialize arrays for gateway data
                    $gatewayData = []; // ['Stripe' => [0,0,0,...], 'PayPal' => [...]]
                    $gatewayLabels = []; // slug => name

                    // Get all transactions
                    $response_transaction = json_decode(getData($db_prefix.'transaction',' WHERE brand_id = "'.$global_response_brand['response'][0]['brand_id'].'" AND status ="completed"'), true);

                    foreach($response_transaction['response'] as $row){

                        // Determine key based on date range
                        if ($isCustomRange) {
                            $trxKey = date('Y-m-d', strtotime($row['created_date']));
                        }
                        elseif (in_array($date, ['today','yesterday'])) {
                            $trxKey = date('Y-m-d H', strtotime($row['created_date']));
                        }
                        elseif (in_array($date, ['this_week','last_week','this_month','last_month'])) {
                            $trxKey = date('Y-m-d', strtotime($row['created_date']));
                        }
                        else {
                            $trxKey = date('Y-m', strtotime($row['created_date']));
                        }

                        if (!isset($keyMap[$trxKey])) continue;
                        $i = $keyMap[$trxKey];

                        // Get gateway name
                        $gateway_id = $row['gateway_id'];
                        if (!isset($gatewayLabels[$gateway_id])) {
                            $resGateway = json_decode(getData($db_prefix.'gateways', ' WHERE brand_id = "'.$global_response_brand['response'][0]['brand_id'].'"  AND gateway_id = "'.$gateway_id.'" LIMIT 1'), true);
                            $gatewayName = "Unknown"; // default if gateway missing
                            $gatewayColor = '#d3d3d3'; // default light grey for unknown
                            if ($resGateway['status'] && isset($resGateway['response'][0]['name']) && !empty($resGateway['response'][0]['name'])) {
                                $gatewayRow = $resGateway['response'][0];
                                if (!empty($gatewayRow['name'])) {
                                    $gatewayName = $gatewayRow['name'];
                                }
                                if (!empty($gatewayRow['primary_color'])) {
                                    $gatewayColor = $gatewayRow['primary_color']; // take color from DB
                                }
                            }

                            $gatewayLabels[$gateway_id] = $gatewayName;
                            $gatewayColors[$gatewayName] = $gatewayColor;
                            $gatewayData[$gatewayName] = array_fill(0, count($keys), 0);
                        }

                        $gatewayData[$gatewayLabels[$gateway_id]][$i]++;
                    }

                    if(empty($gatewayData)) {
                        $gatewayData['No Data'] = [1]; 
                        $gatewayLabels = ['No Data'];
                        $gatewayColors['No Data'] = '#f0f0f0'; // light grey
                    }

                    echo json_encode([ 'status' => 'true', 'labels' => $labels, 'keys' => $keys, 'gateway_labels' => array_values($gatewayLabels), 'data' => $gatewayData, 'colors' => array_values($gatewayColors), 'csrf_token' => $new_csrf_token ]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "reports"){
                if($global_user_login == true){
                        if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'reports', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $date = escape_string($_POST['date'] ?? 'this_year');

                        $brand_id = $global_response_brand['response'][0]['brand_id'];

                        $rawStart = $_POST['start'] ?? '';
                        $rawEnd   = $_POST['end'] ?? '';

                        $start = '';
                        $end   = '';

                        if (!empty($rawStart)) {

                            // Try Y-m-d (2026-01-07)
                            $dt = DateTime::createFromFormat('Y-m-d', $rawStart);
                            if ($dt !== false) {
                                $start = $dt->format('Y-m-d');
                            } else {
                                // Fallback m/d/Y (01/07/2026)
                                $dt = DateTime::createFromFormat('m/d/Y', $rawStart);
                                if ($dt !== false) {
                                    $start = $dt->format('Y-m-d');
                                }
                            }
                        }

                        /* ---------- END DATE ---------- */
                        if (!empty($rawEnd)) {

                            // Try Y-m-d (2026-01-07)
                            $dt = DateTime::createFromFormat('Y-m-d', $rawEnd);
                            if ($dt !== false) {
                                $end = $dt->format('Y-m-d');
                            } else {
                                // Fallback m/d/Y (01/07/2026)
                                $dt = DateTime::createFromFormat('m/d/Y', $rawEnd);
                                if ($dt !== false) {
                                    $end = $dt->format('Y-m-d');
                                }
                            }
                        }
                        if (!empty($start) || !empty($end)) {

                            /* ---------- BOTH DATES ---------- */
                            if (!empty($start) && !empty($end)) {

                                if ($start > $end) {
                                    echo json_encode([
                                        'status' => 'false',
                                        'title' => 'Invalid Date Range',
                                        'message' => 'Start date must be earlier than end date.',
                                        'csrf_token' => $new_csrf_token
                                    ]);
                                    exit;
                                }

                                $rangeStart = $start;
                                $rangeEnd   = $end;
                            }

                            /* ---------- ONLY START DATE ---------- */
                            elseif (!empty($start)) {

                                $rangeStart = $start;
                                $rangeEnd   = date('Y-m-d'); // today
                            }

                            /* ---------- ONLY END DATE ---------- */
                            else {

                                $rangeEnd   = $end;
                                $rangeStart = $end; // single-day report
                            }

                            /* ---------- DISPLAY RANGE ---------- */
                            $from = date('M d, Y', strtotime($rangeStart));
                            $to   = date('M d, Y', strtotime($rangeEnd));

                            /* ---------- SQL WHERE ---------- */
                            $where = "DATE(created_date) BETWEEN '$rangeStart' AND '$rangeEnd'";

                            /* ---------- PREVIOUS RANGE ---------- */
                            $days = (strtotime($rangeEnd) - strtotime($rangeStart)) / 86400 + 1;

                            $prevStart = date('Y-m-d', strtotime("$rangeStart -$days days"));
                            $prevEnd   = date('Y-m-d', strtotime("$rangeEnd -$days days"));

                            $prevWhere = "DATE(created_date) BETWEEN '$prevStart' AND '$prevEnd'";

                        } else {
                            switch ($date) {

                                case 'today':
                                    $from = date('M d, Y');
                                    $to = $from;
                                    $where = "DATE(created_date)=CURDATE()";
                                    $prevWhere = "DATE(created_date)=CURDATE()-INTERVAL 1 DAY";
                                    break;

                                case 'yesterday':
                                    $from = date('M d, Y', strtotime('-1 day'));
                                    $to = $from;
                                    $where = "DATE(created_date)=CURDATE()-INTERVAL 1 DAY";
                                    $prevWhere = "DATE(created_date)=CURDATE()-INTERVAL 2 DAY";
                                    break;

                                case 'this_week':
                                    $from = date('M d, Y', strtotime('monday this week'));
                                    $to = date('M d, Y', strtotime('sunday this week'));
                                    $where = "YEARWEEK(created_date,1)=YEARWEEK(CURDATE(),1)";
                                    $prevWhere = "YEARWEEK(created_date,1)=YEARWEEK(CURDATE(),1)-1";
                                    break;

                                case 'last_week':
                                    $from = date('M d, Y', strtotime('monday last week'));
                                    $to = date('M d, Y', strtotime('sunday last week'));
                                    $where = "YEARWEEK(created_date,1)=YEARWEEK(CURDATE(),1)-1";
                                    $prevWhere = "YEARWEEK(created_date,1)=YEARWEEK(CURDATE(),1)-2";
                                    break;

                                case 'this_month':
                                    $from = date('M 01, Y');
                                    $to = date('M t, Y');
                                    $where = "MONTH(created_date)=MONTH(CURDATE()) AND YEAR(created_date)=YEAR(CURDATE())";
                                    $prevWhere = "MONTH(created_date)=MONTH(CURDATE()-INTERVAL 1 MONTH)";
                                    break;

                                case 'last_month':
                                    $from = date('M 01, Y', strtotime('first day of last month'));
                                    $to = date('M t, Y', strtotime('last day of last month'));
                                    $where = "MONTH(created_date)=MONTH(CURDATE()-INTERVAL 1 MONTH)";
                                    $prevWhere = "MONTH(created_date)=MONTH(CURDATE()-INTERVAL 2 MONTH)";
                                    break;

                                case 'previous_year':
                                    $from = 'Jan 01, '.date('Y', strtotime('-1 year'));
                                    $to = 'Dec 31, '.date('Y', strtotime('-1 year'));
                                    $where = "YEAR(created_date)=YEAR(CURDATE())-1";
                                    $prevWhere = "YEAR(created_date)=YEAR(CURDATE())-2";
                                    break;

                                case 'this_year':
                                default:
                                    $from = 'Jan 01, '.date('Y');
                                    $to = 'Dec 31, '.date('Y');
                                    $where = "YEAR(created_date)=YEAR(CURDATE())";
                                    $prevWhere = "YEAR(created_date)=YEAR(CURDATE())-1";
                                    break;
                            }
                        }

                        $currencyRates = [];

                        $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$brand_id.'"'), true);
                        if (!empty($currencyRes['response'])) {
                            foreach ($currencyRes['response'] as $c) {
                                $currencyRates[$c['code']] = (string)$c['rate']; 
                            }
                        }

                        $global_brand_currency_code = $global_response_brand['response'][0]['currency_code'];
                        $global_brand_currency_rate = "1"; 

                        $res = json_decode(getData($db_prefix.'transaction', " WHERE brand_id='$brand_id' AND status NOT IN ('initiated', 'expired') AND $where"), true);

                        $total = 0;
                        $completed = 0;
                        $revenue = "0"; 

                        foreach ($res['response'] as $row) {
                            $total++;
                            if ($row['status'] === 'completed') {
                                $completed++;

                                $txnAmount = (string)$row['amount'];
                                $txnCurrency = $row['currency'];

                                $rate = $txnCurrency === $global_brand_currency_code ? "1" : ($currencyRates[$txnCurrency] ?? "0");

                                $convertedAmount = money_mul($txnAmount, $rate);

                                $revenue = money_add($revenue, $convertedAmount);
                            }
                        }

                        $successRate = $total ? money_div((string)($completed * 100), (string)$total, 2) : "0";
                        $average = $completed ? money_div($revenue, (string)$completed, 2) : "0";

                        $prevRes = json_decode(getData($db_prefix.'transaction', " WHERE brand_id='$brand_id' AND status NOT IN ('initiated', 'expired') AND $prevWhere"), true);

                        $prevTotal = 0;
                        $prevCompleted = 0;

                        foreach ($prevRes['response'] as $row) {
                            $prevTotal++;
                            if ($row['status'] === 'completed') {
                                $prevCompleted++;
                            }
                        }

                        $prevSuccessRate = $prevTotal ? money_div((string)($prevCompleted * 100), (string)$prevTotal, 2) : "0";

                        $trend = bccomp($successRate, $prevSuccessRate, 2) > 0 ? 'up' : (bccomp($successRate, $prevSuccessRate, 2) < 0 ? 'down' : 'same');

                        echo json_encode(['status' => 'true', 'date_range' => $from.' – '.$to, 'revenue' => money_round($revenue, 2), 'completed' => $completed, 'total' => $total, 'success_rate' => money_round($successRate, 2), 'prev_success_rate' => money_round($prevSuccessRate, 2), 'success_trend' => $trend, 'average' => money_round($average, 2), 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "customer-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    $tabType = escape_string($_POST['tabType'] ?? '');

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($tabType !== "all") {
                        $where[] = "inserted_via = '{$tabType}'";
                    }

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */


                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( name LIKE '%$search_input%' OR email LIKE '%$search_input%' OR mobile LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'customer',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $response[] = [
                                "id"   => $row['ref'],
                                "name"   => $row['name'],
                                "email"   => $row['email'],
                                "mobile"   => $row['mobile'],
                                "status"   => $row['status'],
                                'suspend_reason' => ($row['suspend_reason'] == "--") ? '' : $row['suspend_reason'],
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'customer',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query),true);


                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "customers-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $name = escape_string($_POST['name'] ?? '');
                    $email = escape_string($_POST['email'] ?? '');
                    $mobile = escape_string($_POST['mobile'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');
                    $suspend_reason = escape_string($_POST['suspend_reason'] ?? '');

                    if($name == "" || $email == "" || $mobile == "" || $status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if($status == "active" || $status == "suspend"){

                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        if($suspend_reason == ""){
                            $suspend_reason == "--";
                        }

                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $response = json_decode(getData($db_prefix.'customer','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND email ="'.$email.'"'),true);
                            if($response['status'] == false){
                                $ref = generateItemID();

                                $columns = ['ref', 'brand_id', 'name', 'email', 'mobile', 'status', 'suspend_reason', 'created_date', 'updated_date'];
                                $values = [$ref, $global_response_brand['response'][0]['brand_id'], $name, $email, $mobile, $status, $suspend_reason, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                insertData($db_prefix.'customer', $columns, $values);

                                echo json_encode(['status' => 'true', 'title' => 'Customer Created', 'message' => 'The customer has been created successfully.', 'csrf_token' => $new_csrf_token]);
                            }else{
                                echo json_encode(['status' => 'false', 'title' => 'Duplicate Customer', 'message' => 'A customer with this email address already exists. Please choose a different email address.' , 'csrf_token' => $new_csrf_token]);
                            }
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Invalid Email', 'message' => 'Please enter a valid email address.', 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "customers-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'customer','WHERE ref = "'.$itemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', 'delete', $global_user_response['response'][0]['role'])) {
                                    
                                        $condition = "ref = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'customer', $condition);

                                    }
                                }

                                if($actionID == "activated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "ref = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'customer', $columns, $values, $condition);

                                    }
                                }

                                if($actionID == "suspended"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['suspend', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "ref = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'customer', $columns, $values, $condition);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Customers '.$actionID, 'message' => 'The selected customers have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No customers selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "customers-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'customer','WHERE ref = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '),true);
                    if($response_brand['status'] == true){
                        $condition = "ref = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'customer', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Customer Deleted', 'message' => 'The selected customer have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "customers-info-byID"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'customer','WHERE ref = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '),true);
                    if($response_brand['status'] == true){
                        echo json_encode(['status' => 'true', 'name' => $response_brand['response'][0]['name'], 'email' => $response_brand['response'][0]['email'], 'mobile' => $response_brand['response'][0]['mobile'], 'istatus' => $response_brand['response'][0]['status'], 'suspend_reason' => ($response_brand['response'][0]['suspend_reason'] === "--") ? "" : $response_brand['response'][0]['suspend_reason'], 'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "customers-edit"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'customers', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $customer_id = escape_string($_POST['customer_id'] ?? '');
                    $name = escape_string($_POST['name'] ?? '');
                    $email = escape_string($_POST['email'] ?? '');
                    $mobile = escape_string($_POST['mobile'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');
                    $suspend_reason = escape_string($_POST['suspend_reason'] ?? '');

                    if($suspend_reason == ""){
                        $suspend_reason = "--";
                    }

                    if($status == "active" || $status == "suspend"){

                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if($customer_id == "" || $name == "" || $email == "" || $mobile == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $response = json_decode(getData($db_prefix.'customer','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND ref ="'.$customer_id.'"'),true);
                            if($response['status'] == true){
                                if($response['response'][0]['email'] == $email){

                                }else{
                                    $responseCheck = json_decode(getData($db_prefix.'customer','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND email ="'.$email.'"'),true);
                                    if($responseCheck['status'] == false){
                                        
                                    }else{
                                        echo json_encode(['status' => 'false', 'title' => 'Duplicate Customer', 'message' => 'A customer with this email address already exists. Please choose a different email address.' , 'csrf_token' => $new_csrf_token]);
                                        exit();
                                    }
                                }

                                $columns = ['name', 'email', 'mobile', 'status', 'suspend_reason', 'updated_date'];
                                $values = [$name, $email, $mobile, $status, $suspend_reason, getCurrentDatetime('Y-m-d H:i:s')];
                                $condition = "ref = '".$customer_id."'"; 
                                
                                updateData($db_prefix.'customer', $columns, $values, $condition);

                                echo json_encode(['status' => 'true', 'title' => 'Customer Updated', 'message' => 'The customer has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                            }else{
                                echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid Customer ID' , 'csrf_token' => $new_csrf_token]);
                            }
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Invalid Email', 'message' => 'Please enter a valid email address.', 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "invoice-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    $tabType = escape_string($_POST['tabType'] ?? '');

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($tabType !== "all") {
                        $where[] = "status = '{$tabType}'";
                    }

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( customer_info LIKE '%$search_input%' OR currency LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'invoice',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $customer_info = json_decode($row['customer_info'], true);

                            $response_currency = json_decode(getData($db_prefix.'currency',' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND code = "'.$row['currency'].'"'),true);

                            $total = "0";
                            $items_count = 0;

                            $response_items = json_decode(getData($db_prefix.'invoice_items', ' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND invoice_id = "'.$row['ref'].'"'), true);

                            if (!empty($response_items['response']) && is_array($response_items['response'])) {
                                foreach ($response_items['response'] as $items) {
                                    $items_count++;

                                    $item_cost = money_mul($items['amount'], $items['quantity']);

                                    $item_total_cost = money_sub($item_cost, $items['discount']);

                                    $vat_amount = money_div(money_mul($item_total_cost, $items['vat']), "100");

                                    $item_total_cost_with_vat = money_add($item_total_cost, $vat_amount);

                                    $total = money_add($total, $item_total_cost_with_vat);
                                }
                            }

                            $total = money_add($total, $row['shipping']);

                            $currency = $response_currency['response'][0]['symbol'] ?? '';

                            $response[] = [
                                "id"    => $row['ref'],
                                "c_id"   => $customer_info['id']     ?? 'N/A',
                                "name"   => $customer_info['name']   ?? 'Unknown',
                                "email"   => $customer_info['email']  ?? '',
                                "mobile"  => $customer_info['mobile'] ?? '',
                                "status"  => $row['status'],
                                "items"  => $items_count,
                                "amount"  => $currency.money_round($total, 2),
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'invoice',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "invoice-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $customer = $_POST['customers'] ?? [];
                    $currency = escape_string($_POST['currency'] ?? '');
                    $due_date = escape_string($_POST['due_date'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');
                    $shipping = escape_string($_POST['shipping'] ?? '');
                    $note = escape_string($_POST['note'] ?? '');
                    $private_note_content = escape_string($_POST['private-note-content'] ?? '');

                    $item_description = $_POST['item-description'] ?? [];
                    $item_quantity    = $_POST['item-quantity'] ?? [];
                    $item_amount      = $_POST['item-amount'] ?? [];
                    $item_discount    = $_POST['item-discount'] ?? [];
                    $item_vat         = $_POST['item-vat'] ?? [];

                    $item_description = (array) $item_description;
                    $item_quantity    = (array) $item_quantity;
                    $item_amount      = (array) $item_amount;
                    $item_discount    = (array) $item_discount;
                    $item_vat         = (array) $item_vat;

                    if($note == ""){
                        $note = '--';
                    }
                    if($private_note_content == ""){
                        $private_note_content = '--';
                    }

                    if($due_date !== ""){
                        if (dateformat($due_date, 'Y-m-d')) {

                        } else {
                            echo json_encode(['status' => "false", 'title' => 'Invalid due date format', 'message' => 'Please enter the due date in the correct format (DD/MM/YYYY).', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }
                    }else{
                        $due_date = "--";
                    }

                    if($currency == "" || $status == "" || $shipping == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $insert_result = false;

                        $all_invoices = [];

                        foreach ($customer as $customer_id) {
                            $response = json_decode(getData($db_prefix.'customer','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND ref ="'.$customer_id.'" OR brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND email ="'.$customer_id.'"'),true);
                            if($response['status'] == true){
                                $invoice_id = generateItemID(27, 27);

                                $customer_info = json_encode([
                                    'id' => $response['response'][0]['ref'],
                                    'name' => $response['response'][0]['name'],
                                    'email' => $response['response'][0]['email'],
                                    'mobile' => $response['response'][0]['mobile']
                                ]);

                                $invoice_items_array = [];

                                if (count($item_description) > 0) {
                                    for ($i = 0; $i < count($item_description); $i++) {
                                        $descriptions = escape_string($item_description[$i] ?? '');
                                        $quantities   = escape_string($item_quantity[$i] ?? '');
                                        $amounts      = escape_string($item_amount[$i] ?? '');
                                        $discounts    = escape_string($item_discount[$i] ?? '');
                                        $vats         = escape_string($item_vat[$i] ?? '');

                                        $columns = ['invoice_id', 'brand_id', 'description', 'amount', 'quantity', 'discount', 'vat', 'created_date', 'updated_date'];
                                        $values = [$invoice_id, $global_response_brand['response'][0]['brand_id'], $descriptions, money_sanitize($amounts), money_sanitize($quantities), money_sanitize($discounts), money_sanitize($vats), getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                        insertData($db_prefix.'invoice_items', $columns, $values);

                                        $invoice_items_array[] = [
                                            'description' => $descriptions,
                                            'amount'      => money_round($amounts),
                                            'quantity'    => money_round($quantities),
                                            'discount'    => money_round($discounts),
                                            'vat'         => money_round($vats)
                                        ];
                                    }

                                    $insert_result = true;
                                }else{
                                    echo json_encode(['status' => "false", 'title' => 'Add Item Required', 'message' => 'Please add at least 1 item to create an invoice.', 'csrf_token' => $new_csrf_token]);
                                    exit();
                                }

                                $columns = ['ref', 'brand_id', 'customer_info', 'currency', 'due_date', 'shipping', 'status', 'note', 'private_note', 'created_date', 'updated_date'];
                                $values = [$invoice_id, $global_response_brand['response'][0]['brand_id'], $customer_info, $currency, $due_date, money_sanitize($shipping), $status, $note, $private_note_content, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                insertData($db_prefix.'invoice', $columns, $values);

                                $all_invoices['invoice_'.$invoice_id] = [
                                    'customer_info'  => $customer_info,
                                    'invoice_info'   => [
                                        'invoice_id'   => $invoice_id,
                                        'brand_id'     => $global_response_brand['response'][0]['brand_id'],
                                        'currency'     => $currency,
                                        'due_date'     => $due_date,
                                        'shipping'     => money_round($shipping),
                                        'status'       => $status,
                                        'note'         => $note,
                                        'private_note' => $private_note_content,
                                        'created_date' => convertUTCtoUserTZ(getCurrentDatetime('Y-m-d H:i:s') , ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                        'updated_date' => convertUTCtoUserTZ(getCurrentDatetime('Y-m-d H:i:s') , ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                    ],
                                    'invoice_items'  => $invoice_items_array
                                ];
                            }
                        }

                        if($insert_result == true){
                            if(!empty($all_invoices)){
                                do_action('invoices.created', $all_invoices);
                            }

                            echo json_encode(['status' => 'true', 'title' => 'Invoice Created', 'message' => 'The invoice has been created successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "invoice-edit"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $invoiceID = escape_string($_POST['invoiceID'] ?? '');
                    $currency = escape_string($_POST['currency'] ?? '');
                    $due_date = escape_string($_POST['due_date'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');
                    $shipping = escape_string($_POST['shipping'] ?? '');
                    $note = escape_string($_POST['note'] ?? '');
                    $private_note_content = escape_string($_POST['private-note-content'] ?? '');
                    $deletedItems = explode(',', $_POST['deleted_items'] ?? []);

                    $item_description = $_POST['item-description'] ?? [];
                    $item_quantity    = $_POST['item-quantity'] ?? [];
                    $item_amount      = $_POST['item-amount'] ?? [];
                    $item_discount    = $_POST['item-discount'] ?? [];
                    $item_vat         = $_POST['item-vat'] ?? [];
                    $item_id         = $_POST['item-id'] ?? [];

                    $item_description = (array) $item_description;
                    $item_quantity    = (array) $item_quantity;
                    $item_amount      = (array) $item_amount;
                    $item_discount    = (array) $item_discount;
                    $item_vat         = (array) $item_vat;
                    $item_id          = (array) $item_id;

                    if($note == ""){
                        $note = '--';
                    }
                    if($private_note_content == ""){
                        $private_note_content = '--';
                    }

                    if($due_date !== ""){
                        if (dateformat($due_date, 'Y-m-d')) {

                        } else {
                            echo json_encode(['status' => "false", 'title' => 'Invalid due date format', 'message' => 'Please enter the due date in the correct format (DD/MM/YYYY).', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }
                    }else{
                        $due_date = "--";
                    }

                    if($currency == "" || $status == "" || $shipping == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $response = json_decode(getData($db_prefix.'invoice','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND ref ="'.$invoiceID.'"'),true);
                        if($response['status'] == true){
                            $columns = ['currency', 'due_date', 'shipping', 'status', 'note', 'private_note', 'updated_date'];
                            $values = [$currency, $due_date, money_sanitize($shipping), $status, $note, $private_note_content, getCurrentDatetime('Y-m-d H:i:s')];

                            $condition = "ref = '".$invoiceID."'"; 
                            
                            updateData($db_prefix.'invoice', $columns, $values, $condition);

                            foreach ($deletedItems as $itemId) {
                                $condition = "id = '".$itemId."'"; 
                                
                                deleteData($db_prefix.'invoice_items', $condition);
                            }

                            $invoice_items_array = [];

                            if (count($item_description) > 0) {
                                for ($i = 0; $i < count($item_description); $i++) {
                                    $descriptions = escape_string($item_description[$i] ?? '');
                                    $quantities   = escape_string($item_quantity[$i] ?? '');
                                    $amounts      = escape_string($item_amount[$i] ?? '');
                                    $discounts    = escape_string($item_discount[$i] ?? '');
                                    $vats         = escape_string($item_vat[$i] ?? '');
                                    $itemidS         = escape_string($item_id[$i] ?? '');

                                    if($itemidS !== ""){
                                        $columns = ['description', 'amount', 'quantity', 'discount', 'vat', 'updated_date'];
                                        $values = [$descriptions, money_sanitize($amounts), money_sanitize($quantities), money_sanitize($discounts), money_sanitize($vats), getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemidS."'"; 
                                        
                                        updateData($db_prefix.'invoice_items', $columns, $values, $condition);
                                    }else{
                                        $columns = ['invoice_id', 'brand_id', 'description', 'amount', 'quantity', 'discount', 'vat', 'created_date', 'updated_date'];
                                        $values = [$invoiceID, $global_response_brand['response'][0]['brand_id'], $descriptions, money_sanitize($amounts), money_sanitize($quantities), money_sanitize($discounts), money_sanitize($vats), getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                        insertData($db_prefix.'invoice_items', $columns, $values);
                                    }

                                    $invoice_items_array[] = [
                                        'description' => $descriptions,
                                        'amount'      => money_round($amounts),
                                        'quantity'    => money_round($quantities),
                                        'discount'    => money_round($discounts),
                                        'vat'         => money_round($vats)
                                    ];
                                }
                            }

                            $all_invoices= [
                                'customer_info'  => $response['response'][0]['customer_info'],
                                'invoice_info'   => [
                                    'invoice_id'   => $invoiceID,
                                    'brand_id'     => $global_response_brand['response'][0]['brand_id'],
                                    'currency'     => $currency,
                                    'due_date'     => $due_date,
                                    'shipping'     => money_round($shipping),
                                    'status'       => $status,
                                    'note'         => $note,
                                    'private_note' => $private_note_content,
                                    'created_date' => convertUTCtoUserTZ($response['response'][0]['created_date'] , ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                    'updated_date' => convertUTCtoUserTZ(getCurrentDatetime('Y-m-d H:i:s') , ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                ],
                                'invoice_items'  => $invoice_items_array
                            ];
                            if(!empty($all_invoices)){
                                do_action('invoices.updated', $all_invoices);
                            }

                            echo json_encode(['status' => 'true', 'title' => 'Invoice Updated', 'message' => 'The invoice has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "invoice-manageStatus"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $invoiceID = escape_string($_POST['invoice-id'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');

                    $response = json_decode(getData($db_prefix.'invoice','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND ref ="'.$invoiceID.'"'),true);
                    if($response['status'] == true){
                        $columns = ['status', 'updated_date'];
                        $values = [$status, getCurrentDatetime('Y-m-d H:i:s')];

                        $condition = "ref = '".$invoiceID."'"; 
                        
                        updateData($db_prefix.'invoice', $columns, $values, $condition);

                        $invoice_items_array = [];

                        $response_items = json_decode(getData($db_prefix.'invoice_items','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND ref ="'.$invoiceID.'"'),true);
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
                            'customer_info'  => $response['response'][0]['customer_info'],
                            'invoice_info'   => [
                                'invoice_id'   => $invoiceID,
                                'brand_id'     => $global_response_brand['response'][0]['brand_id'],
                                'currency'     => $response['response'][0]['currency'],
                                'due_date'     => $response['response'][0]['due_date'],
                                'shipping'     => money_round($response['response'][0]['shipping']),
                                'status'       => $status,
                                'note'         => $response['response'][0]['note'],
                                'private_note' => $response['response'][0]['private_note'],
                                'created_date' => convertUTCtoUserTZ($response['response'][0]['created_date'] , ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                'updated_date' => convertUTCtoUserTZ(getCurrentDatetime('Y-m-d H:i:s') , ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ],
                            'invoice_items'  => $invoice_items_array
                        ];
                        if(!empty($all_invoices)){
                            do_action('invoices.updated.status', $all_invoices);
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Invoice Updated', 'message' => 'The invoice has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "invoice-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'invoice','WHERE ref = "'.$itemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', 'delete', $global_user_response['response'][0]['role'])) {
                                        $condition = "invoice_id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'invoice_items', $condition);

                                        $condition = "ref = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'invoice', $condition);
                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Invoices '.$actionID, 'message' => 'The selected invoices have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Invoices Failed', 'message' => 'No invoices selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "invoice-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'invoice', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'invoice','WHERE ref = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                    if($response_brand['status'] == true){
                        $condition = "invoice_id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'invoice_items', $condition);

                        $condition = "ref = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'invoice', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Invoice Deleted', 'message' => 'The selected invoice have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "paymentLink-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( product_info LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'payment_link',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $product_info = json_decode($row['product_info'], true);

                            $response_currency = json_decode(getData($db_prefix.'currency',' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND code = "'.$row['currency'].'"'),true);

                            $currency = $response_currency['response'][0]['symbol'] ?? '';

                            if($row['expired_date'] == "--"){
                                $status = $row['status'];
                            }else{
                                if (isExpired($row['expired_date'])) {
                                    $status = 'expired';
                                } else {
                                    $status = $row['status'];
                                }
                            }

                            $response[] = [
                                "id"    => $row['ref'],
                                "title"   => $product_info['title'] ?? 'N/A',
                                "description"   => $product_info['description'] ?? 'N/A',
                                "status"  => $status,
                                "quantity"  => $row['quantity'],
                                "amount"  => $currency.money_round($row['amount'], 2),
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'payment_link',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "paymentLink-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'payment_link','WHERE ref = "'.$itemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', 'delete', $global_user_response['response'][0]['role'])) {
                                    
                                        $condition = "paymentLinkID = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'payment_link_field', $condition);

                                        $condition = "ref = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'payment_link', $condition);

                                    }
                                }

                                if($actionID == "activated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "ref = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'payment_link', $columns, $values, $condition);

                                    }
                                }

                                if($actionID == "inactivated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['inactive', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "ref = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'payment_link', $columns, $values, $condition);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Payment Links '.$actionID, 'message' => 'The selected payment links have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Payment Links Failed', 'message' => 'No payment links selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "paymentLink-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'payment_link','WHERE ref = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                    if($response_brand['status'] == true){
                        $condition = "paymentLinkID = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'payment_link_field', $condition);

                        $condition = "ref = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'payment_link', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Payment Links Deleted', 'message' => 'The selected payment link have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "paymentLink-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $title = escape_string($_POST['title'] ?? '');
                    $quantity = escape_string($_POST['quantity'] ?? '');
                    $description = escape_string($_POST['description'] ?? '');
                    $currency = escape_string($_POST['currency'] ?? '');
                    $amount = escape_string($_POST['amount'] ?? '');
                    $expiry_date = escape_string($_POST['expiry_date'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');

                    $items = $_POST['items'] ?? [];

                    if($expiry_date !== ""){
                        if (dateformat($expiry_date, 'Y-m-d')) {

                        } else {
                            echo json_encode(['status' => "false", 'title' => 'Invalid expiry date format', 'message' => 'Please enter the expiry date in the correct format (DD/MM/YYYY).', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }
                    }else{
                        $expiry_date = "--";
                    }

                    if($title == "" || $quantity == "" || $description == "" || $currency == "" || $amount == "" || $status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $paymentLinkID = generateItemID(27, 27);

                        $product_info = json_encode([
                            'title' => $title,
                            'description' => $description
                        ]);

                        $columns = ['ref', 'brand_id', 'product_info', 'amount', 'quantity', 'currency', 'expired_date', 'status', 'created_date', 'updated_date'];
                        $values = [$paymentLinkID, $global_response_brand['response'][0]['brand_id'], $product_info, money_sanitize($amount), money_sanitize($quantity), $currency, $expiry_date, $status, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                        insertData($db_prefix.'payment_link', $columns, $values);

                        foreach ($items as $uniqueId => $item) {
                            $formType = $item['formType'] ?? '';
                            $fieldName = $item['fieldName'] ?? '';
                            $required = $item['required'] ?? '';
                            $fileExtensions = $item['fileExtensions'] ?? []; // array
                            $addOptions = $item['addOptions'] ?? [];         // array

                            $value = '--';

                            if ($formType === 'file') {
                                $value = implode(', ', $fileExtensions);
                            }
                            if ($formType === 'select' || $formType === 'checkbox' || $formType === 'radio') {
                                $value = implode(', ', $addOptions);
                            }

                            $columns = ['paymentLinkID', 'formType', 'fieldName', 'required', 'value', 'created_date', 'updated_date'];
                            $values = [$paymentLinkID, $formType, $fieldName, $required, $value, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'payment_link_field', $columns, $values);
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Payment Link Created', 'message' => 'The payment link has been created successfully.', 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "paymentLink-edit"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $paymentLinkID = escape_string($_POST['paymentLinkID'] ?? '');
                    $title = escape_string($_POST['title'] ?? '');
                    $quantity = escape_string($_POST['quantity'] ?? '');
                    $description = escape_string($_POST['description'] ?? '');
                    $currency = escape_string($_POST['currency'] ?? '');
                    $amount = escape_string($_POST['amount'] ?? '');
                    $expiry_date = escape_string($_POST['expiry_date'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');
                    $deletedItems = explode(',', $_POST['deleted_items'] ?? []);

                    $items = $_POST['items'] ?? [];

                    if($expiry_date !== ""){
                        if (dateformat($expiry_date, 'Y-m-d')) {

                        } else {
                            echo json_encode(['status' => "false", 'title' => 'Invalid expiry date format', 'message' => 'Please enter the expiry date in the correct format (DD/MM/YYYY).', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }
                    }else{
                        $expiry_date = "--";
                    }

                    if($paymentLinkID == "" || $title == "" || $quantity == "" || $description == "" || $currency == "" || $amount == "" || $status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $product_info = json_encode([
                            'title' => $title,
                            'description' => $description
                        ]);

                        $columns = ['product_info', 'amount', 'quantity', 'currency', 'expired_date', 'status', 'updated_date'];
                        $values = [$product_info, money_sanitize($amount), money_sanitize($quantity), $currency, $expiry_date, $status, getCurrentDatetime('Y-m-d H:i:s')];

                        $condition = "ref = '".$paymentLinkID."'"; 
                        
                        updateData($db_prefix.'payment_link', $columns, $values, $condition);

                        foreach ($deletedItems as $itemId) {
                            $condition = "id = '".$itemId."'"; 
                            
                            deleteData($db_prefix.'payment_link_field', $condition);
                        }

                        foreach ($items as $uniqueId => $item) {
                            $fieldID = $item['fieldID'] ?? '';
                            $formType = $item['formType'] ?? '';
                            $fieldName = $item['fieldName'] ?? '';
                            $required = $item['required'] ?? '';
                            $fileExtensions = $item['fileExtensions'] ?? []; // array
                            $addOptions = $item['addOptions'] ?? [];         // array

                            $value = '--';

                            if ($formType === 'file') {
                                $value = implode(', ', $fileExtensions);
                            }
                            if ($formType === 'select' || $formType === 'checkbox' || $formType === 'radio') {
                                $value = implode(', ', $addOptions);
                            }

                            if($fieldID == ""){
                                $columns = ['paymentLinkID', 'formType', 'fieldName', 'required', 'value', 'created_date', 'updated_date'];
                                $values = [$paymentLinkID, $formType, $fieldName, $required, $value, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                insertData($db_prefix.'payment_link_field', $columns, $values);
                            }else{
                                $columns = ['formType', 'fieldName', 'required', 'value', 'updated_date'];
                                $values = [$formType, $fieldName, $required, $value, getCurrentDatetime('Y-m-d H:i:s')];

                                $condition = "id = '".$fieldID."'"; 
                                
                                updateData($db_prefix.'payment_link_field', $columns, $values, $condition);
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Payment Link Updated', 'message' => 'The payment link has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "currency-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'currency_settings', 'view', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);


                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( code LIKE '%$search_input%' OR symbol LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'currency',' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query.' ORDER BY (code = "'.$global_brand_currency_code.'") DESC, id ASC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            if($global_brand_currency_code == $row['code']){
                                $rate = '1.00 '.$global_brand_currency_code.' = 1.00 '.$row['code'];
                            }else{
                                $rate = '1.00 '.$row['code'].' = '.money_round($row['rate'], 4).' '.$global_brand_currency_code;
                            }

                            if($global_brand_currency_code == $row['code']){
                                $default = 'true';
                            }else{
                                $default = 'false';
                            }

                            $response[] = [
                                "default" => $default,
                                "id"    => $row['id'],
                                "code"   => $row['code'],
                                "symbol"   => $row['symbol'],
                                "rate"  => $rate,
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'currency',' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "currency-edit"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'currency_settings', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $currency_id = escape_string($_POST['currency_id'] ?? '');
                    $currency_symbol = escape_string($_POST['currency_symbol'] ?? '');
                    $currency_rate = escape_string($_POST['currency_rate'] ?? '');

                    if($currency_id == "" || $currency_symbol == "" || $currency_rate == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $response = json_decode(getData($db_prefix.'currency','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND id ="'.$currency_id.'"'),true);
                        if($response['status'] == true){
                            $columns = ['symbol', 'rate', 'updated_date'];
                            $values = [$currency_symbol, money_sanitize($currency_rate), getCurrentDatetime('Y-m-d H:i:s')];
                            $condition = "id = '".$currency_id."'"; 
                            
                            updateData($db_prefix.'currency', $columns, $values, $condition);

                            echo json_encode(['status' => 'true', 'title' => 'Currency Updated', 'message' => 'The currency has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                        
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid Currency ID' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "currency-info-byID"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'currency_settings', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'currency','WHERE id = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '),true);
                    if($response_brand['status'] == true){
                        echo json_encode(['status' => 'true', 'code' => $response_brand['response'][0]['code'], 'symbol' => $response_brand['response'][0]['symbol'], 'rate' => money_sanitize($response_brand['response'][0]['rate']), 'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "currency-bulkImport"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'currency_settings', 'import', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $url = "https://gist.githubusercontent.com/ksafranski/2973986/raw/";

                    // Initialize cURL
                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_SSL_VERIFYPEER => false, // Only if SSL issues, not recommended for production
                    ]);

                    // Execute cURL
                    $response = curl_exec($ch);
                    curl_close($ch);

                    // Decode JSON into associative array
                    $currencies = json_decode($response, true);

                    // Check if JSON decoded successfully
                    if ($currencies === null) {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    // Loop through each currency
                    foreach ($currencies as $code => $details) {
                        $response = json_decode(getData($db_prefix.'currency','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND code ="'.$code.'"'),true);
                        if($response['status'] == false){
                            $columns = ['brand_id', 'code', 'symbol', 'rate', 'created_date', 'updated_date'];
                            $values = [$global_response_brand['response'][0]['brand_id'], $code, $details['symbol_native'], '0', getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'currency', $columns, $values);
                        }
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Currencies Imported', 'message' => 'All currency data has been imported successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "currency-rateSync"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'currency_settings', 'sync_rate', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'currency','WHERE id = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                    if($response_brand['status'] == true){

                            
                        $url = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/'.strtolower($global_brand_currency_code).'.json';

                        $ch = curl_init($url);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT => 10,
                            CURLOPT_SSL_VERIFYPEER => false,
                        ]);

                        $response = curl_exec($ch);
                        curl_close($ch);

                        $data = json_decode($response, true);

                        if (!isset($data[strtolower($global_brand_currency_code)])) {
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid default currency' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $rates = $data[strtolower($global_brand_currency_code)];
                        

                        foreach ($rates as $currency => $rate) {

                            if ($currency === strtolower($global_brand_currency_code)) {
                                continue;
                            }

                            if ($rate <= 0) {
                                continue;
                            }

                            if(strtolower($response_brand['response'][0]['code']) == $currency){
                                $columns = ['rate', 'updated_date'];
                                $values = [money_div(1, money_sanitize(sprintf('%.14f',$rate))), getCurrentDatetime('Y-m-d H:i:s')];

                                $condition = 'brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND id = "'.$ItemID.'"'; 
                                
                                updateData($db_prefix.'currency', $columns, $values, $condition);

                                break;
                            }
                        }
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Currency Rate Updated', 'message' => 'The selected currency rate have been updated successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "currency-bulk-rateSync"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'currency_settings', 'sync_rate', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $url = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/'.strtolower($global_brand_currency_code).'.json';

                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_SSL_VERIFYPEER => false,
                    ]);

                    $response = curl_exec($ch);
                    curl_close($ch);

                    $data = json_decode($response, true);

                    if (!isset($data[strtolower($global_brand_currency_code)])) {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid default currency' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $rates = $data[strtolower($global_brand_currency_code)];

                    foreach ($rates as $currency => $rate) {

                        if ($currency === strtolower($global_brand_currency_code)) {
                            continue;
                        }

                        if ($rate <= 0) {
                            continue;
                        }

                        $columns = ['rate', 'updated_date'];
                        $values = [money_div(1, money_sanitize(sprintf('%.14f',$rate))), getCurrentDatetime('Y-m-d H:i:s')];

                        $condition = 'brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND code = "'.$currency.'"'; 
                        
                        updateData($db_prefix.'currency', $columns, $values, $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Currencies Rate Updated', 'message' => 'The selected currencies rate have been updated successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "geneal-application-settings"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_general', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $homepageRedirect = escape_string($_POST['homepageRedirect'] ?? '');
                    $adminPath = escape_string($_POST['adminPath'] ?? '');
                    $invoicePath = escape_string($_POST['invoicePath'] ?? '');
                    $paymentLinkPath = escape_string($_POST['paymentLinkPath'] ?? '');
                    $paymentPath = escape_string($_POST['paymentPath'] ?? '');
                    $cronPath = escape_string($_POST['cronPath'] ?? '');
                    $default_timezone = escape_string($_POST['default_timezone'] ?? '');
                    $webhook_attempts_limit = escape_string($_POST['webhook_attempts_limit'] ?? '');

                    set_env('geneal-application-settings-homepageRedirect', $homepageRedirect);
                    set_env('geneal-application-settings-adminPath', $adminPath);
                    set_env('geneal-application-settings-invoicePath', $invoicePath);
                    set_env('geneal-application-settings-paymentLinkPath', $paymentLinkPath);
                    set_env('geneal-application-settings-paymentPath', $paymentPath);
                    set_env('geneal-application-settings-cronPath', $cronPath);
                    set_env('geneal-application-settings-default_timezone', $default_timezone);
                    set_env('geneal-application-settings-webhook_attempts_limit', $webhook_attempts_limit);

                    echo json_encode(['status' => 'true', 'title' => 'Settings Updated', 'message' => 'The application settings has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "faq-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'faq_settings', 'view', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( title LIKE '%$search_input%' OR description LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'faq',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $response[] = [
                                "id"    => $row['id'],
                                "title"   => $row['title'],
                                "description"   => $row['description'],
                                "status"   => $row['status'],
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'faq',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "faq-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'faq_settings', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $faq_title = escape_string($_POST['faq_title'] ?? '');
                    $faq_description = escape_string($_POST['faq_description'] ?? '');
                    $faq_status = escape_string($_POST['faq_status'] ?? '');

                    if($faq_title == "" || $faq_description == "" || $faq_status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $columns = ['brand_id', 'title', 'description', 'status', 'created_date', 'updated_date'];
                        $values = [$global_response_brand['response'][0]['brand_id'], $faq_title, $faq_description, $faq_status, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                        insertData($db_prefix.'faq', $columns, $values);

                        echo json_encode(['status' => 'true', 'title' => 'FAQ Created', 'message' => 'The faq has been created successfully.', 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "faq-info-byID"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'faq_settings', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'faq','WHERE id = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '),true);
                    if($response_brand['status'] == true){
                        echo json_encode(['status' => 'true', 'title' => $response_brand['response'][0]['title'], 'description' => $response_brand['response'][0]['description'], 'fstatus' => $response_brand['response'][0]['status'], 'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "faq-edit"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'faq_settings', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $faq_id = escape_string($_POST['faq_id'] ?? '');
                    $faq_title = escape_string($_POST['faq_title'] ?? '');
                    $faq_description = escape_string($_POST['faq_description'] ?? '');
                    $faq_status = escape_string($_POST['faq_status'] ?? '');

                    if($faq_id == "" || $faq_title == "" || $faq_description == "" || $faq_status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $response_faq = json_decode(getData($db_prefix.'faq','WHERE id = "'.$faq_id.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                        if($response_faq['status'] == true){

                            $columns = [ 'title', 'description', 'status', 'updated_date'];
                            $values = [$faq_title, $faq_description, $faq_status, getCurrentDatetime('Y-m-d H:i:s')];

                            $condition = "id = '".$faq_id."'"; 
                            
                            updateData($db_prefix.'faq', $columns, $values, $condition);

                            echo json_encode(['status' => 'true', 'title' => 'FAQ Updated', 'message' => 'The faq has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "faq-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'faq_settings', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'faq','WHERE id = "'.$itemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'faq_settings', 'delete', $global_user_response['response'][0]['role'])) {
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'faq', $condition);
                                    }
                                }

                                if($actionID == "activated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'faq_settings', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'faq', $columns, $values, $condition);

                                    }
                                }

                                if($actionID == "inactivated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'faq_settings', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['inactive', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'faq', $columns, $values, $condition);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'FAQ '.$actionID, 'message' => 'The selected faqs have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'FAQ Failed', 'message' => 'No faqs selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "faq-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'faq_settings', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'faq','WHERE id = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                    if($response_brand['status'] == true){
                        $condition = "id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'faq', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'FAQ Deleted', 'message' => 'The selected faq have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "api-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'view', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $api_name = escape_string($_POST['api_name'] ?? '');
                    $apiExpiryDate = escape_string($_POST['apiExpiryDate'] ?? '');
                    $api_status = escape_string($_POST['api_status'] ?? '');
                    $scopes = $_POST['scopes'] ?? [];

                    if($api_name == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $response = json_decode(getData($db_prefix.'api','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND name ="'.$api_name.'"'),true);
                        if($response['status'] == true){
                            echo json_encode(['status' => 'false', 'title' => 'API Name Already Exists', 'message' => 'This API name is already in use. Please choose a different name.' , 'csrf_token' => $new_csrf_token]);
                        }else{
                            if($apiExpiryDate !== ""){
                                if (dateformat($apiExpiryDate, 'Y-m-d')) {

                                } else {
                                    echo json_encode(['status' => "false", 'title' => 'Invalid expiry date format', 'message' => 'Please enter the expiry date in the correct format (DD/MM/YYYY).', 'csrf_token' => $new_csrf_token]);
                                    exit();
                                }
                            }else{
                                $apiExpiryDate = "--";
                            }

                            $api_key = bin2hex(random_bytes(25));
                            $scopes_json = json_encode($scopes);

                            $columns = ['brand_id', 'name', 'api_key', 'expired_date', 'status', 'api_scopes', 'created_date', 'updated_date'];
                            $values = [$global_response_brand['response'][0]['brand_id'], $api_name, $api_key, $apiExpiryDate, $api_status, $scopes_json, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'api', $columns, $values);

                            echo json_encode(['status' => 'true', 'title' => 'Api Created', 'message' => 'The api has been created successfully.', 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "api-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'view', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( name LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'api',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            if($row['expired_date'] == "--"){
                                $status = $row['status'];
                            }else{
                                if (isExpired($row['expired_date'])) {
                                    $status = 'expired';
                                } else {
                                    $status = $row['status'];
                                }
                            }

                            $response[] = [
                                "id"    => $row['id'],
                                "name"  => $row['name'],
                                "api_key"  => $row['api_key'],
                                "expired_date"  => $row['expired_date'],
                                "status"  => $status,
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'api',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "api-info-byID"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'view', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'api','WHERE id = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '),true);
                    if($response_brand['status'] == true){
                        echo json_encode(['status' => 'true', 'name' => $response_brand['response'][0]['name'], 'expired_date' => $response_brand['response'][0]['expired_date'], 'api_scopes' => json_decode($response_brand['response'][0]['api_scopes'], true), 'astatus' => $response_brand['response'][0]['status'], 'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "api-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'view', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'api','WHERE id = "'.$itemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'delete', $global_user_response['response'][0]['role'])) {
                                    
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'api', $condition);

                                    }
                                }

                                if($actionID == "activated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'api', $columns, $values, $condition);

                                    }
                                }

                                if($actionID == "inactivated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['inactive', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'api', $columns, $values, $condition);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Api Key '.$actionID, 'message' => 'The selected api key have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Api Key Failed', 'message' => 'No api selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "api-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'view', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'api','WHERE id = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '),true);
                    if($response_brand['status'] == true){
                        $condition = "id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'api', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Api Key Deleted', 'message' => 'The selected api key have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "api-edit"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'view', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'api_settings', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $api_id = escape_string($_POST['api_id'] ?? '');
                    $api_name = escape_string($_POST['api_name'] ?? '');
                    $apiExpiryDate = escape_string($_POST['apiExpiryDate'] ?? '');
                    $api_status = escape_string($_POST['api_status'] ?? '');
                    $scopes = $_POST['scopes'] ?? [];

                    if($api_name == "" || $api_status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $responseApi = json_decode(getData($db_prefix.'api','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND id ="'.$api_id.'"'),true);
                        if($responseApi['status'] == true){
                            $response = json_decode(getData($db_prefix.'api','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND name ="'.$api_name.'"'),true);
                            if($response['status'] == true){
                                if($response['response'][0]['id'] == $api_id){

                                }else{
                                    echo json_encode(['status' => 'false', 'title' => 'API Name Already Exists', 'message' => 'This API name is already in use. Please choose a different name.' , 'csrf_token' => $new_csrf_token]);
                                    exit();
                                }
                            }

                            if($apiExpiryDate !== ""){
                                if (dateformat($apiExpiryDate, 'Y-m-d')) {

                                } else {
                                    echo json_encode(['status' => "false", 'title' => 'Invalid expiry date format', 'message' => 'Please enter the expiry date in the correct format (DD/MM/YYYY).', 'csrf_token' => $new_csrf_token]);
                                    exit();
                                }
                            }else{
                                $apiExpiryDate = "--";
                            }

                            $scopes_json = json_encode($scopes);

                            $columns = ['name', 'expired_date', 'status', 'api_scopes', 'updated_date'];
                            $values = [$api_name, $apiExpiryDate, $api_status, $scopes_json, getCurrentDatetime('Y-m-d H:i:s')];

                            $condition = "id = '".$api_id."'"; 
                            
                            updateData($db_prefix.'api', $columns, $values, $condition);

                            echo json_encode(['status' => 'true', 'title' => 'Api Updated', 'message' => 'The api has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "general-setting"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', 'view', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $site_name = escape_string($_POST['site_name'] ?? '');
                    $default_timezone = escape_string($_POST['default_timezone'] ?? '');
                    $default_language = escape_string($_POST['default_language'] ?? '');
                    $default_currency = escape_string($_POST['default_currency'] ?? '');
                    $payment_tolerance = escape_string($_POST['payment_tolerance'] ?? '');

                    $street_address = escape_string($_POST['street_address'] ?? '');
                    $city_town = escape_string($_POST['city_town'] ?? '');
                    $postal_code = escape_string($_POST['postal_code'] ?? '');
                    $country = escape_string($_POST['country'] ?? '');

                    $support_phone_number = escape_string($_POST['support_phone_number'] ?? '');
                    $support_email_address = escape_string($_POST['support_email_address'] ?? '');
                    $support_website = escape_string($_POST['support_website'] ?? '');
                    $whatsapp_number = escape_string($_POST['whatsapp_number'] ?? '');
                    $telegram = escape_string($_POST['telegram'] ?? '');
                    $facebook_messenger = escape_string($_POST['facebook_messenger'] ?? '');
                    $facebook_page = escape_string($_POST['facebook_page'] ?? '');
                    $autoExchange = escape_string($_POST['autoExchange'] ?? '');

                    if($autoExchange == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $max_file_size = 2 * 1024 * 1024; 
                        
                        $branding_favicon = json_decode(uploadImage($_FILES['favicon']?? null, $max_file_size), true);
                        if($branding_favicon['status'] == true){
                            $branding_favicon = $site_url.'pp-media/storage/'.$branding_favicon['file'];
                            
                            deleteImage($global_response_brand['response'][0]['favicon']);
                        }else{
                            $branding_favicon = $global_response_brand['response'][0]['favicon'];
                        }

                        $branding_primary_logo = json_decode(uploadImage($_FILES['primary_logo']?? null, $max_file_size), true);
                        if($branding_primary_logo['status'] == true){
                            $branding_primary_logo = $site_url.'pp-media/storage/'.$branding_primary_logo['file'];
                            
                            deleteImage($global_response_brand['response'][0]['logo']);
                        }else{
                            $branding_primary_logo = $global_response_brand['response'][0]['logo'];
                        }

                        if($site_name == "" || $default_timezone == "" || $default_language == "" || $default_currency == "" || $payment_tolerance == ""){
                            echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $columns = ['autoExchange', 'favicon', 'logo', 'name', 'timezone', 'language', 'currency_code', 'payment_tolerance', 'street_address', 'city_town', 'postal_code', 'country', 'support_phone_number', 'support_email_address', 'support_website', 'whatsapp_number', 'telegram', 'facebook_messenger', 'facebook_page', 'updated_date'];
                        $values = [$autoExchange, $branding_favicon, $branding_primary_logo, $site_name, $default_timezone, $default_language, $default_currency, money_sanitize($payment_tolerance), $street_address, $city_town, $postal_code, $country, $support_phone_number, $support_email_address, $support_website, $whatsapp_number, $telegram, $facebook_messenger, $facebook_page, getCurrentDatetime('Y-m-d H:i:s')];
                        $condition = "brand_id = '".$global_response_brand['response'][0]['brand_id']."'"; 
                        
                        updateData($db_prefix.'brands', $columns, $values, $condition);

                        echo json_encode(['status' => 'true', 'title' => 'Brand Setting Updated', 'message' => 'The brand setting has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "device-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        if ($filter_status === 'connected') {
                            $where[] = "updated_date >= (NOW() - INTERVAL 6 MINUTE)";
                        } elseif ($filter_status === 'disconnected') {
                            $where[] = "updated_date < (NOW() - INTERVAL 6 MINUTE)";
                        }
                    }
                    
                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */


                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( name LIKE '%$search_input%' OR model LIKE '%$search_input%' OR android_level LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'device',' WHERE '.$where_sql.' status ="used" '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $response[] = [
                                "id"   => $row['device_id'],
                                "name"   => $row['name'],
                                "model"   => $row['model'],
                                "android_level"   => $row['android_level'],
                                "status"   => $row['status'],
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "last_sync"     => ($row['last_sync'] == "--") ? '' : convertUTCtoUserTZ($row['last_sync'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'device',' WHERE '.$where_sql.' status ="used" '.$sql_query),true);


                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "device-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'device','WHERE device_id = "'.$ItemID.'"'),true);
                    if($response_brand['status'] == true){
                        $condition = "device_id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'device', $condition);

                        $condition = "device_id = '".$response_brand['response'][0]['device_id']."'"; 
                        
                        deleteData($db_prefix.'balance_verification', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Device Deleted', 'message' => 'The selected device have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "device-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'device','WHERE device_id = "'.$itemID.'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'delete', $global_user_response['response'][0]['role'])) {
                                    
                                        $condition = "device_id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'device', $condition);

                                        $condition = "device_id = '".$response_brand['response'][0]['device_id']."'"; 
                                        
                                        deleteData($db_prefix.'balance_verification', $condition);
                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Devices '.$actionID, 'message' => 'The selected devices have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No devices selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "device-connect-info"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'connect', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $otp = generateItemID();

                    $response_brand = json_decode(getData($db_prefix.'device','WHERE status = "processing" AND d_id = "'.$pp_admin.'"'),true);
                    if($response_brand['status'] == true){
                        $columns = ['otp', 'updated_date'];
                        $values = [$otp, getCurrentDatetime('Y-m-d H:i:s')];
                        $condition = "id = '".$response_brand['response'][0]['id']."'"; 
                        
                        updateData($db_prefix.'device', $columns, $values, $condition);

                        echo json_encode(['status' => 'true', 'otp' => $otp, 'csrf_token' => $new_csrf_token]);
                    }else{
                        $device_id = generateItemID();

                        $columns = ['d_id', 'device_id', 'otp', 'created_date', 'updated_date'];
                        $values = [$pp_admin, $device_id, $otp, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                        insertData($db_prefix.'device', $columns, $values);

                        echo json_encode(['status' => 'true', 'otp' => $otp, 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "balance-verification-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'balance_verification_for', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $d_id = escape_string($_POST['d_id'] ?? '');
                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }
                    
                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */


                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( sender_key LIKE '%$search_input%' OR type LIKE '%$search_input%' OR current_balance LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'balance_verification',' WHERE '.$where_sql.' device_id = "'.$d_id.'" AND status NOT IN ("--") '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $provider = senderWhitelist(null, $row['sender_key']);

                            if ($provider) {
                                $payment_method = $provider['name'];     // Ipay
                                $currency       = $provider['currency']; // BDT
                            }else{
                                $payment_method = '';     // Ipay
                                $currency       = ''; // BDT
                            }

                            $response[] = [
                                "id"   => $row['id'],
                                "simslot"   => $row['simslot'],
                                "payment_method"   => $payment_method,
                                "payment_type"   => $row['type'],
                                "current_balance"   => money_round($row['current_balance'], 2),
                                "status"   => $row['status'],
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'balance_verification',' WHERE '.$where_sql.' status NOT IN ("--") '.$sql_query),true);


                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "balance-verification-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'balance_verification','WHERE id = "'.$itemID.'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'balance_verification_for', $global_user_response['response'][0]['role'])) {
                                    
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'balance_verification', $condition);

                                    }
                                }

                                if($actionID == "activated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'balance_verification_for', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'balance_verification', $columns, $values, $condition);

                                    }
                                }

                                if($actionID == "inactivated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'balance_verification_for', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['inactive', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'balance_verification', $columns, $values, $condition);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Balance verifications '.$actionID, 'message' => 'The selected balance verifications have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No balance verifications selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "balance-verification-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'balance_verification_for', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'balance_verification','WHERE id = "'.$ItemID.'"'),true);
                    if($response_brand['status'] == true){
                        $condition = "id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'balance_verification', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Balance Verification Deleted', 'message' => 'The selected balance verification have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "balance-verification-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'balance_verification_for', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $d_id = escape_string($_POST['d_id'] ?? '');
                    $sender_key = escape_string($_POST['sender_key'] ?? '');
                    $payment_type = escape_string($_POST['payment_type'] ?? '');
                    $simslot = escape_string($_POST['simslot'] ?? '');
                    $current_balance = escape_string($_POST['current_balance'] ?? '');
                    $balance_verification_status = escape_string($_POST['balance_verification_status'] ?? '');

                    if($sender_key == "" || $payment_type == "" || $simslot == "" || $current_balance == "" || $balance_verification_status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if($balance_verification_status == "active" || $balance_verification_status == "inactive"){

                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $response = json_decode(getData($db_prefix.'device','WHERE device_id ="'.$d_id.'" AND status ="used"'),true);
                        if($response['status'] == true){
                            $responseCheck = json_decode(getData($db_prefix.'balance_verification','WHERE device_id ="'.$d_id.'" AND sender_key ="'.$sender_key.'" AND type ="'.$payment_type.'"'),true);
                            if($responseCheck['status'] == true){
                                echo json_encode(['status' => 'false', 'title' => 'Duplicate Entry', 'message' => 'A record with this info already exists.' , 'csrf_token' => $new_csrf_token]);
                                exit();
                            }

                            $columns = ['device_id', 'sender_key', 'type', 'current_balance', 'simslot', 'status', 'created_date', 'updated_date'];
                            $values = [$d_id, $sender_key, $payment_type, money_sanitize($current_balance), $simslot, $balance_verification_status, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'balance_verification', $columns, $values);

                            echo json_encode(['status' => 'true', 'title' => 'Balance Verification Created', 'message' => 'The balance verification has been created successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "balance-verification-iupdate"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'balance_verification_for', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');
                    $balance = escape_string($_POST['balance'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'balance_verification','WHERE id = "'.$ItemID.'"'),true);
                    if($response_brand['status'] == true){
                        if($balance == ""){
                            $balance = 0;
                        }

                        $columns = ['current_balance', 'updated_date'];
                        $values = [money_sanitize($balance),  getCurrentDatetime('Y-m-d H:i:s')];
                        $condition = "id = '".$ItemID."'"; 
                        
                        updateData($db_prefix.'balance_verification', $columns, $values, $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Balance Verification Updated', 'message' => 'The selected balance verification have been updated successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "balance-verification-info-byID"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'balance_verification_for', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'balance_verification','WHERE id = "'.$ItemID.'"'),true);
                    if($response_brand['status'] == true){
                        echo json_encode(['status' => 'true', 'sender_key' => $response_brand['response'][0]['sender_key'], 'type' => $response_brand['response'][0]['type'], 'current_balance' => money_round($response_brand['response'][0]['current_balance'], 2), 'simslot' => $response_brand['response'][0]['simslot'], 'istatus' => $response_brand['response'][0]['status'], 'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "balance-verification-update"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'device', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'device', 'balance_verification_for', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $itemID = escape_string($_POST['itemID'] ?? '');
                    $sender_key = escape_string($_POST['sender_key'] ?? '');
                    $payment_type = escape_string($_POST['payment_type'] ?? '');
                    $simslot = escape_string($_POST['simslot'] ?? '');
                    $current_balance = escape_string($_POST['current_balance'] ?? '');
                    $balance_verification_status = escape_string($_POST['balance_verification_status'] ?? '');

                    if($sender_key == "" || $payment_type == "" || $simslot == "" || $current_balance == "" || $balance_verification_status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if($balance_verification_status == "active" || $balance_verification_status == "inactive"){

                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $response = json_decode(getData($db_prefix.'balance_verification','WHERE id ="'.$itemID.'"'),true);
                        if($response['status'] == true){
                            $responseCheck = json_decode(getData($db_prefix.'balance_verification','WHERE device_id ="'.$response['response'][0]['device_id'].'" AND sender_key ="'.$sender_key.'" AND type ="'.$payment_type.'"'),true);
                            if($responseCheck['status'] == true){
                                if($responseCheck['response'][0]['id'] == $itemID){

                                }else{
                                    echo json_encode(['status' => 'false', 'title' => 'Duplicate Entry', 'message' => 'A record with this info already exists.' , 'csrf_token' => $new_csrf_token]);
                                    exit();
                                }
                            }

                            $columns = ['sender_key', 'type', 'current_balance', 'simslot', 'status', 'updated_date'];
                            $values = [$sender_key, $payment_type, money_sanitize($current_balance), $simslot, $balance_verification_status, getCurrentDatetime('Y-m-d H:i:s')];

                            $condition = "id = '".$itemID."'"; 
                            
                            updateData($db_prefix.'balance_verification', $columns, $values, $condition);

                            echo json_encode(['status' => 'true', 'title' => 'Balance Verification Updated', 'message' => 'The balance verification has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "sms-data-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    $tabType = escape_string($_POST['tabType'] ?? '');

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($tabType !== "all") {
                        $where[] = "status = '{$tabType}'";
                    }

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */


                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( sender_key LIKE '%$search_input%' OR amount LIKE '%$search_input%' OR currency LIKE '%$search_input%' OR trx_id LIKE '%$search_input%' OR message LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'sms_data',' WHERE '.$where_sql.' device_id NOT IN ("00") AND status NOT IN ("error") '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $device_name = '';
                            $response_device = json_decode(getData($db_prefix.'device',' WHERE device_id = "'.$row['device_id'].'"'),true);
                            if($response_device['status'] == true){
                                $device_name = $response_device['response'][0]['name'];
                            }

                            $provider = senderWhitelist(null, $row['sender_key']);

                            if ($provider) {
                                $payment_method = $provider['name'];     // Ipay
                                $currency       = $provider['currency']; // BDT
                            }else{
                                $payment_method = '';     // Ipay
                                $currency       = ''; // BDT
                            }

                            $response[] = [
                                "id"   => $row['id'],
                                "device"   => $device_name,
                                "payment_method"   => $payment_method,
                                "type"   => ($row['type'] === '--') ? '' : $row['type'],
                                "mobileNumber"   => ($row['number'] === '--') ? '' : $row['number'],
                                "transaction_id"   => ($row['trx_id'] === '--') ? '' : $row['trx_id'],
                                "amount"  => ($row['currency'] === '--') ? '' : $row['currency'] .' '. money_round($row['amount'], 2),
                                "balance" => ($row['currency'] === '--') ? '' : $row['currency'] .' '. money_round($row['balance'], 2),
                                "status"   => $row['status'],
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'sms_data',' WHERE '.$where_sql.' device_id NOT IN ("00") AND status NOT IN ("error") '.$sql_query),true);


                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "sms-data-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'sms_data','WHERE id = "'.$ItemID.'"'),true);
                    if($response_brand['status'] == true){
                        $condition = "id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'sms_data', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'SMS Data Deleted', 'message' => 'The selected sms data have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "sms-data-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'sms_data','WHERE id = "'.$itemID.'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', 'delete', $global_user_response['response'][0]['role'])) {
                                    
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'sms_data', $condition);

                                    }
                                }

                                if($actionID !== "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = [$actionID, getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'sms_data', $columns, $values, $condition);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'SMS Data '.$actionID, 'message' => 'The selected sms datas have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No customers selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }



            if($action == "sms-data-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $device_id = escape_string($_POST['device'] ?? '');
                    $entry_type = escape_string($_POST['entry_type'] ?? '');
                    $sender_key = escape_string($_POST['sender_key'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');
                    $message = escape_string($_POST['message'] ?? '');
                    $type = escape_string($_POST['type'] ?? '');
                    $amount = escape_string($_POST['amount'] ?? '');
                    $phone_number = escape_string($_POST['phone_number'] ?? '');
                    $transaction_id = escape_string($_POST['transaction_id'] ?? '');
                    $currency = escape_string($_POST['currency'] ?? '');

                    if($entry_type == "" || $sender_key == "" || $status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if($entry_type == "automatic"){
                            if($message == ""){
                                echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                                exit();
                            }else{
                                $result = MFSMessageVerified($sender_key, $message);

                                if ($result === false) {
                                    echo json_encode(['status' => "false", 'title' => 'Invalid or unknown MFS message', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                                    exit();
                                } else {
                                    $type = escape_string($result['type'] ?? '');
                                    $amount = escape_string($result['amount'] ?? '');
                                    $balance = escape_string($result['balance'] ?? '');
                                    $phone_number = escape_string($result['sender'] ?? '');
                                    $transaction_id = escape_string($result['trxid'] ?? '');

                                    if($type == "" || $amount == "" || $phone_number == "" || $transaction_id == ""){
                                        echo json_encode(['status' => "false", 'title' => 'Invalid or unknown MFS message', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                                        exit();
                                    }
                                    $params = [ ':sender_key' => $sender_key, ':trx_id' => $transaction_id ];

                                    $response = json_decode(getData($db_prefix.'sms_data','WHERE sender_key = :sender_key AND trx_id = :trx_id', '* FROM', $params),true);
                                    if($response['status'] == false){
                                        if($device_id == ""){
                                            $device_id = '--';
                                        }

                                        /*$response_balance_verification = json_decode(getData($db_prefix.'balance_verification','WHERE device_id ="'.$device_id.'" AND sender_key = "'.$sender_key.'" AND payment_type = "'.$type.'"'),true);
                                        if($response_balance_verification['status'] == true){
                                            $balance = $response_balance_verification['response'][0]['current_balance']+number_validator($amount);

                                            $columns = ['current_balance', 'updated_date'];
                                            $values = [$balance, getCurrentDatetime('Y-m-d H:i:s')];
                                            $condition = "id = '".$response_balance_verification['response'][0]['id']."'"; 
                                            
                                            updateData($db_prefix.'balance_verification', $columns, $values, $condition);
                                        }*/

                                        $columns = ['device_id', 'sender_key', 'number', 'amount', 'currency', 'trx_id', 'balance', 'type', 'entry_type', 'status', 'message', 'created_date', 'updated_date'];
                                        $values = [$device_id, $sender_key, $phone_number, money_sanitize($amount), $currency, $transaction_id, money_sanitize($balance), $type, $entry_type, $status, $message, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                        insertData($db_prefix.'sms_data', $columns, $values);

                                        echo json_encode(['status' => 'true', 'title' => 'SMS Data Created', 'message' => 'The sms data has been created successfully.'.$amount, 'csrf_token' => $new_csrf_token]);
                                    }else{
                                        echo json_encode(['status' => 'false', 'title' => 'Duplicate Transaction', 'message' => 'The provided Transaction ID already exists in our system.', 'csrf_token' => $new_csrf_token]); 
                                    }
                                }
                            }
                        }else{
                            if($type == "" || $amount == "" || $phone_number == "" || $transaction_id == ""){
                                echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                                exit();
                            }else{
                                $params = [ ':sender_key' => $sender_key, ':trx_id' => $transaction_id ];

                                $response = json_decode(getData($db_prefix.'sms_data','WHERE sender_key = :sender_key AND trx_id = :trx_id', '* FROM', $params),true);
                                if($response['status'] == false){
                                    if($device_id == ""){
                                        $device_id = '--';
                                    }

                                    $balance = 0;

                                    /*$response_balance_verification = json_decode(getData($db_prefix.'balance_verification','WHERE device_id ="'.$device_id.'" AND sender_key = "'.$sender_key.'" AND payment_type = "'.$type.'"'),true);
                                    if($response_balance_verification['status'] == true){
                                        $balance = $response_balance_verification['response'][0]['current_balance']+number_validator($amount);

                                        $columns = ['current_balance', 'updated_date'];
                                        $values = [$balance, getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$response_balance_verification['response'][0]['id']."'"; 
                                        
                                        updateData($db_prefix.'balance_verification', $columns, $values, $condition);
                                    }*/

                                    $columns = ['device_name', 'sender_key', 'number', 'amount', 'currency', 'trx_id', 'balance', 'type', 'entry_type', 'status', 'message', 'created_date', 'updated_date'];
                                    $values = [$device, $sender_key, $phone_number, money_sanitize($amount), $currency, $transaction_id, money_sanitize($balance), $type, $entry_type, $status, $message, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                    insertData($db_prefix.'sms_data', $columns, $values);

                                    echo json_encode(['status' => 'true', 'title' => 'SMS Data Created', 'message' => 'The sms data has been created successfully.', 'csrf_token' => $new_csrf_token]);
                                }else{
                                    echo json_encode(['status' => 'false', 'title' => 'Duplicate Transaction', 'message' => 'The provided Transaction ID already exists in our system.', 'csrf_token' => $new_csrf_token]); 
                                }
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "sms-data-info-byID"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'sms_data','WHERE id = "'.$ItemID.'"'),true);
                    if($response_brand['status'] == true){
                        echo json_encode(['status' => 'true', 'device_id' => $response_brand['response'][0]['device_id'], 'sender_key' => $response_brand['response'][0]['sender_key'], 'number' => $response_brand['response'][0]['number'], 'amount' => money_round($response_brand['response'][0]['amount'], 2),  'currency' => $response_brand['response'][0]['currency'],  'trx_id' => $response_brand['response'][0]['trx_id'],  'balance' => money_round($response_brand['response'][0]['balance'], 2),  'message' => $response_brand['response'][0]['message'],  'type' => $response_brand['response'][0]['type'],  'entry_type' => $response_brand['response'][0]['entry_type'],  'istatus' => $response_brand['response'][0]['status'],  'reason' => $response_brand['response'][0]['reason'], 'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "sms-data-edit"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'sms_data', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $itemid = escape_string($_POST['itemid'] ?? '');
                    $device_id = escape_string($_POST['device'] ?? '');
                    $sender_key = escape_string($_POST['sender_key'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');
                    $message = escape_string($_POST['message'] ?? '');
                    $type = escape_string($_POST['type'] ?? '');
                    $amount = escape_string($_POST['amount'] ?? '');
                    $phone_number = escape_string($_POST['phone_number'] ?? '');
                    $transaction_id = escape_string($_POST['transaction_id'] ?? '');
                    $currency = escape_string($_POST['currency'] ?? '');

                    $responseV = json_decode(getData($db_prefix.'sms_data','WHERE id ="'.$itemid.'"'),true);
                    if($responseV['status'] == false){
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }else{
                        $entry_type = $responseV['response'][0]['entry_type'];
                    }

                    if($entry_type == "" || $sender_key == "" || $status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if($entry_type == "automatic"){
                            if($message == ""){
                                echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                                exit();
                            }else{
                                $result = MFSMessageVerified($sender_key, $message);

                                if ($result === false) {
                                    echo json_encode(['status' => "false", 'title' => 'Invalid or unknown MFS message', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                                    exit();
                                } else {
                                    $type = escape_string($result['type'] ?? '');
                                    $amount = escape_string($result['amount'] ?? '');
                                    $balance = escape_string($result['balance'] ?? '');
                                    $phone_number = escape_string($result['sender'] ?? '');
                                    $transaction_id = escape_string($result['trxid'] ?? '');

                                    if($type == "" || $amount == "" || $phone_number == "" || $transaction_id == ""){
                                        echo json_encode(['status' => "false", 'title' => 'Invalid or unknown MFS message', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                                        exit();
                                    }

                                    $params = [ ':sender_key' => $sender_key, ':trx_id' => $transaction_id ];

                                    $response = json_decode(getData($db_prefix.'sms_data','WHERE sender_key = :sender_key AND trx_id = :trx_id', '* FROM', $params),true);
                                    if($response['status'] == false){
                                        if($response['response'][0]['id'] == $itemid){

                                        }else{
                                            echo json_encode(['status' => 'false', 'title' => 'Duplicate Transaction', 'message' => 'The provided Transaction ID already exists in our system.', 'csrf_token' => $new_csrf_token]); 
                                            exit();
                                        }
                                    }

                                    if($device_id == ""){
                                        $device_id = '--';
                                    }

                                    /*$response_balance_verification = json_decode(getData($db_prefix.'balance_verification','WHERE device_id ="'.$device_id.'" AND sender_key = "'.$sender_key.'" AND payment_type = "'.$type.'"'),true);
                                    if($response_balance_verification['status'] == true){
                                        $balance = $response_balance_verification['response'][0]['current_balance']-$responseV['response'][0]['amount']+number_validator($amount);

                                        $columns = ['current_balance', 'updated_date'];
                                        $values = [$balance, getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$response_balance_verification['response'][0]['id']."'"; 
                                        
                                        updateData($db_prefix.'balance_verification', $columns, $values, $condition);
                                    }*/

                                    $columns = ['device_id', 'sender_key', 'number', 'amount', 'currency', 'trx_id', 'balance', 'type', 'entry_type', 'status', 'message', 'updated_date'];
                                    $values = [$device_id, $sender_key, $phone_number, money_sanitize($amount), $currency, $transaction_id, money_sanitize($balance), $type, $entry_type, $status, $message, getCurrentDatetime('Y-m-d H:i:s')];

                                    $condition = "id = '".$itemid."'"; 
                                    
                                    updateData($db_prefix.'sms_data', $columns, $values, $condition);

                                    echo json_encode(['status' => 'true', 'title' => 'SMS Data Updated', 'message' => 'The sms data has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                                }
                            }
                        }else{
                            if($type == "" || $amount == "" || $phone_number == "" || $transaction_id == ""){
                                echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                                exit();
                            }else{
                                $params = [ ':sender_key' => $sender_key, ':trx_id' => $transaction_id ];

                                $response = json_decode(getData($db_prefix.'sms_data','WHERE sender_key = :sender_key AND trx_id = :trx_id', '* FROM', $params),true);
                                if($response['status'] == false){
                                    if($response['response'][0]['id'] == $itemid){

                                    }else{
                                        echo json_encode(['status' => 'false', 'title' => 'Duplicate Transaction', 'message' => 'The provided Transaction ID already exists in our system.', 'csrf_token' => $new_csrf_token]); 
                                        exit();
                                    }
                                }

                                if($device_id == ""){
                                    $device_id = '--';
                                }

                                $balance = 0;

                                /*$response_balance_verification = json_decode(getData($db_prefix.'balance_verification','WHERE device_id ="'.$device_id.'" AND sender_key = "'.$sender_key.'" AND payment_type = "'.$type.'"'),true);
                                if($response_balance_verification['status'] == true){
                                    $balance = $response_balance_verification['response'][0]['current_balance']-$responseV['response'][0]['amount']+number_validator($amount);

                                    $columns = ['current_balance', 'updated_date'];
                                    $values = [$balance, getCurrentDatetime('Y-m-d H:i:s')];
                                    $condition = "id = '".$response_balance_verification['response'][0]['id']."'"; 
                                    
                                    updateData($db_prefix.'balance_verification', $columns, $values, $condition);
                                }*/

                                $columns = ['device_id', 'sender_key', 'number', 'amount', 'currency', 'trx_id', 'balance', 'type', 'entry_type', 'status', 'message', 'updated_date'];
                                $values = [$device_id, $sender_key, $phone_number, money_sanitize($amount), $currency, $transaction_id, money_sanitize($balance), $type, $entry_type, $status, $message, getCurrentDatetime('Y-m-d H:i:s')];

                                $condition = "id = '".$itemid."'"; 
                                
                                updateData($db_prefix.'sms_data', $columns, $values, $condition);

                                echo json_encode(['status' => 'true', 'title' => 'SMS Data Updated', 'message' => 'The sms data has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "paymentLink-defaultLinkCurrency"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'payment_link', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $DefaultCurrency = escape_string($_POST['DefaultCurrency'] ?? '');

                    set_env('payment-link-default-currency', $DefaultCurrency, $global_response_brand['response'][0]['brand_id']);

                    echo json_encode(['status' => 'true', 'title' => 'Default Currency Updated', 'message' => 'The default payment link currency has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "themes-new-active"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'theme_settings', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $slug = escape_string($_POST['slug'] ?? '');

                    $columns = ['theme', 'updated_date'];
                    $values = [$slug, getCurrentDatetime('Y-m-d H:i:s')];

                    $condition = "id = '".$global_response_brand['response'][0]['id']."'"; 
                    
                    updateData($db_prefix.'brands', $columns, $values, $condition);

                    echo json_encode(['status' => 'true', 'title' => 'Theme Activated', 'message' => 'The theme has been activated successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "theme-setting-update"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brand_settings', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'theme_settings', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $themeSlug = $global_response_brand['response'][0]['theme'];

                    foreach($_POST as $key=>$value){
                        if(in_array($key,['action','csrf_token'])) continue;

                        $optionName = $themeSlug.'-'.$key;

                        // Multi-select arrays -> JSON
                        if(is_array($value)){
                            $value = json_encode($value);
                        }

                        // Checkbox unchecked -> 0
                        if(!isset($_POST[$key]) && strpos($key, 'is_')===0){
                            $value = 0;
                        }

                        set_env($optionName, $value, $global_response_brand['response'][0]['brand_id']);  // save in DB
                    }

                    foreach ($_FILES as $key => $file) {
                        // Skip empty uploads
                        if (empty($file['name'])) continue;

                        $max_file_size = 5 * 1024 * 1024; 

                        $optionName = $themeSlug.'-'.$key;
                        
                        $mediaUpload = json_decode(uploadImage($_FILES[$key] ?? null, $max_file_size), true);
                        if($mediaUpload['status'] == true){
                            set_env($optionName, $site_url.'pp-media/storage/'.$mediaUpload['file'], $global_response_brand['response'][0]['brand_id']);
                        }
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Theme Setting Updated', 'message' => 'The theme setting has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "transaction-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    $tabType = escape_string($_POST['tabType'] ?? '');

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($tabType !== "all") {
                        $where[] = "status = '{$tabType}'";
                    }

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( customer_info LIKE '%$search_input%' OR trx_id LIKE '%$search_input%' OR gateway_slug LIKE '%$search_input%' OR sender LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'transaction',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND status NOT IN ("initiated") '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $customer_info = json_decode($row['customer_info'], true);

                            $response_currency = json_decode(getData($db_prefix.'currency',' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND code = "'.$row['currency'].'"'),true);

                            $currency = $response_currency['response'][0]['symbol'] ?? '';

                            $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND gateway_id = "'.$row['gateway_id'].'"'),true);

                            $gateway = $response_gateway['response'][0]['name'] ?? '';

                            $amount = money_sanitize($row['amount']);
                            $processing_fee = money_sanitize($row['processing_fee']);
                            $discount = money_sanitize($row['discount_amount']);

                            $net = money_sub(money_add($amount, $processing_fee), $discount);

                            $response[] = [
                                "id"    => $row['ref'],
                                "c_id"   => $customer_info['id']     ?? 'N/A',
                                "name"   => $customer_info['name']   ?? 'Unknown',
                                "email"   => $customer_info['email']  ?? '',
                                "mobile"  => $customer_info['mobile'] ?? '',
                                "status"  => $row['status'],
                                "gateway"  => $gateway,
                                "trx_id"  => ($row['trx_id'] == '--' || $row['trx_id'] == '') ? '': $row['trx_id'],
                                "net_amount" => $currency . money_round($net, 2),
                                "amount"     => $currency . money_round($amount, 2),
                                "created_date"     => convertUTCtoUserTZ($row['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A"),
                                "updated_date"     => convertUTCtoUserTZ($row['updated_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'transaction',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND status NOT IN ("initiated") '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "transaction-bulk-action"){
                if($global_user_login == true){
                        if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $actionID = escape_string($_POST['actionID'] ?? '');
                        $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                        $selected_ids = json_decode($selected_ids_json, true);
                        $actionsID = $actionID;

                        if (!empty($selected_ids)) {
                            $all_transactions = [];

                            $jobs = [];
                            $failed = [];

                            foreach ($selected_ids as $id) {
                                $itemID = escape_string($id);

                                $response_brand = json_decode(getData($db_prefix.'transaction','WHERE ref = "'.$itemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                                if($response_brand['status'] == true){
                                    if($actionID == "deleted"){
                                        if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', 'delete', $global_user_response['response'][0]['role'])) {
                                            $condition = "ref = '".$itemID."'"; 
                                            
                                            deleteData($db_prefix.'transaction', $condition);
                                        }
                                    }

                                    if($actionID == "approved"){
                                        if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', 'approve', $global_user_response['response'][0]['role'])) {
                                            $columns = ['status', 'updated_date'];
                                            $values = ['completed', getCurrentDatetime('Y-m-d H:i:s')];

                                            $condition = "ref = '".$itemID."'"; 
                                            
                                            updateData($db_prefix.'transaction', $columns, $values, $condition);
                                        }
                                    }

                                    if($actionID == "refunded"){
                                        if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', 'refund', $global_user_response['response'][0]['role'])) {
                                            $transactionRow = $response_brand['response'][0];

                                            if (($transactionRow['status'] ?? '') !== 'completed') {
                                                $failed[] = [
                                                    'ref' => $itemID,
                                                    'message' => 'Only completed transactions can be refunded.'
                                                ];
                                                continue;
                                            }

                                            $refundResult = null;
                                            $params = [ ':gateway_id' => $transactionRow['gateway_id'], ':brand_id' => $transactionRow['brand_id'] ];
                                            $response_gateway_info = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = :gateway_id AND brand_id = :brand_id', '* FROM', $params),true);
                                            $gateway_slug = $response_gateway_info['response'][0]['slug'] ?? '';

                                            if ($gateway_slug === 'bkash-api-tokenized') {
                                                $refundPayload = [
                                                    'amount' => money_round($transactionRow['local_net_amount']),
                                                    'sku' => $transactionRow['ref'],
                                                    'reason' => 'Admin refund'
                                                ];

                                                $refundResult = pp_bkash_tokenized_refund($transactionRow, $refundPayload);

                                                if (empty($refundResult['status'])) {
                                                    $failed[] = [
                                                        'ref' => $itemID,
                                                        'message' => $refundResult['message'] ?? 'Refund failed.'
                                                    ];
                                                    continue;
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

                                            $columns = ['status', 'updated_date'];
                                            $values = ['refunded', getCurrentDatetime('Y-m-d H:i:s')];

                                            if ($source_info_changed) {
                                                $columns[] = 'source_info';
                                                $values[] = json_encode($source_info, JSON_UNESCAPED_UNICODE);
                                                $response_brand['response'][0]['source_info'] = json_encode($source_info, JSON_UNESCAPED_UNICODE);
                                            }

                                            $condition = "ref = '".$itemID."'"; 
                                            updateData($db_prefix.'transaction', $columns, $values, $condition);

                                            $response_brand['response'][0]['status'] = 'refunded';
                                        }
                                    }

                                    if($actionID == "canceled"){
                                        if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', 'cancel', $global_user_response['response'][0]['role'])) {
                                            $columns = ['status', 'updated_date'];
                                            $values = ['canceled', getCurrentDatetime('Y-m-d H:i:s')];

                                            $condition = "ref = '".$itemID."'"; 
                                            
                                            updateData($db_prefix.'transaction', $columns, $values, $condition);
                                        }
                                    }

                                    if($actionID == "ipnsend"){
                                        if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', 'send_ipn', $global_user_response['response'][0]['role'])) {
                                            $actionsID = 'IPN Triggered';

                                            if($response_brand['response'][0]['webhook_url'] == "--" || $response_brand['response'][0]['webhook_url'] == ""){

                                            }else{
                                                $metadata = json_decode($response_brand['response'][0]['metadata'], true) ?: [];

                                                $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND gateway_id = "'.$response_brand['response'][0]['gateway_id'].'"'),true);

                                                $gateway = $response_gateway['response'][0]['name'] ?? '';

                                                $customer_info = json_decode($response_brand['response'][0]['customer_info'], true) ?: [];

                                                $net = money_sub(money_add($response_brand['response'][0]['amount'], $response_brand['response'][0]['processing_fee']), $response_brand['response'][0]['discount_amount']);

                                                $ipnData = [
                                                    "pp_id" => $response_brand['response'][0]['ref'],
                                                    "full_name" => $customer_info['name'] ?? 'N/A',
                                                    "email_address" => $customer_info['email'] ?? 'N/A',
                                                    "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                                                    "gateway" => $gateway,
                                                    "amount" => money_round($response_brand['response'][0]['amount']),
                                                    "fee" => money_round($response_brand['response'][0]['processing_fee']),
                                                    "discount_amount" => money_round($response_brand['response'][0]['discount_amount']),
                                                    "total" => money_round($net),
                                                    "local_net_amount" => money_round($response_brand['response'][0]['local_net_amount']),
                                                    "currency" => $response_brand['response'][0]['currency'],
                                                    "local_currency" => $response_brand['response'][0]['local_currency'],
                                                    "metadata" => $metadata, // ← AS-IS
                                                    "sender" => $response_brand['response'][0]['sender'],
                                                    "transaction_id" => $response_brand['response'][0]['trx_id'],
                                                    "status" => $response_brand['response'][0]['status'],
                                                    "date" => convertUTCtoUserTZ($response_brand['response'][0]['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                                ];

                                                $payload = json_encode($ipnData, JSON_UNESCAPED_UNICODE);
                                                
                                                $jobs[] = [
                                                    'id'      => rand(),
                                                    'url'     => $response_brand['response'][0]['webhook_url'],
                                                    'payload' => json_decode($payload, true),
                                                ];
                                            }
                                        }
                                    }

                                    if($actionID == "refunded" || $actionID == "canceled" || $actionID == "approved"){
                                        $metadata = json_decode($response_brand['response'][0]['metadata'], true) ?: [];

                                        $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND gateway_id = "'.$response_brand['response'][0]['gateway_id'].'"'),true);

                                        $gateway = $response_gateway['response'][0]['name'] ?? '';

                                        $customer_info = json_decode($response_brand['response'][0]['customer_info'], true) ?: [];

                                        $net = money_sub(money_add($response_brand['response'][0]['amount'], $response_brand['response'][0]['processing_fee']), $response_brand['response'][0]['discount_amount']);

                                        $all_transactions[] = [
                                            "pp_id" => $response_brand['response'][0]['ref'],
                                            "full_name" => $customer_info['name'] ?? 'N/A',
                                            "email_address" => $customer_info['email'] ?? 'N/A',
                                            "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                                            "gateway" => $gateway,
                                            "amount" => money_round($response_brand['response'][0]['amount']),
                                            "fee" => money_round($response_brand['response'][0]['processing_fee']),
                                            "discount_amount" => money_round($response_brand['response'][0]['discount_amount']),
                                            "total" => money_round($net),
                                            "local_net_amount" => money_round($response_brand['response'][0]['local_net_amount']),
                                            "currency" => $response_brand['response'][0]['currency'],
                                            "local_currency" => $response_brand['response'][0]['local_currency'],
                                            "metadata" => $metadata, // ← AS-IS
                                            "sender" => $response_brand['response'][0]['sender'],
                                            "transaction_id" => $response_brand['response'][0]['trx_id'],
                                            "status" => $response_brand['response'][0]['status'],
                                            "date" => convertUTCtoUserTZ($response_brand['response'][0]['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                        ];
                                    }
                                }
                            }

                            $results = sendIPNMulti($jobs);

                            foreach ($jobs as $job) {
                                $code = $results[$job['id']] ?? 0;
                                $status = ($code === 200) ? 'completed' : 'pending';

                                if($status == 'completed'){

                                }else{
                                    $columns = ['ref', 'brand_id', 'payload', 'url', 'created_date', 'updated_date'];
                                    $values = [rand(), $response_brand['response'][0]['brand_id'], json_encode($job['payload']), $job['url'], getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                    insertData($db_prefix.'webhook_log', $columns, $values);
                                }
                            }

                            if (!empty($all_transactions)) {
                                do_action('transactions.updated', $all_transactions);
                            }

                            if (!empty($failed)) {
                                $firstFail = $failed[0];
                                $failCount = count($failed);
                                $message = $failCount > 1
                                    ? $firstFail['message'].' (and '.($failCount - 1).' more)'
                                    : $firstFail['message'];

                                echo json_encode([
                                    'status' => 'false',
                                    'title' => 'Refund Failed',
                                    'message' => $message,
                                    'csrf_token' => $new_csrf_token
                                ]);
                            }else{
                                echo json_encode(['status' => 'true', 'title' => 'Transactions '.$actionsID, 'message' => 'The selected transactions have been '.$actionsID.' successfully.', 'csrf_token' => $new_csrf_token]);
                            }
                        } else {
                            echo json_encode(['status' => 'false', 'title' => 'Transactions Failed', 'message' => 'No transactions selected.' , 'csrf_token' => $new_csrf_token]);
                        }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "transaction-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'transaction','WHERE ref = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                    if($response_brand['status'] == true){
                        $condition = "ref = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'transaction', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Transaction Deleted', 'message' => 'The selected Transaction have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "transaction-ipn"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'transaction', 'send_ipn', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'transaction','WHERE ref = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                    if($response_brand['status'] == true){
                        $metadata = json_decode($response_brand['response'][0]['metadata'], true) ?: [];

                        $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND gateway_id = "'.$response_brand['response'][0]['gateway_id'].'"'),true);

                        $gateway = $response_gateway['response'][0]['name'] ?? '';

                        $customer_info = json_decode($response_brand['response'][0]['customer_info'], true) ?: [];

                        $net = money_sub(money_add($response_brand['response'][0]['amount'], $response_brand['response'][0]['processing_fee']), $response_brand['response'][0]['discount_amount']);

                        $ipnData = [
                            "pp_id" => $response_brand['response'][0]['ref'],
                            "full_name" => $response_brand['response'][0]['name'] ?? 'N/A',
                            "email_address" => $response_brand['response'][0]['email'] ?? 'N/A',
                            "mobile_number" => $response_brand['response'][0]['mobile'] ?? 'N/A',
                            "gateway" => $gateway,
                            "amount" => money_round($response_brand['response'][0]['amount']),
                            "fee" => money_round($response_brand['response'][0]['processing_fee']),
                            "discount_amount" => money_round($response_brand['response'][0]['discount_amount']),
                            "total" => money_round($net),
                            "local_net_amount" => money_round($response_brand['response'][0]['local_net_amount']),
                            "currency" => $response_brand['response'][0]['currency'],
                            "metadata" => $metadata, // ← AS-IS
                            "sender" => $response_brand['response'][0]['sender'],
                            "transaction_id" => $response_brand['response'][0]['trx_id'],
                            "status" => $response_brand['response'][0]['status'],
                            "date" => convertUTCtoUserTZ($response_brand['response'][0]['created_date'], ($global_response_brand['response'][0]['timezone'] === '--' || $global_response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $global_response_brand['response'][0]['timezone'], "M d, Y h:i A")
                        ];

                        if($response_brand['response'][0]['webhook_url'] == "--" || $response_brand['response'][0]['webhook_url'] == ""){

                        }else{
                            sendIPN($response_brand['response'][0]['webhook_url'], $ipnData);
                        }
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Transaction IPN Triggered', 'message' => 'The IPN for the transaction has been sent successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "system-settings-update-setting"){
                if($global_user_login == true){
                    if (!empty($pp_demo_mode)) {
                        echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_update', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $update_channel = escape_string($_POST['update_channel'] ?? '');
                        $automatic_update = escape_string($_POST['automatic_update'] ?? '');
                        $create_backup = escape_string($_POST['create_backup'] ?? '');

                        set_env('system-settings-update_channel', $update_channel);
                        set_env('system-settings-automatic_update', $automatic_update);
                        set_env('system-settings-create_backup', $create_backup);

                        echo json_encode(['status' => 'true', 'title' => 'Settings Updated', 'message' => 'Your changes have been saved successfully.', 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "system-settings-update-check"){
                if($global_user_login == true){
                    if (!empty($pp_demo_mode)) {
                        echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_update', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        set_env('last-auto-update-check', getCurrentDatetime('Y-m-d H:i:s'));

                        $manifest = json_decode(file_get_contents('https://updates.piprapay.com/manifest.json'), true);

                        $current_code = $piprapay_current_version['version_code'];
                        $current_name = $piprapay_current_version['version_name'];
                        $version_hash = $piprapay_current_version['version_hash'];

                        if(get_env('system-settings-update_channel') == "" || get_env('system-settings-update_channel') == "--" || get_env('system-settings-update_channel') == "stable"){
                            $update_channel = 'stable';
                        }else{
                            $update_channel = 'beta';
                        }

                        $channel_data = $manifest['channels'][$update_channel] ?? null;

                        $update_available = false;
                        $latest_name = null;
                        $latest_code = null;
                        $latest_hash = null;

                        if ($channel_data) {
                            $latest_name = $channel_data['latest_version_name'];
                            $latest_code = $channel_data['latest_version_code'];

                            $latest_hash = '';
                            foreach ($channel_data['versions'] as $version) {
                                if ($version['version_code'] === $latest_code) {
                                    $latest_hash = $version['checksum'];
                                    break;
                                }
                            }

                            if (version_compare($latest_code, $current_code, '>')) {
                                $update_available = true;
                            }
                        }

                        if($update_available == true){
                            set_env('last-update-version-name', $latest_name);
                            set_env('last-update-version-hash', $latest_hash);
                            set_env('last-update-version', $latest_code);

                            echo json_encode(['status' => 'true', 'title' => 'Update Available', 'message' => 'A new system update is available. Please update to get the latest features and improvements.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            set_env('last-update-version-name', $current_name);
                            set_env('last-update-version-hash', $version_hash);
                            set_env('last-update-version', $current_code);

                           echo json_encode(['status' => 'true', 'title' => 'System Up to Date', 'message' => 'Everything is up to date. No updates were found.', 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "system-settings-update-download"){
                if($global_user_login == true){
                    if (!empty($pp_demo_mode)) {
                        echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_update', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $lasted_update_version = get_env('last-update-version');

                        if (version_compare($lasted_update_version, $piprapay_current_version['version_code'], '>')) {
                            $update_available = true;
                        }

                        if($update_available == true){
                            $url = "https://updates.piprapay.com/download.php?version=$lasted_update_version";

                            $saveDir =  __DIR__ . '/../../pp-media/storage/updates/';

                            if (!is_dir($saveDir)) {
                                mkdir($saveDir, 0755, true);
                            }

                            $saveTo = $saveDir . $lasted_update_version . '.zip';

                            // Initialize curl
                            $ch = curl_init($url);
                            $fp = fopen($saveTo, 'w');

                            curl_setopt($ch, CURLOPT_FILE, $fp);          // write to file
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirects
                            curl_setopt($ch, CURLOPT_FAILONERROR, true);    // HTTP >= 400 will fail
                            curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // max 2 minutes
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);  // connection timeout

                            $success = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            $error = curl_error($ch);

                            curl_close($ch);
                            fclose($fp);

                            if (!$success || $httpCode >= 400) {
                                echo json_encode(['status' => 'false', 'title' => 'Download Failed', 'message' => 'The latest update could not be downloaded. Please check your internet connection or try again later.', 'csrf_token' => $new_csrf_token]);
                            }else{
                                echo json_encode(['status' => 'true', 'title' => 'Update Downloaded', 'message' => 'The latest version has been downloaded successfully and is ready to be installed.', 'csrf_token' => $new_csrf_token]);
                            }
                        }else{
                            echo json_encode(['status' => 'true', 'title' => 'System Up to Date', 'message' => 'Everything is up to date. No updates were found.', 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "system-settings-update-install"){
                if($global_user_login == true){
                    if (!empty($pp_demo_mode)) {
                        echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_update', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $lasted_update_version = get_env('last-update-version');
                        $lasted_update_version_hash = get_env('last-update-version-hash');

                        if (version_compare($lasted_update_version, $piprapay_current_version['version_code'], '>')) {
                            $update_available = true;
                        }

                        if($update_available == true){
                            $root = realpath(__DIR__ . '/../../'); 
                            $storage = __DIR__ . '/../../pp-media/storage/';

                            $backupDir = $storage . 'backup/';
                            $tempDir   = $storage . "temp/$lasted_update_version/";
                            $zipFile   = $storage . "updates/$lasted_update_version.zip";

                            if (sha1_file($zipFile) !== $lasted_update_version_hash) {
                                echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Update file checksum mismatch! Possible corruption or tampering.' , 'csrf_token' => $new_csrf_token]);
                                exit();
                            }

                            @mkdir($backupDir, 0755, true);
                            @mkdir($tempDir, 0755, true);

                            zipFolder($root, "$backupDir/".$piprapay_current_version['version_code'].".zip");

                            backupDatabasePDO("$backupDir/db_".$piprapay_current_version['version_code'].".sql");

                            file_put_contents("$root/.maintenance", 'updating');

                            extractUpdate($zipFile, $tempDir);

                            copyFolder($tempDir, $root);

                            if (file_exists("$tempDir/update.sql")) {
                                runSql("$tempDir/update.sql");
                            }

                            deleteFolder($tempDir);
                            unlink("$root/.maintenance");

                            echo json_encode(['status' => 'true', 'title' => 'Installation Successful', 'message' => 'The latest version has been installed successfully. Your system is now up to date.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'true', 'title' => 'System Up to Date', 'message' => 'Everything is up to date. No updates were found.', 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "system-settings-import"){
                if($global_user_login == true){
                    if (!empty($pp_demo_mode)) {
                        echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_import', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
                            echo json_encode([
                                'status' => 'false',
                                'title' => 'Upload Failed',
                                'message' => 'No file uploaded or upload error occurred.',
                                'csrf_token' => $new_csrf_token
                            ]);
                            exit;
                        }

                        $uploadedFile = $_FILES['zip_file'];
                        $max_file_size = 100 * 1024 * 1024; // 100MB

                        if ($uploadedFile['size'] > $max_file_size) {
                            echo json_encode([
                                'status' => 'false',
                                'title' => 'File Too Large',
                                'message' => 'File exceeds maximum allowed size of 100MB.',
                                'csrf_token' => $new_csrf_token
                            ]);
                            exit;
                        }

                        $fileExt = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
                        if ($fileExt !== 'zip') {
                            echo json_encode([
                                'status' => 'false',
                                'title' => 'Invalid File',
                                'message' => 'Only ZIP files are allowed.',
                                'csrf_token' => $new_csrf_token
                            ]);
                            exit;
                        }

                        $zip = new ZipArchive;
                        if ($zip->open($uploadedFile['tmp_name']) !== true) {
                            echo json_encode([
                                'status' => 'false',
                                'title' => 'Invalid File',
                                'message' => 'Uploaded file is not a valid ZIP.',
                                'csrf_token' => $new_csrf_token
                            ]);
                            exit;
                        }
                        $zip->close();

                        $root    = realpath(__DIR__ . '/../../');
                        $storage = __DIR__ . '/../../pp-media/storage/';
                        $updatesDir = $storage . "import/";

                        if (!is_dir($storage)) mkdir($storage, 0755, true);
                        if (!is_dir($updatesDir)) mkdir($updatesDir, 0755, true);

                        $sanitizedName = pathinfo($uploadedFile['name'], PATHINFO_FILENAME);
                        $tempDir = $storage . "temp/" . $sanitizedName . "/";
                        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

                        $destination = $updatesDir . $uploadedFile['name'];
                        if (!move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
                            echo json_encode([
                                'status' => 'false',
                                'title' => 'Upload Failed',
                                'message' => 'Failed to move uploaded file.',
                                'csrf_token' => $new_csrf_token
                            ]);
                            exit;
                        }

                        try {
                            extractUpdate($destination, $tempDir);
                            copyFolder($tempDir, $root);

                            $sqlFile = $tempDir . "sql.sql";
                            if (file_exists($sqlFile)) runSql($sqlFile);

                            deleteFolder($tempDir);

                            if (file_exists($destination)) {
                                unlink($destination); // deletes the file
                            }

                            echo json_encode([
                                'status' => 'true',
                                'title' => 'Import Successful',
                                'message' => 'ZIP file imported and applied successfully!',
                                'csrf_token' => $new_csrf_token
                            ]);

                        } catch (Exception $e) {
                            echo json_encode([
                                'status' => 'false',
                                'title' => 'Server Error',
                                'message' => $e->getMessage(),
                                'csrf_token' => $new_csrf_token
                            ]);
                            exit;
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "gateway-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $gateway = escape_string($_POST['gateway'] ?? '');

                    if($gateway == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $gateway)) {
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        }else if (!file_exists(__DIR__ . '/../pp-modules/pp-gateways/'.$gateway.'/class.php')) {
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        }else{
                            try {
                                require_once __DIR__ . '/../pp-modules/pp-gateways/'.$gateway.'/class.php';

                                $slug = basename(__DIR__ . '/../pp-modules/pp-gateways/'.$gateway);

                                // twenty-six → TwentySixTheme
                                $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $slug))) . 'Gateway';

                                if (!class_exists($class)) {
                                    error_log('PipraPay gateway create failed for ' . $gateway . ': class ' . $class . ' not found');
                                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                                }else{
                                    $gatewayObj = new $class();

                                    $gatewayInfo = $gatewayObj->info();
                                    $gatewayColor = $gatewayObj->color();

                                    $gateway_id = generateItemID();

                                    $columns = ['gateway_id', 'brand_id', 'slug', 'name', 'display', 'logo', 'currency', 'primary_color', 'text_color', 'btn_color', 'btn_text_color', 'tab', 'created_date', 'updated_date'];
                                    $values = [$gateway_id, $global_response_brand['response'][0]['brand_id'], $slug, $gatewayInfo['title'], $gatewayInfo['title'], $site_url.'pp-content/pp-modules/pp-gateways/'.$gateway.'/'.$gatewayInfo['logo'], $gatewayInfo['currency'], $gatewayColor['primary_color'], $gatewayColor['text_color'], $gatewayColor['btn_color'], $gatewayColor['btn_text_color'], $gatewayInfo['tab'], getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                    insertData($db_prefix.'gateways', $columns, $values);

                                    echo json_encode(['status' => 'true', 'title' => 'Gateway Created', 'message' => 'The gateway has been created successfully.', 'csrf_token' => $new_csrf_token]);
                                
                                }
                            } catch (Throwable $e) {
                                error_log('PipraPay gateway create failed for ' . $gateway . ': ' . $e->getMessage());
                                echo json_encode(['status' => 'false', 'title' => 'Gateway Error', 'message' => 'The selected gateway module could not be loaded. Check the hosting error log for details.' , 'csrf_token' => $new_csrf_token]);
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }


            if($action == "gateways-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    $tabType = escape_string($_POST['tabType'] ?? '');

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($tabType !== "all") {
                        $where[] = "tab = '{$tabType}'";
                    }

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( name LIKE '%$search_input%' OR display LIKE '%$search_input%' )";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'gateways',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $response[] = [
                                "id"    => $row['gateway_id'],
                                "name"   => $row['name'],
                                "display"   => $row['display'],
                                "currency"  => $row['currency'],
                                "status"  => $row['status']
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'gateways',' WHERE '.$where_sql.' brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "gateways-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = "'.$ItemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" '),true);
                    if($response_brand['status'] == true){
                        $condition = "gateway_id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'gateways', $condition);

                        $condition = "gateway_id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'gateways_parameter', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Gateway Deleted', 'message' => 'The selected gateway have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "gateways-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'gateways','WHERE gateway_id = "'.$itemID.'" AND brand_id ="'.$global_response_brand['response'][0]['brand_id'].'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', 'delete', $global_user_response['response'][0]['role'])) {
                                    
                                        $condition = "gateway_id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'gateways', $condition);

                                        $condition = "gateway_id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'gateways_parameter', $condition);
                                    }
                                }

                                if($actionID == "activated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "gateway_id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'gateways', $columns, $values, $condition);

                                    }
                                }

                                if($actionID == "inactivated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['inactive', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "gateway_id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'gateways', $columns, $values, $condition);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Gateways '.$actionID, 'message' => 'The selected gateways have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No gateways selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "gateway-setting-update"){
                if($global_user_login == true){
                        if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', 'edit', $global_user_response['response'][0]['role'])) {
                            echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                            exit();
                        }

                        $gateway_id = escape_string($_POST['gateway-id'] ?? '');

                        $display_name = escape_string($_POST['display_name'] ?? '');

                        $min_amount = escape_string($_POST['min_amount'] ?? '');
                        $max_amount = escape_string($_POST['max_amount'] ?? '');

                        $fixed_charge = escape_string($_POST['fixed_charge'] ?? '');
                        $percentage_charge = escape_string($_POST['percentage_charge'] ?? '');

                        $fixed_discount = escape_string($_POST['fixed_discount'] ?? '');
                        $percentage_discount = escape_string($_POST['percentage_discount'] ?? '');

                        $primary_color = escape_string($_POST['primary_color'] ?? '');
                        $text_color = escape_string($_POST['text_color'] ?? '');
                        $btn_color = escape_string($_POST['btn_color'] ?? '');
                        $btn_text_color = escape_string($_POST['btn_text_color'] ?? '');

                        $status = escape_string($_POST['status'] ?? '');
                        $currency = escape_string($_POST['currency'] ?? '');

                        if($gateway_id == "" || $display_name == "" || $min_amount == "" || $max_amount == "" || $fixed_charge == "" || $percentage_charge == "" || $fixed_discount == "" || $percentage_discount == "" || $primary_color == "" || $text_color == "" || $btn_color == "" || $btn_text_color == ""){
                            echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            $response = json_decode(getData($db_prefix.'gateways','WHERE brand_id ="'.$global_response_brand['response'][0]['brand_id'].'" AND gateway_id ="'.$gateway_id.'"'),true);
                            if($response['status'] == true){
                                $max_file_size = 2 * 1024 * 1024; 
                                
                                $assets_logo = json_decode(uploadImage($_FILES['gateway_logo'] ?? null, $max_file_size), true);
                                if($assets_logo['status'] == true){
                                    $logo = $site_url.'pp-media/storage/'.$assets_logo['file'];
                                }else{
                                    $logo = $response['response'][0]['logo'];
                                }

                                $columns = ['display', 'logo', 'currency', 'min_allow', 'max_allow', 'fixed_discount', 'percentage_discount', 'fixed_charge', 'percentage_charge', 'primary_color', 'text_color', 'btn_color', 'btn_text_color', 'status', 'updated_date'];
                                $values = [$display_name, $logo, $currency, money_sanitize($min_amount), money_sanitize($max_amount), money_sanitize($fixed_discount), money_sanitize($percentage_discount), money_sanitize($fixed_charge), money_sanitize($percentage_charge), $primary_color, $text_color, $btn_color, $btn_text_color, $status, getCurrentDatetime('Y-m-d H:i:s')];
                                $condition = "gateway_id = '".$gateway_id."'"; 
                                
                                updateData($db_prefix.'gateways', $columns, $values, $condition);

                                $configData = [];

                                foreach ($_POST as $key => $value) {

                                    // Skip known system fields
                                    if (in_array($key, [
                                        'action','gateway-id','csrf_token',
                                        'gateway_name','display_name',
                                        'min_amount','max_amount',
                                        'fixed_charge','percentage_charge',
                                        'fixed_discount','percentage_discount',
                                        'currency','status',
                                        'gateway_logo',
                                        'primary_color','text_color','btn_color','btn_text_color'
                                    ])) {
                                        continue;
                                    }

                                    // Handle multi-select (array)
                                    if (is_array($value)) {
                                        $value = json_encode($value);
                                    }

                                    $configData[$key] = $value;
                                }

                                foreach ($_FILES as $key => $file) {
                                    // Skip empty uploads
                                    if (empty($file['name'])) continue;

                                    $max_file_size = 5 * 1024 * 1024; 
                                    
                                    $mediaUpload = json_decode(uploadImage($_FILES[$key] ?? null, $max_file_size), true);
                                    if($mediaUpload['status'] == true){
                                        $configData[$key] = $site_url.'pp-media/storage/'.$mediaUpload['file'];
                                    }
                                }

                                foreach ($configData as $optionName => $optionValue) {

                                    $response_optionValue = json_decode(getData($db_prefix.'gateways_parameter','WHERE gateway_id = "'.$gateway_id.'" AND brand_id = "'.$global_response_brand['response'][0]['brand_id'].'" AND option_name = "'.$optionName.'"'),true);

                                    if(isset($response_optionValue['response'][0]['value'])){
                                        $columns = ['value', 'updated_date'];
                                        $values = [($optionValue == "") ? '--' : $optionValue, getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "id = '".$response_optionValue['response'][0]['id']."'"; 
                                        
                                        updateData($db_prefix.'gateways_parameter', $columns, $values, $condition);
                                    }else{
                                        $columns = ['brand_id', 'gateway_id', 'option_name', 'value', 'created_date', 'updated_date'];
                                        $values = [$global_response_brand['response'][0]['brand_id'], $gateway_id, $optionName, ($optionValue == "") ? '--' : $optionValue, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                        insertData($db_prefix.'gateways_parameter', $columns, $values);
                                    }

                                }

                                echo json_encode(['status' => 'true', 'title' => 'Gateway Updated', 'message' => 'The gateway has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                            }else{
                                echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid Gateway ID' , 'csrf_token' => $new_csrf_token]);
                            }
                        }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "gateway-setting-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'gateways', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $gateway_name = escape_string($_POST['gateway_name'] ?? '');
                    $display_name = escape_string($_POST['display_name'] ?? '');

                    $min_amount = escape_string($_POST['min_amount'] ?? '');
                    $max_amount = escape_string($_POST['max_amount'] ?? '');

                    $fixed_charge = escape_string($_POST['fixed_charge'] ?? '');
                    $percentage_charge = escape_string($_POST['percentage_charge'] ?? '');

                    $fixed_discount = escape_string($_POST['fixed_discount'] ?? '');
                    $percentage_discount = escape_string($_POST['percentage_discount'] ?? '');

                    $primary_color = escape_string($_POST['primary_color'] ?? '');
                    $text_color = escape_string($_POST['text_color'] ?? '');
                    $btn_color = escape_string($_POST['btn_color'] ?? '');
                    $btn_text_color = escape_string($_POST['btn_text_color'] ?? '');

                    $status = escape_string($_POST['status'] ?? '');
                    $currency = escape_string($_POST['currency'] ?? '');

                    $gateway_id = generateItemID();

                    if($gateway_id == "" || $display_name == "" || $min_amount == "" || $max_amount == "" || $fixed_charge == "" || $percentage_charge == "" || $fixed_discount == "" || $percentage_discount == "" || $primary_color == "" || $text_color == "" || $btn_color == "" || $btn_text_color == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $max_file_size = 2 * 1024 * 1024; 
                        
                        $assets_logo = json_decode(uploadImage($_FILES['gateway_logo'] ?? null, $max_file_size), true);
                        if($assets_logo['status'] == true){
                            $logo = $site_url.'pp-media/storage/'.$assets_logo['file'];
                        }else{
                            $logo = '--';
                        }

                        $columns = ['gateway_id', 'brand_id', 'name', 'tab', 'display', 'logo', 'currency', 'min_allow', 'max_allow', 'fixed_discount', 'percentage_discount', 'fixed_charge', 'percentage_charge', 'primary_color', 'text_color', 'btn_color', 'btn_text_color', 'status', 'created_date', 'updated_date'];
                        $values = [$gateway_id, $global_response_brand['response'][0]['brand_id'], $gateway_name, 'bank', $display_name, $logo, $currency, money_sanitize($min_amount), money_sanitize($max_amount), money_sanitize($fixed_discount), money_sanitize($percentage_discount), money_sanitize($fixed_charge), money_sanitize($percentage_charge), $primary_color, $text_color, $btn_color, $btn_text_color, $status, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];
                        
                        insertData($db_prefix.'gateways', $columns, $values);

                        $configData = [];

                        foreach ($_POST as $key => $value) {

                            // Skip known system fields
                            if (in_array($key, [
                                'action','csrf_token',
                                'gateway_name','display_name',
                                'min_amount','max_amount',
                                'fixed_charge','percentage_charge',
                                'fixed_discount','percentage_discount',
                                'currency','status',
                                'primary_color','text_color','btn_color','btn_text_color'
                            ])) {
                                continue;
                            }

                            // Handle multi-select (array)
                            if (is_array($value)) {
                                $value = json_encode($value);
                            }

                            $configData[$key] = $value;
                        }

                        foreach ($configData as $optionName => $optionValue) {
                            $columns = ['brand_id', 'gateway_id', 'option_name', 'value', 'created_date', 'updated_date'];
                            $values = [$global_response_brand['response'][0]['brand_id'], $gateway_id, $optionName, ($optionValue == "") ? '--' : $optionValue, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                            insertData($db_prefix.'gateways_parameter', $columns, $values);
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Gateway Created', 'message' => 'The gateway has been created successfully.', 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "addons-create"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', 'create', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $addon = escape_string($_POST['addon'] ?? '');

                    if($addon == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        if (!file_exists(__DIR__ . '/../pp-modules/pp-addons/'.$addon.'/class.php')) {
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                        }else{
                            require_once __DIR__ . '/../pp-modules/pp-addons/'.$addon.'/class.php';

                            $slug = basename(__DIR__ . '/../pp-modules/pp-addons/'.$addon);

                            // twenty-six → TwentySixTheme
                            $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $slug))) . 'Addon';

                            if (!class_exists($class)) {
                                echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                            }else{
                                $addonObj = new $class();

                                $addonInfo = $addonObj->info();

                                $addon_id = generateItemID();

                                $columns = ['addon_id', 'slug', 'name', 'created_date', 'updated_date'];
                                $values = [$addon_id, $slug, $addonInfo['title'], getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                insertData($db_prefix.'addon', $columns, $values);

                                echo json_encode(['status' => 'true', 'title' => 'Addon Created', 'message' => 'The addon has been created successfully.', 'csrf_token' => $new_csrf_token]);
                            
                            }
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "addons-list"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $search_input = escape_string($_POST['search_input'] ?? '');
                    $show_limit = escape_string($_POST['show_limit'] ?? 5);

                    /* Filters */
                    $filter_status = escape_string($_POST['filter_status'] ?? '');
                    $filter_start  = escape_string($_POST['filter_start'] ?? '');
                    $filter_end    = escape_string($_POST['filter_end'] ?? '');

                    $where = [];

                    if ($filter_start !== '') {
                        $where[] = "created_date >= '{$filter_start} 00:00:00'";
                    }

                    if ($filter_end !== '') {
                        $where[] = "created_date <= '{$filter_end} 23:59:59'";
                    }

                    if ($filter_status !== '') {
                        $where[] = "status = '{$filter_status}'";
                    }

                    $where_sql = $where ? implode(' AND ', $where) . ' AND ' : '';
                    /* Filters */

                    $page = max(1, intval($_POST['page'] ?? 1));
                    $show_limit = ($_POST['show_limit'] == '') ? 999999 : intval($_POST['show_limit']);
                    $offset = ($page - 1) * $show_limit;

                    $sql_query = '';

                    if ($search_input !== '') {
                        $sql_query .= " AND ( name LIKE '%$search_input%')";
                    }

                    $sql_limit = '';
                    if($show_limit == 'all'){

                    }else{
                       $sql_limit = " LIMIT $offset, $show_limit";
                    }

                    $response_result = json_decode(getData($db_prefix.'addon',' WHERE '.$where_sql.' status NOT IN ("--") '.$sql_query.' ORDER BY 1 DESC '.$sql_limit),true);
                    if($response_result['status'] == true){
                        $response = [];

                        foreach($response_result['response'] as $row){
                            $response[] = [
                                "id"    => $row['addon_id'],
                                "name"   => $row['name'],
                                "status"  => $row['status']
                            ];
                        }

                        $count_data = json_decode(getData($db_prefix.'addon',' WHERE '.$where_sql.' status NOT IN ("--") '.$sql_query),true);

                        $total_records = count($count_data['response'] ?? []);
                        $total_pages = ceil($total_records / $show_limit);

                        $pagination = '<ul class="pagination m-0 ms-auto">';

                        // Prev button
                        $pagination .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">
                            <button class="page-link" '.($page > 1 ? 'data-page="'.($page-1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M15 6l-6 6l6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $pagination .= '<li class="page-item'.($i == $page ? ' active' : '').'">
                                <button class="page-link" data-page="'.$i.'">'.$i.'</button>
                            </li>';
                        }

                        // Next button
                        $pagination .= '<li class="page-item'.($page >= $total_pages ? ' disabled' : '').'">
                            <button class="page-link" '.($page < $total_pages ? 'data-page="'.($page+1).'"' : '').'>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                    <path d="M9 6l6 6l-6 6"></path>
                                </svg>
                            </button>
                        </li>';

                        $pagination .= '</ul>';

                        $start = ($offset + 1);
                        $end = min($offset + $show_limit, $total_records);

                        $datatableInfo = "Showing <strong>$start to $end</strong> of <strong>$total_records entries</strong>";

                        echo json_encode(['status' => "true", 'response' => $response, 'datatableInfo' => $datatableInfo, 'pagination' => $pagination,'csrf_token' => $new_csrf_token]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Nothing Here Yet', 'message' => 'No data is available at the moment.', 'csrf_token' => $new_csrf_token]);
                        exit();
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "addons-delete"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', 'delete', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $ItemID = escape_string($_POST['ItemID'] ?? '');

                    $response_brand = json_decode(getData($db_prefix.'addon','WHERE addon_id = "'.$ItemID.'" '),true);
                    if($response_brand['status'] == true){
                        $condition = "addon_id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'addon', $condition);

                        $condition = "addon_id = '".$ItemID."'"; 
                        
                        deleteData($db_prefix.'addon_parameter', $condition);
                    }

                    echo json_encode(['status' => 'true', 'title' => 'Addon Deleted', 'message' => 'The selected addon have been deleted successfully.', 'csrf_token' => $new_csrf_token]);
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "addons-bulk-action"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $actionID = escape_string($_POST['actionID'] ?? '');
                    $selected_ids_json = $_POST['selected_ids'] ?? '[]';
                    $selected_ids = json_decode($selected_ids_json, true);

                    if (!empty($selected_ids)) {
                        foreach ($selected_ids as $id) {
                            $itemID = escape_string($id);

                            $response_brand = json_decode(getData($db_prefix.'addon','WHERE addon_id = "'.$itemID.'"'),true);
                            if($response_brand['status'] == true){
                                if($actionID == "deleted"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', 'delete', $global_user_response['response'][0]['role'])) {
                                    
                                        $condition = "addon_id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'addon', $condition);

                                        $condition = "addon_id = '".$itemID."'"; 
                                        
                                        deleteData($db_prefix.'addon_parameter', $condition);
                                    }
                                }

                                if($actionID == "activated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['active', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "addon_id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'addon', $columns, $values, $condition);

                                    }
                                }

                                if($actionID == "inactivated"){
                                    if (hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', 'edit', $global_user_response['response'][0]['role'])) {
                                    
                                        $columns = ['status', 'updated_date'];
                                        $values = ['inactive', getCurrentDatetime('Y-m-d H:i:s')];
                                        $condition = "addon_id = '".$itemID."'"; 
                                        
                                        updateData($db_prefix.'addon', $columns, $values, $condition);

                                    }
                                }
                            }
                        }

                        echo json_encode(['status' => 'true', 'title' => 'Addons '.$actionID, 'message' => 'The selected addons have been '.$actionID.' successfully.', 'csrf_token' => $new_csrf_token]);
                    } else {
                        echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'No addons selected.' , 'csrf_token' => $new_csrf_token]);
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "addon-setting-update"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $addon_id = escape_string($_POST['addon-id'] ?? '');
                    $status = escape_string($_POST['status'] ?? '');

                    if($addon_id == "" || $status == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $response = json_decode(getData($db_prefix.'addon','WHERE addon_id ="'.$addon_id.'"'),true);
                        if($response['status'] == true){
                            $columns = ['status', 'updated_date'];
                            $values = [$status, getCurrentDatetime('Y-m-d H:i:s')];
                            $condition = "addon_id = '".$addon_id."'"; 
                            
                            updateData($db_prefix.'addon', $columns, $values, $condition);

                            echo json_encode(['status' => 'true', 'title' => 'Addon Updated', 'message' => 'The addon has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid Addon ID' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }

            if($action == "addon-configuration-update"){
                if($global_user_login == true){
                    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'addons', 'edit', $global_user_response['response'][0]['role'])) {
                        echo json_encode(['status' => 'false', 'title' => 'Access denied', 'message' => 'You need permission to perform this action. Please contact the admin.' , 'csrf_token' => $new_csrf_token]);
                        exit();
                    }

                    $addon_id = escape_string($_POST['addon-id'] ?? '');

                    if($addon_id == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.', 'csrf_token' => $new_csrf_token]);
                    }else{
                        $response = json_decode(getData($db_prefix.'addon','WHERE addon_id ="'.$addon_id.'"'),true);
                        if($response['status'] == true){
                            $configData = [];

                            foreach ($_POST as $key => $value) {
                                // Handle multi-select (array)
                                if (is_array($value)) {
                                    $value = json_encode($value);
                                }

                                $configData[$key] = $value;
                            }

                            foreach ($_FILES as $key => $file) {
                                if (empty($file['name'])) continue;

                                $max_file_size = 5 * 1024 * 1024; 
                                
                                $mediaUpload = json_decode(uploadImage($_FILES[$key] ?? null, $max_file_size), true);
                                if($mediaUpload['status'] == true){
                                    $configData[$key] = $site_url.'pp-media/storage/'.$mediaUpload['file'];
                                }
                            }

                            foreach ($configData as $optionName => $optionValue) {
                                $response_optionValue = json_decode(getData($db_prefix.'addon_parameter','WHERE addon_id = "'.$addon_id.'" AND option_name = "'.$optionName.'"'),true);

                                if(isset($response_optionValue['response'][0]['value'])){
                                    $columns = ['value', 'updated_date'];
                                    $values = [($optionValue == "") ? '--' : $optionValue, getCurrentDatetime('Y-m-d H:i:s')];
                                    $condition = "id = '".$response_optionValue['response'][0]['id']."'"; 
                                    
                                    updateData($db_prefix.'addon_parameter', $columns, $values, $condition);
                                }else{
                                    $columns = ['addon_id', 'option_name', 'value', 'created_date', 'updated_date'];
                                    $values = [$addon_id, $optionName, ($optionValue == "") ? '--' : $optionValue, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                    insertData($db_prefix.'addon_parameter', $columns, $values);
                                }
                            }

                            echo json_encode(['status' => 'true', 'title' => 'Addon Updated', 'message' => 'The addon configuration has been updated successfully.', 'csrf_token' => $new_csrf_token]);
                        }else{
                            echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid Addon ID' , 'csrf_token' => $new_csrf_token]);
                        }
                    }
                }else{
                    echo json_encode(['status' => 'false', 'title' => 'Request Failed', 'message' => 'Invalid request' , 'csrf_token' => $new_csrf_token]);
                }
            }






























































































































































        }

        exit();
    }

    if(isset($_POST['action-v2'])){
        $action = escape_string($_POST['action-v2'] ?? '');

        if($action == ""){
            echo json_encode(['status' => "false", 'title' => 'Oops! Something went wrong', 'message' => 'Your request could not be processed. Please try again.']);
        }else{
            if($action == "invoice"){
                $itemid = escape_string($_POST['itemid'] ?? '');

                if($itemid == ""){
                    echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.']);
                }else{
                    $params = [ ':invoiceID' => $itemid, ':status' => 'unpaid' ];

                    $response = json_decode(getData($db_prefix.'invoice','WHERE ref = :invoiceID AND status = :status', '* FROM', $params),true);
                    if($response['status'] == true){
                        $invoiceRow = $response['response'][0];

                        $subTotal = "0";
                        $totalDiscount = "0";
                        $totalVat = "0";

                        $params = [':invoice_id' => $invoiceRow['ref'], ':brand_id'   => $invoiceRow['brand_id']];

                        $response_invoiceItem = json_decode(getData($db_prefix.'invoice_items', 'WHERE invoice_id = :invoice_id AND brand_id = :brand_id', '* FROM', $params), true);

                        if ($response_invoiceItem['status'] == true) {

                            foreach ($response_invoiceItem['response'] as $row) {
                                $amount   = money_sanitize($row['amount']);
                                $quantity = money_sanitize($row['quantity']);
                                $discount = money_sanitize($row['discount']);
                                $vatRate  = money_sanitize($row['vat']); 

                                $grossAmount = money_mul($amount, $quantity);

                                $netAmount = money_sub($grossAmount, $discount);

                                $vatAmount = money_div(money_mul($netAmount, $vatRate),"100");

                                $lineTotal = money_add($netAmount, $vatAmount);

                                $invoiceItems[] = [
                                    'description' => $row['description'],
                                    'unitPrice'   => money_round($amount, 2),
                                    'quantity'    => $quantity,
                                    'discount'    => money_round($discount, 2),
                                    'vat'         => money_round($vatAmount, 2),
                                    'total'       => money_round($lineTotal, 2),
                                ];

                                $subTotal      = money_add($subTotal, $grossAmount);
                                $totalDiscount = money_add($totalDiscount, $discount);
                                $totalVat      = money_add($totalVat, $vatAmount);
                            }
                        }

                        $customerInfo = json_decode($invoiceRow['customer_info'], true);

                        $customer_name = $customerInfo['name'] ?? '';
                        $customer_email = $customerInfo['email'] ?? '';
                        $customer_mobile = $customerInfo['mobile'] ?? '';

                        $source_info = '[{ "label": "Invoice Id", "value": "'.$itemid.'" }]';
                        $metadata = '{"invoice_id": "'.$itemid.'"}';

                        $amount = money_add( money_add( money_sub($subTotal, $totalDiscount), $totalVat ), money_sanitize($invoiceRow['shipping']) );

                        $currency = $invoiceRow['currency'];

                        $return_url = $site_url.$path_invoice.'/'.$itemid;
                        $webhook_url= $site_url.$path_invoice.'/webhook';

                        $payment_id = generateItemID(27, 27);

                        $columns = ['brand_id', 'source', 'ref', 'customer_info', 'amount', 'currency', 'source_info', 'metadata', 'return_url', 'webhook_url', 'created_date', 'updated_date'];
                        $values = [$invoiceRow['brand_id'], 'invoice', $payment_id, '{ "name": "'.$customer_name.'", "email": "'.$customer_email.'", "mobile": "'.$customer_mobile.'" }', money_sanitize($amount), $currency, $source_info, $metadata, $return_url, $webhook_url, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                        insertData($db_prefix.'transaction', $columns, $values);

                        echo json_encode(['status' => "true", 'redirect' => $site_url.$path_payment.'/'.$payment_id]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Invalid Invoice ID', 'message' => 'Please fill in all required fields before proceeding.']);
                    }
                }
            }

            if($action == "payment-link"){
                $itemid = escape_string($_POST['itemid'] ?? '');

                if($itemid == ""){
                    echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.']);
                }else{
                    $params = [ ':ref' => $itemid ];

                    $response_payment_link = json_decode(getData($db_prefix.'payment_link','WHERE ref = :ref', '* FROM', $params),true);
                    if($response_payment_link['status'] == true){
                        $paymentRow = $response_payment_link['response'][0];

                        if($paymentRow['quantity'] > 0){
                            $columns = ['quantity'];
                            $values = [$paymentRow['quantity']-1];
                            $condition = "ref = '".$paymentRow['ref']."'"; 
                            
                            updateData($db_prefix.'payment_link', $columns, $values, $condition);
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Product Not Available', 'message' => 'Cannot generate payment link because the product is out of stock.']);
                            exit();
                        }

                        if($paymentRow['expired_date'] == "--"){
                            $status = $paymentRow['status'];
                        }else{
                            if (isExpired($paymentRow['expired_date'])) {
                                $status = 'expired';
                            } else {
                                $status = $paymentRow['status'];
                            }
                        }

                        if($status !== "active"){
                            echo json_encode(['status' => "false", 'title' => 'Product Not Active', 'message' => 'This payment link cannot be generated because the product is currently inactive.']);
                            exit();
                        }

                        $form_data = [];

                        $customFields = [];

                        $params = [ ':paymentLinkID' => $paymentRow['ref'] ];

                        $response_PaymentLinkItem = json_decode(getData($db_prefix.'payment_link_field','WHERE paymentLinkID = :paymentLinkID', '* FROM', $params),true);
                        if($response_PaymentLinkItem['status'] == true){
                            foreach($response_PaymentLinkItem['response'] as $row){
                                $Inputoptions = [];
                                if ($row['formType'] === 'select' && $row['value'] !== '--' || $row['formType'] === 'file' && $row['value'] !== '--') {
                                    $Inputoptions = array_map('trim', explode(',', $row['value']));
                                }

                                $customFields[] = [
                                    'type'        => $row['formType'],  
                                    'name'        => strtolower(preg_replace('/[^a-z0-9_]/i', '_', $row['fieldName'])),                             
                                    'label'       => $row['fieldName'],         
                                    'options'     => $Inputoptions,      
                                    'required'    => $row['required'],                     
                                ];
                            }
                        }

                        foreach ($customFields as $field) {
                            $name  = $field['name'];
                            $label = $field['label'];
                            $type  = $field['type'];

                            if ($type === 'file' && isset($_FILES[$name]) && $_FILES[$name]['error'] === 0) {
                                $max_file_size = 5 * 1024 * 1024; 
                                
                                $mediaUpload = json_decode(uploadImage($_FILES[$name]?? null, $max_file_size), true);
                                if($mediaUpload['status'] == true){
                                    $url = $site_url.'pp-media/storage/'.$mediaUpload['file'];
                                    
                                    $form_data[] = [
                                        'label' => $label,
                                        'value' => $url
                                    ];
                                }
                            }elseif ($type === 'checkbox') {

                                $value = isset($_POST[$name])
                                    ? implode(', ', $_POST[$name])
                                    : '';

                                $form_data[] = [
                                    'label' => $label,
                                    'value' => $value
                                ];
                            }elseif (isset($_POST[$name])) {

                                $value = is_array($_POST[$name])
                                    ? implode(', ', $_POST[$name])
                                    : trim($_POST[$name]);

                                $form_data[] = [
                                    'label' => $label,
                                    'value' => $value
                                ];
                            }
                        }

                        $customer_name  = trim($_POST['full-name'] ?? '');
                        $customer_email          = trim($_POST['email-address'] ?? '');
                        $customer_mobile  = trim($_POST['mobile-number'] ?? '');

                        $source_info = json_encode($form_data);
                        $metadata = '{"paymentLink_id": "'.$itemid.'"}';

                        $currency = $paymentRow['currency'];

                        $payment_id = generateItemID(27, 27);

                        $columns = ['brand_id', 'source', 'ref', 'customer_info', 'amount', 'currency', 'source_info', 'metadata', 'created_date', 'updated_date'];
                        $values = [$paymentRow['brand_id'], 'payment-link', $payment_id, '{ "name": "'.$customer_name.'", "email": "'.$customer_email.'", "mobile": "'.$customer_mobile.'" }', money_sanitize($paymentRow['amount']), $currency, $source_info, $metadata, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                        insertData($db_prefix.'transaction', $columns, $values);

                        echo json_encode(['status' => "true", 'redirect' => $site_url.$path_payment.'/'.$payment_id]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Invalid Payment Link ID', 'message' => 'Please fill in all required fields before proceeding.']);
                    }
                }
            }

            if($action == "payment-link-default"){
                $itemid = escape_string($_POST['itemid'] ?? '');

                if($itemid == ""){
                    echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.']);
                }else{
                    $params = [ ':brand_id' => $itemid ];

                    $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                    if($response_brand['status'] == true){
                        $brandRow = $response_brand['response'][0];

                        $customer_name  = trim($_POST['full-name'] ?? '');
                        $customer_email          = trim($_POST['email-address'] ?? '');
                        $customer_mobile  = trim($_POST['mobile-number'] ?? '');

                        $metadata = '{"paymentLink_id": "'.$itemid.'"}';

                        $amount = trim($_POST['amount'] ?? '');
                        $currency = (($v = get_env('payment-link-default-currency', $response_brand['response'][0]['brand_id'])) && $v !== '--') ? $v : $brandRow['currency_code'];

                        $payment_id = generateItemID(27, 27);

                        $columns = ['brand_id', 'source', 'ref', 'customer_info', 'amount', 'currency', 'created_date', 'updated_date'];
                        $values = [$brandRow['brand_id'], 'payment-link-default', $payment_id, '{ "name": "'.$customer_name.'", "email": "'.$customer_email.'", "mobile": "'.$customer_mobile.'" }', money_sanitize($amount), $currency, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                        insertData($db_prefix.'transaction', $columns, $values);

                        echo json_encode(['status' => "true", 'redirect' => $site_url.$path_payment.'/'.$payment_id]);
                    }else{
                        echo json_encode(['status' => "false", 'title' => 'Invalid Payment Link ID', 'message' => 'Please fill in all required fields before proceeding.']);
                    }
                }
            }

            if($action == "transaction-verify"){
                    $gateway_id = escape_string($_POST['gateway-id'] ?? '');
                    $transaction_id = trim(escape_string($_POST['transaction-id'] ?? ''));

                    if($gateway_id == "" || $transaction_id == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.']);
                    }else{
                        $params = [ ':ref' => $transaction_id, ':status' => 'initiated' ];

                        $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref AND status = :status ', '* FROM', $params),true);
                        if($response_transaction['status'] == true){
                            $params = [ ':brand_id' => $response_transaction['response'][0]['brand_id'] ];

                            $response_brand = json_decode(getData($db_prefix.'brands','WHERE brand_id = :brand_id', '* FROM', $params),true);
                            if($response_brand['status'] == true){
                                $params = [ ':gateway_id' => $gateway_id, ':brand_id' => $response_brand['response'][0]['brand_id'] ];

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

                                    $currencyRates = [];

                                    $currencyRes = json_decode(getData($db_prefix.'currency', ' WHERE brand_id = "'.$response_gateway['response'][0]['brand_id'].'"'),true);

                                    if (!empty($currencyRes['response'])) {
                                        foreach ($currencyRes['response'] as $c) {
                                            $currencyRates[$c['code']] = money_sanitize($c['rate']);
                                        }
                                    }

                                    $txnAmount   = money_sanitize($response_transaction['response'][0]['amount']);
                                    $txnCurrency = $response_transaction['response'][0]['currency'];

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
                                        $rate = $currencyRates[$gatewayCurrency];

                                        $totalDiscount      = money_mul($totalDiscount, $rate);
                                        $totalProcessingFee = money_mul($totalProcessingFee, $rate);
                                    }

                                    if(file_exists(__DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php')){
                                        require_once __DIR__.'/../pp-modules/pp-gateways/'.$response_gateway['response'][0]['slug'].'/class.php';

                                        $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $response_gateway['response'][0]['slug']))) . 'Gateway';

                                        $gateway = new $class();

                                        $gateway_info = $gateway->info();
                                        $supported_languages = $gateway->supported_languages();
                                        $lang_text = $gateway->lang_text();
                                    }else{
                                        if($response_gateway['response'][0]['tab'] == 'bank'){
                                            $gateway = '';

                                            $gateway_info = [
                                                'gateway_type'        => 'manual',
                                                'verify_by'        => 'slip',
                                            ];
                                        }else{
                                            echo json_encode(['status' => "false", 'title' => 'Request Failed. Code 104', 'message' => 'Please fill in all required fields before proceeding.']);
                                            exit();
                                        }
                                    }

                                    if(isset($gateway_info)){
                                        $all_transactions = [];

                                        if(isset($gateway_info['gateway_type']) && $gateway_info['gateway_type'] == "automation"){
                                            $trxid = escape_string($_POST['trxid'] ?? '');

                                            if($trxid == ""){
                                                echo json_encode(['status' => "false", 'title' => 'Missing Transaction ID', 'message' => 'The Transaction ID field cannot be empty. Please provide a valid Transaction ID.']);
                                            }else{
                                                $params = [ ':trx_id' => $trxid ];

                                                $response_Checktransaction = json_decode(getData($db_prefix.'transaction','WHERE trx_id = :trx_id', '* FROM', $params),true);
                                                if($response_Checktransaction['status'] == true){
                                                    echo json_encode(['status' => "false", 'title' => 'Duplicate Transaction ID', 'message' => 'This Transaction ID is already exits. Please provide a different one.']);
                                                }else{
                                                    $params = [ ':sender_key' => $gateway_info['sender_key'], ':type' => $gateway_info['sender_type'], ':trx_id' => $trxid, ':status' => 'approved' ];

                                                    $response_pending_SMSTransaction = json_decode(getData($db_prefix.'sms_data','WHERE sender_key = :sender_key AND type = :type AND trx_id = :trx_id AND status = :status', '* FROM', $params), true);
                                                    if($response_pending_SMSTransaction['status'] == true){

                                                        $response_brand = json_decode(getData($db_prefix.'brands',' WHERE brand_id ="'.$response_transaction['response'][0]['brand_id'].'"'),true);
                                                        if($response_brand['status'] == true){

                                                            if (verifyPaymentTolerance($convertedAmount, $response_pending_SMSTransaction['response'][0]['amount'], $response_brand['response'][0]['payment_tolerance'])) {
                                                                $columns = ['status', 'updated_date'];
                                                                $values = ['used', getCurrentDatetime('Y-m-d H:i:s')];
                                                                $condition = 'id ="'.$response_pending_SMSTransaction['response'][0]['id'].'"'; 
                                                                
                                                                updateData($db_prefix.'sms_data', $columns, $values, $condition);

                                                                $columns = ['processing_fee', 'discount_amount', 'local_net_amount', 'local_currency', 'gateway_id', 'sender_key',  'status', 'sender', 'trx_id', 'updated_date'];
                                                                $values = [money_sanitize($totalProcessingFee), money_sanitize($totalDiscount), money_sanitize($convertedAmount), $response_gateway['response'][0]['currency'], $gateway_id, $gateway_info['sender_key'], 'completed', $response_pending_SMSTransaction['response'][0]['number'], $trxid, getCurrentDatetime('Y-m-d H:i:s')];
                                                                $condition = 'id ="'.$response_transaction['response'][0]['id'].'"'; 

                                                                updateData($db_prefix.'transaction', $columns, $values, $condition);

                                                                $params = [ ':ref' => $transaction_id, ':status' => 'completed' ];

                                                                $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref AND status = :status ', '* FROM', $params),true);

                                                                $metadata = json_decode($response_transaction['response'][0]['metadata'], true) ?: [];

                                                                $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$response_brand['response'][0]['brand_id'].'" AND gateway_id = "'.$gateway_id.'"'),true);

                                                                $gateway = $response_gateway['response'][0]['name'] ?? '';

                                                                $customer_info = json_decode($response_transaction['response'][0]['customer_info'], true) ?: [];

                                                                $all_transactions[] = [
                                                                    "pp_id" => $response_transaction['response'][0]['ref'],
                                                                    "full_name" => $customer_info['name'] ?? 'N/A',
                                                                    "email_address" => $customer_info['email'] ?? 'N/A',
                                                                    "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                                                                    "gateway" => $gateway,
                                                                    "amount" => money_round($response_transaction['response'][0]['amount']),
                                                                    "fee" => money_round($response_transaction['response'][0]['processing_fee']),
                                                                    "discount_amount" => money_round($response_transaction['response'][0]['discount_amount']),
                                                                    "total" => money_sub(money_add($response_transaction['response'][0]['amount'], $response_transaction['response'][0]['processing_fee']),$response_transaction['response'][0]['discount_amount']),
                                                                    "local_net_amount" => money_round($response_transaction['response'][0]['local_net_amount']),
                                                                    "currency" => $response_transaction['response'][0]['currency'],
                                                                    "local_currency" => $response_transaction['response'][0]['local_currency'],
                                                                    "metadata" => $metadata, // ← AS-IS
                                                                    "sender" => $response_pending_SMSTransaction['response'][0]['number'],
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
                                                                        "total" => money_sub(money_add($response_transaction['response'][0]['amount'], $response_transaction['response'][0]['processing_fee']),$response_transaction['response'][0]['discount_amount']),
                                                                        "local_net_amount" => money_round($response_transaction['response'][0]['local_net_amount']),
                                                                        "currency" => $response_transaction['response'][0]['currency'],
                                                                        "local_currency" => $response_transaction['response'][0]['local_currency'],
                                                                        "metadata" => $metadata, // ← AS-IS
                                                                        "sender" => $response_pending_SMSTransaction['response'][0]['number'],
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

                                                                echo json_encode(['status' => "true", 'title' => 'Transaction Verified', 'message' => 'The Transaction ID has been successfully verified.']);
                                                            }else{
                                                                echo json_encode(['status' => "false", 'title' => 'Transaction Not Found', 'message' => 'The Transaction ID you entered could not be verified. Please check the ID and try again after some time.']);
                                                            }
                                                        }
                                                    }else{
                                                       if(isset($options['pending_payment']) && $options['pending_payment'] == "enable"){
                                                          $mobile_number = escape_string($_POST['mobile_number'] ?? '');

                                                          if($mobile_number == ""){
                                                              echo json_encode(['status' => "false", 'title' => 'Transaction Not Matched', 'message' => 'The Transaction ID you entered could not be verified. Please try again after some time, or enter your phone number and submit for manual approval.', 'visible_number' => "true"]);
                                                          }else{
                                                                $columns = ['processing_fee', 'discount_amount', 'local_net_amount', 'local_currency', 'gateway_id', 'sender_key',  'status', 'sender', 'trx_id', 'updated_date'];
                                                                $values = [money_sanitize($totalProcessingFee), money_sanitize($totalDiscount), money_sanitize($convertedAmount), $response_gateway['response'][0]['currency'], $gateway_id, $gateway_info['sender_key'], 'pending', $mobile_number, $trxid, getCurrentDatetime('Y-m-d H:i:s')];
                                                                $condition = 'id ="'.$response_transaction['response'][0]['id'].'"'; 

                                                                updateData($db_prefix.'transaction', $columns, $values, $condition);
                                                                echo json_encode([ 'status' => "true", 'title' => 'Transaction Submitted', 'message' => 'Your Transaction ID has been successfully submitted' ]);
                                                          }
                                                       }else{
                                                           echo json_encode(['status' => "false", 'title' => 'Transaction Not Found', 'message' => 'The Transaction ID you entered could not be verified. Please check the ID and try again after some time.']);
                                                       }
                                                    }
                                                }
                                            }
                                        }
                                        if(isset($gateway_info['gateway_type']) && $gateway_info['gateway_type'] == "manual"){
                                            if(isset($gateway_info['verify_by']) && $gateway_info['verify_by'] == "trxid"){
                                                $trxid = escape_string($_POST['trxid'] ?? '');

                                                if($trxid == ""){
                                                    echo json_encode(['status' => "false", 'title' => 'Missing Transaction ID', 'message' => 'The Transaction ID field cannot be empty. Please provide a valid Transaction ID.']);
                                                }else{
                                                    $params = [ ':trx_id' => $trxid ];

                                                    $response_Checktransaction = json_decode(getData($db_prefix.'transaction','WHERE trx_id = :trx_id', '* FROM', $params),true);
                                                    if($response_Checktransaction['status'] == true){
                                                        echo json_encode(['status' => "false", 'title' => 'Duplicate Transaction ID', 'message' => 'This Transaction ID is already exits. Please provide a different one.']);
                                                    }else{
                                                        $response_brand = json_decode(getData($db_prefix.'brands',' WHERE brand_id ="'.$response_transaction['response'][0]['brand_id'].'"'),true);
                                                        if($response_brand['status'] == true){
                                                            $columns = ['processing_fee', 'discount_amount', 'local_net_amount', 'local_currency', 'gateway_id', 'status', 'trx_id', 'updated_date'];
                                                            $values = [money_sanitize($totalProcessingFee), money_sanitize($totalDiscount), money_sanitize($convertedAmount), $response_gateway['response'][0]['currency'], $gateway_id, 'pending', $trxid, getCurrentDatetime('Y-m-d H:i:s')];
                                                            $condition = 'id ="'.$response_transaction['response'][0]['id'].'"'; 

                                                            updateData($db_prefix.'transaction', $columns, $values, $condition);

                                                            $params = [ ':ref' => $transaction_id, ':status' => 'pending' ];

                                                            $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref AND status = :status ', '* FROM', $params),true);

                                                            $metadata = json_decode($response_transaction['response'][0]['metadata'], true) ?: [];

                                                            $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$response_brand['response'][0]['brand_id'].'" AND gateway_id = "'.$gateway_id.'"'),true);

                                                            $gateway = $response_gateway['response'][0]['name'] ?? '';

                                                            $customer_info = json_decode($response_transaction['response'][0]['customer_info'], true) ?: [];

                                                            $all_transactions[] = [
                                                                "pp_id" => $response_transaction['response'][0]['ref'],
                                                                "full_name" => $customer_info['name'] ?? 'N/A',
                                                                "email_address" => $customer_info['email'] ?? 'N/A',
                                                                "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                                                                "gateway" => $gateway,
                                                                "amount" => money_round($response_transaction['response'][0]['amount']),
                                                                "fee" => money_round($response_transaction['response'][0]['processing_fee']),
                                                                "discount_amount" => money_round($response_transaction['response'][0]['discount_amount']),
                                                                "total" => money_sub(money_add($response_transaction['response'][0]['amount'], $response_transaction['response'][0]['processing_fee']),$response_transaction['response'][0]['discount_amount']),
                                                                "local_net_amount" => money_round($response_transaction['response'][0]['local_net_amount']),
                                                                "currency" => $response_transaction['response'][0]['currency'],
                                                                "local_currency" => $response_transaction['response'][0]['local_currency'],
                                                                "metadata" => $metadata, // ← AS-IS
                                                                "sender" => '--',
                                                                "transaction_id" => $response_transaction['response'][0]['trx_id'],
                                                                "status" => $response_transaction['response'][0]['status'],
                                                                "date" => convertUTCtoUserTZ($response_transaction['response'][0]['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                                            ];

                                                            echo json_encode([ 'status' => "true", 'title' => 'Transaction Submitted', 'message' => 'Your Transaction ID has been successfully submitted' ]);
                                                        }
                                                    }
                                                }
                                            }else{
                                                if(isset($gateway_info['verify_by']) && $gateway_info['verify_by'] == "slip"){
                                                    $slip = escape_string($_FILES['slip'] ?? '');

                                                    if($slip == ""){
                                                        echo json_encode(['status' => "false", 'title' => 'Missing Transaction Slip', 'message' => 'The Transaction slip field cannot be empty. Please provide a valid Transaction Slip.']);
                                                    }else{
                                                        $response_brand = json_decode(getData($db_prefix.'brands',' WHERE brand_id ="'.$response_transaction['response'][0]['brand_id'].'"'),true);
                                                        if($response_brand['status'] == true){
                                                            $max_file_size = 5 * 1024 * 1024; 
                                                            
                                                            $mediaUpload = json_decode(uploadImage($slip ?? null, $max_file_size), true);
                                                            if($mediaUpload['status'] == true){
                                                                $trx_slip = $site_url.'pp-media/storage/'.$mediaUpload['file'];
                                                            }else{
                                                                echo json_encode(['status' => "false", 'title' => 'Missing Transaction Slip', 'message' => 'The Transaction slip field cannot be empty. Please provide a valid Transaction Slip.']);
                                                                exit();
                                                            }

                                                            $columns = ['processing_fee', 'discount_amount', 'local_net_amount', 'local_currency', 'gateway_id', 'status', 'trx_slip', 'updated_date'];
                                                            $values = [money_sanitize($totalProcessingFee), money_sanitize($totalDiscount), money_sanitize($convertedAmount), $response_gateway['response'][0]['currency'], $gateway_id, 'pending', $trx_slip, getCurrentDatetime('Y-m-d H:i:s')];
                                                            $condition = 'id ="'.$response_transaction['response'][0]['id'].'"'; 

                                                            updateData($db_prefix.'transaction', $columns, $values, $condition);

                                                            $params = [ ':ref' => $transaction_id, ':status' => 'pending' ];

                                                            $response_transaction = json_decode(getData($db_prefix.'transaction','WHERE ref = :ref AND status = :status ', '* FROM', $params),true);

                                                            $metadata = json_decode($response_transaction['response'][0]['metadata'], true) ?: [];

                                                            $response_gateway = json_decode(getData($db_prefix.'gateways',' WHERE brand_id ="'.$response_brand['response'][0]['brand_id'].'" AND gateway_id = "'.$gateway_id.'"'),true);

                                                            $gateway = $response_gateway['response'][0]['name'] ?? '';

                                                            $customer_info = json_decode($response_transaction['response'][0]['customer_info'], true) ?: [];

                                                            $all_transactions[] = [
                                                                "pp_id" => $response_transaction['response'][0]['ref'],
                                                                "full_name" => $customer_info['name'] ?? 'N/A',
                                                                "email_address" => $customer_info['email'] ?? 'N/A',
                                                                "mobile_number" => $customer_info['mobile'] ?? 'N/A',
                                                                "gateway" => $gateway,
                                                                "amount" => money_round($response_transaction['response'][0]['amount']),
                                                                "fee" => money_round($response_transaction['response'][0]['processing_fee']),
                                                                "discount_amount" => money_round($response_transaction['response'][0]['discount_amount']),
                                                                "total" => money_sub(money_add($response_transaction['response'][0]['amount'], $response_transaction['response'][0]['processing_fee']),$response_transaction['response'][0]['discount_amount']),
                                                                "local_net_amount" => money_round($response_transaction['response'][0]['local_net_amount']),
                                                                "currency" => $response_transaction['response'][0]['currency'],
                                                                "local_currency" => $response_transaction['response'][0]['local_currency'],
                                                                "metadata" => $metadata, // ← AS-IS
                                                                "sender" => '--',
                                                                "transaction_id" => '--',
                                                                "status" => $response_transaction['response'][0]['status'],
                                                                "date" => convertUTCtoUserTZ($response_transaction['response'][0]['created_date'], ($response_brand['response'][0]['timezone'] === '--' || $response_brand['response'][0]['timezone'] === '') ? 'Asia/Dhaka' : $response_brand['response'][0]['timezone'], "M d, Y h:i A")
                                                            ];

                                                            echo json_encode([ 'status' => "true", 'title' => 'Transaction Submitted', 'message' => 'Your Transaction ID has been successfully submitted' ]);
                                                        }
                                                    }
                                                }else{
                                                    echo json_encode(['status' => "false", 'title' => 'Request Failed. Code 106', 'message' => 'Please fill in all required fields before proceeding.']);
                                                }
                                            }
                                        }

                                        if (!empty($all_transactions)) {
                                            do_action('transactions.updated', $all_transactions);
                                        }
                                    }else{
                                        echo json_encode(['status' => "false", 'title' => 'Request Failed. Code 105', 'message' => 'Please fill in all required fields before proceeding.']);
                                    }
                                }else{
                                    echo json_encode(['status' => "false", 'title' => 'Request Failed. Code 103', 'message' => 'Please fill in all required fields before proceeding.']);
                                }
                            }else{
                                echo json_encode(['status' => "false", 'title' => 'Request Failed. Code 102', 'message' => 'Please fill in all required fields before proceeding.']);
                            }
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Request Failed. Code 101', 'message' => 'Please fill in all required fields before proceeding.']);
                        }
                    }
            }
        }
        exit();
    }

    if(isset($_POST['action-companion'])){
        $action = escape_string($_POST['action-companion'] ?? '');

        if($action == ""){
            echo json_encode(['status' => "false", 'title' => 'Oops! Something went wrong', 'message' => 'Your request could not be processed. Please try again.']);
        }else{
            if($action == "login"){
                if (!empty($pp_demo_mode)) {
                    echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.']);
                }else{
                    $onetimepassword = escape_string($_POST['onetimepassword'] ?? '');
                    $name = escape_string($_POST['name'] ?? '');
                    $model = escape_string($_POST['model'] ?? '');
                    $android_level = escape_string($_POST['android_level'] ?? '');
                    $app_version = escape_string($_POST['app_version'] ?? '');

                    if($onetimepassword == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.']);
                    }else{
                        $params = [ ':otp' => $onetimepassword ];

                        $response = json_decode(getData($db_prefix.'device','WHERE otp = :otp', '* FROM', $params),true);
                        if($response['status'] == true){
                            $otp_new = generateItemID();

                            $columns = ['otp', 'name', 'model', 'android_level', 'app_version', 'status', 'updated_date'];
                            $values = [$otp_new, $name, $model, $android_level, $app_version, 'used', getCurrentDatetime('Y-m-d H:i:s')];

                            $condition = "id = '".$response['response'][0]['id']."'"; 
                            
                            updateData($db_prefix.'device', $columns, $values, $condition);

                            echo json_encode(['status' => "true", 'token' => $otp_new]);
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Invalid Credentials', 'message' => 'Please enter the correct credentials or scan the QR code again.']);
                        }
                    }
                }
            }

            if($action == "account-information"){
                if (!empty($pp_demo_mode)) {
                    echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.']);
                }else{
                    $token = escape_string($_POST['token'] ?? '');

                    if($token == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.']);
                    }else{
                        $params = [ ':otp' => $token, ':status' => 'used' ];

                        $response = json_decode(getData($db_prefix.'device','WHERE otp = :otp AND status = :status', '* FROM', $params),true);
                        if($response['status'] == true){
                            $params = [ ':cookie' => $response['response'][0]['d_id'] ];

                            $responseLog = json_decode(getData($db_prefix.'browser_log','WHERE cookie = :cookie', '* FROM', $params),true);
                            if($responseLog['status'] == true){
                                $params = [ ':a_id' => $responseLog['response'][0]['a_id'] ];
                                
                                $responseAdmin = json_decode(getData($db_prefix.'admin','WHERE a_id = :a_id', '* FROM', $params),true);
                                if($responseAdmin['status'] == true){


                                    $response_result = json_decode(getData($db_prefix.'sms_data',' WHERE source = "app" AND device_id = "'.$response['response'][0]['device_id'].'" AND status NOT IN ("awaiting-review") ORDER BY 1 DESC'),true);

                                    if ($response_result['status'] == true) {

                                        $response = [
                                            'status' => 'true',
                                            'fullname' => $responseAdmin['response'][0]['full_name'] ?? '',
                                            'email'    => $responseAdmin['response'][0]['email'] ?? '',
                                            'stored_count'   => 0,
                                            'used_count' => 0,
                                            'error_count'    => 0,
                                            'stored'   => [],
                                            'used' => [],
                                            'error'    => []
                                        ];

                                        foreach ($response_result['response'] as $row) {
                                            $json_status = ($row['status'] === 'approved') ? 'stored' : $row['status'];

                                            $item = [
                                                'id'        => $row['id'],
                                                'sender'    => $row['sender'],
                                                'message'   => $row['message'],
                                                'reason'   => $row['reason'],
                                                'simslot'   => $row['simslot'],
                                                'timestamp' => convertUTCtoUserTZ($row['created_date'], (get_env('geneal-application-settings-default_timezone') === '--' || get_env('geneal-application-settings-default_timezone') === '') ? 'Asia/Dhaka' : get_env('geneal-application-settings-default_timezone'), "M d, Y h:i A"),
                                                'status'    => $json_status
                                            ];

                                            switch ($row['status']) {
                                                case 'approved':
                                                case 'awaiting-review':
                                                    $response['stored'][] = $item;
                                                    $response['stored_count']++;
                                                    break;

                                                case 'used':
                                                    $response['used'][] = $item;
                                                    $response['used_count']++;
                                                    break;

                                                case 'error':
                                                    $response['error'][] = $item;
                                                    $response['error_count']++;
                                                    break;
                                            }
                                        }

                                        echo json_encode($response);
                                    }else{
                                        $response = [
                                            'status' => 'true',
                                            'fullname' => $responseAdmin['response'][0]['full_name'] ?? '',
                                            'email'    => $responseAdmin['response'][0]['email'] ?? '',
                                            'stored_count'   => 0,
                                            'used_count' => 0,
                                            'error_count'    => 0,
                                            'stored'   => [],
                                            'used' => [],
                                            'error'    => []
                                        ];

                                        echo json_encode($response);
                                    }
                                }else{
                                    echo json_encode(['status' => "false", 'title' => 'Authentication Failed', 'message' => 'Please try again or scan the QR code again.']);
                                }
                            }else{
                                echo json_encode(['status' => "false", 'title' => 'Authentication Failed', 'message' => 'Please try again or scan the QR code again.']);
                            }
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Authentication Failed', 'message' => 'Please try again or scan the QR code again.']);
                        }
                    }
                }
            }

            if($action == "sms-transmit-bulk"){
                if (!empty($pp_demo_mode)) {
                    echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.']);
                }else{
                    $token = escape_string($_POST['token'] ?? '');
                    $sms_list_raw = $_POST['sms_list'] ?? '';

                    if($token == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.']);
                    }else{
                        $params = [ ':otp' => $token, ':status' => 'used' ];

                        $response = json_decode(getData($db_prefix.'device','WHERE otp = :otp AND status = :status', '* FROM', $params),true);
                        if($response['status'] == true){
                            $sms_list = json_decode($sms_list_raw, true);

                            foreach ($sms_list as $sms) {
                                $id = trim((string)escape_string($sms['id'] ?? ''));
                                $sender = strtolower((string)trim(escape_string($sms['sender'] ?? '')));
                                $message = trim((string)escape_string($sms['message'] ?? ''));
                                $simslot = trim((string)escape_string($sms['simSlot'] ?? ''));
                                $timestamp = trim((string)escape_string($sms['timestamp'] ?? ''));
                                
                                $status = 'approved';
                                $reason = '--';

                                $device_id = $response['response'][0]['device_id'];

                                $senderInfo = senderWhitelist($sender);
                                if($senderInfo) {
                                    $sender_key = $senderInfo['provider_key'];
                                    $currency = $senderInfo['currency'];
                                    $balance_verify = $senderInfo['balance_verify'];
                                }else{
                                    $sender_key = '--';
                                    $currency = '--';
                                    $balance_verify = '--';
                                }

                                $result = MFSMessageVerified($sender_key, $message);

                                if ($result === false) {
                                    $status = 'error';
                                    $reason = 'Invalid or unknown message. Code 101';

                                    $columns = ['source', 'device_id', 'sender', 'simslot', 'status', 'message', 'reason', 'created_date', 'updated_date'];
                                    $values = ['app', $device_id, $sender, $simslot, $status, $message, $reason, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                    insertData($db_prefix.'sms_data', $columns, $values);

                                    $columns = ['last_sync'];
                                    $values = [getCurrentDatetime('Y-m-d H:i:s')];

                                    $condition = "id = '".$response['response'][0]['id']."'"; 
                                    
                                    updateData($db_prefix.'device', $columns, $values, $condition);

                                    echo json_encode(['status' => "false", 'title' => 'Invalid or unknown MFS message', 'message' => 'Please fill in all required fields before proceeding.']);
                                } else {
                                    $type = escape_string($result['type'] ?? '');
                                    $amount = escape_string($result['amount'] ?? '0');
                                    $balance = escape_string($result['balance'] ?? '0');
                                    $phone_number = escape_string($result['sender'] ?? '');
                                    $transaction_id = escape_string($result['trxid'] ?? '');
                                    $datetime = escape_string($result['datetime'] ?? '');

                                    if($type == "" || $amount == "" || $phone_number == "" || $transaction_id == ""){
                                        $status = 'error';
                                        $reason = 'Invalid or unknown message. Code 102';

                                        $columns = ['source', 'device_id', 'sender', 'simslot', 'status', 'message', 'reason', 'created_date', 'updated_date'];
                                        $values = ['app', $device_id, $sender, $simslot, $status, $message, $reason, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                        insertData($db_prefix.'sms_data', $columns, $values);

                                        $columns = ['last_sync'];
                                        $values = [getCurrentDatetime('Y-m-d H:i:s')];

                                        $condition = "id = '".$response['response'][0]['id']."'"; 
                                        
                                        updateData($db_prefix.'device', $columns, $values, $condition);

                                        echo json_encode(['status' => "false", 'title' => 'Invalid or unknown MFS message', 'message' => 'Please fill in all required fields before proceeding.']);
                                        exit();
                                    }

                                    $params = [ ':sender_key' => $sender_key, ':trx_id' => $transaction_id ];

                                    $responseSmsData = json_decode(getData($db_prefix.'sms_data','WHERE sender_key = :sender_key AND trx_id = :trx_id', '* FROM', $params),true);
                                    if($responseSmsData['status'] == false){
                                        if($balance_verify == "false"){
                                            $status = 'approved';
                                            $reason = '--';

                                            $columns = ['source', 'device_id', 'sender', 'sender_key', 'simslot', 'number', 'amount', 'currency', 'trx_id', 'balance', 'type', 'status', 'message', 'reason', 'created_date', 'updated_date'];
                                            $values = ['app', $device_id, $sender, $sender_key, $simslot, $phone_number, money_sanitize($amount), $currency, $transaction_id, money_sanitize($balance), $type, $status, $message, $reason, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                            insertData($db_prefix.'sms_data', $columns, $values);

                                            $columns = ['last_sync'];
                                            $values = [getCurrentDatetime('Y-m-d H:i:s')];

                                            $condition = "id = '".$response['response'][0]['id']."'"; 
                                            
                                            updateData($db_prefix.'device', $columns, $values, $condition);

                                            echo json_encode(['status' => 'true', 'title' => 'SMS Data Created', 'message' => 'The sms data has been created successfully.']);
                                        }else{
                                            $params = [ ':device_id' => $device_id, ':sender_key' => $sender_key, ':type' => $type ];

                                            $response_balance_verification = json_decode(getData($db_prefix.'balance_verification','WHERE device_id = :device_id AND sender_key = :sender_key AND type = :type', '* FROM', $params),true);
                                            if($response_balance_verification['status'] == true){
                                                if($response_balance_verification['response'][0]['status'] == "active"){
                                                    if($simslot == 1){
                                                        $bsimslot = 'Sim1';
                                                    }else{
                                                        $bsimslot = 'Sim2';
                                                    }

                                                    $expected_balance = money_add($response_balance_verification['response'][0]['current_balance'], $amount);

                                                    if($expected_balance == $balance){
                                                        if($response_balance_verification['response'][0]['simslot'] !== "Any"){
                                                            if($response_balance_verification['response'][0]['simslot'] == $bsimslot){
                                                                $status = 'approved';
                                                                $reason = '--';

                                                                $columns = ['current_balance', 'updated_date'];
                                                                $values = [money_sanitize($expected_balance), getCurrentDatetime('Y-m-d H:i:s')];
                                                                $condition = "id = '".$response_balance_verification['response'][0]['id']."'"; 
                                                                
                                                                updateData($db_prefix.'balance_verification', $columns, $values, $condition);
                                                            }else{
                                                                $status = 'awaiting-review';
                                                                $reason = 'SIM slot and expected slot do not match. Recorded: '.$bsimslot.'; Expected: '.$response_balance_verification['response'][0]['simslot'];
                                                            }
                                                        }else{
                                                            $status = 'approved';
                                                            $reason = '--';

                                                            $columns = ['current_balance', 'updated_date'];
                                                            $values = [money_sanitize($expected_balance), getCurrentDatetime('Y-m-d H:i:s')];
                                                            $condition = "id = '".$response_balance_verification['response'][0]['id']."'"; 
                                                            
                                                            updateData($db_prefix.'balance_verification', $columns, $values, $condition);
                                                        }

                                                        $columns = ['source', 'device_id', 'sender', 'sender_key', 'simslot', 'number', 'amount', 'currency', 'trx_id', 'balance', 'type', 'status', 'message', 'reason', 'created_date', 'updated_date'];
                                                        $values = ['app', $device_id, $sender, $sender_key, $simslot, $phone_number, money_sanitize($amount), $currency, $transaction_id, money_sanitize($balance), $type, $status, $message, $reason, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                                        insertData($db_prefix.'sms_data', $columns, $values);

                                                        $columns = ['last_sync'];
                                                        $values = [getCurrentDatetime('Y-m-d H:i:s')];

                                                        $condition = "id = '".$response['response'][0]['id']."'"; 
                                                        
                                                        updateData($db_prefix.'device', $columns, $values, $condition);

                                                        echo json_encode(['status' => 'true', 'title' => 'SMS Data Created', 'message' => 'The sms data has been created successfully.']);
                                                    }else{
                                                        $reasons = [];

                                                        $status = 'awaiting-review';
                                                        $reasons[] = 'SMS balance and expected balance do not match. Recorded SMS balance: '.money_round($balance).'; Expected balance: '.money_round($expected_balance);

                                                        if($response_balance_verification['response'][0]['simslot'] !== "Any"){
                                                            if($response_balance_verification['response'][0]['simslot'] == $bsimslot){

                                                            }else{
                                                                $status = 'awaiting-review';
                                                                $reasons[] = 'SIM slot and expected slot do not match. Recorded: '.$bsimslot.'; Expected: '.$response_balance_verification['response'][0]['simslot'];
                                                            }
                                                        }

                                                        $reason = implode(' | ', $reasons);

                                                        $columns = ['source', 'device_id', 'sender', 'sender_key', 'simslot', 'number', 'amount', 'currency', 'trx_id', 'balance', 'type', 'status', 'message', 'reason', 'created_date', 'updated_date'];
                                                        $values = ['app', $device_id, $sender, $sender_key, $simslot, $phone_number, money_sanitize($amount), $currency, $transaction_id, money_sanitize($balance), $type, $status, $message, $reason, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                                        insertData($db_prefix.'sms_data', $columns, $values);

                                                        $columns = ['last_sync'];
                                                        $values = [getCurrentDatetime('Y-m-d H:i:s')];

                                                        $condition = "id = '".$response['response'][0]['id']."'"; 
                                                        
                                                        updateData($db_prefix.'device', $columns, $values, $condition);

                                                        reconcileByLongestChain($device_id, $sender_key, $type);

                                                        echo json_encode(['status' => 'true', 'title' => 'SMS Data Created', 'message' => 'The sms data has been created successfully.']);
                                                    }
                                                }else{
                                                    $status = 'approved';
                                                    $reason = '--';

                                                    $columns = ['source', 'device_id', 'sender', 'sender_key', 'simslot', 'number', 'amount', 'currency', 'trx_id', 'balance', 'type', 'status', 'message', 'reason', 'created_date', 'updated_date'];
                                                    $values = ['app', $device_id, $sender, $sender_key, $simslot, $phone_number, money_sanitize($amount), $currency, $transaction_id, money_sanitize($balance), $type, $status, $message, $reason, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                                    insertData($db_prefix.'sms_data', $columns, $values);

                                                    $columns = ['last_sync'];
                                                    $values = [getCurrentDatetime('Y-m-d H:i:s')];

                                                    $condition = "id = '".$response['response'][0]['id']."'"; 
                                                    
                                                    updateData($db_prefix.'device', $columns, $values, $condition);

                                                    echo json_encode(['status' => 'true', 'title' => 'SMS Data Created', 'message' => 'The sms data has been created successfully.']);
                                                }
                                            }else{
                                                $status = 'approved';
                                                $reason = '--';

                                                $columns = ['source', 'device_id', 'sender', 'sender_key', 'simslot', 'number', 'amount', 'currency', 'trx_id', 'balance', 'type', 'status', 'message', 'reason', 'created_date', 'updated_date'];
                                                $values = ['app', $device_id, $sender, $sender_key, $simslot, $phone_number, money_sanitize($amount), $currency, $transaction_id, money_sanitize($balance), $type, $status, $message, $reason, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                                insertData($db_prefix.'sms_data', $columns, $values);

                                                $columns = ['last_sync'];
                                                $values = [getCurrentDatetime('Y-m-d H:i:s')];

                                                $condition = "id = '".$response['response'][0]['id']."'"; 
                                                
                                                updateData($db_prefix.'device', $columns, $values, $condition);

                                                echo json_encode(['status' => 'true', 'title' => 'SMS Data Created', 'message' => 'The sms data has been created successfully.']);
                                            }
                                        }
                                    }else{
                                        $status = 'error';
                                        $reason = 'Duplicate message. Code 103';

                                        $columns = ['source', 'device_id', 'sender', 'simslot', 'status', 'message', 'reason', 'created_date', 'updated_date'];
                                        $values = ['app', $device_id, $sender, $simslot, $status, $message, $reason, getCurrentDatetime('Y-m-d H:i:s'), getCurrentDatetime('Y-m-d H:i:s')];

                                        insertData($db_prefix.'sms_data', $columns, $values);

                                        $columns = ['last_sync'];
                                        $values = [getCurrentDatetime('Y-m-d H:i:s')];

                                        $condition = "id = '".$response['response'][0]['id']."'"; 
                                        
                                        updateData($db_prefix.'device', $columns, $values, $condition);

                                        echo json_encode(['status' => 'false', 'title' => 'Duplicate Transaction', 'message' => 'The provided Transaction ID already exists in our system.']); 
                                    }
                                }
                            }
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Authentication Failed', 'message' => 'Please try again or scan the QR code again.']);
                        }
                    }
                }
            }


            if($action == "sms-transmit-sender"){
                if (!empty($pp_demo_mode)) {
                    echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.']);
                }else{
                    $token = escape_string($_POST['token'] ?? '');

                    if($token == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.']);
                    }else{
                        $params = [ ':otp' => $token, ':status' => 'used' ];

                        $response = json_decode(getData($db_prefix.'device','WHERE otp = :otp AND status = :status', '* FROM', $params),true);
                        if($response['status'] == true){
                            $senders = senderWhitelist(null, null, 'senders');

                            echo json_encode(["status" => "true","senders" => $senders], JSON_PRETTY_PRINT);
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Authentication Failed', 'message' => 'Please try again or scan the QR code again.']);
                        }
                    }
                }
            }

            if($action == "delete-sms-data"){
                if (!empty($pp_demo_mode)) {
                    echo json_encode(['status' => "false", 'title' => 'Demo Restriction', 'message' => 'This feature is disabled in the demo version.']);
                }else{
                    $token = escape_string($_POST['token'] ?? '');
                    $stored = escape_string($_POST['stored'] ?? '');
                    $used = escape_string($_POST['used'] ?? '');
                    $error = escape_string($_POST['error'] ?? '');

                    if($token == ""){
                        echo json_encode(['status' => "false", 'title' => 'Incomplete Information', 'message' => 'Please fill in all required fields before proceeding.']);
                    }else{
                        $params = [ ':otp' => $token, ':status' => 'used' ];

                        $response = json_decode(getData($db_prefix.'device','WHERE otp = :otp AND status = :status', '* FROM', $params),true);
                        if($response['status'] == true){
                            if($stored == "yes"){
                                $condition = "device_id = '".$response['response'][0]['device_id']."' AND status = 'approved'"; 
                                
                                deleteData($db_prefix.'sms_data', $condition);

                                $condition = "device_id = '".$response['response'][0]['device_id']."' AND status = 'awaiting-review'"; 
                                
                                deleteData($db_prefix.'sms_data', $condition);
                            }

                            if($used == "yes"){
                                $condition = "device_id = '".$response['response'][0]['device_id']."' AND status = 'used'"; 
                                
                                deleteData($db_prefix.'sms_data', $condition);
                            }

                            if($error == "yes"){
                                $condition = "device_id = '".$response['response'][0]['device_id']."' AND status = 'error'"; 
                                
                                deleteData($db_prefix.'sms_data', $condition);
                            }

                            echo json_encode(['status' => "true", 'title' => 'Deletion Successful', 'message' => 'The selected data has been deleted successfully.']);
                        }else{
                            echo json_encode(['status' => "false", 'title' => 'Authentication Failed', 'message' => 'Please try again or scan the QR code again.']);
                        }
                    }
                }
            }


        }
        exit();
    }

    if (isset($_POST['root'])) {
        if($global_user_login == true){
            $root = escape_string(trim($_POST['root'] ?? ''));
            $root = preg_replace('/[^a-zA-Z0-9\-\/_]/', '', $root); // sanitize

            if ($root == "") {
                echo json_encode(['status' => "false", 'message' => 'Something went wrong!']);
                exit;
            }

            $initPendingTrscount = 0;
            $response_dashboard_info = json_decode(getData($db_prefix.'transaction',' WHERE brand_id = "'.$global_response_brand['response'][0]['brand_id'].'" AND status = "pending"'),true);
            if($response_dashboard_info['status'] == true){
                foreach($response_dashboard_info['response'] as $row){
                    $initPendingTrscount++;
                }
            }
?> 
            <script>
                function initPendingTrs(){
                    <?php
                        if($initPendingTrscount == 0){
                    ?>
                           document.querySelector(".nav-item-transaction .bg-danger").style.display = 'none';
                    <?php
                        }else{
                    ?>
                           document.querySelector(".nav-item-transaction .bg-danger").innerHTML = '<?= $initPendingTrscount ?>';
                    <?php
                        }
                    ?>
                }
                initPendingTrs();
            </script>
<?php
            $base = __DIR__ . '/../pp-admin/pp-root/';

            if (file_exists($base . $root . '.php')) {
                include($base . $root . '.php');
            }

            else if (file_exists($base . $root . '/index.php')) {
                include($base . $root . '/index.php');
            } else {
                echo 'Page not found!';
                exit;
            }
        }else{
            echo json_encode(['status' => 'false', 'message' => 'Invalid request']);
        }
        exit;

    }
