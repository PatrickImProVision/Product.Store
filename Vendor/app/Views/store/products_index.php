<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Products - Store</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Products</h1>
        <a href="<?= site_url('Store/Product/Create') ?>" class="btn btn-primary">Create Product</a>
    </div>

    <form id="searchForm" method="get" action="<?= site_url('Store/Index') ?>" class="mb-4">
        <div class="input-group">
            <input
                id="searchInput"
                type="text"
                name="q"
                list="searchWhisperList"
                class="form-control"
                placeholder="Search products (name, description, price)"
                value="<?= esc($search ?? '') ?>"
            >
            <button type="submit" class="btn btn-dark">Search</button>
            <button id="clearBtn" type="button" class="btn btn-outline-secondary">Clear</button>
        </div>
        <datalist id="searchWhisperList"></datalist>
    </form>

    <?php if (! empty($message)): ?>
        <div class="alert alert-info" role="alert">
            <?= esc($message) ?>
        </div>
    <?php endif; ?>

    <div id="productsFeed">
        <?= view('store/partials/products_feed', ['products' => $products, 'search' => $search ?? '']) ?>
    </div>

    <div class="mt-3">
        <a href="<?= site_url('Index') ?>" class="btn btn-outline-secondary">Back to Home</a>
    </div>
</div>
<script>
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearBtn');
    const productsFeed = document.getElementById('productsFeed');
    const whisperList = document.getElementById('searchWhisperList');
    let searchTimer = null;

    async function runLiveSearch(query) {
        const url = new URL(searchForm.action, window.location.origin);
        if (query.trim() !== '') {
            url.searchParams.set('q', query.trim());
        }

        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const html = await response.text();
        productsFeed.innerHTML = html;
        window.history.replaceState({}, '', url.toString());
    }

    async function loadWhisper(query) {
        const whisperUrl = new URL('<?= site_url('Store/Search/Whisper') ?>', window.location.origin);
        if (query.trim() !== '') {
            whisperUrl.searchParams.set('q', query.trim());
        }

        const response = await fetch(whisperUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        const result = await response.json();
        whisperList.innerHTML = '';

        if (result && result.success && Array.isArray(result.suggestions)) {
            result.suggestions.forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                whisperList.appendChild(option);
            });
        }
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadWhisper(searchInput.value).catch(() => {});
            runLiveSearch(searchInput.value).catch(() => {});
        }, 300);
    });

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        runLiveSearch(searchInput.value).catch(() => {});
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        whisperList.innerHTML = '';
        runLiveSearch('').catch(() => {});
    });
</script>
</body>
</html>
