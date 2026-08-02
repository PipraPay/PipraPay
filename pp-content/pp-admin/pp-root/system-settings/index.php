<?php
    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
        http_response_code(403);
        exit('Access denied. You need permission to perform this action. Please contact the admin.');
    }
?>

<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
            <!-- Page pre-title -->
                <div class="page-pretitle">System Settings</div>
                <h2 class="page-title">System Settings</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Settings</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4 <?= hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_general', $global_user_response['response'][0]['role']) ? '' : 'd-none' ?>" onclick="load_content('System Settings','<?php echo $site_url.$path_admin ?>/system-settings/geneal','nav-item-system-settings')"  style="cursor: pointer;">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <!-- Icon -->
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:50px;height:50px;">
                                        <svg xmlns="http://www.w3.org/2000/svg"  style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-settings"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>
                                    </div>

                                    <!-- Text -->
                                    <div class="ms-3">
                                        <h5 class="card-title m-0 mb-1 fw-medium text-primary" style=" margin-top: -3px !important; ">
                                            Geneal Setting
                                        </h5>
                                        <p class="m-0 text-dark">Manage essential system preferences and core configurations.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 <?= hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_email', $global_user_response['response'][0]['role']) ? '' : 'd-none' ?>" onclick="load_content('System Settings','<?php echo $site_url.$path_admin ?>/system-settings/email-settings','nav-item-system-settings')"  style="cursor: pointer;">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <!-- Icon -->
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:50px;height:50px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-mail"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                                    </div>

                                    <!-- Text -->
                                    <div class="ms-3">
                                        <h5 class="card-title m-0 mb-1 fw-medium text-primary" style=" margin-top: -3px !important; ">
                                            Email Settings
                                        </h5>
                                        <p class="m-0 text-dark">Configure Brevo or SMTP for sending email notifications.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 <?= hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_cron', $global_user_response['response'][0]['role']) ? '' : 'd-none' ?>" onclick="load_content('System Settings','<?php echo $site_url.$path_admin ?>/system-settings/cron-job','nav-item-system-settings')"  style="cursor: pointer;">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <!-- Icon -->
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:50px;height:50px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-clock"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.5 21h-4.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v3" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h10" /><path d="M14 18a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M18 16.5v1.5l.5 .5" /></svg>
                                    </div>

                                    <!-- Text -->
                                    <div class="ms-3">
                                        <h5 class="card-title m-0 mb-1 fw-medium text-primary" style=" margin-top: -3px !important; ">
                                            Cron Job
                                        </h5>
                                        <p class="m-0 text-dark">Manage scheduled tasks and automated system processes.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 <?= hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_update', $global_user_response['response'][0]['role']) ? '' : 'd-none' ?>" onclick="load_content('System Settings','<?php echo $site_url.$path_admin ?>/system-settings/update','nav-item-system-settings')"  style="cursor: pointer;">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <!-- Icon -->
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:50px;height:50px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                                    </div>

                                    <!-- Text -->
                                    <div class="ms-3">
                                        <h5 class="card-title m-0 mb-1 fw-medium text-primary" style=" margin-top: -3px !important; ">
                                            Update
                                        </h5>
                                        <p class="m-0 text-dark">Manage system updates, patches, and version upgrades.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 <?= hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_import', $global_user_response['response'][0]['role']) ? '' : 'd-none' ?>" onclick="load_content('System Settings','<?php echo $site_url.$path_admin ?>/system-settings/import','nav-item-system-settings')"  style="cursor: pointer;">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <!-- Icon -->
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:50px;height:50px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-upload"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg>
                                    </div>

                                    <!-- Text -->
                                    <div class="ms-3">
                                        <h5 class="card-title m-0 mb-1 fw-medium text-primary" style=" margin-top: -3px !important; ">
                                            Import
                                        </h5>
                                        <p class="m-0 text-dark">Import themes, add-ons, payment gateways, or any other modules</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
