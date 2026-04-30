<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SEO Settings - Product Store</title>
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
                    <h1 class="h3 mb-4">SEO Settings</h1>

                    <?php if (! empty($message)): ?>
                        <div id="formMessage" class="alert alert-success" role="alert">
                            <?= esc($message) ?>
                        </div>
                    <?php else: ?>
                        <div id="formMessage" class="alert d-none" role="alert"></div>
                    <?php endif; ?>

                    <form id="seoSettingsForm" method="post" action="<?= site_url('DashBoard/SEO_Settings') ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input
                                type="text"
                                class="form-control"
                                id="meta_title"
                                name="meta_title"
                                maxlength="255"
                                value="<?= esc($seo['meta_title'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea
                                class="form-control"
                                id="meta_description"
                                name="meta_description"
                                rows="4"
                            ><?= esc($seo['meta_description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <textarea
                                class="form-control"
                                id="meta_keywords"
                                name="meta_keywords"
                                rows="3"
                                placeholder="example: store, ecommerce, products"
                            ><?= esc($seo['meta_keywords'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                            <a href="<?= site_url('Index') ?>" class="btn btn-outline-secondary">Back to Home</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const seoForm = document.getElementById('seoSettingsForm');
    const seoMessage = document.getElementById('formMessage');

    seoForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const submitBtn = seoForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        try {
            const response = await fetch(seoForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(seoForm),
            });

            const contentType = response.headers.get('content-type') || '';
            const result = contentType.includes('application/json')
                ? await response.json()
                : { success: false, message: (await response.text()).slice(0, 300) };

            if (result.csrfName && result.csrfHash) {
                const csrfInput = seoForm.querySelector(`input[name="${result.csrfName}"]`);
                if (csrfInput) {
                    csrfInput.value = result.csrfHash;
                }
            }

            const ok = result.success === true;
            const serverMessage = result.message || result.error || result.messages || '';

            seoMessage.classList.remove('d-none', 'alert-success', 'alert-danger');
            seoMessage.classList.add(ok ? 'alert-success' : 'alert-danger');
            seoMessage.textContent = serverMessage || 'Request failed. Please try again.';
        } catch (error) {
            seoMessage.classList.remove('d-none', 'alert-success');
            seoMessage.classList.add('alert-danger');
            seoMessage.textContent = 'Failed to save settings. Please try again.';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Settings';
        }
    });
</script>
</body>
</html>
