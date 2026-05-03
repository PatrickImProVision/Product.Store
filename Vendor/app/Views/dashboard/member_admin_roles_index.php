<?php
if (! function_exists('dashboard_role_snippet')) {
    /**
     * @return array{0: string, 1: string} display HTML-safe snippet and full text for title
     */
    function dashboard_role_snippet(string $text, int $max = 72): array
    {
        $t = trim($text);
        if ($t === '') {
            return ['', ''];
        }

        $full = $t;
        if (mb_strlen($t) > $max) {
            $t = mb_substr($t, 0, $max) . '…';
        }

        return [$t, $full];
    }
}
?>
<?= view('dashboard/partials/chrome_start') ?>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">Roles</h1>
        <a class="btn btn-primary" href="<?= site_url('DashBoard/Member/Admin/Role/Create') ?>">Create role</a>
    </div>
    <?php if (! empty($message)): ?>
        <div class="alert alert-success" role="alert"><?= esc((string) $message) ?></div>
    <?php endif; ?>
    <p class="text-secondary small mb-3 mb-md-4">
        <strong>Access level</strong> is a numeric tier for application checks (higher = broader access).
        <strong>Restriction</strong> lists capabilities by <strong>role type</strong> (guest, user, author/moderator, administrator/owner) plus optional notes. When saved with capability boxes, allowlists apply to the navbar and matching routes for signed-in members (except built-in Owner/Administrator, which bypass lists). Legacy restriction text without capability JSON does not enforce routes.
        Default catalog roles (<code>guest</code>, <code>user</code>, <code>author</code>, <code>moderator</code>, <code>administrator</code>, <code>owner</code>) cannot be deleted; remove members from any custom role before deleting it.
    </p>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Slug</th>
                            <th scope="col">Display name</th>
                            <th scope="col" class="text-end text-nowrap">Level</th>
                            <th scope="col">Description</th>
                            <th scope="col">Restriction</th>
                            <th scope="col">Behaviour</th>
                            <th scope="col" class="text-end text-nowrap">Edit</th>
                            <th scope="col" class="text-end text-nowrap">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="9" class="text-center py-4 text-secondary">No roles found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                $slug          = (string) ($r['slug'] ?? '');
                                $protectedList = $protectedSlugs ?? \App\Models\RolesModel::getProtectedRoleSlugs();
                                $isProtected   = in_array($slug, $protectedList, true);
                                $level = (int) ($r['access_level'] ?? 0);

                                $desc = trim((string) ($r['description'] ?? ''));
                                [$descShow, $descFull] = dashboard_role_snippet($desc, 80);

                                $restRaw = (string) ($r['restriction'] ?? '');
                                $restFmt = \App\Libraries\RoleRestrictionCapabilities::formatForDisplay($restRaw, 0);
                                $rest    = trim($restFmt);
                                [$restShow, $restFull] = dashboard_role_snippet($rest, 72);

                                $beh = trim((string) ($r['behaviour'] ?? ''));
                                [$behShow, $behFull] = dashboard_role_snippet($beh, 72);
                                ?>
                                <tr>
                                    <td><?= esc((string) ($r['id'] ?? '')) ?></td>
                                    <td><code class="small"><?= esc($slug) ?></code></td>
                                    <td><?= esc((string) ($r['name'] ?? '')) ?></td>
                                    <td class="text-end fw-semibold"><?= esc((string) $level) ?></td>
                                    <td class="small text-secondary">
                                        <?php if ($desc === ''): ?>
                                            <span class="text-muted">—</span>
                                        <?php else: ?>
                                            <span title="<?= esc($descFull) ?>"><?= esc($descShow) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-secondary">
                                        <?php if ($rest === ''): ?>
                                            <span class="text-muted">—</span>
                                        <?php else: ?>
                                            <span title="<?= esc($restFull) ?>"><?= esc($restShow) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-secondary">
                                        <?php if ($beh === ''): ?>
                                            <span class="text-muted">—</span>
                                        <?php else: ?>
                                            <span title="<?= esc($behFull) ?>"><?= esc($behShow) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('DashBoard/Member/Admin/Role/Edit/' . (int) ($r['id'] ?? 0)) ?>">Edit</a>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <?php if ($isProtected): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                disabled
                                                title="Default catalog roles cannot be deleted."
                                            >
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <a
                                                class="btn btn-sm btn-outline-danger"
                                                href="<?= site_url('DashBoard/Member/Admin/Role/Delete/' . (int) ($r['id'] ?? 0)) ?>"
                                                onclick="return confirm('Delete this role? It must have no members assigned.');"
                                            >
                                                Delete
                                            </a>
                                        <?php endif; ?>
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
