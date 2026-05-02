<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? ('Become administrator — ' . ($webTitle ?? 'Product Store')),
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? 'Grant administrator role to an existing member account',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];
$formOpen       = $formOpen ?? false;
$requiresSecret = $requiresSecret ?? false;
$errors         = $errors ?? [];
$prefill        = $prefill ?? [];
$notice         = $notice ?? null;
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
                    <h1 class="h3 mb-2">Become administrator</h1>

                    <?php if (! $formOpen): ?>
                        <p class="text-secondary mb-4">
                            Granting administrator role is disabled because an administrator already exists.
                            Set <code class="small">admin.registerKey</code> in your environment and use that value
                            as the registration key on this page to allow promoting another account.
                        </p>
                        <a href="<?= site_url('Member/User/Login') ?>" class="btn btn-outline-secondary">Member sign in</a>
                        <a href="<?= site_url('Index') ?>" class="btn btn-link">Home</a>
                    <?php else: ?>
                        <p class="text-secondary small mb-4">
                            Use an <strong>existing member</strong> email and current password. This only updates that account&rsquo;s role to administrator.
                            If you do not have an account yet, <a href="<?= site_url('Member/User/Register') ?>">register as a member</a> first.
                        </p>
                        <?php if ($requiresSecret): ?>
                            <p class="text-secondary small mb-3">
                                Adding administrators after the first requires the server registration key (<code>admin.registerKey</code>).
                            </p>
                        <?php endif; ?>

                        <?php if ($notice !== null && $notice !== ''): ?>
                            <div class="alert alert-info" role="alert"><?= esc((string) $notice) ?></div>
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

                        <form method="post" action="<?= esc(site_url('Member/Admin/Register')) ?>" novalidate>
                            <?= csrf_field() ?>

                            <?php if ($requiresSecret): ?>
                                <div class="mb-3">
                                    <label for="registration_secret" class="form-label">Registration key</label>
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="registration_secret"
                                        name="registration_secret"
                                        autocomplete="off"
                                        maxlength="255"
                                        value="<?= esc((string) ($prefill['registration_secret'] ?? '')) ?>"
                                        required
                                    >
                                    <div class="form-text">Must match <code>admin.registerKey</code> in <code>.env</code>.</div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    autocomplete="email"
                                    maxlength="255"
                                    value="<?= esc((string) ($prefill['email'] ?? '')) ?>"
                                    required
                                >
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
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
                                    >
                                        Show
                                    </button>
                                </div>
                                <div class="form-text">Your current member password (not a new password).</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-dark btn-lg">Grant administrator role</button>
                                <a href="<?= site_url('Member/User/Register') ?>" class="btn btn-link text-secondary small py-0">Create member account</a>
                                <a href="<?= site_url('Member/Admin/Login') ?>" class="btn btn-link text-secondary">Administrator sign in</a>
                            </div>
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
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.textContent = show ? 'Hide' : 'Show';
        });
    });
})();
</script>

<?= view('shared/site_footer', $chrome) ?>
