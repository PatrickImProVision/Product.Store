<?php

$chrome = [
    'documentTitle'   => $documentTitle ?? '',
    'pageTitle'       => $pageTitle ?? 'Basket',
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
        <h1 class="h3 mb-0">Basket</h1>
        <a href="<?= site_url('Store/Index') ?>" class="btn btn-outline-secondary">Back to Products</a>
    </div>

    <div class="alert alert-secondary py-2">
        Profile: <strong><?= esc((string) ($profile['name'] ?? 'Guest Visitor')) ?></strong>
        (<?= esc((string) ($profile['id'] ?? 'guest')) ?>)
    </div>

    <?php if (! empty($message)): ?>
        <div id="basketMessage" class="alert alert-info" role="alert">
            <?= esc($message) ?>
        </div>
    <?php else: ?>
        <div id="basketMessage" class="alert d-none" role="alert"></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-dark">
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($basket)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">Your basket is empty.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($basket as $item): ?>
                            <tr data-item-id="<?= esc((string) ($item['id'] ?? 0)) ?>">
                                <td><?= esc((string) ($item['name'] ?? '')) ?></td>
                                <td><?= esc(number_format((float) ($item['price'] ?? 0), 2)) ?></td>
                                <td><?= esc((string) ($item['quantity'] ?? 0)) ?></td>
                                <td class="item-total"><?= esc(number_format((float) ($item['total'] ?? 0), 2)) ?></td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        data-delete-url="<?= site_url('Store/Basket/Delete/' . (int) ($item['id'] ?? 0)) ?>"
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        Delete From Basket
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Grand Total</th>
                        <th id="grandTotalValue"><?= esc(number_format((float) $grandTotal, 2)) ?></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    const basketMessage = document.getElementById('basketMessage');
    const grandTotalEl = document.getElementById('grandTotalValue');

    function showBasketMessage(message, success = true) {
        basketMessage.classList.remove('d-none', 'alert-info', 'alert-danger', 'alert-success');
        basketMessage.classList.add(success ? 'alert-success' : 'alert-danger');
        basketMessage.textContent = message;
    }

    function recalcGrandTotal() {
        const totalCells = document.querySelectorAll('.item-total');
        let sum = 0;

        totalCells.forEach((cell) => {
            const value = parseFloat((cell.textContent || '0').replace(/,/g, ''));
            if (!Number.isNaN(value)) {
                sum += value;
            }
        });

        grandTotalEl.textContent = sum.toFixed(2);
    }

    document.querySelectorAll('button[data-delete-url]').forEach((btn) => {
        btn.addEventListener('click', async function () {
            const row = btn.closest('tr');
            const url = btn.getAttribute('data-delete-url');
            btn.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });

                const result = await response.json();

                if (result.success) {
                    row.remove();
                    recalcGrandTotal();
                    showBasketMessage(result.message || 'Item removed from basket.', true);

                    const rowsLeft = document.querySelectorAll('tbody tr[data-item-id]').length;
                    if (rowsLeft === 0) {
                        location.reload();
                    }
                } else {
                    showBasketMessage(result.message || 'Unable to remove item.', false);
                    btn.disabled = false;
                }
            } catch (error) {
                showBasketMessage('Failed to remove item. Please try again.', false);
                btn.disabled = false;
            }
        });
    });
</script>

<?= view('shared/site_footer', $chrome) ?>
