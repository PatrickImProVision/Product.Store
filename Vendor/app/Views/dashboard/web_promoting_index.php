<?= view('dashboard/partials/chrome_start') ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3 mb-3">
                <div>
                    <h1 class="h4 mb-1">Web promoting</h1>
                    <p class="mb-0 text-secondary small">
                        The home page lists every active promotion in sort order, two per row; additional cards wrap to the next row.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= site_url('DashBoard/Web_Promoting/Create') ?>" class="btn btn-primary">Add promotion</a>
                    <a href="<?= site_url('DashBoard/Index') ?>" class="btn btn-outline-secondary">Dashboard</a>
                </div>
            </div>

            <?php if (! empty($message)): ?>
                <div class="alert alert-success" role="alert"><?= esc($message) ?></div>
            <?php endif; ?>

            <?php if (empty($rows)): ?>
                <div class="alert alert-warning mb-0" role="alert">
                    No promotions yet. Create one — until then, the home page shows two sample cards.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Title</th>
                                <th scope="col" class="text-nowrap">Sort</th>
                                <th scope="col" class="text-nowrap">Active</th>
                                <th scope="col" class="text-end text-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= esc((string) ($r['title'] ?? '')) ?></div>
                                        <div class="text-secondary small text-truncate" style="max-width: 520px;">
                                            <?= esc((string) ($r['description'] ?? '')) ?>
                                        </div>
                                    </td>
                                    <td class="text-nowrap"><?= esc((string) ($r['sort_order'] ?? '0')) ?></td>
                                    <td class="text-nowrap">
                                        <?php if ((int) ($r['is_active'] ?? 1) === 1): ?>
                                            <span class="badge text-bg-success">On</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">Off</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('DashBoard/Web_Promoting/Edit/' . (int) $r['id']) ?>">Edit</a>
                                        <a class="btn btn-sm btn-outline-danger" href="<?= site_url('DashBoard/Web_Promoting/Delete/' . (int) $r['id']) ?>">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
