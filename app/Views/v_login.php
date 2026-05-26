<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title>Wismilak Portal - <?= esc($title); ?></title>

    <meta name="description" content="OneUI - Bootstrap 5 Admin Template &amp; UI Framework created by pixelcave and published on Themeforest">
    <meta name="author" content="pixelcave">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph Meta -->
    <meta property="og:title" content="OneUI - Bootstrap 5 Admin Template &amp; UI Framework">
    <meta property="og:site_name" content="OneUI">
    <meta property="og:description" content="OneUI - Bootstrap 5 Admin Template &amp; UI Framework created by pixelcave and published on Themeforest">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="">

    <!-- Icons -->
    <!-- The following icons can be replaced with your own, they are used by desktop and mobile browsers -->
    <link rel="shortcut icon" href="<?= base_url('assets/media/favicons/favicon.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('assets/media/favicons/favicon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/media/favicons/apple-touch-icon-180x180.png') ?>">
    <!-- END Icons -->

    <!-- Stylesheets -->
    <!-- OneUI framework -->
    <link rel="stylesheet" id="css-main" href="<?= base_url('assets/css/oneui.min.css') ?>">

    <!-- You can include a specific file from css/themes/ folder to alter the default color theme of the template. eg: -->
    <!-- <link rel="stylesheet" id="css-theme" href="assets/css/themes/amethyst.min.css"> -->
    <!-- END Stylesheets -->
  </head>

  <body>
    <div id="page-container">

      <!-- Main Container -->
      <main id="main-container">
        <!-- Page Content -->
        <div class="bg-image" style="background-image: url('assets/media/photos/photo28@2x.jpg');">
          <div class="row g-0 bg-primary-dark-op">
            <!-- Meta Info Section -->
            <div class="hero-static col-lg-4 d-none d-lg-flex flex-column justify-content-center">
              <div class="p-4 p-xl-5 flex-grow-1 d-flex align-items-center">
                <div class="w-100">
                  <a class="link-fx fw-semibold fs-2 text-white" href="index.html">
                    OneUI
                  </a>
                  <p class="text-white-75 me-xl-8 mt-2">
                    Welcome to your amazing app. Feel free to login and start managing your projects and clients.
                  </p>
                </div>
              </div>
              <div class="p-4 p-xl-5 d-xl-flex justify-content-between align-items-center fs-sm">
                <p class="fw-medium text-white-50 mb-0">
                  <strong>OneUI 5.7</strong> &copy; <span data-toggle="year-copy"></span>
                </p>
                <ul class="list list-inline mb-0 py-2">
                  <li class="list-inline-item">
                    <a class="text-white-75 fw-medium" href="javascript:void(0)">Legal</a>
                  </li>
                  <li class="list-inline-item">
                    <a class="text-white-75 fw-medium" href="javascript:void(0)">Contact</a>
                  </li>
                  <li class="list-inline-item">
                    <a class="text-white-75 fw-medium" href="javascript:void(0)">Terms</a>
                  </li>
                </ul>
              </div>
            </div>
            <!-- END Meta Info Section -->

            <!-- Main Section -->
            <div class="hero-static col-lg-8 d-flex flex-column align-items-center bg-body-extra-light">
              <div class="p-3 w-100 d-lg-none text-center">
                <a class="link-fx fw-semibold fs-3 text-dark" href="">
                  OneUI
                </a>
              </div>
              <div class="p-4 w-100 flex-grow-1 d-flex align-items-center">
                <div class="w-100">
                  <!-- Header -->
                  <div class="text-center mb-5">
                    <p class="mb-3">
                      <i class="fa fa-2x fa-circle-notch text-primary-light"></i>
                    </p>
                    <h1 class="fw-bold mb-2">
                      Sign In
                    </h1>
                    <p class="fw-medium text-muted">
                      Welcome, please login or <a href="">sign up</a> for a new account.
                    </p>
                  </div>
                  <!-- END Header -->

                  <?php $azureConfig = config(\Config\Azure::class); ?>
                  <?php if (! empty($errors['azure'])): ?>
                    <div class="row g-0 justify-content-center">
                      <div class="col-sm-8 col-xl-4">
                        <div class="alert alert-danger" role="alert"><?= esc($errors['azure']) ?></div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if (! empty($azureConfig->clientId)): ?>
                  <div class="row g-0 justify-content-center mb-4">
                    <div class="col-sm-8 col-xl-4">
                      <a href="<?= site_url('auth/azure') ?>" class="btn btn-lg btn-alt-secondary w-100">
                        <i class="fab fa-microsoft me-2"></i> Sign in with Microsoft
                      </a>
                      <?php if ($azureConfig->allowLocalLogin): ?>
                        <div class="text-center text-muted my-3 fs-sm">or sign in with username</div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endif; ?>

                  <?php if ($azureConfig->allowLocalLogin || empty($azureConfig->clientId)): ?>
                  <!-- Sign In Form -->
                  <!-- jQuery Validation (.js-validation-signin class is initialized in js/pages/op_auth_signin.min.js which was auto compiled from _js/pages/op_auth_signin.js) -->
                  <!-- For more info and examples you can check out https://github.com/jzaefferer/jquery-validation -->
                  <div class="row g-0 justify-content-center">
                    <div class="col-sm-8 col-xl-4">
                      <form class="js-validation-signin" action="login" method="POST">
                        <div class="mb-4">
                          <input type="text" class="form-control form-control-lg form-control-alt py-3" id="login-username" name="login-username" placeholder="Username" value="<?= set_value('login-username') ?>" autofocus>
                          <div class="invalid-feedback2"><?= $erruser = $errors['login-username'] ?? ''; ?></div>
                        </div>
                        <div class="mb-4">
                          <input type="password" class="form-control form-control-lg form-control-alt py-3" id="login-password" name="login-password" placeholder="Password" value="<?= set_value('login-password') ?>">
                          <div class="invalid-feedback2"><?= $errpass = $errors['login-password'] ?? '' ?></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                          <div>
                            <a class="text-muted fs-sm fw-medium d-block d-lg-inline-block mb-1" href="">
                              Forgot Password?
                            </a>
                          </div>
                          <div>
                            <button type="submit" class="btn btn-lg btn-alt-primary">
                              <i class="fa fa-fw fa-sign-in-alt me-1 opacity-50"></i> Sign In
                            </button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                  <!-- END Sign In Form -->
                  <?php endif; ?>
                </div>
              </div>
              <div class="px-4 py-3 w-100 d-lg-none d-flex flex-column flex-sm-row justify-content-between fs-sm text-center text-sm-start">
                <p class="fw-medium text-black-50 py-2 mb-0">
                  <strong>OneUI 5.7</strong> &copy; <span data-toggle="year-copy"></span>
                </p>
                <ul class="list list-inline py-2 mb-0">
                  <li class="list-inline-item">
                    <a class="text-muted fw-medium" href="javascript:void(0)">Legal</a>
                  </li>
                  <li class="list-inline-item">
                    <a class="text-muted fw-medium" href="javascript:void(0)">Contact</a>
                  </li>
                  <li class="list-inline-item">
                    <a class="text-muted fw-medium" href="javascript:void(0)">Terms</a>
                  </li>
                </ul>
              </div>
            </div>
            <!-- END Main Section -->
          </div>
        </div>
        <!-- END Page Content -->
      </main>
      <!-- END Main Container -->
    </div>
    <!-- END Page Container -->

    <script src="<?= base_url('assets/js/oneui.app.min.js') ?>"></script>

    <!-- jQuery (required for jQuery Validation plugin) -->
    <script src="<?= base_url('assets/js/lib/jquery.min.js') ?>"></script>

    <!-- Page JS Plugins -->
    <script src="<?= base_url('assets/js/plugins/jquery-validation/jquery.validate.min.js') ?>"></script>

    <!-- Page JS Code -->
    <script src="<?= base_url('assets/js/pages/op_auth_signin.min.js') ?>"></script>

    <script type="text/javascript">
        
    </script>

  </body>
</html>
