<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? ('Forgot password — ' . ($webTitle ?? 'Product Store')),
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? 'Reset your password',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];
$errors    = $errors ?? [];
$notice    = $notice ?? null;
$resetLink = $resetLink ?? null;
$mailDebug = $mailDebug ?? null;
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
                    <h1 class="h3 mb-2">Forgot password</h1>
                    <p class="text-secondary small mb-4">Enter your account email to generate a password reset link.</p>

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

                    <form method="post" action="<?= esc(site_url('Member/User/ForgotPassword')) ?>" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-4">
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

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dark btn-lg">Send reset link</button>
                            <a href="<?= site_url('Member/User/Login') ?>" class="btn btn-link text-secondary">Back to sign in</a>
                        </div>
                    </form>

                    <?php if (is_string($resetLink) && $resetLink !== ''): ?>
                        <hr class="my-4">
                        <p class="small text-secondary mb-2">Development reset link</p>
                        <div class="small text-break">
                            <a href="<?= esc($resetLink) ?>"><?= esc($resetLink) ?></a>
                        </div>
                    <?php endif; ?>

                    <?php if (is_string($mailDebug) && $mailDebug !== ''): ?>
                        <div class="alert alert-warning small mt-3 mb-0" role="alert">
                            Mail server rejected send. Check email settings. Debug: <?= esc($mailDebug) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?= view('shared/site_footer', $chrome) ?>
