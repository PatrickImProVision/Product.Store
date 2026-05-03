<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? '',
    'pageTitle'       => $pageTitle ?? '',
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? 'Product Store powered by CodeIgniter',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
    'bodyClass'       => $bodyClass ?? 'bg-light',
];
?>
<?= view('shared/site_head', $chrome) ?>
<?= view('shared/site_nav', $chrome) ?>

<main class="container py-5">
    <?= view('shared/site_hero', [
        'webTitle'       => $webTitle ?? 'Product Store',
        'webDescription' => $webDescription ?? '',
    ]) ?>

    <div class="row g-4">
        <?php foreach (($promotions ?? []) as $promotion): ?>
            <div class="col-12 col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5 card-title"><?= esc($promotion['title'] ?? 'Web Promote') ?></h2>
                        <p class="card-text mb-0 text-secondary"><?= esc($promotion['description'] ?? '') ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (! empty($siteContacts)): ?>
        <section class="mt-5 pt-4 border-top" aria-labelledby="home-site-contacts-heading">
            <h2 id="home-site-contacts-heading" class="h4 mb-4">Contacts</h2>
            <div class="row g-4">
                <?php foreach ($siteContacts as $contact): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <?php
                                $cImg = trim((string) ($contact['remote_image'] ?? ''));
                                $cImgOk = $cImg !== '' && preg_match('#\Ahttps?://#i', $cImg) === 1;
                                ?>
                                <?php if ($cImgOk): ?>
                                    <div class="text-center mb-3">
                                        <img
                                            src="<?= esc($cImg, 'attr') ?>"
                                            alt=""
                                            class="rounded-circle border shadow-sm mx-auto d-block"
                                            style="width: 140px; height: 140px; object-fit: cover;"
                                            loading="lazy"
                                            referrerpolicy="no-referrer"
                                            onerror="this.classList.add('d-none'); var fb=this.nextElementSibling; if(fb) fb.classList.remove('d-none');"
                                        >
                                        <p class="d-none small mb-0 mt-2"><a href="<?= esc($cImg, 'attr') ?>" target="_blank" rel="noopener noreferrer">Open image</a></p>
                                    </div>
                                <?php endif; ?>
                                <h3 class="h6 card-title mb-2"><?= esc((string) ($contact['name'] ?? '')) ?></h3>
                                <?php $em = trim((string) ($contact['email'] ?? '')); ?>
                                <?php if ($em !== ''): ?>
                                    <p class="small mb-2">
                                        <a href="mailto:<?= esc($em, 'attr') ?>"><?= esc($em) ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php $msg = trim((string) ($contact['message'] ?? '')); ?>
                                <?php if ($msg !== ''): ?>
                                    <p class="card-text small text-secondary mb-0" style="white-space: pre-wrap;"><?= esc($msg) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?= view('shared/site_footer', $chrome) ?>
