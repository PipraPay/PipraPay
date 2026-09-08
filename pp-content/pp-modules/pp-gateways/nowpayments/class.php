<?php
/**
 * Piprapay NowPayments Module
 *
 * @category  Payment_Gateway
 * @package   Piprapay
 * @author    Obaidullah Rion <support@webfuran.com>
 * @copyright 2026 WebFuran
 * @license   MIT (https://opensource.org/licenses/MIT)
 * @link      https://github.com/obaidullahrion
 */
    class NowpaymentsGateway
    {
        public function info()
        {
            return [
                'title'        => 'NOWPayments Crypto',
                'logo'         => 'assets/icon.png',
                'currency'     => 'USD',
                'tab'          => 'global',
                'gateway_type' => 'api',
            ];
        }

        public function color()
        {
            return [
                'primary_color'   => '#6c35c3',
                'text_color'      => '#FFFFFF',
                'btn_color'       => '#6c35c3',
                'btn_text_color'  => '#FFFFFF',
            ];
        }

        public function fields()
        {
            return [
                [
                    'name'  => 'api_key',
                    'label' => 'NOWPayments API Key',
                    'type'  => 'text',
                ],
                [
                    'name'  => 'ipn_secret_key',
                    'label' => 'IPN Secret Key',
                    'type'  => 'text',
                ],
                [
                    'name'    => 'mode',
                    'label'   => 'Mode',
                    'type'    => 'select',
                    'options' => [
                        'live'    => 'Live',
                        'sandbox' => 'Sandbox',
                    ],
                    'value'    => 'live',
                    'required' => true,
                    'multiple' => false,
                ],
            ];
        }

        // NOWPayments requires keys sorted recursively before HMAC signing.
        // A flat ksort breaks on nested fields like "fee" — this handles that.
        private function tksort(array $arr): array
        {
            ksort($arr);
            foreach (array_keys($arr) as $k) {
                if (is_array($arr[$k])) {
                    $arr[$k] = $this->tksort($arr[$k]);
                }
            }
            return $arr;
        }

        private function base_url($mode)
        {
            return ($mode === 'sandbox')
                ? 'https://api-sandbox.nowpayments.io/v1'
                : 'https://api.nowpayments.io/v1';
        }

        private function api_get($url, $api_key)
        {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'x-api-key: ' . $api_key,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response  = curl_exec($ch);
            $curl_err  = curl_errno($ch);
            $curl_msg  = curl_error($ch);
            curl_close($ch);

            if ($curl_err !== 0 || $response === false) {
                error_log('NOWPayments GET failed [' . $curl_err . ']: ' . $curl_msg . ' url=' . $url);
                return null;
            }

            $decoded = json_decode($response, true);
            if ($decoded === null) {
                error_log('NOWPayments GET bad JSON url=' . $url . ' body=' . substr($response, 0, 200));
                return null;
            }

            return $decoded;
        }

        private function api_post($url, $api_key, $payload)
        {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    'x-api-key: ' . $api_key,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response  = curl_exec($ch);
            $curl_err  = curl_errno($ch);
            $curl_msg  = curl_error($ch);
            curl_close($ch);

            if ($curl_err !== 0 || $response === false) {
                error_log('NOWPayments POST failed [' . $curl_err . ']: ' . $curl_msg . ' url=' . $url);
                return null;
            }

            $decoded = json_decode($response, true);
            if ($decoded === null) {
                error_log('NOWPayments POST bad JSON url=' . $url . ' body=' . substr($response, 0, 200));
                return null;
            }

            return $decoded;
        }

        // Two-step flow:
        // First load renders the coin picker. When the user picks a coin and submits,
        // we create a NOWPayments invoice and redirect them to it.
        function process_payment($data = [])
        {
            $api_key        = $data['options']['api_key'] ?? '';
            $mode           = $data['options']['mode']    ?? 'live';
            $base           = $this->base_url($mode);
            $ref            = $data['transaction']['ref'];
            $amount         = $data['transaction']['local_net_amount'];
            $price_currency = strtolower($data['transaction']['local_currency'] ?? 'usd');

            // User picked a coin and submitted — create the invoice and send them over
            if (!empty($_POST['nowpay_currency'])) {
                $chosen  = strtolower(trim($_POST['nowpay_currency']));

                $payload = [
                    'price_amount'      => (float) $amount,
                    'price_currency'    => $price_currency,
                    'pay_currency'      => $chosen,
                    'order_id'          => $ref,
                    'order_description' => 'Payment #' . $ref,
                    'ipn_callback_url'  => pp_ipn_url($data['gateway']['gateway_id']),
                    'success_url'       => pp_callback_url(),
                    'cancel_url'        => pp_callback_url() . '&pp_status=cancel',
                ];

                $decoded = $this->api_post($base . '/invoice', $api_key, $payload);

                if ($decoded === null) {
                    echo '<div class="alert alert-danger mt-3">Could not reach NOWPayments. Please try again in a moment.</div>';
                    return;
                }

                if (isset($decoded['invoice_url'])) {
                    // The invoice response contains both "id" (used for status checks later)
                    // and "invoice_url" (where the customer pays). We must store the id now
                    // because by the time callback() runs there's no other way to look it up.
                    //
                    // Guard: if NOWPayments returns a blank id for some reason, fall back to
                    // storing the invoice_url itself so callback() has *something* to work with
                    // rather than silently writing an empty string and breaking the flow.
                    $invoice_id = !empty($decoded['id']) ? (string)$decoded['id'] : '';

                    if (empty($invoice_id)) {
                        error_log('NOWPayments: invoice created but id was empty for ref=' . $ref . ' response=' . json_encode($decoded));
                        echo '<div class="alert alert-danger mt-3">NOWPayments returned an incomplete response. Please try again.</div>';
                        return;
                    }

                    set_env('nowpayments-invoice-' . $ref, $invoice_id);
                    echo '<script>location.href="' . htmlspecialchars($decoded['invoice_url'], ENT_QUOTES) . '";</script>';
                } else {
                    $msg = $decoded['message'] ?? json_encode($decoded);
                    echo '<div class="alert alert-danger mt-3">NOWPayments Error: ' . htmlspecialchars($msg) . '</div>';
                }
                return;
            }

            // No coin chosen yet — pull the available currencies and show the picker
            $resp       = $this->api_get($base . '/currencies', $api_key);
            $currencies = ($resp !== null) ? ($resp['currencies'] ?? []) : [];

            if (empty($currencies)) {
                $detail = ($resp === null) ? 'Could not reach NOWPayments — check your server connection.' : 'Empty response from NOWPayments — check your API key.';
                echo '<div class="alert alert-danger">Could not load currencies. ' . htmlspecialchars($detail) . '</div>';
                return;
            }

            // Sort alphabetically for easier browsing
            sort($currencies);

            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                         . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            // Pretty names for the coins we know about — everything else just shows the ticker
            $known_names = [
                'btc'=>'Bitcoin','eth'=>'Ethereum','usdt'=>'Tether','usdttrc20'=>'Tether (TRC-20)',
                'usdterc20'=>'Tether (ERC-20)','usdtbsc'=>'Tether (BSC)','usdtmatic'=>'Tether (Polygon)',
                'usdtsol'=>'Tether (Solana)','usdcbsc'=>'USD Coin (BSC)','usdcerc20'=>'USD Coin (ERC-20)',
                'usdc'=>'USD Coin','bnb'=>'BNB','bnbbsc'=>'BNB Smart Chain','sol'=>'Solana',
                'xrp'=>'XRP','ada'=>'Cardano','doge'=>'Dogecoin','trx'=>'TRON','dot'=>'Polkadot',
                'matic'=>'Polygon','shib'=>'Shiba Inu','ltc'=>'Litecoin','avax'=>'Avalanche',
                'atom'=>'Cosmos','xlm'=>'Stellar','link'=>'Chainlink','algo'=>'Algorand',
                'xmr'=>'Monero','bch'=>'Bitcoin Cash','etc'=>'Ethereum Classic','fil'=>'Filecoin',
                'icp'=>'Internet Computer','hbar'=>'Hedera','vet'=>'VeChain','theta'=>'Theta',
                'egld'=>'MultiversX','axs'=>'Axie Infinity','sand'=>'The Sandbox','mana'=>'Decentraland',
                'gala'=>'Gala','apt'=>'Aptos','arb'=>'Arbitrum','op'=>'Optimism','near'=>'NEAR',
                'ftm'=>'Fantom','ton'=>'Toncoin','dai'=>'DAI','busd'=>'BUSD','zec'=>'Zcash',
                'dash'=>'Dash','neo'=>'NEO','waves'=>'Waves','eos'=>'EOS','xtz'=>'Tezos',
            ];
            ?>
            <style>
                .np-wrap { font-family: inherit; }

                .np-selected-bar {
                    display: none;
                    align-items: center;
                    gap: 10px;
                    background: #f0eaff;
                    border: 2px solid #6c35c3;
                    border-radius: 10px;
                    padding: 10px 16px;
                    margin-bottom: 12px;
                    font-size: 14px;
                    font-weight: 600;
                    color: #5527a8;
                }
                .np-selected-bar .np-coin-icon {
                    width: 30px; height: 30px;
                    border-radius: 50%;
                    background: #fff;
                    display: flex; align-items: center; justify-content: center;
                    font-weight: 700; font-size: 11px;
                    color: #6c35c3;
                    border: 2px solid #6c35c3;
                    flex-shrink: 0;
                }
                .np-selected-bar .hint { margin-left:auto; font-size:11px; color:#999; font-weight:400; }

                .np-search-row {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 12px;
                }
                .np-search {
                    flex: 1;
                    padding: 10px 14px;
                    border: 1.5px solid #e0e0e0;
                    border-radius: 8px;
                    font-size: 14px;
                    outline: none;
                    transition: border-color .2s;
                    background: #fafafa;
                }
                .np-search:focus { border-color: #6c35c3; background: #fff; }
                .np-count { font-size: 12px; color: #999; white-space: nowrap; }

                .np-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
                    gap: 8px;
                    max-height: 300px;
                    overflow-y: auto;
                    padding: 2px 2px 4px;
                }
                .np-grid::-webkit-scrollbar { width: 5px; }
                .np-grid::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

                .np-coin {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    padding: 10px 6px 8px;
                    border: 2px solid #eee;
                    border-radius: 10px;
                    cursor: pointer;
                    transition: all .15s;
                    background: #fafafa;
                    text-align: center;
                    user-select: none;
                    position: relative;
                }
                .np-coin:hover {
                    border-color: #6c35c3;
                    background: #f5eeff;
                    transform: translateY(-2px);
                    box-shadow: 0 3px 10px rgba(108,53,195,.12);
                }
                .np-coin.selected {
                    border-color: #6c35c3;
                    background: #ede1ff;
                }
                .np-coin.selected::after {
                    content: '✓';
                    position: absolute;
                    top: 4px; right: 6px;
                    font-size: 10px; color: #6c35c3; font-weight: 700;
                }

                .np-coin .np-icon {
                    width: 36px; height: 36px;
                    border-radius: 50%;
                    margin-bottom: 6px;
                    object-fit: contain;
                    background: #fff;
                    box-shadow: 0 1px 4px rgba(0,0,0,.08);
                }
                .np-coin .np-letter-icon {
                    width: 36px; height: 36px;
                    border-radius: 50%;
                    margin-bottom: 6px;
                    background: linear-gradient(135deg, #6c35c3, #9b59b6);
                    display: flex; align-items: center; justify-content: center;
                    font-weight: 700; font-size: 12px; color: #fff;
                    box-shadow: 0 1px 4px rgba(0,0,0,.12);
                    flex-shrink: 0;
                }
                .np-coin .np-ticker {
                    font-weight: 700; font-size: 11px;
                    text-transform: uppercase; color: #222;
                    line-height: 1.2;
                }
                .np-coin .np-name {
                    font-size: 9px; color: #999; margin-top: 2px;
                    white-space: nowrap; overflow: hidden;
                    text-overflow: ellipsis; max-width: 84px;
                }

                .np-no-result {
                    display: none; grid-column: 1/-1;
                    text-align: center; color: #bbb; padding: 20px; font-size: 13px;
                }

                .np-proceed-btn {
                    width: 100%;
                    padding: 13px;
                    background: #6c35c3;
                    color: #fff;
                    border: none;
                    border-radius: 8px;
                    font-size: 15px;
                    font-weight: 600;
                    cursor: pointer;
                    margin-top: 14px;
                    transition: background .2s;
                    display: none;
                }
                .np-proceed-btn:hover:not(:disabled) { background: #5527a8; }
                .np-proceed-btn:disabled { opacity: .55; cursor: not-allowed; }
            </style>

            <form method="POST" action="<?php echo htmlspecialchars($current_url); ?>" id="np-form">
                <input type="hidden" name="nowpay_currency" id="np-currency-input" value="">

                <div class="np-wrap">

                    <div class="np-selected-bar" id="np-selected-bar">
                        <div class="np-coin-icon" id="np-sel-icon"></div>
                        <span id="np-sel-label"></span>
                        <span class="hint">Click another to change</span>
                    </div>

                    <div class="np-search-row">
                        <input type="text" class="np-search" id="np-search"
                               placeholder="Search coin (BTC, ETH, USDT...)">
                        <span class="np-count" id="np-count"><?php echo count($currencies); ?> coins</span>
                    </div>

                    <div class="np-grid" id="np-grid">
                        <?php foreach ($currencies as $ticker):
                            $t    = strtoupper($ticker);
                            $name = $known_names[$ticker] ?? $t;
                            $logo = 'https://nowpayments.io/images/coins/' . $ticker . '.svg';
                            $initials = substr($t, 0, 3);
                        ?>
                        <div class="np-coin"
                             data-val="<?php echo htmlspecialchars($ticker); ?>"
                             data-ticker="<?php echo htmlspecialchars($t); ?>"
                             data-name="<?php echo htmlspecialchars($name); ?>"
                             onclick="npSelect(this)">
                            <img class="np-icon"
                                 src="<?php echo htmlspecialchars($logo); ?>"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                 alt="<?php echo htmlspecialchars($t); ?>">
                            <div class="np-letter-icon" style="display:none"><?php echo htmlspecialchars($initials); ?></div>
                            <div class="np-ticker"><?php echo htmlspecialchars($t); ?></div>
                            <div class="np-name"><?php echo htmlspecialchars($name); ?></div>
                        </div>
                        <?php endforeach; ?>
                        <div class="np-no-result" id="np-no-result">No results found.</div>
                    </div>

                    <button type="submit" class="np-proceed-btn" id="np-proceed-btn" disabled>
                        Continue to Payment →
                    </button>
                </div>
            </form>

            <script>
            (function(){
                var selected = '';

                window.npSelect = function(el) {
                    document.querySelectorAll('.np-coin.selected').forEach(function(c){ c.classList.remove('selected'); });
                    el.classList.add('selected');

                    selected = el.getAttribute('data-val');
                    var ticker = el.getAttribute('data-ticker');
                    var name   = el.getAttribute('data-name');

                    document.getElementById('np-currency-input').value = selected;

                    var bar = document.getElementById('np-selected-bar');
                    bar.style.display = 'flex';
                    document.getElementById('np-sel-icon').textContent = ticker.substring(0,3);
                    document.getElementById('np-sel-label').textContent = ticker + ' — ' + name;

                    var btn = document.getElementById('np-proceed-btn');
                    btn.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Pay with ' + ticker + ' →';

                    // Scroll into view on mobile
                    btn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                };

                // filter coins as the user types
                var searchEl  = document.getElementById('np-search');
                var countEl   = document.getElementById('np-count');
                var noResult  = document.getElementById('np-no-result');
                var allCoins  = document.querySelectorAll('.np-coin[data-val]');

                searchEl.addEventListener('input', function(){
                    var q = this.value.trim().toLowerCase();
                    var visible = 0;
                    allCoins.forEach(function(c){
                        var t = c.getAttribute('data-ticker').toLowerCase();
                        var n = c.getAttribute('data-name').toLowerCase();
                        var show = !q || t.indexOf(q) !== -1 || n.indexOf(q) !== -1;
                        c.style.display = show ? '' : 'none';
                        if (show) visible++;
                    });
                    noResult.style.display = visible === 0 ? 'block' : 'none';
                    countEl.textContent = visible + ' coins';
                });

                // don't let the form submit if nothing is selected
                document.getElementById('np-form').addEventListener('submit', function(e){
                    if (!selected) {
                        e.preventDefault();
                        alert('Please select a cryptocurrency to pay with.');
                        return false;
                    }
                    var btn = document.getElementById('np-proceed-btn');
                    btn.disabled = true;
                    btn.textContent = 'Redirecting to NOWPayments...';
                });
            })();
            </script>
            <?php
        }

        // Called when the customer lands back from the NOWPayments payment page.
        // We check the invoice status and mark the transaction done if it cleared.
        function callback($data = [])
        {
            echo '<center><div class="spinner-border text-primary m-3 loading-nowpay" role="status">
                <span class="visually-hidden">Loading...</span></div></center>';

            $api_key = $data['options']['api_key'] ?? '';
            $mode    = $data['options']['mode']    ?? 'live';
            $ref     = $data['transaction']['ref'];
            $base    = $this->base_url($mode);

            if (isset($_GET['pp_status']) && $_GET['pp_status'] === 'cancel') {
                echo '<div class="alert alert-warning">Payment was cancelled. Please try again.</div>';
                echo '<style>.loading-nowpay{display:none;}</style>';
                return;
            }

            $invoice_id = get_env('nowpayments-invoice-' . $ref);

            // get_env returns '--' when the key doesn't exist in some frameworks,
            // and an empty string if set_env was called with a blank value.
            // Either way we can't do a status check without a real id.
            if (empty($invoice_id) || $invoice_id === '--') {
                // Log enough context to diagnose whether the issue is a missing
                // set_env call, a framework storage failure, or a session mismatch.
                error_log('NOWPayments: missing invoice id for ref=' . $ref);
                echo '<div class="alert alert-danger">Could not retrieve payment reference. Please contact support.</div>';
                echo '<style>.loading-nowpay{display:none;}</style>';
                return;
            }

            $decoded = $this->api_get($base . '/invoice/' . $invoice_id, $api_key);

            if ($decoded === null) {
                echo '<div class="alert alert-danger">Could not reach NOWPayments to verify your payment. Please refresh in a moment.</div>';
                echo '<style>.loading-nowpay{display:none;}</style>';
                return;
            }

            $status  = $decoded['status'] ?? '';

            if (in_array($status, ['finished', 'confirmed'])) {
                $trx_id = (string)($decoded['id'] ?? $invoice_id);

                if (pp_check_transaction_id($trx_id)) {
                    echo '<div class="alert alert-warning">Transaction already processed.</div>';
                    echo '<style>.loading-nowpay{display:none;}</style>';
                    return;
                }

                pp_set_transaction_status($ref, 'completed', $data['gateway']['gateway_id'], $trx_id, [
                    ['label' => 'NOWPayments Invoice ID', 'value' => $invoice_id],
                    ['label' => 'Status',                 'value' => $status],
                ]);

                echo '<script>location.reload();</script>';

            } elseif (in_array($status, ['waiting', 'confirming', 'sending', 'partially_paid'])) {
                echo '<div class="alert alert-info">
                    <strong>Payment Pending</strong> — Status: <strong>' . htmlspecialchars($status) . '</strong>.
                    <br>Please wait and refresh this page shortly.
                </div>';
                echo '<style>.loading-nowpay{display:none;}</style>';
            } else {
                echo '<div class="alert alert-danger">Payment not completed. Status: <strong>'
                    . htmlspecialchars($status ?: 'unknown') . '</strong>. Please try again.</div>';
                echo '<style>.loading-nowpay{display:none;}</style>';
            }
        }

        // Server-to-server webhook from NOWPayments whenever a payment status changes.
        // We verify the HMAC signature first, then complete the order on finished/confirmed.
        // Possible statuses: waiting → confirming → confirmed → sending → finished
        // (also: partially_paid, failed, refunded, expired)
        function ipn($data = [])
        {
            $raw_post  = file_get_contents('php://input');
            $post_data = json_decode($raw_post, true);

            if (empty($post_data)) {
                http_response_code(400);
                echo 'Invalid payload';
                return;
            }

            $ipn_secret   = $data['gateway']['options']['ipn_secret_key'] ?? '';
            $received_sig = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

            // Both the secret and the incoming signature must be present — no exceptions
            if (empty($ipn_secret)) {
                http_response_code(500);
                echo 'IPN secret key not configured';
                return;
            }

            if (empty($received_sig)) {
                http_response_code(400);
                echo 'No HMAC signature provided';
                return;
            }

            // Sort keys recursively before hashing — NOWPayments signs it this way too
            $sorted_data  = $this->tksort($post_data);
            $sorted_json  = json_encode($sorted_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $expected_sig = hash_hmac('sha512', $sorted_json, $ipn_secret);

            if (!hash_equals($expected_sig, $received_sig)) {
                http_response_code(400);
                echo 'Invalid HMAC signature';
                return;
            }

            $payment_status = $post_data['payment_status'] ?? '';
            $order_id       = (string)($post_data['order_id']   ?? '');
            $payment_id     = (string)($post_data['payment_id'] ?? '');
            $pay_address    = $post_data['pay_address']          ?? '';
            $pay_currency   = strtoupper($post_data['pay_currency'] ?? '');
            $actually_paid  = $post_data['actually_paid']        ?? '';

            // only mark the order complete when payment is fully done
            if (in_array($payment_status, ['finished', 'confirmed'])) {
                if (!pp_check_transaction($order_id)) {
                    http_response_code(400);
                    echo 'Transaction not found';
                    return;
                }

                if (pp_check_transaction_id($payment_id)) {
                    http_response_code(200);
                    echo 'Already processed';
                    return;
                }

                pp_set_transaction_status($order_id, 'completed', $data['gateway']['gateway_id'], $payment_id, [
                    ['label' => 'NOWPayments Payment ID', 'value' => $payment_id],
                    ['label' => 'Paid With',              'value' => $pay_currency],
                    ['label' => 'Amount Paid',            'value' => $actually_paid . ' ' . $pay_currency],
                    ['label' => 'Pay Address',            'value' => $pay_address],
                ]);
            }

            http_response_code(200);
            echo 'OK';
        }
    }