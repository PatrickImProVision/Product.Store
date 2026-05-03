<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(\App\Libraries\SiteChrome::browserTitle([
        'webTitle'   => $webTitle ?? null,
        'metaTitle'  => $metaTitle ?? null,
        'pageTitle'  => $pageTitle ?? ($mode === 'edit' ? 'Edit checkout' : 'Create checkout'),
    ])) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3"><?= $mode === 'edit' ? 'Edit CheckOut' : 'Create CheckOut' ?></h1>
            <form method="post" action="<?= esc($action) ?>">
                <?= csrf_field() ?>
                <?php if ($mode === 'edit'): ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <input type="text" name="status" class="form-control" value="<?= esc((string) ($row['status'] ?? 'pending')) ?>">
                    </div>
                <?php else: ?>
                    <p class="text-muted">Create a checkout from the current guest basket.</p>
                <?php endif; ?>
                <button class="btn btn-primary" type="submit">Save</button>
                <a href="<?= site_url('Store/CheckOut/Index') ?>" class="btn btn-outline-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
