<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(\App\Libraries\SiteChrome::browserTitle([
        'webTitle'   => $webTitle ?? null,
        'metaTitle'  => $metaTitle ?? null,
        'pageTitle'  => $pageTitle ?? 'Edit basket item',
    ])) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">Edit Basket Item</h1>
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Product</label>
                    <input class="form-control" value="<?= esc((string) ($item['name'] ?? '')) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" min="1" name="quantity" class="form-control" value="<?= esc((string) ($item['quantity'] ?? 1)) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?= site_url('Store/Basket/Index') ?>" class="btn btn-outline-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
