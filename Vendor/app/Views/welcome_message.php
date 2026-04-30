<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($metaTitle ?? 'Product Store') ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Product Store powered by CodeIgniter') ?>">
    <meta name="keywords" content="<?= esc($metaKeywords ?? '') ?>">
    <link rel="shortcut icon" type="image/png" href="/favicon.ico">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-semibold text-dark" href="<?= site_url('Index') ?>"><?= esc($webTitle ?? 'Product Store') ?></a>
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link text-secondary" href="<?= site_url('Store/Index') ?>">Products</a></li>
                <li class="nav-item"><a class="nav-link text-secondary" href="<?= site_url('Store/Product/Create') ?>">Add Product</a></li>
                <li class="nav-item"><a class="nav-link text-secondary" href="<?= site_url('DashBoard/SEO_Settings') ?>">SEO</a></li>
                <li class="nav-item"><a class="nav-link text-secondary" href="<?= site_url('DashBoard/Web_Settings') ?>">Web</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-5">
    <div class="p-5 mb-4 bg-white rounded-3 shadow-sm border">
        <div class="container-fluid py-4">
            <div
                class="rounded-3 mb-4"
                style="
                    height: 220px;
                    background-image:
                        linear-gradient(120deg, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.15)),
                        url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1600&q=80');
                    background-size: cover;
                    background-position: center;
                    background-repeat: no-repeat;
                    position: relative;
                    overflow: hidden;
                "
            >
                <div
                    style="
                        position: absolute;
                        inset: 0;
                        display: flex;
                        align-items: flex-end;
                        padding: 1.5rem;
                    "
                >
                    <div class="text-white">
                        <h1 class="h2 fw-bold mb-2"><?= esc($webTitle ?? 'Product Store') ?></h1>
                        <p class="mb-0 opacity-75"><?= esc($webDescription ?? '') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 card-title">Web Promote</h2>
                    <p class="card-text mb-2">
                        Promote your products with powerful search, basket flow, and a clean storefront experience.
                    </p>
                    <p class="card-text mb-0 text-secondary">
                        Use SEO and web settings to improve discoverability and branding.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="border-top py-4 bg-white">
    <div class="container text-center text-secondary small">
        <p class="mb-1">Page rendered in <strong>{elapsed_time}</strong> seconds using <strong>{memory_usage} MB</strong> of memory.</p>
        &copy; <?= date('Y') ?> <?= esc($webTitle ?? 'Product Store') ?>
    </div>
</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>
</body>
</html>
