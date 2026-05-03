<?= view('dashboard/partials/chrome_start') ?>
    <?php if (($notice ?? null) !== null && ($notice ?? '') !== ''): ?>
        <div class="alert alert-success" role="alert"><?= esc((string) $notice) ?></div>
    <?php endif; ?>

    <h1 class="h4 mb-2"><?= esc((string) ($pageTitle ?? 'Dashboard')) ?></h1>
    <p class="text-secondary mb-4"><?= esc((string) ($pageMessage ?? '')) ?></p>

    <?php $overview = $overview ?? ['usage' => [], 'other' => []]; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-secondary mb-3">Usage status</h2>
                    <ul class="list-group list-group-flush">
                        <?php foreach (($overview['usage'] ?? []) as $row): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3 border-secondary-subtle">
                                <span class="mb-0">
                                    <?php if (! empty($row['href'])): ?>
                                        <a href="<?= esc($row['href']) ?>"><?= esc((string) $row['label']) ?></a>
                                    <?php else: ?>
                                        <?= esc((string) $row['label']) ?>
                                    <?php endif; ?>
                                </span>
                                <span class="text-secondary text-end text-nowrap"><?= esc((string) $row['value']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-secondary mb-3">Other</h2>
                    <ul class="list-group list-group-flush">
                        <?php foreach (($overview['other'] ?? []) as $row): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3 border-secondary-subtle">
                                <span><?= esc((string) $row['label']) ?></span>
                                <span class="text-secondary text-end"><?= esc((string) $row['value']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
