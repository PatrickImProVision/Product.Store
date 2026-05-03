<?= view('dashboard/partials/chrome_start') ?>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Member profiles</h1>
        <a class="btn btn-primary" href="<?= site_url('DashBoard/Member/User/Profile/Create') ?>">Create profile</a>
    </div>
    <?php if (! empty($message)): ?>
        <div class="alert alert-success" role="alert"><?= esc((string) $message) ?></div>
    <?php endif; ?>
    <p class="text-secondary small mb-3 mb-md-4">
        Manage storefront member accounts (email sign-in). Deleting your own signed-in account is blocked.
    </p>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Email</th>
                            <th scope="col">Display name</th>
                            <th scope="col" class="text-center text-nowrap">Photo</th>
                            <th scope="col">Role</th>
                            <th scope="col">Active</th>
                            <th scope="col">Created</th>
                            <th scope="col" class="text-end text-nowrap" style="width: 1%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-secondary">No members found.</td></tr>
                        <?php else: ?>
                            <?php
                            $sessionMember = session()->get('member_user');
                            $sessionId     = is_array($sessionMember) ? (int) ($sessionMember['id'] ?? 0) : 0;
                            ?>
                            <?php foreach ($rows as $u): ?>
                                <?php
                                $uid    = (int) ($u['id'] ?? 0);
                                $isSelf = $sessionId !== 0 && $uid === $sessionId;
                                ?>
                                <?php
                                $imgUrl = trim((string) ($u['remote_image'] ?? ''));
                                $imgOk  = $imgUrl !== '' && preg_match('#\Ahttps?://#i', $imgUrl) === 1;
                                ?>
                                <tr>
                                    <td><?= esc((string) $uid) ?></td>
                                    <td><?= esc((string) ($u['email'] ?? '')) ?></td>
                                    <td><?= esc((string) ($u['display_name'] ?? '')) ?></td>
                                    <td class="text-center">
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
                                                <span class="d-none position-absolute top-50 start-50 translate-middle text-muted small user-select-none" title="Preview blocked">—</span>
                                            </span>
                                        <?php elseif ($imgUrl !== ''): ?>
                                            <a href="<?= esc($imgUrl, 'attr') ?>" target="_blank" rel="noopener noreferrer" class="small text-break d-inline-block" style="max-width: 6rem;" title="Open URL">URL</a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code class="small"><?= esc((string) ($u['role_slug'] ?? '')) ?></code>
                                        <span class="text-secondary small"><?= esc((string) ($u['role_display'] ?? '')) ?></span>
                                    </td>
                                    <td>
                                        <?php if ((int) ($u['active'] ?? 1) === 1): ?>
                                            <span class="badge text-bg-success">Yes</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-secondary"><?= esc((string) ($u['created_at'] ?? '')) ?></td>
                                    <td class="text-end text-nowrap py-2">
                                        <div class="d-inline-flex flex-nowrap gap-1 justify-content-end align-items-center" role="group" aria-label="Row actions">
                                            <a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= site_url('DashBoard/Member/User/Profile/Edit/' . $uid) ?>">Edit</a>
                                            <?php if ($isSelf): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" disabled title="Cannot delete your current session.">
                                                    Delete
                                                </button>
                                            <?php else: ?>
                                                <a
                                                    class="btn btn-sm btn-outline-danger text-nowrap"
                                                    href="<?= site_url('DashBoard/Member/User/Profile/Delete/' . $uid) ?>"
                                                    onclick="return confirm('Delete this member account permanently?');"
                                                >
                                                    Delete
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
