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
                    <h1 class="h2 fw-bold mb-2"><?= esc($heroTitle ?? $webTitle ?? 'Product Store') ?></h1>
                    <p class="mb-0 opacity-75"><?= esc($heroDescription ?? $webDescription ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
