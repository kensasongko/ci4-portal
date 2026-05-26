<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Manage Applications - Portal</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" href="<?= base_url('assets/media/favicons/favicon.png') ?>">
    <link rel="stylesheet" id="css-main" href="<?= base_url('assets/css/oneui.min.css') ?>">
</head>
<body>
<?php
    $currentUser = $user;
    $isAzure     = ($currentUser['source'] ?? null) === 'azure';
    $logoutUrl   = base_url($isAzure ? 'auth/azure/logout' : 'logout');
    $displayName = $currentUser['name'] ?? $currentUser['username'] ?? '';
?>
<div id="page-container">

    <!-- Header -->
    <header id="page-header">
        <div class="content-header">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= base_url('home') ?>" class="fw-semibold fs-4 text-dark text-decoration-none">Portal</a>
                <span class="text-muted">/</span>
                <span class="text-muted">Manage Applications</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold fs-sm d-none d-sm-inline"><?= esc($displayName) ?></span>
                <?php if ($isAzure): ?>
                <span class="badge bg-primary-light text-primary"><i class="fab fa-microsoft me-1"></i>Microsoft</span>
                <?php endif; ?>
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

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center py-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Applications</h1>
                    <p class="text-muted mb-0">Manage the application list shown on the portal home page.</p>
                </div>
                <a href="<?= base_url('applications/create') ?>" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> Add Application
                </a>
            </div>

            <!-- Flash Messages -->
            <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i><?= esc(session()->getFlashdata('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Applications Table -->
            <div class="block block-rounded">
                <div class="block-content p-0">
                    <?php if (empty($applications)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="fa fa-th-large fa-2x mb-3 d-block"></i>
                        No applications yet. <a href="<?= base_url('applications/create') ?>">Add the first one.</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px" class="text-center">Sort</th>
                                    <th>Name</th>
                                    <th>SSO Login URL</th>
                                    <th>Local Login URL</th>
                                    <th style="width:90px" class="text-center">Status</th>
                                    <th style="width:130px" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= esc($app['sort_order']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php
                                                $color = esc($app['color'] ?: 'primary');
                                                $icon  = esc($app['icon'] ?: 'fa-th-large');
                                            ?>
                                            <span class="d-inline-flex align-items-center justify-content-center rounded bg-<?= $color ?>-light flex-shrink-0"
                                                  style="width:36px;height:36px;">
                                                <i class="fa <?= $icon ?> text-<?= $color ?>"></i>
                                            </span>
                                            <div>
                                                <div class="fw-semibold"><?= esc($app['name']) ?></div>
                                                <?php if (!empty($app['description'])): ?>
                                                <div class="fs-sm text-muted"><?= esc($app['description']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fs-sm">
                                        <?php if (!empty($app['sso_login_url'])): ?>
                                        <a href="<?= esc($app['sso_login_url']) ?>" target="_blank" rel="noopener"
                                           class="text-muted text-truncate d-inline-block" style="max-width:220px"
                                           title="<?= esc($app['sso_login_url']) ?>">
                                            <?= esc($app['sso_login_url']) ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted fst-italic">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fs-sm">
                                        <?php if (!empty($app['local_login_url'])): ?>
                                        <a href="<?= esc($app['local_login_url']) ?>" target="_blank" rel="noopener"
                                           class="text-muted text-truncate d-inline-block" style="max-width:220px"
                                           title="<?= esc($app['local_login_url']) ?>">
                                            <?= esc($app['local_login_url']) ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted fst-italic">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <form method="post" action="<?= base_url('applications/toggle/' . $app['id']) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm <?= $app['is_active'] ? 'btn-success' : 'btn-alt-secondary' ?>"
                                                    title="<?= $app['is_active'] ? 'Active - click to deactivate' : 'Inactive - click to activate' ?>">
                                                <i class="fa <?= $app['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= base_url('applications/edit/' . $app['id']) ?>"
                                           class="btn btn-sm btn-alt-secondary me-1" title="Edit">
                                            <i class="fa fa-pencil-alt"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-alt-danger"
                                                title="Delete"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-delete-<?= $app['id'] ?>">
                                            <i class="fa fa-trash-alt"></i>
                                        </button>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="modal-delete-<?= $app['id'] ?>"
                                             tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Application</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Delete <strong><?= esc($app['name']) ?></strong>? This cannot be undone.
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="post" action="<?= base_url('applications/delete/' . $app['id']) ?>">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- END Delete Confirmation Modal -->
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- END Applications Table -->

        </div>
    </main>
    <!-- END Main Container -->

</div>
<!-- END Page Container -->

<script src="<?= base_url('assets/js/oneui.app.min.js') ?>"></script>
</body>
</html>
