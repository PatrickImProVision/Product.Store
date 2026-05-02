<?= view('dashboard/partials/chrome_start') ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3 mb-3">
                <h1 class="h4 mb-0"><?= esc(($mode ?? '') === 'create' ? 'Create promotion' : 'Edit promotion') ?></h1>
                <a href="<?= site_url('DashBoard/Web_Promoting') ?>" class="btn btn-outline-secondary">Back</a>
            </div>

            <?php if (! empty($message)): ?>
                <div class="alert alert-success" role="alert"><?= esc($message) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= esc($action ?? '') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        maxlength="255"
                        value="<?= esc((string) ($row['title'] ?? '')) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="5"><?= esc((string) ($row['description'] ?? '')) ?></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Sort order</label>
                        <input
                            type="number"
                            name="sort_order"
                            class="form-control"
                            min="0"
                            step="1"
                            value="<?= esc((string) ($row['sort_order'] ?? '0')) ?>"
                        >
                        <div class="form-text">Lower numbers appear first.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Visibility</label>
                        <select name="is_active" class="form-select">
                            <?php $active = (int) ($row['is_active'] ?? 1) === 1; ?>
                            <option value="1" <?= $active ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= ! $active ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <div class="form-text">Inactive promotions never appear on the home page.</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a href="<?= site_url('DashBoard/Web_Promoting') ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
