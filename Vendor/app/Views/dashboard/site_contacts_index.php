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
            <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col" class="text-center">Photo</th>
                        <th scope="col">Message</th>
                        <th scope="col" class="text-end text-nowrap" style="width: 1%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center py-4">No contacts found.</td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <?php
                    $imgUrl = trim((string) ($row['remote_image'] ?? ''));
                    $imgOk  = $imgUrl !== '' && preg_match('#\Ahttps?://#i', $imgUrl) === 1;
                    ?>
                    <tr>
                        <td><?= esc((string) $row['id']) ?></td>
                        <td><?= esc((string) $row['name']) ?></td>
                        <td><?= esc((string) $row['email']) ?></td>
                        <td class="text-center align-middle">
                            <?php if ($imgOk): ?>
                                <span class="d-inline-block position-relative rounded-circle overflow-hidden border shadow-sm align-middle" style="width: 40px; height: 40px; line-height: 0;">
                                    <img
                                        src="<?= esc($imgUrl, 'attr') ?>"
                                        alt=""
                                        class="d-block"
                                        style="width: 40px; height: 40px; object-fit: cover;"
                                        loading="lazy"
                                        referrerpolicy="no-referrer"
                                        onerror="this.classList.add('d-none'); var fb=this.nextElementSibling; if(fb) fb.classList.remove('d-none');"
                                    >
                                    <span class="d-none position-absolute top-50 start-50 translate-middle text-muted small user-select-none">—</span>
                                </span>
                            <?php elseif ($imgUrl !== ''): ?>
                                <a href="<?= esc($imgUrl, 'attr') ?>" target="_blank" rel="noopener noreferrer" class="small">URL</a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string) ($row['message'] ?? '')) ?></td>
                        <td class="text-end text-nowrap py-2">
                            <div class="d-inline-flex flex-nowrap gap-1 justify-content-end align-items-center">
                                <a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= site_url('DashBoard/Site_Contact/Edit/' . $row['id']) ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger text-nowrap" href="<?= site_url('DashBoard/Site_Contact/Delete/' . $row['id']) ?>">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
