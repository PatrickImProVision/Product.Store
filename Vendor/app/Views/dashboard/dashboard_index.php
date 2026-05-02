<?= view('dashboard/partials/chrome_start') ?>
    <?php if (($notice ?? null) !== null && ($notice ?? '') !== ''): ?>
        <div class="alert alert-success" role="alert"><?= esc((string) $notice) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3"><?= esc((string) ($pageTitle ?? 'Dashboard')) ?></h1>
            <p class="mb-0 text-secondary"><?= esc((string) ($pageMessage ?? '')) ?></p>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
