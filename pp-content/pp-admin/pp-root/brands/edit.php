<?php
    if (!defined('PipraPay_INIT')) {
        http_response_code(403);
        exit('Direct access not allowed');
    }

    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', $global_user_response['response'][0]['role'])) {
        http_response_code(403);
        exit('Access denied. You need permission to perform this action. Please contact the admin.');
    }

    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'brands', 'edit', $global_user_response['response'][0]['role'])) {
        http_response_code(403);
        exit('Access denied. You need permission to perform this action. Please contact the admin.');
    }

    $params = json_decode($_POST['params'] ?? '{}', true);

    $b_id = getParam($params, 'b_id');

    if ($b_id === null) {
        http_response_code(403);
        exit('Invalid brand id');
    }else{
        $b_id = escape_string($b_id);

        $response_brands = json_decode(getData($db_prefix.'brands','WHERE brand_id = "'.$b_id.'"'),true);
        if($response_brands['status'] != true){
            http_response_code(403);
            exit('Direct access not allowed');
        }
    }
?>

<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
            <!-- Page pre-title -->
                <div class="page-pretitle">
                    <ol class="breadcrumb breadcrumb-arrow mb-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0)" onclick="load_content('All Brands','<?php echo $site_url.$path_admin ?>/brands','nav-item-brands')">All Brands</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Edit Brand</a></li>
                    </ol>
                </div>
                <h2 class="page-title">Edit Brand</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row g-gs">
            <div class="col-12 col-xxl-12">
                <form class="form-edit-brand">
                    <input type="hidden" name="action" value="edit-brand">
                    <input type="hidden" name="b_id" value="<?= $b_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">

                    <div class="card p-2">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="brand-name" class="form-label">Brand Name<span
                                                class="text-danger">*</span></label>
                                        <div class="form-control-wrap">
                                            <input type="text" class="form-control" id="brand-name" name="brand-name" placeholder="Brand Name" value="<?php echo $response_brands['response'][0]['identify_name']?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end pt-3">
                        <button class="btn btn-primary btn-edit-brand" type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script data-cfasync="false">
    $('.form-edit-brand').submit(function (e) {
        e.preventDefault();

        var btnClass = 'btn-edit-brand';

        var btn = document.querySelector('.'+btnClass).innerHTML;

        document.querySelector('.'+btnClass).innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';

        var formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: '<?php echo $site_url ?>dashboard',
            data: formData,
            dataType: 'json',
            success: function (response) {
                closeAllBootstrapModals();

                document.querySelector('.'+btnClass).innerHTML = btn;

                document.querySelectorAll('input[name="csrf_token"]').forEach(i => {
                    i.value = response.csrf_token;
                });

                document.querySelectorAll('input[name="csrf_token_default"]').forEach(input => {
                    input.value = response.csrf_token;
                });

                if (response.status === 'true') {
                    createToast({
                        title: response.title,
                        description: response.message,
                        svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5f38f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>`,
                        timeout: 6000,
                        top: 70
                    });

                    location.reload();
                } else {
                    createToast({
                        title: response.title,
                        description: response.message,
                        svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                        timeout: 6000,
                        top: 70
                    });
                }
            },
            error: function (xhr, status, error) {
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