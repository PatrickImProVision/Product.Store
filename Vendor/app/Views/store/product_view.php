<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Product</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
</head>
<body class="bg-light">
<div class="container py-5">
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

                    <div class="d-flex gap-2 mt-4">
                        <a href="<?= site_url('Store/Basket/Add/' . $product['id']) ?>" class="btn btn-success">Add To Basket</a>
                        <a href="<?= site_url('Store/Basket') ?>" class="btn btn-dark">View Basket</a>
                        <a href="<?= site_url('Store/Product/Edit/' . $product['id']) ?>" class="btn btn-primary">Edit</a>
                        <a href="<?= site_url('Store/Product/Delete/' . $product['id']) ?>" class="btn btn-danger">Delete</a>
                        <a href="<?= site_url('Store/Index') ?>" class="btn btn-outline-secondary">Back to Products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
