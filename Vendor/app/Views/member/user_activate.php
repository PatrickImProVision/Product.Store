<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? '',
    'pageTitle'       => $pageTitle ?? '',
    'metaTitle'       => $metaTitle ?? ($webTitle ?? 'Product Store'),
    'metaDescription' => $metaDescription ?? 'Activate your account or set a new password',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];
$tokenValid    = $tokenValid ?? false;
$statusMessage = $statusMessage ?? null;
$errors        = $errors ?? [];
$accountEmail  = $accountEmail ?? '';
$guid          = $guid ?? '';
?>
<?= view('shared/site_head', $chrome) ?>
<?= view('shared/site_nav', $chrome) ?>

<main class="container py-5">
    <?= view('shared/site_hero', [
        'webTitle'       => $webTitle ?? 'Product Store',
        'webDescription' => $webDescription ?? '',
    ]) ?>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <?php if (! $tokenValid): ?>
                        <h1 class="h3 mb-3">Link not usable</h1>
                        <?php if ($statusMessage !== null && $statusMessage !== ''): ?>
                            <div class="alert alert-warning" role="alert"><?= esc((string) $statusMessage) ?></div>
                        <?php endif; ?>
                        <p class="text-secondary small mb-4">
                            For security, reset links expire after 24 hours and work only once.
                        </p>
                        <a href="<?= esc(site_url('Member/User/ForgotPassword')) ?>" class="btn btn-primary">Forgot password</a>
                        <a href="<?= esc(site_url('Member/User/Login')) ?>" class="btn btn-link">Sign in</a>
                    <?php else: ?>
                        <h1 class="h3 mb-2">Activate account</h1>
                        <p class="text-secondary small mb-4">
                            Choose a new password for <strong><?= esc($accountEmail) ?></strong>.
                            After saving, sign in with your email and this password.
                        </p>

                        <?php if (! empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= esc(is_array($err) ? implode(' ', $err) : (string) $err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= esc(site_url('Member/User/Activate/' . $guid)) ?>" novalidate>
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label for="password" class="form-label">New password</label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password"
                                        name="password"
                                        autocomplete="new-password"
                                        minlength="8"
                                        maxlength="255"
                                        required
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary js-toggle-pass"
                                        data-target="password"
                                        aria-label="Show password"
                                        aria-pressed="false"
                                        aria-controls="password"
                                    >
                                        Show
                                    </button>
                                </div>
                                <div class="form-text">At least 8 characters.</div>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirm" class="form-label">Confirm password</label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password_confirm"
                                        name="password_confirm"
                                        autocomplete="new-password"
                                        maxlength="255"
                                        required
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary js-toggle-pass"
                                        data-target="password_confirm"
                                        aria-label="Show confirm password"
                                        aria-pressed="false"
                                        aria-controls="password_confirm"
                                    >
                                        Show
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save password</button>
                            <a href="<?= esc(site_url('Member/User/Login')) ?>" class="btn btn-link">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
(function () {
    document.querySelectorAll('.js-toggle-pass').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-target');
            var input = id ? document.getElementById(id) : null;
            if (!input) {
                return;
            }
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            var isConfirm = id === 'password_confirm';
            btn.setAttribute(
                'aria-label',
                show
                    ? (isConfirm ? 'Hide confirm password' : 'Hide password')
                    : (isConfirm ? 'Show confirm password' : 'Show password')
            );
            btn.textContent = show ? 'Hide' : 'Show';
        });
    });
})();
</script>

<?= view('shared/site_footer', $chrome) ?>
