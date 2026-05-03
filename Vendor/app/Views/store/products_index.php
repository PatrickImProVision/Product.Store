<?php
$memberSignedIn = $memberSignedIn ?? false;

$chrome = [
    'documentTitle'   => $documentTitle ?? '',
    'pageTitle'       => $pageTitle ?? 'Store',
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

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0">Products</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('Store/Search/Index') ?>" class="btn btn-outline-secondary">Search catalog</a>
            <?php if ($memberSignedIn): ?>
                <a href="<?= site_url('Store/Product/Create') ?>" class="btn btn-primary">Create Product</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (! empty($message)): ?>
        <div class="alert alert-info" role="alert">
            <?= esc($message) ?>
        </div>
    <?php endif; ?>

    <?= view('store/partials/products_feed', ['products' => $products, 'search' => $search ?? '']) ?>
</main>

<?= view('shared/site_footer', $chrome) ?>
