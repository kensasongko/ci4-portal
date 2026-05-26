<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?= $application ? 'Edit' : 'New' ?> Application - Portal</title>
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
    $isEdit      = $application !== null;

    $val = function(string $field) use ($application, $old): string {
        if (!empty($old[$field])) return esc($old[$field]);
        if ($application !== null) return esc($application[$field] ?? '');
        return '';
    };

    $colors = ['primary', 'success', 'danger', 'warning', 'info', 'secondary', 'dark'];
?>
<div id="page-container">

    <!-- Header -->
    <header id="page-header">
        <div class="content-header">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= base_url('home') ?>" class="fw-semibold fs-4 text-dark text-decoration-none">Portal</a>
                <span class="text-muted">/</span>
                <a href="<?= base_url('applications') ?>" class="text-muted text-decoration-none">Applications</a>
                <span class="text-muted">/</span>
                <span class="text-muted"><?= $isEdit ? 'Edit' : 'New' ?></span>
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

            <div class="py-4">
                <h1 class="h3 fw-bold mb-1"><?= $isEdit ? 'Edit Application' : 'New Application' ?></h1>
                <p class="text-muted mb-0">
                    <?= $isEdit ? 'Update the application details below.' : 'Fill in the details to add a new application to the portal.' ?>
                </p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle me-2"></i>
                Please fix the errors below before saving.
            </div>
            <?php endif; ?>

            <div class="block block-rounded">
                <div class="block-content">
                    <form method="post" action="<?= base_url($isEdit ? 'applications/update/' . $application['id'] : 'applications/store') ?>">
                        <?= csrf_field() ?>

                        <div class="row g-4">

                            <!-- Name -->
                            <div class="col-12 col-md-8">
                                <label class="form-label fw-semibold" for="name">
                                    Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                       value="<?= $val('name') ?>"
                                       placeholder="e.g. HR System"
                                       maxlength="100"
                                       required
                                       autofocus>
                                <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= esc($errors['name']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Sort Order -->
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="sort_order">Sort Order</label>
                                <input type="number"
                                       id="sort_order"
                                       name="sort_order"
                                       class="form-control <?= isset($errors['sort_order']) ? 'is-invalid' : '' ?>"
                                       value="<?= $val('sort_order') ?: '0' ?>"
                                       min="0">
                                <div class="form-text">Lower numbers appear first.</div>
                                <?php if (isset($errors['sort_order'])): ?>
                                <div class="invalid-feedback"><?= esc($errors['sort_order']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="description">Description</label>
                                <input type="text"
                                       id="description"
                                       name="description"
                                       class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                       value="<?= $val('description') ?>"
                                       placeholder="Short description shown under the app name"
                                       maxlength="255">
                                <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= esc($errors['description']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Icon -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="icon">Icon</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="icon-preview">
                                        <i id="icon-preview-el" class="fa <?= $val('icon') ?: 'fa-th-large' ?>"></i>
                                    </span>
                                    <input type="text"
                                           id="icon"
                                           name="icon"
                                           class="form-control <?= isset($errors['icon']) ? 'is-invalid' : '' ?>"
                                           value="<?= $val('icon') ?>"
                                           placeholder="fa-cogs"
                                           maxlength="80">
                                    <?php if (isset($errors['icon'])): ?>
                                    <div class="invalid-feedback"><?= esc($errors['icon']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">
                                    Font Awesome class, e.g.
                                    <code>fa-cogs</code>, <code>fa-users</code>, <code>fa-chart-bar</code>,
                                    <code>fa-file-alt</code>, <code>fa-shopping-cart</code>.
                                </div>
                            </div>

                            <!-- Color -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="color">Color</label>
                                <select id="color" name="color"
                                        class="form-select <?= isset($errors['color']) ? 'is-invalid' : '' ?>">
                                    <?php
                                        $selectedColor = !empty($old['color'])
                                            ? $old['color']
                                            : ($application['color'] ?? 'primary');
                                    ?>
                                    <?php foreach ($colors as $c): ?>
                                    <option value="<?= $c ?>" <?= $selectedColor === $c ? 'selected' : '' ?>>
                                        <?= ucfirst($c) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['color'])): ?>
                                <div class="invalid-feedback"><?= esc($errors['color']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- SSO Login URL -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="sso_login_url">
                                    SSO Login URL
                                    <span class="badge bg-primary-light text-primary ms-1 fw-normal fs-xs">
                                        <i class="fab fa-microsoft me-1"></i>Entra ID users
                                    </span>
                                </label>
                                <input type="url"
                                       id="sso_login_url"
                                       name="sso_login_url"
                                       class="form-control <?= isset($errors['sso_login_url']) ? 'is-invalid' : '' ?>"
                                       value="<?= $val('sso_login_url') ?>"
                                       placeholder="https://app.example.com/sso/login">
                                <div class="form-text">Redirect destination for Microsoft Entra ID authenticated users.</div>
                                <?php if (isset($errors['sso_login_url'])): ?>
                                <div class="invalid-feedback"><?= esc($errors['sso_login_url']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Local Login URL -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="local_login_url">
                                    Local Login URL
                                    <span class="badge bg-secondary-light text-secondary ms-1 fw-normal fs-xs">
                                        <i class="fa fa-user me-1"></i>Local users
                                    </span>
                                </label>
                                <input type="url"
                                       id="local_login_url"
                                       name="local_login_url"
                                       class="form-control <?= isset($errors['local_login_url']) ? 'is-invalid' : '' ?>"
                                       value="<?= $val('local_login_url') ?>"
                                       placeholder="https://app.example.com/login">
                                <div class="form-text">Redirect destination for locally authenticated users.</div>
                                <?php if (isset($errors['local_login_url'])): ?>
                                <div class="invalid-feedback"><?= esc($errors['local_login_url']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Active -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           id="is_active" name="is_active" value="1"
                                           <?php
                                               if (!empty($old)) {
                                                   echo isset($old['is_active']) ? 'checked' : '';
                                               } elseif ($application !== null) {
                                                   echo $application['is_active'] ? 'checked' : '';
                                               } else {
                                                   echo 'checked';
                                               }
                                           ?>>
                                    <label class="form-check-label fw-semibold" for="is_active">Active</label>
                                </div>
                                <div class="form-text">Inactive applications are hidden from the portal home page.</div>
                            </div>

                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex gap-2 mt-5 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save me-1"></i> <?= $isEdit ? 'Save Changes' : 'Create Application' ?>
                            </button>
                            <a href="<?= base_url('applications') ?>" class="btn btn-alt-secondary">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </main>
    <!-- END Main Container -->

</div>
<!-- END Page Container -->

<script src="<?= base_url('assets/js/oneui.app.min.js') ?>"></script>
<script>
    // Live icon preview
    document.getElementById('icon').addEventListener('input', function() {
        var el = document.getElementById('icon-preview-el');
        el.className = 'fa ' + (this.value.trim() || 'fa-th-large');
    });
</script>
</body>
</html>
