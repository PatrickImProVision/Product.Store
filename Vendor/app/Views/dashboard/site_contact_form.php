<?= view('dashboard/partials/chrome_start') ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3"><?= ($mode ?? '') === 'edit' ? 'Edit site contact' : 'Create site contact' ?></h1>
            <form method="post" action="<?= esc($action) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" value="<?= esc((string) ($row['name'] ?? '')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" value="<?= esc((string) ($row['email'] ?? '')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4"><?= esc((string) ($row['message'] ?? '')) ?></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Save</button>
                <a href="<?= site_url('DashBoard/Site_Contacts') ?>" class="btn btn-outline-secondary">Back</a>
            </form>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
