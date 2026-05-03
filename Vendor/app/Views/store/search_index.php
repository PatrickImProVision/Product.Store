<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? '',
    'pageTitle'       => $pageTitle ?? 'Search',
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? 'Product Store powered by CodeIgniter',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];

$searchIndexUrl = site_url('Store/Search/Index');
$whisperUrl     = site_url('Store/Search/Whisper');
?>
<?= view('shared/site_head', $chrome) ?>
<?= view('shared/site_nav', $chrome) ?>

<main class="container py-5">
    <?= view('shared/site_hero', [
        'webTitle'       => $webTitle ?? 'Product Store',
        'webDescription' => $webDescription ?? '',
    ]) ?>

    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Search</h1>
            <p class="text-secondary small mb-0">Find products by name or description. Suggestions open below the box after <strong>3</strong> letters or digits.</p>
        </div>
        <a href="<?= site_url('Store/Index') ?>" class="btn btn-outline-secondary shrink-0">Back to store</a>
    </div>

    <?php if (! empty($message)): ?>
        <div class="alert alert-info" role="alert"><?= esc($message) ?></div>
    <?php endif; ?>

    <form id="searchPageForm" method="get" action="<?= esc($searchIndexUrl) ?>" class="mb-4">
        <label for="q" class="visually-hidden">Search</label>
        <div class="position-relative overflow-visible">
            <div class="input-group input-group-lg shadow-sm overflow-visible">
                <input
                    type="text"
                    id="q"
                    name="q"
                    class="form-control"
                    placeholder="Search products…"
                    value="<?= esc($q ?? '') ?>"
                    spellcheck="false"
                    autocomplete="off"
                    aria-autocomplete="list"
                    aria-expanded="false"
                    aria-controls="whisperPanel"
                >
                <button type="submit" class="btn btn-dark px-4">Search</button>
                <button type="button" id="searchClearBtn" class="btn btn-outline-secondary">Clear</button>
            </div>
            <div
                id="whisperPanel"
                class="list-group position-absolute w-100 mt-1 shadow border rounded overflow-hidden bg-white d-none"
                style="z-index: 1050; max-height: 280px; overflow-y: auto;"
                role="listbox"
            ></div>
        </div>
    </form>

    <div id="searchLiveFeed">
        <?= view('store/partials/search_results', [
            'q' => $q ?? '',
            'products' => $products ?? [],
            'totalMatched' => $totalMatched ?? 0,
            'engineKey' => $engineKey ?? 'idle',
            'engineLabel' => $engineLabel ?? '',
            'fulltextAvailable' => $fulltextAvailable ?? false,
            'elapsedMs' => $elapsedMs ?? 0,
            'errorNote' => $errorNote ?? null,
            'highlightTokens' => $highlightTokens ?? [],
        ]) ?>
    </div>
</main>

<script>
(function () {
    const searchIndexUrl = <?= json_encode($searchIndexUrl, JSON_UNESCAPED_SLASHES) ?>;
    const whisperEndpoint = <?= json_encode($whisperUrl, JSON_UNESCAPED_SLASHES) ?>;

    const searchForm = document.getElementById('searchPageForm');
    const searchInput = document.getElementById('q');
    const whisperPanel = document.getElementById('whisperPanel');
    const liveFeed = document.getElementById('searchLiveFeed');
    const clearBtn = document.getElementById('searchClearBtn');
    let liveTimer = null;

    const WHISPER_MIN_SIG = 3;

    /** Count letters/digits; Unicode-aware when the engine supports \\p property escapes. */
    const whisperSigLen = (function () {
        let strip = function (t) {
            return t.replace(/[^a-zA-Z0-9]/g, '');
        };
        try {
            const re = new RegExp('[^\\p{L}\\p{N}]+', 'gu');
            strip = function (t) {
                return t.replace(re, '');
            };
        } catch (err) { /* older browsers */ }

        return function (s) {
            return strip(String(s || '').trim()).length;
        };
    })();

    function resolveCiUrl(u) {
        try {
            return new URL(u);
        } catch (e) {
            return new URL(u, window.location.href);
        }
    }

    function buildUrl(base, params) {
        const url = resolveCiUrl(base);
        Object.keys(params).forEach((k) => {
            const v = params[k];
            if (v !== null && v !== undefined && String(v).trim() !== '') {
                url.searchParams.set(k, String(v).trim());
            } else {
                url.searchParams.delete(k);
            }
        });
        return url.toString();
    }

    function hideWhisper() {
        whisperPanel.innerHTML = '';
        whisperPanel.classList.add('d-none');
        searchInput.setAttribute('aria-expanded', 'false');
    }

    function showWhisperHint(text, variant) {
        whisperPanel.innerHTML = '';
        const row = document.createElement('div');
        row.className = 'list-group-item small py-2 px-3 ' + (variant === 'danger' ? 'text-danger' : 'text-muted');
        row.setAttribute('role', 'presentation');
        row.textContent = text;
        whisperPanel.appendChild(row);
        whisperPanel.classList.remove('d-none');
        searchInput.setAttribute('aria-expanded', 'true');
    }

    function showWhisperSuggestions(items) {
        whisperPanel.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            showWhisperHint('No suggestions yet — results below still use full search (all words in name or description).', 'muted');
            return;
        }

        items.slice(0, 10).forEach((text) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-2 px-3 text-start small';
            btn.setAttribute('role', 'option');
            btn.textContent = String(text);
            btn.addEventListener('mousedown', (e) => e.preventDefault());
            btn.addEventListener('click', () => {
                searchInput.value = String(text);
                hideWhisper();
                clearTimeout(liveTimer);
                loadWhisper(searchInput.value);
                runLiveSearch(searchInput.value);
            });
            whisperPanel.appendChild(btn);
        });

        whisperPanel.classList.remove('d-none');
        searchInput.setAttribute('aria-expanded', 'true');
    }

    async function loadWhisper(query) {
        whisperPanel.innerHTML = '';
        whisperPanel.classList.add('d-none');

        const trimmed = query.trim();
        if (whisperSigLen(trimmed) < WHISPER_MIN_SIG) {
            searchInput.setAttribute('aria-expanded', 'false');
            return;
        }

        try {
            const url = buildUrl(whisperEndpoint, { q: trimmed });
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const raw = (await response.text()).replace(/^\uFEFF/, '');

            if (!response.ok) {
                showWhisperHint('Suggestions request failed (' + response.status + '). Check the Network tab for Store/Search/Whisper.', 'danger');
                return;
            }

            let result = null;
            try {
                result = raw ? JSON.parse(raw) : null;
            } catch (e) {
                showWhisperHint('Suggestions returned non-JSON (often a PHP error page). Check Store/Search/Whisper response.', 'danger');
                return;
            }

            if (!result || result.success !== true) {
                showWhisperHint('Suggestions are unavailable right now.', 'danger');
                return;
            }

            if (Array.isArray(result.suggestions)) {
                showWhisperSuggestions(result.suggestions);
            }
        } catch (e) {
            showWhisperHint('Could not reach the whisper URL.', 'danger');
        }
    }

    async function runLiveSearch(query) {
        try {
            const url = buildUrl(searchIndexUrl, { q: query.trim() });
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const html = await response.text();
            liveFeed.innerHTML = html;
            window.history.replaceState({}, '', url);
        } catch (e) {
            /* ignore */
        }
    }

    function scheduleLiveAndWhisper() {
        clearTimeout(liveTimer);
        liveTimer = setTimeout(() => {
            const q = searchInput.value;
            loadWhisper(q);
            runLiveSearch(q);
        }, 300);
    }

    searchInput.addEventListener('input', scheduleLiveAndWhisper);

    searchInput.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') {
            hideWhisper();
        }
    });

    searchForm.addEventListener('submit', (ev) => {
        ev.preventDefault();
        hideWhisper();
        clearTimeout(liveTimer);
        loadWhisper(searchInput.value);
        runLiveSearch(searchInput.value);
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        hideWhisper();
        clearTimeout(liveTimer);
        loadWhisper('');
        runLiveSearch('');
    });

    document.addEventListener('click', (ev) => {
        if (!searchForm.contains(ev.target)) {
            hideWhisper();
        }
    });

    if (whisperSigLen(searchInput.value.trim()) >= WHISPER_MIN_SIG) {
        loadWhisper(searchInput.value);
    }
})();
</script>

<?= view('shared/site_footer', $chrome) ?>
