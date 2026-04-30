<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $mode === 'edit' ? 'Edit Product' : 'Create Product' ?></title>
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
                    <h1 class="h3 mb-4"><?= $mode === 'edit' ? 'Edit Product' : 'Create Product' ?></h1>

                    <?php if (! empty($message)): ?>
                        <div id="formMessage" class="alert alert-warning" role="alert">
                            <?= esc($message) ?>
                        </div>
                    <?php else: ?>
                        <div id="formMessage" class="alert d-none" role="alert"></div>
                    <?php endif; ?>

                    <form id="productForm" method="post" action="<?= esc($action) ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="name"
                                name="name"
                                maxlength="255"
                                value="<?= esc($product['name'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    id="price"
                                    name="price"
                                    value="<?= esc((string) ($product['price'] ?? '0.00')) ?>"
                                    required
                                >
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    class="form-control"
                                    id="quantity"
                                    name="quantity"
                                    value="<?= esc((string) ($product['quantity'] ?? '0')) ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                class="form-control"
                                id="description"
                                name="description"
                                rows="4"
                            ><?= esc($product['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="remote_image" class="form-label">Remote Image URL</label>
                            <input
                                type="url"
                                class="form-control"
                                id="remote_image"
                                name="remote_image"
                                maxlength="2048"
                                placeholder="https://example.com/image.jpg"
                                value="<?= esc($product['remote_image'] ?? '') ?>"
                            >
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Update Product' : 'Create Product' ?></button>
                            <a href="<?= site_url('Store/Index') ?>" class="btn btn-outline-secondary">Back to Products</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const productForm = document.getElementById('productForm');
    const productMessage = document.getElementById('formMessage');

    productForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const submitBtn = productForm.querySelector('button[type="submit"]');
        const defaultLabel = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        try {
            const response = await fetch(productForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(productForm),
            });

            const contentType = response.headers.get('content-type') || '';
            const result = contentType.includes('application/json')
                ? await response.json()
                : { success: false, message: (await response.text()).slice(0, 300) };

            if (result.csrfName && result.csrfHash) {
                const csrfInput = productForm.querySelector(`input[name="${result.csrfName}"]`);
                if (csrfInput) {
                    csrfInput.value = result.csrfHash;
                }
            }

            productMessage.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
            productMessage.classList.add(result.success ? 'alert-success' : 'alert-danger');
            productMessage.textContent = result.message || 'Request failed. Please try again.';

            if (result.success && result.redirect) {
                window.location.href = result.redirect;
            }
        } catch (error) {
            productMessage.classList.remove('d-none', 'alert-success', 'alert-warning');
            productMessage.classList.add('alert-danger');
            productMessage.textContent = 'Failed to save product. Please try again.';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = defaultLabel;
        }
    });
</script>
</body>
</html>
