<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? '',
    'pageTitle'       => $pageTitle ?? '',
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
                    <div class="text-center mb-4">
                        <?php if ($avatar !== ''): ?>
                            <img
                                src="<?= esc($avatar, 'attr') ?>"
                                alt=""
                                class="img-thumbnail rounded-circle shadow-sm profile-remote-image-preview"
                                style="width: 120px; height: 120px; object-fit: cover; background: #fff;"
                                referrerpolicy="no-referrer"
                                loading="lazy"
                                decoding="async"
                                onerror="this.classList.add('d-none'); var fb=this.nextElementSibling; if(fb) fb.classList.remove('d-none');"
                            >
                            <div class="d-none small text-secondary">
                                <p class="mb-0">Photo preview is blocked or unavailable here (some hosts disallow embedding).</p>
                            </div>
                        <?php else: ?>
                            <div
                                class="rounded-circle bg-light border shadow-sm d-inline-flex align-items-center justify-content-center text-secondary mx-auto"
                                style="width: 120px; height: 120px;"
                                role="img"
                                aria-label="No profile photo set"
                            >
                                <span class="small px-2 text-center">No profile photo</span>
                            </div>
                        <?php endif; ?>
                    </div>

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
