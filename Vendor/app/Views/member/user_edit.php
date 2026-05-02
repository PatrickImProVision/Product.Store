<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? ('Edit profile — ' . ($webTitle ?? 'Product Store')),
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? 'Update your account',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];
$errors   = $errors ?? [];
$userId   = (int) ($userId ?? 0);
$prefill  = $prefill ?? [];
$editUrl  = site_url('Member/User/Edit/' . $userId);
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
                    <h1 class="h3 mb-2">Edit profile</h1>
                    <p class="text-secondary small mb-4">User ID <?= esc((string) $userId) ?></p>

                    <?php if (! empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= esc(is_array($err) ? implode(' ', $err) : (string) $err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= esc($editUrl) ?>" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                autocomplete="email"
                                maxlength="255"
                                value="<?= esc($prefill['email'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display name <span class="text-secondary small">(optional)</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="display_name"
                                name="display_name"
                                autocomplete="name"
                                maxlength="120"
                                value="<?= esc($prefill['display_name'] ?? '') ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="remote_image" class="form-label">Profile image URL <span class="text-secondary small">(optional)</span></label>
                            <input
                                type="url"
                                class="form-control"
                                id="remote_image"
                                name="remote_image"
                                inputmode="url"
                                autocomplete="off"
                                maxlength="2048"
                                placeholder="https://example.com/photo.jpg"
                                value="<?= esc($prefill['remote_image'] ?? '') ?>"
                            >
                            <div class="form-text">Clear the field to remove your profile photo.</div>
                        </div>

                        <hr class="my-4">

                        <p class="small text-secondary mb-3">Enter your current password to save changes.</p>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current password</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    class="form-control"
                                    id="current_password"
                                    name="current_password"
                                    autocomplete="current-password"
                                    maxlength="255"
                                    required
                                >
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary js-toggle-pass"
                                    data-target="current_password"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    Show
                                </button>
                            </div>
                        </div>

                        <p class="small text-secondary mb-2">Change password <span class="text-muted">(leave blank to keep)</span></p>

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
                            <div class="form-text">At least 8 characters when set.</div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">Confirm new password</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password_confirm"
                                    name="password_confirm"
                                    autocomplete="new-password"
                                    maxlength="255"
                                >
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary js-toggle-pass"
                                    data-target="password_confirm"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    Show
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dark btn-lg">Save changes</button>
                            <a href="<?= site_url('Member/User/Profile') ?>" class="btn btn-link text-secondary">Cancel — back to profile</a>
                        </div>
                    </form>
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
