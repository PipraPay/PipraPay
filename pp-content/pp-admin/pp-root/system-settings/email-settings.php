<?php
    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
        http_response_code(403);
        exit('Access denied. You need permission to perform this action. Please contact the admin.');
    }

    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_email', $global_user_response['response'][0]['role'])) {
        http_response_code(403);
        exit('Access denied. You need permission to perform this action. Please contact the admin.');
    }

    $notify_email = get_env('email-settings-notify_email');
    $notify_name = get_env('email-settings-notify_name');

    $email_provider = get_env('email-settings-provider') !== '' ? get_env('email-settings-provider') : 'smtp';

    $brevo_api_key = get_env('email-settings-brevo_api_key');
    $brevo_sender_name = get_env('email-settings-brevo_sender_name');
    $brevo_sender_email = get_env('email-settings-brevo_sender_email');

    $smtp_host = get_env('email-settings-smtp_host');
    $smtp_port = get_env('email-settings-smtp_port') !== '' ? get_env('email-settings-smtp_port') : '587';
    $smtp_encryption = get_env('email-settings-smtp_encryption') !== '' ? get_env('email-settings-smtp_encryption') : 'tls';
    $smtp_username = get_env('email-settings-smtp_username');
    $smtp_password = get_env('email-settings-smtp_password');
    $smtp_sender_name = get_env('email-settings-smtp_sender_name');
    $smtp_sender_email = get_env('email-settings-smtp_sender_email');
?>

<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
            <!-- Page pre-title -->
                <div class="page-pretitle">
                    <ol class="breadcrumb breadcrumb-arrow mb-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0)" onclick="load_content('System Settings','<?php echo $site_url.$path_admin ?>/system-settings','nav-item-system-settings')">System Settings</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Email Settings</a></li>
                    </ol>
                </div>
                <h2 class="page-title">Email Settings</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row g-gs">
            <div class="col-12 col-xxl-4">
                <h2 class="card-title m-0 mb-1">Email Notification Settings</h2>
                <p>Configure how the system sends email notifications, either through the Brevo API or a standard SMTP server.</p>
            </div>
            <div class="col-12 col-xxl-8">
                <div class="card p-2">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="notify_email" class="form-label">Admin Notification Email<span class="text-danger">*</span></label>
                                    <div class="form-control-wrap">
                                        <input type="email" class="form-control" id="notify_email" placeholder="admin@example.com" value="<?= htmlspecialchars($notify_email) ?>">
                                    </div>
                                    <small class="form-hint">
                                        System notifications (e.g. new payments) are sent only to this address. Customers/payers never receive notification emails.
                                    </small>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="notify_name" class="form-label">Recipient Name</label>
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" id="notify_name" placeholder="Admin" value="<?= htmlspecialchars($notify_name) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <hr class="my-2">
                            </div>

                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="email_provider" class="form-label">Email Provider<span class="text-danger">*</span></label>
                                    <div class="form-control-wrap">
                                        <select class="js-select" id="email_provider" data-search="false" data-remove="false" data-placeholder="Select provider" required>
                                            <option value="smtp" <?= $email_provider === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                                            <option value="brevo" <?= $email_provider === 'brevo' ? 'selected' : '' ?>>Brevo (API)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12 email-provider-section" data-provider="brevo" style="<?= $email_provider === 'brevo' ? '' : 'display:none;' ?>">
                                <hr class="my-2">
                                <h4 class="mb-3">Brevo API Settings</h4>

                                <div class="form-group mb-3">
                                    <label for="brevo_api_key" class="form-label">Brevo API Key<span class="text-danger">*</span></label>
                                    <div class="input-group input-group-flat">
                                        <input type="password" class="form-control password-input" id="brevo_api_key" placeholder="xkeysib-xxxxxxxxxxxxxxxx" value="<?= htmlspecialchars($brevo_api_key) ?>">
                                        <span class="input-group-text password-toggle" onclick="toggleEmailSettingsPassword(this)">
                                            <a href="javascript:void(0)" class="link-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Show">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-eye"><path d="M10 12a2 2 0 1 0 4 0"></path><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path></svg>
                                            </a>
                                        </span>
                                    </div>
                                    <small class="form-hint">Found under SMTP &amp; API &gt; API Keys in your Brevo dashboard.</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="brevo_sender_name" class="form-label">Sender Name</label>
                                    <div class="form-control-wrap">
                                        <input type="text" class="form-control" id="brevo_sender_name" placeholder="Your Company Name" value="<?= htmlspecialchars($brevo_sender_name) ?>">
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="brevo_sender_email" class="form-label">Sender Email<span class="text-danger">*</span></label>
                                    <div class="form-control-wrap">
                                        <input type="email" class="form-control" id="brevo_sender_email" placeholder="noreply@example.com" value="<?= htmlspecialchars($brevo_sender_email) ?>">
                                    </div>
                                    <small class="form-hint">Must be a verified sender in your Brevo account.</small>
                                </div>
                            </div>

                            <div class="col-lg-12 email-provider-section" data-provider="smtp" style="<?= $email_provider === 'smtp' ? '' : 'display:none;' ?>">
                                <hr class="my-2">
                                <h4 class="mb-3">SMTP Settings</h4>

                                <div class="row g-3">
                                    <div class="col-lg-8">
                                        <div class="form-group">
                                            <label for="smtp_host" class="form-label">SMTP Host<span class="text-danger">*</span></label>
                                            <div class="form-control-wrap">
                                                <input type="text" class="form-control" id="smtp_host" placeholder="smtp.example.com" value="<?= htmlspecialchars($smtp_host) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="smtp_port" class="form-label">Port<span class="text-danger">*</span></label>
                                            <div class="form-control-wrap">
                                                <input type="number" class="form-control" id="smtp_port" placeholder="587" value="<?= htmlspecialchars($smtp_port) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="smtp_encryption" class="form-label">Encryption</label>
                                            <div class="form-control-wrap">
                                                <select class="js-select" id="smtp_encryption" data-search="false" data-remove="false" data-placeholder="Select encryption">
                                                    <option value="tls" <?= $smtp_encryption === 'tls' ? 'selected' : '' ?>>TLS (STARTTLS)</option>
                                                    <option value="ssl" <?= $smtp_encryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                    <option value="none" <?= $smtp_encryption === 'none' ? 'selected' : '' ?>>None</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="smtp_username" class="form-label">Username</label>
                                            <div class="form-control-wrap">
                                                <input type="text" class="form-control" id="smtp_username" placeholder="user@example.com" value="<?= htmlspecialchars($smtp_username) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="smtp_password" class="form-label">Password</label>
                                            <div class="input-group input-group-flat">
                                                <input type="password" class="form-control password-input" id="smtp_password" placeholder="••••••••" value="<?= htmlspecialchars($smtp_password) ?>">
                                                <span class="input-group-text password-toggle" onclick="toggleEmailSettingsPassword(this)">
                                                    <a href="javascript:void(0)" class="link-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Show">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-eye"><path d="M10 12a2 2 0 1 0 4 0"></path><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path></svg>
                                                    </a>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="smtp_sender_name" class="form-label">Sender Name</label>
                                            <div class="form-control-wrap">
                                                <input type="text" class="form-control" id="smtp_sender_name" placeholder="Your Company Name" value="<?= htmlspecialchars($smtp_sender_name) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="smtp_sender_email" class="form-label">Sender Email<span class="text-danger">*</span></label>
                                            <div class="form-control-wrap">
                                                <input type="email" class="form-control" id="smtp_sender_email" placeholder="noreply@example.com" value="<?= htmlspecialchars($smtp_sender_email) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3">
                    <div class="input-group" style="max-width: 380px;">
                        <input type="email" class="form-control" id="email_test_recipient" placeholder="Send a test email to..." value="<?= htmlspecialchars($notify_email) ?>">
                        <button class="btn btn-outline-primary btn-email-settings-test">Send Test Email</button>
                    </div>
                    <button class="btn btn-primary btn-email-settings-save">Save changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script data-cfasync="false">
    function toggleEmailSettingsPassword(el) {
        var input = $(el).closest('.input-group').find('input');
        var isPassword = input.attr('type') === 'password';
        input.attr('type', isPassword ? 'text' : 'password');
    }

    $('#email_provider').on('change', function () {
        var selected = $(this).val();
        $('.email-provider-section').each(function () {
            $(this).toggle($(this).data('provider') === selected);
        });
    });

    $('.btn-email-settings-save').click(function () {
        var csrf_token_default = $('input[name="csrf_token_default"]').val();

        var data = {
            action: 'email-settings',
            csrf_token: csrf_token_default,
            notify_email: $('#notify_email').val(),
            notify_name: $('#notify_name').val(),
            email_provider: $('#email_provider').val(),
            brevo_api_key: $('#brevo_api_key').val(),
            brevo_sender_name: $('#brevo_sender_name').val(),
            brevo_sender_email: $('#brevo_sender_email').val(),
            smtp_host: $('#smtp_host').val(),
            smtp_port: $('#smtp_port').val(),
            smtp_encryption: $('#smtp_encryption').val(),
            smtp_username: $('#smtp_username').val(),
            smtp_password: $('#smtp_password').val(),
            smtp_sender_name: $('#smtp_sender_name').val(),
            smtp_sender_email: $('#smtp_sender_email').val()
        };

        var btnClass = 'btn-email-settings-save';
        var btn = document.querySelector('.'+btnClass).innerHTML;
        document.querySelector('.'+btnClass).innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';

        $.ajax({
            type: 'POST',
            url: '<?php echo $site_url.$path_admin ?>/dashboard',
            data: data,
            dataType: 'json',
            success: function (response) {
                document.querySelector('.'+btnClass).innerHTML = btn;

                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = response.csrf_token;
                });
                document.querySelectorAll('input[name="csrf_token_default"]').forEach(input => {
                    input.value = response.csrf_token;
                });

                createToast({
                    title: response.title,
                    description: response.message,
                    svg: response.status === 'true'
                        ? `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5f38f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>`
                        : `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 70
                });
            },
            error: function (xhr, status, error) {
                document.querySelector('.'+btnClass).innerHTML = btn;
                createToast({
                    title: 'Something Wrong!',
                    description: 'For further assistance, please contact our support team.',
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 70
                });
            }
        });
    });

    $('.btn-email-settings-test').click(function () {
        var csrf_token_default = $('input[name="csrf_token_default"]').val();
        var testRecipient = $('#email_test_recipient').val();

        if (!testRecipient) {
            createToast({
                title: 'Recipient Required',
                description: 'Please enter an email address to send the test email to.',
                svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                timeout: 6000,
                top: 70
            });
            return;
        }

        var data = {
            action: 'email-settings-test',
            csrf_token: csrf_token_default,
            test_recipient: testRecipient,
            email_provider: $('#email_provider').val(),
            brevo_api_key: $('#brevo_api_key').val(),
            brevo_sender_name: $('#brevo_sender_name').val(),
            brevo_sender_email: $('#brevo_sender_email').val(),
            smtp_host: $('#smtp_host').val(),
            smtp_port: $('#smtp_port').val(),
            smtp_encryption: $('#smtp_encryption').val(),
            smtp_username: $('#smtp_username').val(),
            smtp_password: $('#smtp_password').val(),
            smtp_sender_name: $('#smtp_sender_name').val(),
            smtp_sender_email: $('#smtp_sender_email').val()
        };

        var btnClass = 'btn-email-settings-test';
        var btn = document.querySelector('.'+btnClass).innerHTML;
        document.querySelector('.'+btnClass).innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';

        $.ajax({
            type: 'POST',
            url: '<?php echo $site_url.$path_admin ?>/dashboard',
            data: data,
            dataType: 'json',
            success: function (response) {
                document.querySelector('.'+btnClass).innerHTML = btn;

                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = response.csrf_token;
                });
                document.querySelectorAll('input[name="csrf_token_default"]').forEach(input => {
                    input.value = response.csrf_token;
                });

                createToast({
                    title: response.title,
                    description: response.message,
                    svg: response.status === 'true'
                        ? `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5f38f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>`
                        : `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 8000,
                    top: 70
                });
            },
            error: function (xhr, status, error) {
                document.querySelector('.'+btnClass).innerHTML = btn;
                createToast({
                    title: 'Something Wrong!',
                    description: 'For further assistance, please contact our support team.',
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 70
                });
            }
        });
    });
</script>
