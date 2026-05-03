<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? '',
    'pageTitle'       => $pageTitle ?? 'Delete product',
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

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">Delete this product?</h1>
            <p class="text-secondary mb-2">
                You are about to permanently remove <strong><?= esc($product['name'] ?? '') ?></strong>
                <?php if (! empty($product['id'])): ?>
                    <span class="text-muted">(ID <?= esc((string) $product['id']) ?>)</span>
                <?php endif; ?>
                from the catalog.
            </p>
            <p class="text-danger small mb-4">This cannot be undone.</p>

            <form method="post" action="<?= site_url('Store/Product/Delete/' . (int) ($product['id'] ?? 0)) ?>" class="d-flex flex-wrap gap-2">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger">Delete permanently</button>
                <a href="<?= site_url('Store/Product/View/' . (int) ($product['id'] ?? 0)) ?>" class="btn btn-outline-secondary">Cancel</a>
                <a href="<?= site_url('Store/Index') ?>" class="btn btn-outline-secondary">Back to Store</a>
            </form>
        </div>
    </div>
</main>

<?= view('shared/site_footer', $chrome) ?>
