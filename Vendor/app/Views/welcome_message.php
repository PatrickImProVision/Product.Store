<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? ($metaTitle ?? $webTitle ?? 'Product Store'),
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? 'Product Store powered by CodeIgniter',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
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
</main>

<?= view('shared/site_footer', $chrome) ?>
