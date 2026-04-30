<?php if (empty($products)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-4">
            <?= ! empty($search) ? 'No products match your search.' : 'No products found.' ?>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($products as $product): ?>
            <?php
            $description = trim((string) ($product['description'] ?? ''));
            $words       = preg_split('/\s+/', $description, -1, PREG_SPLIT_NO_EMPTY);
            $shortDesc   = $description;

            if (is_array($words) && count($words) > 30) {
                $shortDesc = implode(' ', array_slice($words, 0, 30)) . '...';
            }
            ?>
            <div class="col-md-6 col-lg-3">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <?php if (! empty($product['remote_image'])): ?>
                                <img
                                    src="<?= esc($product['remote_image']) ?>"
                                    alt="<?= esc($product['name']) ?>"
                                    class="img-thumbnail"
                                    style="width: 120px; height: 120px; object-fit: contain; background: #fff;"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                >
                                <span class="text-danger small" style="display: none;">Image unavailable</span>
                            <?php else: ?>
                                <div class="text-muted small">No image</div>
                            <?php endif; ?>
                        </div>
                        <h2 class="h5 mb-2"><?= esc($product['name']) ?></h2>
                        <p class="mb-2 text-secondary"><?= esc($shortDesc) ?></p>
                        <div class="small text-muted mb-3">
                            Price: <strong><?= esc(number_format((float) $product['price'], 2)) ?></strong>
                            &nbsp;|&nbsp; Quantity: <strong><?= esc((string) $product['quantity']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-end mt-auto">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('Store/Product/View/' . $product['id']) ?>">View</a>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
