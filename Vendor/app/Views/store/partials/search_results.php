<?php
declare(strict_types=1);

if (! function_exists('store_search_escape_highlight')) {
    function store_search_escape_highlight(string $text, array $tokens): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        foreach ($tokens as $w) {
            $w = trim((string) $w);
            if (mb_strlen($w) < 2) {
                continue;
            }

            $wEsc = htmlspecialchars($w, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/(' . preg_quote($wEsc, '/') . ')/iu', '<mark class="bg-warning bg-opacity-50 rounded px-0">$1</mark>', $text);
        }

        return $text;
    }
}

if (! function_exists('store_search_snippet')) {
    function store_search_snippet(string $haystack, array $tokens, int $radius = 96): string
    {
        $haystack = trim($haystack);
        if ($haystack === '') {
            return '';
        }

        $pos = false;
        foreach ($tokens as $t) {
            $t = trim((string) $t);
            if (mb_strlen($t) < 2) {
                continue;
            }

            $p = mb_stripos($haystack, $t);
            if ($p !== false) {
                $pos = $p;

                break;
            }
        }

        if ($pos === false) {
            $slice = mb_substr($haystack, 0, $radius * 2);

            return $slice . (mb_strlen($haystack) > mb_strlen($slice) ? '…' : '');
        }

        $start   = max(0, $pos - $radius);
        $snippet = mb_substr($haystack, $start, $radius * 2 + 32);
        $prefix  = $start > 0 ? '…' : '';
        $suffix  = ($start + mb_strlen($snippet) < mb_strlen($haystack)) ? '…' : '';

        return $prefix . $snippet . $suffix;
    }
}

$shown = count($products ?? []);
$total = (int) ($totalMatched ?? 0);
?>

<div class="search-results-meta border-bottom pb-3 mb-4">
    <?php if (($q ?? '') !== ''): ?>
        <p class="mb-1 small text-secondary">
            <strong><?= esc((string) $shown) ?></strong> result<?= $shown === 1 ? '' : 's' ?> on this page
            <?php if ($total > $shown): ?>
                (<?= esc((string) $total) ?> total matches)
            <?php elseif ($total > 0): ?>
                · <?= esc((string) $total) ?> total matches
            <?php endif; ?>
            · <span class="text-muted"><?= esc((string) ($elapsedMs ?? 0)) ?> ms</span>
        </p>
        <p class="mb-0 small">
            <span class="badge text-bg-light border"><?= esc((string) ($engineLabel ?? '')) ?></span>
            <?php if (! empty($fulltextAvailable) && ($engineKey ?? '') === 'like'): ?>
                <span class="badge text-bg-secondary">FULLTEXT available — switch mode away from “LIKE only” to use it</span>
            <?php endif; ?>
        </p>
        <?php if (! empty($errorNote)): ?>
            <div class="alert alert-warning small mt-2 mb-0 py-2"><?= esc($errorNote) ?></div>
        <?php endif; ?>
    <?php else: ?>
        <p class="mb-0 small text-secondary">Run a search to see ranked results and timing.</p>
    <?php endif; ?>
</div>

<?php if (empty($products)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-secondary">
            <?php if (($q ?? '') === ''): ?>
                Use the form above to query product names and descriptions.
            <?php else: ?>
                No products matched your criteria. Try LIKE mode, fewer filters, or different keywords.
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($products as $product): ?>
            <?php
            $tokens = $highlightTokens ?? [];
            $nameRaw = (string) ($product['name'] ?? '');
            $descRaw = (string) ($product['description'] ?? '');
            $snippetSource = store_search_snippet($descRaw !== '' ? $descRaw : $nameRaw, $tokens);
            ?>
            <div class="col-md-6 col-xl-4">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h2 class="h5 mb-0"><?= store_search_escape_highlight($nameRaw, $tokens) ?></h2>
                            <?php if (isset($product['relevance_score']) && ($engineKey ?? '') !== 'like'): ?>
                                <span class="badge text-bg-dark text-nowrap" title="FULLTEXT relevance">
                                    <?= esc(number_format((float) $product['relevance_score'], 4)) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-center mb-3">
                            <?php if (! empty($product['remote_image'])): ?>
                                <img
                                    src="<?= esc((string) $product['remote_image']) ?>"
                                    alt="<?= esc($nameRaw) ?>"
                                    class="img-thumbnail"
                                    style="width: 96px; height: 96px; object-fit: contain; background: #fff;"
                                    onerror="this.style.display='none';"
                                >
                            <?php else: ?>
                                <div class="text-muted small">No image</div>
                            <?php endif; ?>
                        </div>
                        <p class="small text-secondary flex-grow-1 mb-3"><?= store_search_escape_highlight($snippetSource, $tokens) ?></p>
                        <div class="small text-muted mb-3">
                            Price: <strong><?= esc(number_format((float) ($product['price'] ?? 0), 2)) ?></strong>
                            &nbsp;|&nbsp; Qty: <strong><?= esc((string) ($product['quantity'] ?? '0')) ?></strong>
                            &nbsp;|&nbsp; ID <strong><?= esc((string) ($product['id'] ?? '')) ?></strong>
                        </div>
                        <?php
                        $memberUid = $memberUserId ?? null;
                        $isAdmin   = $memberIsAdministrator ?? false;
                        $canManage = $isAdmin || (
                            $memberUid !== null
                            && array_key_exists('user_id', $product)
                            && ($product['user_id'] !== null && $product['user_id'] !== '' && (int) $product['user_id'] === $memberUid)
                        );
                        ?>
                        <div class="d-flex justify-content-end flex-wrap mt-auto gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('Store/Product/View/' . (int) ($product['id'] ?? 0)) ?>">View</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('Store/Basket/Add/' . (int) ($product['id'] ?? 0)) ?>">Add to basket</a>
                            <?php if ($canManage): ?>
                                <a class="btn btn-sm btn-outline-dark" href="<?= site_url('Store/Product/Edit/' . (int) ($product['id'] ?? 0)) ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger" href="<?= site_url('Store/Product/Delete/' . (int) ($product['id'] ?? 0)) ?>">Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
