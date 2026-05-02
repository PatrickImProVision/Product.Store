<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? ('Profile — ' . ($webTitle ?? 'Product Store')),
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? 'Your account',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];
$profile = $profile ?? [];
$notice  = $notice ?? null;

$formatDt = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '—';
    }
    $ts = strtotime($value);

    return $ts !== false ? date('M j, Y \a\t g:i A', $ts) : $value;
};
?>
<?= view('shared/site_head', $chrome) ?>
<?= view('shared/site_nav', $chrome) ?>

<main class="container py-5">
    <?= view('shared/site_hero', [
        'webTitle'       => $webTitle ?? 'Product Store',
        'webDescription' => $webDescription ?? '',
    ]) ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-start gap-3 mb-4">
                <h1 class="h3 mb-0">Your profile</h1>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= site_url('Store/Index') ?>" class="btn btn-outline-secondary btn-sm">Store</a>
                    <a href="<?= site_url('Member/User/Edit/' . (int) ($profile['id'] ?? 0)) ?>" class="btn btn-outline-primary btn-sm">Edit profile</a>
                    <a href="<?= site_url('Member/User/DeActivateRequest') ?>" class="btn btn-outline-secondary btn-sm">Deactivate account</a>
                    <a href="<?= site_url('Member/User/Logout') ?>" class="btn btn-outline-danger btn-sm">Sign out</a>
                </div>
            </div>

            <?php if ($notice !== null && $notice !== ''): ?>
                <div class="alert alert-success" role="alert"><?= esc((string) $notice) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <?php $avatar = trim((string) ($profile['remote_image'] ?? '')); ?>
                    <?php if ($avatar !== ''): ?>
                        <div class="text-center mb-4">
                            <a
                                href="<?= esc($avatar) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="d-inline-block rounded-circle"
                                title="Open profile image"
                                aria-label="Open profile image in new tab"
                            >
                                <img
                                    src="<?= esc($avatar) ?>"
                                    alt="Profile photo — opens full image"
                                    class="img-thumbnail rounded-circle shadow-sm"
                                    style="width: 120px; height: 120px; object-fit: cover; background: #fff;"
                                    referrerpolicy="no-referrer"
                                    onerror="this.style.display='none'; this.closest('a').style.display='none'; this.closest('a').nextElementSibling.style.display='block';"
                                >
                            </a>
                            <p class="text-danger small mt-2 mb-0" style="display: none;">Image could not be loaded.</p>
                        </div>
                    <?php endif; ?>

                    <h2 class="h5 text-secondary mb-4">Account details</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 col-md-3 text-secondary small text-uppercase">Email</dt>
                        <dd class="col-sm-8 col-md-9"><?= esc((string) ($profile['email'] ?? '')) ?></dd>

                        <dt class="col-sm-4 col-md-3 text-secondary small text-uppercase pt-3">Display name</dt>
                        <dd class="col-sm-8 col-md-9 pt-3">
                            <?php $dn = trim((string) ($profile['display_name'] ?? '')); ?>
                            <?= $dn !== '' ? esc($dn) : '<span class="text-muted">Not set</span>' ?>
                        </dd>

                        <dt class="col-sm-4 col-md-3 text-secondary small text-uppercase pt-3">Role</dt>
                        <dd class="col-sm-8 col-md-9 pt-3"><?= esc((string) ($profile['role_name'] ?? 'User')) ?></dd>

                        <dt class="col-sm-4 col-md-3 text-secondary small text-uppercase pt-3">Member since</dt>
                        <dd class="col-sm-8 col-md-9 pt-3"><?= esc($formatDt((string) ($profile['created_at'] ?? ''))) ?></dd>

                        <dt class="col-sm-4 col-md-3 text-secondary small text-uppercase pt-3">Last updated</dt>
                        <dd class="col-sm-8 col-md-9 pt-3"><?= esc($formatDt((string) ($profile['updated_at'] ?? ''))) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</main>

<?= view('shared/site_footer', $chrome) ?>
