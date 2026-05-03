<?php
$canManageProduct = $canManageProduct ?? false;

$chrome = [
    'documentTitle'   => $documentTitle ?? '',
    'pageTitle'       => $pageTitle ?? ($product['name'] ?? 'Product'),
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

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 mb-4">Product Details</h1>

                    <?php if (! empty($message)): ?>
                        <div class="alert alert-success" role="alert">
                            <?= esc($message) ?>
                        </div>
                    <?php endif; ?>

                    <div id="product-add-to-basket-feedback" class="alert d-none" role="alert" aria-live="polite"></div>

                    <div class="mb-4 text-center text-md-start">
                        <?php if (! empty($product['remote_image'])): ?>
                            <img
                                src="<?= esc($product['remote_image']) ?>"
                                alt="<?= esc($product['name']) ?>"
                                class="img-thumbnail"
                                style="max-width: 280px; max-height: 280px; object-fit: contain; background: #fff;"
                            >
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-3">Title</dt>
                        <dd class="col-sm-9"><?= esc($product['name']) ?></dd>

                        <dt class="col-sm-3">Price</dt>
                        <dd class="col-sm-9"><?= esc(number_format((float) $product['price'], 2)) ?></dd>

                        <dt class="col-sm-3">Quantity</dt>
                        <dd class="col-sm-9"><?= esc((string) $product['quantity']) ?></dd>

                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9"><?= esc((string) ($product['description'] ?? '')) ?></dd>
                    </dl>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a
                            href="<?= site_url('Store/Basket/Add/' . $product['id']) ?>"
                            class="btn btn-success"
                            id="product-add-to-basket"
                        >Add To Basket</a>
                        <a href="<?= site_url('Store/Basket') ?>" class="btn btn-dark">View Basket</a>
                        <?php if ($canManageProduct): ?>
                            <a href="<?= site_url('Store/Product/Edit/' . $product['id']) ?>" class="btn btn-primary">Edit</a>
                            <a href="<?= site_url('Store/Product/Delete/' . $product['id']) ?>" class="btn btn-danger">Delete</a>
                        <?php endif; ?>
                        <a href="<?= site_url('Store/Index') ?>" class="btn btn-outline-secondary">Back to Products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
(function () {
    var btn = document.getElementById('product-add-to-basket');
    var box = document.getElementById('product-add-to-basket-feedback');
    if (!btn || !box) {
        return;
    }

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (btn.getAttribute('aria-busy') === 'true') {
            return;
        }

        var url = btn.getAttribute('href');
        if (!url) {
            return;
        }

        btn.setAttribute('aria-busy', 'true');
        btn.classList.add('disabled');

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (wrapped) {
                var data = wrapped.data;
                box.classList.remove('d-none', 'alert-success', 'alert-danger');

                if (wrapped.ok && data && data.success) {
                    box.classList.add('alert-success');
                    var msg = data.message || 'Added to basket.';
                    var detail = '';
                    if (typeof data.basketTotalQuantity === 'number') {
                        detail = ' Basket now has ' + data.basketTotalQuantity + ' item' +
                            (data.basketTotalQuantity === 1 ? '' : 's') + '.';
                    }
                    box.textContent = msg + detail;
                } else {
                    box.classList.add('alert-danger');
                    box.textContent = (data && data.message) ? data.message : 'Could not add to basket.';
                }
            })
            .catch(function () {
                box.classList.remove('d-none', 'alert-success', 'alert-danger');
                box.classList.add('alert-danger');
                box.textContent = 'Could not add to basket. Please try again.';
            })
            .finally(function () {
                btn.removeAttribute('aria-busy');
                btn.classList.remove('disabled');
            });
    });
})();
</script>

<?= view('shared/site_footer', $chrome) ?>
