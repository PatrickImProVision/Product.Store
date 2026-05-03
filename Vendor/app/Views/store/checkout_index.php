<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(\App\Libraries\SiteChrome::browserTitle([
        'webTitle'   => $webTitle ?? null,
        'metaTitle'  => $metaTitle ?? null,
        'pageTitle'  => $pageTitle ?? 'Checkout',
    ])) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">CheckOut</h1>
        <a class="btn btn-primary" href="<?= site_url('Store/CheckOut/Create') ?>">Create CheckOut</a>
    </div>
    <?php if (! empty($message)): ?><div class="alert alert-info"><?= esc($message) ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>ID</th><th>Profile</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center py-4">No checkouts found.</td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc((string) $row['id']) ?></td>
                        <td><?= esc((string) $row['profile_name']) ?></td>
                        <td><?= esc(number_format((float) $row['total_amount'], 2)) ?></td>
                        <td><?= esc((string) $row['status']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('Store/CheckOut/Edit/' . $row['id']) ?>">Edit</a>
                            <a class="btn btn-sm btn-outline-danger" href="<?= site_url('Store/CheckOut/Delete/' . $row['id']) ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
