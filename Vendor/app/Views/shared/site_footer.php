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
