<?php
    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    if(isset($_GET['lang'])){
        if($_GET['lang'] !== ""){
            pp_set_lang($_GET['lang']);
?>
            <script>
                location.href = '<?php echo pp_checkout_address().'?gateway='.urlencode($_GET['gateway']);?>';
            </script>
<?php
            exit();
        }
    }

    if(isset($_GET['gateway'])){
        $gateway_info = pp_gateway_info($_GET['gateway'], $data);

        if($gateway_info['status'] == false){
            http_response_code(403);
            exit('Direct access not allowed');
        }
    }else{
        http_response_code(403);
        exit('Direct access not allowed');
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
            --tblr-btn-color: <?php echo $gateway_info['gateway']['text_color'];?>;
            --tblr-btn-bg: <?php echo $gateway_info['gateway']['primary_color'];?>;
            --tblr-btn-hover-color: <?php echo $gateway_info['gateway']['text_color'];?>;
            --tblr-btn-hover-bg: <?php echo pp_hexToRgba($gateway_info['gateway']['primary_color'], 0.80)?>;
            --tblr-btn-active-color: <?php echo $gateway_info['gateway']['text_color'];?>;
            --tblr-btn-active-bg: <?php echo pp_hexToRgba($gateway_info['gateway']['primary_color'], 0.80)?>;
            --tblr-btn-disabled-bg: <?php echo $gateway_info['gateway']['primary_color'];?>;
            --tblr-btn-disabled-color: <?php echo $gateway_info['gateway']['text_color'];?>;
            --tblr-btn-box-shadow: <?php echo $gateway_info['gateway']['text_color'];?>;
        }
        .form-control:focus{
            border-color: <?php echo $gateway_info['gateway']['primary_color'];?>;
            box-shadow: var(--tblr-shadow-input), 0 0 0 .25rem <?php echo pp_hexToRgba($gateway_info['gateway']['primary_color'], 0.25)?>;
        }

        .payment-instructions{
            background-color: <?php echo $gateway_info['gateway']['primary_color'];?>;
            color: <?php echo $gateway_info['gateway']['text_color'];?>;

            border-radius: 10px;
            padding-top: 5px;
            padding-bottom: 5px;
            padding-left: 20px;
            padding-right: 20px;
            margin: 0px;
        }
        .payment-instructions li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 0;
            word-break: break-word;
            border-bottom: 1px solid <?php echo pp_hexToRgba($gateway_info['gateway']['text_color'], 0.25)?>;
        }

        .payment-instructions li .dot{
            width: 6px;
            height: 6px;
            border-radius: 100%;
            background-color: <?php echo $gateway_info['gateway']['text_color'];?>;
            min-width: 6px;
        }
        .payment-instructions li p{
            margin: 0;
        }

        .payment-instructions li .dynamic-value{
            font-weight: 600;
        }

        .payment-instructions li svg{
            width: 17px;
            height: 17px;
        }
        .payment-instructions li .button-icon{
            padding: 5px;
            margin-left: 10px;
            background-color: <?php echo $gateway_info['gateway']['text_color'];?>;
            color: <?php echo $gateway_info['gateway']['primary_color'];?>;
            border-radius: 5px;
            cursor: pointer;
        }

        .payment-instructions li:last-child {
            border-bottom: none;
        }

        .bp-modal {
            position: fixed;
            inset: 0;
            background: rgb(86 85 85 / 13%);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 15px;
        }

        .bp-modal-content {
            position: relative;
            background: #FFFFFF;
            border-radius: 5px;
            padding: 10px;
            max-width: 95vw;
            max-height: 95vh;
            box-shadow: 0 00px 5px rgb(157 145 145 / 60%);
            animation: bpZoomIn 0.25s ease-out;
        }

        .bp-model-image-b{
            margin: 20px;
        }

        #bp-modal-image {
            display: block;
            max-width: 300px;
            border-radius: 10px;
            width: 100%;
        }

        .bp-close {
            position: absolute;
            top: -12px;
            right: -12px;
            width: 36px;
            height: 36px;
            background: #ff4d4f;
            color: #fff;
            font-size: 22px;
            font-weight: bold;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 0px 5px rgba(0, 0, 0, 0.4);
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .bp-close:hover {
            background: #ff1f1f;
            transform: scale(1.1);
        }

        @keyframes bpZoomIn {
            from {
                transform: scale(0.92);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 576px) {
            .bp-close {
                top: -10px;
                right: -10px;
                width: 32px;
                height: 32px;
                font-size: 20px;
            }
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
              <div class="d-flex align-items-center justify-content-between border rounded p-2">
                  <div onclick="location.href='<?php echo pp_checkout_address();?>'" style="text-align: right; cursor: pointer; color: <?php echo $data['options']['primary_color'];?>"><svg xmlns="http://www.w3.org/2000/svg" style=" padding: 6px; background-color: <?php echo pp_hexToRgba($data['options']['primary_color'], 0.05)?>; border-radius: 100%; width: 32px; height: 32px; " viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M5 12l6 6" /><path d="M5 12l6 -6" /></svg></div>
                  <div class="btns-group d-flex gap-2">
                      <div style="text-align: right; cursor: pointer; color: <?php echo $data['options']['primary_color'];?>" data-bs-target="#modal-language" data-bs-toggle="modal"><svg xmlns="http://www.w3.org/2000/svg" style=" padding: 6px; background-color: <?php echo pp_hexToRgba($data['options']['primary_color'], 0.05)?>; border-radius: 100%; width: 32px; height: 32px; " viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-language"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6.371c0 4.418 -2.239 6.629 -5 6.629" /><path d="M4 6.371h7" /><path d="M5 9c0 2.144 2.252 3.908 6 4" /><path d="M12 20l4 -9l4 9" /><path d="M19.1 18h-6.2" /><path d="M6.694 3l.793 .582" /></svg></div>
                  </div>
              </div>

              <center>
                  <img src="<?php echo $gateway_info['gateway']['logo'];?>" alt="" class="company-logo">
              </center>

              <?php
                 pp_gateway_render($_GET['gateway'] ?? '', $data);
              ?>
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
                                <?php
                                    foreach ($gateway_info['supported_languages'] ?? [] as $code => $language) {
                                ?>
                                            <option value="<?= htmlspecialchars($code) ?>">
                                                <?= htmlspecialchars($language) ?>
                                            </option>
                                <?php
                                    }
                                ?>
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
        var ppLang = {
            copied:        '<?php echo addslashes($data['lang']['copied_successfully'])?>',
            copiedDesc:    '<?php echo addslashes($data['lang']['copy_content_copied'])?>',
            copyFailed:    '<?php echo addslashes($data['lang']['copy_failed'])?>',
            copyFailedDesc:'<?php echo addslashes($data['lang']['copy_failed_text'])?>',
            noContent:     '<?php echo addslashes($data['lang']['copy_no_content'])?>',
            somethingWrong:'<?php echo addslashes($data['lang']['something_wrong'])?>',
            supportText:   '<?php echo addslashes($data['lang']['support_contact_text'])?>',
        };

        function copy_value(content){
            if (!content) {
                createToast({
                    title: ppLang.somethingWrong,
                    description: ppLang.noContent,
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 20
                });
                return;
            }

            navigator.clipboard.writeText(content).then(() => {
                createToast({
                    title: ppLang.copied,
                    description: ppLang.copiedDesc,
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5f38f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>`,
                    timeout: 4000,
                    top: 20
                });
            }).catch((err) => {
                createToast({
                    title: ppLang.copyFailed,
                    description: ppLang.copyFailedDesc,
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 20
                });
                console.error('Clipboard error:', err);
            });
        }

        function failed(title, message){
            createToast({
                title: title,
                description: message,
                svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                timeout: 6000,
                top: 20
            });
        }

        function success(){
            location.href = "<?php echo pp_checkout_address();?>";
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Only buttons that should auto-activate
            const autoButtons = document.querySelectorAll('.btn-group .btn');

            // All buttons for click handling
            const allButtons = document.querySelectorAll('.btn-group .btn, .btns-group .btns');

            const rows = {};

            // Attach click events to all buttons
            allButtons.forEach(btn => {
                const tab = btn.dataset.tab;
                if (!tab) return; // skip buttons without data-tab

                // Store row element if exists
                const row = document.getElementById('gateways-' + tab);
                if (row) rows[tab] = row;

                btn.addEventListener('click', function() {
                    // Remove active from all buttons
                    allButtons.forEach(b => b.classList.remove('active'));

                    // Add active only to clicked button
                    this.classList.add('active');

                    // Hide all rows
                    Object.values(rows).forEach(r => r.style.display = 'none');

                    // Show selected row if it exists
                    if (rows[tab]) rows[tab].style.display = rows[tab].classList.contains('row') ? 'flex' : 'block';
                });
            });

            // ✅ Auto-enable first available tab ONLY from .btn-group .btn
            if (autoButtons.length > 0) {
                autoButtons[0].click();
            }
        });

        function hitLanguage(){
            var language = document.querySelector("#model-languages").value;

            if(language !== ""){
                location.href = '<?php echo pp_checkout_address().'?gateway='.urlencode($_GET['gateway']);?>&lang='+language;
            }
        }

        $(document).ready(function() {
            $('#form').on('submit', function(e) {
                e.preventDefault(); // prevent default form submission

                var formData = $(this).serialize(); // serialize all form inputs

                document.querySelector("#payButton").innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';

                $.ajax({
                    url: '<?php echo pp_site_address(); ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: formData, // send all form data
                    success: function(data) {
                        document.querySelector("#payButton").innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-credit-card"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3l0 -8" /><path d="M3 10l18 0" /><path d="M7 15l.01 0" /><path d="M11 15l2 0" /></svg> <?php echo $data['lang']['pay_now']?>';

                        if (data.status == "true") {
                            location.href = data.redirect;
                        } else {
                            createToast({
                                title: data.title,
                                description: data.message,
                                svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                                timeout: 6000
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        createToast({
                            title: ppLang.somethingWrong,
                            description: ppLang.supportText,
                            svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                            timeout: 6000
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
