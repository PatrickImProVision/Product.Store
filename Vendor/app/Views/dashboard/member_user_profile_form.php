<?php

use App\Models\RolesModel;

$isEdit = ($mode ?? '') === 'edit';
$r      = is_array($row ?? null) ? $row : [];

$selectedRoleId = (int) ($r['role_id'] ?? 0);
if ($selectedRoleId === 0 && ! empty($roles)) {
    foreach ($roles as $role) {
        if (($role['slug'] ?? '') === RolesModel::SLUG_USER) {
            $selectedRoleId = (int) ($role['id'] ?? 0);
            break;
        }
    }
}
if ($selectedRoleId === 0 && ! empty($roles)) {
    $selectedRoleId = (int) ($roles[0]['id'] ?? 0);
}

$activeChecked = ((int) ($r['active'] ?? 1)) === 1;

$remotePersonalImage = trim((string) ($r['remote_image'] ?? ''));
$remotePersonalPreview = $remotePersonalImage !== ''
    && preg_match('#\Ahttps?://#i', $remotePersonalImage) === 1;

?>
<?= view('dashboard/partials/chrome_start') ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3"><?= $isEdit ? 'Edit member profile' : 'Create member profile' ?></h1>

            <?php if (! empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= esc((string) $err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= esc($action) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" value="<?= esc((string) ($r['email'] ?? '')) ?>" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="dashboard_member_password"><?= $isEdit ? 'New password' : 'Password' ?></label>
                    <div class="input-group">
                        <input
                            id="dashboard_member_password"
                            name="password"
                            type="password"
                            class="form-control"
                            autocomplete="new-password"
                            <?= $isEdit ? '' : 'required' ?>
                            minlength="8"
                            maxlength="255"
                            <?php if ($isEdit): ?>aria-describedby="dashboard_member_password_help"<?php endif; ?>
                        >
                        <button
                            type="button"
                            class="btn btn-outline-secondary js-password-toggle"
                            data-target="#dashboard_member_password"
                            aria-controls="dashboard_member_password"
                            aria-pressed="false"
                            aria-label="Show password"
                        >
                            Show
                        </button>
                    </div>
                    <?php if ($isEdit): ?>
                        <div id="dashboard_member_password_help" class="form-text">Leave blank to keep the current password.</div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="dashboard_member_password_confirm">Confirm password</label>
                    <div class="input-group">
                        <input
                            id="dashboard_member_password_confirm"
                            name="password_confirm"
                            type="password"
                            class="form-control"
                            autocomplete="new-password"
                            <?= $isEdit ? '' : 'required' ?>
                            minlength="8"
                            maxlength="255"
                        >
                        <button
                            type="button"
                            class="btn btn-outline-secondary js-password-toggle"
                            data-target="#dashboard_member_password_confirm"
                            aria-controls="dashboard_member_password_confirm"
                            aria-pressed="false"
                            aria-label="Show password"
                        >
                            Show
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Display name</label>
                    <input name="display_name" class="form-control" value="<?= esc((string) ($r['display_name'] ?? '')) ?>" maxlength="120">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="remote_personal_image">Remote personal image</label>
                    <input
                        id="remote_personal_image"
                        name="remote_image"
                        type="text"
                        inputmode="url"
                        class="form-control"
                        value="<?= esc($remotePersonalImage) ?>"
                        maxlength="2048"
                        placeholder="https://…"
                        autocomplete="off"
                    >
                    <div class="form-text">Optional public image URL. Must start with <code>http://</code> or <code>https://</code> (max 2048 characters).</div>
                    <?php if ($remotePersonalPreview): ?>
                        <div class="mt-3 d-flex align-items-start gap-3 flex-wrap">
                            <img
                                src="<?= esc($remotePersonalImage, 'attr') ?>"
                                alt=""
                                class="rounded-circle border shadow-sm flex-shrink-0 dashboard-remote-image-preview"
                                style="width: 120px; height: 120px; object-fit: cover;"
                                loading="lazy"
                                referrerpolicy="no-referrer"
                                width="120"
                                height="120"
                                onerror="this.classList.add('d-none'); var fb=this.nextElementSibling; if(fb) fb.classList.remove('d-none');"
                            >
                            <div class="d-none small text-secondary" style="max-width: 28rem;">
                                <p class="mb-1">Thumbnail blocked by the image host; URL is still saved.</p>
                                <code class="text-break d-inline-block"><?= esc($remotePersonalImage) ?></code>
                            </div>
                            <span class="small text-secondary align-self-center dashboard-remote-image-caption">Current URL preview (saved after you click Save).</span>
                        </div>
                    <?php elseif ($remotePersonalImage !== ''): ?>
                        <p class="small text-warning mt-2 mb-0">Stored URL does not start with http:// or https:// — fix it to enable preview.</p>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select" required>
                        <?php foreach ($roles ?? [] as $role): ?>
                            <?php $rid = (int) ($role['id'] ?? 0); ?>
                            <option value="<?= esc((string) $rid) ?>" <?= $rid === $selectedRoleId ? 'selected' : '' ?>>
                                <?= esc((string) ($role['name'] ?? '')) ?> (<code class="small"><?= esc((string) ($role['slug'] ?? '')) ?></code>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4 form-check">
                    <input type="hidden" name="active" value="0">
                    <input class="form-check-input" type="checkbox" name="active" id="active_member" value="1" <?= $activeChecked ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active_member">Account active (can sign in)</label>
                </div>

                <button class="btn btn-primary" type="submit">Save</button>
                <a href="<?= esc($listUrl) ?>" class="btn btn-outline-secondary">Back</a>
            </form>
        </div>
    </div>
    <script>
    (function () {
        document.querySelectorAll('.js-password-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var sel = btn.getAttribute('data-target');
                var input = sel ? document.querySelector(sel) : null;
                if (! input) {
                    return;
                }
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.textContent = show ? 'Hide' : 'Show';
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    })();
    </script>
<?= view('dashboard/partials/chrome_end') ?>
