<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Portal</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" href="<?= base_url('assets/media/favicons/favicon.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('assets/media/favicons/favicon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/media/favicons/apple-touch-icon-180x180.png') ?>">
    <link rel="stylesheet" id="css-main" href="<?= base_url('assets/css/oneui.min.css') ?>">
</head>
<body>
<?php
    $displayName = $user['name'] ?? '';
    $displayUser = $user['username'] ?? ($user['email'] ?? '');
    $isAzure     = ($user['source'] ?? null) === 'azure';
    $logoutUrl   = base_url($isAzure ? 'auth/azure/logout' : 'logout');
?>
<div id="page-container">

    <!-- Header -->
    <header id="page-header">
        <div class="content-header">
            <div class="d-flex align-items-center">
                <span class="fw-semibold fs-4">Portal</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-user-circle fa-lg text-muted"></i>
                    <div class="lh-sm">
                        <div class="fw-semibold fs-sm">
                            <?= esc($displayName !== '' ? $displayName : $displayUser) ?>
                        </div>
                        <?php if ($displayName !== '' && $displayUser !== ''): ?>
                        <div class="text-muted fs-xs"><?= esc($displayUser) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($isAzure): ?>
                    <span class="badge bg-primary-light text-primary ms-1">
                        <i class="fab fa-microsoft me-1"></i>Microsoft
                    </span>
                    <?php endif; ?>
                </div>
                <a href="<?= base_url('applications') ?>" class="btn btn-sm btn-alt-secondary">
                    <i class="fa fa-cogs me-1"></i> Manage Apps
                </a>
                <a href="<?= $logoutUrl ?>" class="btn btn-sm btn-alt-secondary">
                    <i class="fa fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </header>
    <!-- END Header -->

    <!-- Main Container -->
    <main id="main-container">
        <div class="content content-full">

            <div class="py-4 text-center">
                <h2 class="fw-bold mb-1">Applications</h2>
                <p class="text-muted">Select an application to continue</p>
            </div>

            <?php if (empty($applications)): ?>
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>
                        No applications available yet.
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row g-4 justify-content-center">
                <?php foreach ($applications as $app):
                    $targetUrl = $isAzure
                        ? ($app['sso_login_url'] ?? '')
                        : ($app['local_login_url'] ?? '');
                    $color     = esc($app['color'] ?? 'primary');
                    $icon      = esc($app['icon'] ?? 'fa-th-large');
                ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <?php if ($targetUrl !== ''): ?>
                    <a href="<?= esc($targetUrl) ?>" target="_blank" rel="noopener noreferrer" class="block block-link-shadow block-rounded text-center p-4 text-decoration-none">
                    <?php else: ?>
                    <div class="block block-rounded text-center p-4" title="No URL configured for this application">
                    <?php endif; ?>
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-<?= $color ?>-light" style="width:64px;height:64px;">
                                <i class="fa <?= $icon ?> fa-2x text-<?= $color ?>"></i>
                            </span>
                        </div>
                        <div class="fw-semibold"><?= esc($app['name']) ?></div>
                        <?php if (!empty($app['description'])): ?>
                        <div class="fs-sm text-muted mt-1"><?= esc($app['description']) ?></div>
                        <?php endif; ?>
                        <?php if ($targetUrl === ''): ?>
                        <div class="fs-xs text-danger mt-2">
                            <i class="fa fa-exclamation-circle me-1"></i>Not configured
                        </div>
                        <?php endif; ?>
                    <?php if ($targetUrl !== ''): ?>
                    </a>
                    <?php else: ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>
    <!-- END Main Container -->

</div>
<!-- END Page Container -->

<script src="<?= base_url('assets/js/oneui.app.min.js') ?>"></script>
</body>
</html>
