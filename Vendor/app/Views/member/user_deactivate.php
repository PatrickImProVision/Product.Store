<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? ('Deactivate — ' . ($webTitle ?? 'Product Store')),
    'metaTitle'       => $metaTitle ?? ($webTitle ?? 'Product Store'),
    'metaDescription' => $metaDescription ?? 'Deactivate your account',
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
                        <h1 class="h3 mb-3">Cannot deactivate</h1>
                        <?php if ($statusMessage !== null && $statusMessage !== ''): ?>
                            <div class="alert alert-warning" role="alert"><?= esc((string) $statusMessage) ?></div>
                        <?php endif; ?>
                        <?php if ($accountEmail !== ''): ?>
                            <p class="small text-secondary mb-4"><?= esc($accountEmail) ?></p>
                        <?php endif; ?>
                        <a href="<?= esc(site_url('Member/User/Login')) ?>" class="btn btn-primary">Sign in</a>
                        <a href="<?= esc(site_url('Index')) ?>" class="btn btn-link">Home</a>
                    <?php else: ?>
                        <h1 class="h3 mb-2 text-danger">Deactivate account</h1>
                        <p class="mb-3">
                            You are about to deactivate <strong><?= esc($accountEmail) ?></strong>.
                            You will not be able to sign in until an administrator re-enables the account.
                        </p>
                        <div class="alert alert-warning small" role="alert">
                            This action uses your email link only — no password is required on this page.
                            Links expire after 24 hours and work once.
                        </div>

                        <?php if (! empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= esc(is_array($err) ? implode(' ', $err) : (string) $err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= esc(site_url('Member/User/DeActivate/' . $guid)) ?>" class="mt-4">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger">Confirm deactivate account</button>
                            <a href="<?= esc(site_url('Member/User/Login')) ?>" class="btn btn-outline-secondary">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?= view('shared/site_footer', $chrome) ?>
