<?php
    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    if(isset($_GET['receipt'])){
        pp_downloadReceiptPDF($data);
    }

    if(isset($_GET['lang'])){
        if($_GET['lang'] !== ""){
            pp_set_lang($_GET['lang']);
?>
            <script>
                location.href = '?lang=';
            </script>
<?php
            exit();
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="author" content="Softnio">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $data['lang']['checkout']?> - <?php echo $data['brand']['name'];?></title>
    <link rel="shortcut icon" href="<?php echo $data['brand']['favicon'];?>">
    <?php
       echo pp_assets('head');
    ?>

    <style>
        .container{
            max-width: 650px; 
            width: 100%;
        }
        .company-logo{
            margin-top: 15px;
            height: 50px;
            margin-bottom: 15px;
        }

        .btn-primary {
            --tblr-btn-border-color: transparent;
            --tblr-btn-hover-border-color: transparent;
            --tblr-btn-active-border-color: transparent;
            --tblr-btn-color: <?php echo $data['options']['text_color'];?>;
            --tblr-btn-bg: <?php echo $data['options']['primary_color'];?>;
            --tblr-btn-hover-color: <?php echo $data['options']['text_color'];?>;
            --tblr-btn-hover-bg: <?php echo pp_hexToRgba($data['options']['primary_color'], 0.80)?>;
            --tblr-btn-active-color: <?php echo $data['options']['text_color'];?>;
            --tblr-btn-active-bg: <?php echo pp_hexToRgba($data['options']['primary_color'], 0.80)?>;
            --tblr-btn-disabled-bg: <?php echo $data['options']['primary_color'];?>;
            --tblr-btn-disabled-color: <?php echo $data['options']['text_color'];?>;
            --tblr-btn-box-shadow: <?php echo $data['options']['text_color'];?>;
        }

    </style>

    <?php
        $seoTitle = trim($data['options']['seo_title'] ?? '');
        $seoDesc  = trim($data['options']['seo_description'] ?? '');
        $seoKey   = trim($data['options']['seo_keywords'] ?? '');
        $analyticsCode = trim($data['options']['analytics_code'] ?? '');

        if ($seoTitle !== '' && $seoTitle !== '--') {
            echo '<title>' . htmlspecialchars($seoTitle) . '</title>' . PHP_EOL;
            echo '<meta name="title" content="' . htmlspecialchars($seoTitle) . '">' . PHP_EOL;
            echo '<meta property="og:title" content="' . htmlspecialchars($seoTitle) . '">' . PHP_EOL;
        }

        if ($seoDesc !== '' && $seoDesc !== '--') {
            echo '<meta name="description" content="' . htmlspecialchars($seoDesc) . '">' . PHP_EOL;
            echo '<meta property="og:description" content="' . htmlspecialchars($seoDesc) . '">' . PHP_EOL;
        }

        if ($seoKey !== '' && $seoKey !== '--') {
            echo '<meta name="keywords" content="' . htmlspecialchars($seoKey) . '">' . PHP_EOL;
        }

        if ($analyticsCode !== '' && $analyticsCode !== '--') {
            echo $analyticsCode;
        }

        $bgStyle = 'background-color:#f8f9fa;';
        if (!empty($data['options']['enable_bg_image']) &&$data['options']['enable_bg_image'] === 'enabled' &&!empty($data['options']['background_image'])) {
            $bgImage = $data['options']['background_image'];
            $bgStyle = "
                background-image: url('{$bgImage}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-attachment: fixed;
            ";
        }
    ?>
</head>
<body style="<?= $bgStyle ?>" loading="lazy">
    <div class="container container-tight py-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-end border rounded p-2 mb-3">
                    <div style="text-align: right; cursor: pointer; color: <?php echo $data['options']['primary_color'];?>" data-bs-target="#modal-language" data-bs-toggle="modal"><svg xmlns="http://www.w3.org/2000/svg" style=" padding: 6px; background-color: <?php echo pp_hexToRgba($data['options']['primary_color'], 0.05)?>; border-radius: 100%; width: 32px; height: 32px; " viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-language"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6.371c0 4.418 -2.239 6.629 -5 6.629" /><path d="M4 6.371h7" /><path d="M5 9c0 2.144 2.252 3.908 6 4" /><path d="M12 20l4 -9l4 9" /><path d="M19.1 18h-6.2" /><path d="M6.694 3l.793 .582" /></svg></div>
                </div>
            </div>
            <div class="card-body text-center">

                <?php
                $status = strtolower($data['transaction']['status'] ?? 'pending');

                $statusMap = [
                    'completed' => ['text' => $data['lang']['payment_successful'], 'color' => 'success', 'icon' => 'check-circle-fill'],
                    'pending'   => ['text' => $data['lang']['payment_pending'], 'color' => 'warning', 'icon' => 'hourglass-split'],
                    'refunded'  => ['text' => $data['lang']['payment_refunded'], 'color' => 'info', 'icon' => 'arrow-counterclockwise'],
                    'canceled'  => ['text' => $data['lang']['payment_canceled'], 'color' => 'danger', 'icon' => 'x-circle-fill'],
                ];

                $currentStatus = $statusMap[$status] ?? $statusMap['pending'];
                ?>

                <div class="mb-4 mt-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="bi bi-<?php echo $currentStatus['icon']; ?> text-<?php echo $currentStatus['color']; ?>" width="80" height="80" fill="currentColor" viewBox="0 0 16 16">
                        <?php
                        switch($status){
                            case 'completed':
                                echo '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 11.03a.75.75 0 0 0 1.08 0l3.992-3.992a.75.75 0 1 0-1.06-1.06L7.5 9.439 5.97 7.97a.75.75 0 1 0-1.06 1.06l2.06 2.06z"/>';
                                break;
                            case 'pending':
                                echo '<path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0-1A6 6 0 1 1 8 2a6 6 0 0 1 0 12zm-.5-6V4h1v5h-1zm0 2h1v1h-1v-1z"/>';
                                break;
                            case 'refunded':
                                echo '<path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 0-.853-.521A4 4 0 1 1 8 4v1l2-2-2-2v1a5 5 0 0 0 0 10z"/>';
                                break;
                            case 'canceled':
                                echo '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM4.646 4.646a.5.5 0 0 0 0 .708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646a.5.5 0 0 0-.708 0z"/>';
                                break;
                        }
                        ?>
                    </svg>
                </div>

                <h2 class="text-<?php echo $currentStatus['color']; ?> mb-3"><?php echo $currentStatus['text']; ?></h2>
                <p class="text-muted mb-4">
                    <?php
                    switch($status){
                        case 'completed':
                            echo $data['lang']['change_status_completed'];
                            break;
                        case 'pending':
                            echo $data['lang']['change_status_pending'];
                            break;
                        case 'refunded':
                            echo $data['lang']['change_status_refunded'];
                            break;
                        case 'canceled':
                            echo $data['lang']['change_status_cancled'];
                            break;
                    }
                    ?>
                </p>

                <?php
                    $redirectUrl = $data['transaction']['redirect_url'] ?? '--';
                    $shouldRedirect = $status === 'completed' && $redirectUrl !== '' && $redirectUrl !== '--';
                    if ($shouldRedirect) {
                ?>
                    <p class="text-muted mb-4">
                        Redirecting to the website in <strong id="redirect-countdown">5</strong> seconds...
                    </p>
                <?php } ?>

                <div class="table-responsive mb-4 <?php echo ($status == "canceled") ? 'd-none' : ''?>">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th><?php echo $data['lang']['payment_method']?></th>
                                <td><?php echo $data['transaction']['payment_method']?? 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo $data['lang']['amount']?></th>
                                <td><?php echo money_round($data['transaction']['amount'] ?? 0, 2); ?> <?php echo $data['transaction']['currency'] ?? 'BDT'; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo $data['lang']['discount']?></th>
                                <td><?php echo money_round($data['transaction']['discount_amount'] ?? 0, 2); ?> <?php echo $data['transaction']['currency'] ?? 'BDT'; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo $data['lang']['processing_fee']?></th>
                                <td><?php echo money_round($data['transaction']['processing_fee'] ?? 0, 2); ?> <?php echo $data['transaction']['currency'] ?? 'BDT'; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo $data['lang']['net_amount']?></th>
                                <td><?php echo money_round(($data['transaction']['amount'] ?? 0) - ($data['transaction']['discount_amount'] ?? 0) + ($data['transaction']['processing_fee'] ?? 0), 2); ?> <?php echo $data['transaction']['currency'] ?? 'BDT'; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo $data['lang']['net_local_amount']?></th>
                                <td><?php echo money_round($data['transaction']['local_net_amount'] ?? 0, 2); ?> <?php echo $data['transaction']['local_currency'] ?? 'BDT'; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo $data['lang']['status']?></th>
                                <td><span class="text-<?php echo $currentStatus['color']; ?>"><?php echo ucfirst($status); ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <a href="<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8')?>" class="btn btn-primary <?php echo !$shouldRedirect ? 'd-none' : ''?>"><?php echo $data['lang']['go_to_site']?></a>
                    <?php
                        if($status == "completed" || $status == "pending" || $status == "refunded"){
                    ?>
                           <a href="<?php echo pp_checkout_address();?>?receipt" class="btn btn-success"><?php echo $data['lang']['download_receipt']?></a>
                    <?php
                        }
                    ?>
                </div>

                <?php if ($shouldRedirect && !isset($_GET['receipt'])) { ?>
                    <script data-cfasync="false">
                        document.addEventListener('DOMContentLoaded', function () {
                            let secondsRemaining = 5;
                            const countdown = document.getElementById('redirect-countdown');
                            const redirectUrl = <?php echo json_encode($redirectUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

                            const timer = window.setInterval(function () {
                                secondsRemaining -= 1;
                                if (countdown) countdown.textContent = secondsRemaining;

                                if (secondsRemaining <= 0) {
                                    window.clearInterval(timer);
                                    window.location.assign(redirectUrl);
                                }
                            }, 1000);
                        });
                    </script>
                <?php } ?>

            </div>
        </div>

        <center class="footer-branding" style="margin-top: 20px;"><?php echo $data['options']['watermark_text'];?></center>
    </div>

    <div class="modal fade" id="modal-language" data-bs-keyboard="false" tabindex="-1" aria-labelledby="scrollableLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-top">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scrollableLabel"><?php echo $data['lang']['select_language']?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mt-1">
                        <label for="" class="form-label"><?php echo $data['lang']['language']?> <span class="text-danger">*</span></label>
                        <div class="form-control-wrap">
                            <select class="form-select" id="model-languages" onchange="hitLanguage()">
                                <option value="" selected><?php echo $data['lang']['select_a_language']?></option>
                                <?php foreach ($data['supported_languages'] ?? [] as $code => $language): ?>
                                    <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($language) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal"><?php echo $data['lang']['close']?></button>
                </div>
            </div>
        </div>
    </div>

    <?php
       echo pp_assets('footer');
    ?>

    <script data-cfasync="false">
        function hitLanguage(){
            var language = document.querySelector("#model-languages").value;
            if(language !== ""){
                location.href = '?lang=' + language;
            }
        }
    </script>
</body>
</html>
