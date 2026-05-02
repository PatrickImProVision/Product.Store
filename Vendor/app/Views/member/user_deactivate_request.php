<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? ('Deactivate account — ' . ($webTitle ?? 'Product Store')),
    'metaTitle'       => $metaTitle ?? ($webTitle ?? 'Product Store'),
    'metaDescription' => $metaDescription ?? 'Request account deactivation',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];
$errors           = $errors ?? [];
$notice           = $notice ?? null;
$deactivateLink   = $deactivateLink ?? null;
$mailDebug        = $mailDebug ?? null;
$accountEmail     = $accountEmail ?? '';
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
                    <h1 class="h3 mb-2">Deactivate account</h1>
                    <p class="text-secondary small mb-4">
                        Signed in as <strong><?= esc($accountEmail) ?></strong>.
                        Enter your password and we will email a confirmation link to this address.
                        You must open that link to finish deactivation.
                    </p>

                    <?php if ($notice !== null && $notice !== ''): ?>
                        <div class="alert alert-success" role="alert"><?= esc((string) $notice) ?></div>
                    <?php endif; ?>

                    <?php if (! empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= esc(is_array($err) ? implode(' ', $err) : (string) $err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($deactivateLink !== null && $deactivateLink !== ''): ?>
                        <div class="alert alert-secondary small mb-4" role="region" aria-label="Deactivation link preview">
                            <div class="fw-semibold mb-1">Confirmation link (copy if email did not arrive)</div>
                            <code class="user-select-all small d-block text-break"><?= esc($deactivateLink) ?></code>
                        </div>
                    <?php endif; ?>

                    <?php if ($mailDebug !== null && $mailDebug !== ''): ?>
                        <div class="alert alert-warning small" role="alert">
                            <div class="fw-semibold mb-1">Email could not be sent</div>
                            <?= esc((string) $mailDebug) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($deactivateLink === null): ?>
                        <form method="post" action="<?= esc(site_url('Member/User/DeActivateRequest')) ?>" novalidate>
                            <?= csrf_field() ?>

                            <div class="mb-4">
                                <label for="password" class="form-label">Current password</label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password"
                                        name="password"
                                        autocomplete="current-password"
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
                            </div>

                            <button type="submit" class="btn btn-outline-danger">Email deactivation link</button>
                            <a href="<?= esc(site_url('Member/User/Profile')) ?>" class="btn btn-link">Back to profile</a>
                        </form>
                    <?php else: ?>
                        <a href="<?= esc(site_url('Member/User/Profile')) ?>" class="btn btn-primary">Back to profile</a>
                        <a href="<?= esc(site_url('Member/User/Logout')) ?>" class="btn btn-link">Sign out</a>
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
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.textContent = show ? 'Hide' : 'Show';
        });
    });
})();
</script>

<?= view('shared/site_footer', $chrome) ?>
