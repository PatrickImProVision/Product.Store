<?= view('dashboard/partials/chrome_start') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Site contacts</h1>
        <a class="btn btn-primary" href="<?= site_url('DashBoard/Site_Contact/Create') ?>">Create contact</a>
    </div>
    <?php if (! empty($message)): ?>
        <div class="alert alert-info" role="alert"><?= esc($message) ?></div>
    <?php endif; ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center py-4">No contacts found.</td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc((string) $row['id']) ?></td>
                        <td><?= esc((string) $row['name']) ?></td>
                        <td><?= esc((string) $row['email']) ?></td>
                        <td><?= esc((string) $row['message']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('DashBoard/Site_Contact/Edit/' . $row['id']) ?>">Edit</a>
                            <a class="btn btn-sm btn-outline-danger" href="<?= site_url('DashBoard/Site_Contact/Delete/' . $row['id']) ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
