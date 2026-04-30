<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Web Settings - Product Store</title>
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
                    <h1 class="h3 mb-4">Web Settings</h1>

                    <?php if (! empty($message)): ?>
                        <div id="formMessage" class="alert alert-success" role="alert">
                            <?= esc($message) ?>
                        </div>
                    <?php else: ?>
                        <div id="formMessage" class="alert d-none" role="alert"></div>
                    <?php endif; ?>

                    <form id="webSettingsForm" method="post" action="<?= site_url('DashBoard/Web_Settings') ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input
                                type="text"
                                class="form-control"
                                id="title"
                                name="title"
                                maxlength="255"
                                value="<?= esc($web['title'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                class="form-control"
                                id="description"
                                name="description"
                                rows="4"
                            ><?= esc($web['description'] ?? '') ?></textarea>
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
    const webForm = document.getElementById('webSettingsForm');
    const webMessage = document.getElementById('formMessage');

    webForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const submitBtn = webForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        try {
            const response = await fetch(webForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(webForm),
            });

            const contentType = response.headers.get('content-type') || '';
            const result = contentType.includes('application/json')
                ? await response.json()
                : { success: false, message: (await response.text()).slice(0, 300) };

            if (result.csrfName && result.csrfHash) {
                const csrfInput = webForm.querySelector(`input[name="${result.csrfName}"]`);
                if (csrfInput) {
                    csrfInput.value = result.csrfHash;
                }
            }

            const ok = result.success === true;
            const serverMessage = result.message || result.error || result.messages || '';

            webMessage.classList.remove('d-none', 'alert-success', 'alert-danger');
            webMessage.classList.add(ok ? 'alert-success' : 'alert-danger');
            webMessage.textContent = serverMessage || 'Request failed. Please try again.';
        } catch (error) {
            webMessage.classList.remove('d-none', 'alert-success');
            webMessage.classList.add('alert-danger');
            webMessage.textContent = 'Failed to save settings. Please try again.';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Settings';
        }
    });
</script>
</body>
</html>
