<?php

use App\Libraries\RoleRestrictionCapabilities;

$errors    = $errors ?? [];
$row       = $row ?? null;
$slugLocked = $slugLocked ?? false;
$listUrl   = $listUrl ?? site_url('DashBoard/Member/Admin/Roles');
$slugVal   = esc((string) ($row['slug'] ?? ''));
$nameVal   = esc((string) ($row['name'] ?? ''));
$descVal       = (string) ($row['description'] ?? '');
$behaviourVal   = (string) ($row['behaviour'] ?? '');
$accessLevelVal = (int) ($row['access_level'] ?? \App\Models\RolesModel::ACCESS_LEVEL_USER_DEFAULT);

$parsedCaps = RoleRestrictionCapabilities::parse((string) ($row['restriction'] ?? ''));
$capKeysSelected = $restrictionCapabilityKeys ?? $parsedCaps['keys'];
$restrictionNotesVal = $restrictionNotes ?? $parsedCaps['prose'];
$capabilityByRoleType = RoleRestrictionCapabilities::catalogByRoleType();

?>
<?= view('dashboard/partials/chrome_start') ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3"><?= ($mode ?? '') === 'edit' ? 'Edit role' : 'Create role' ?></h1>

            <?php if (! empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= esc(is_array($err) ? implode(' ', $err) : (string) $err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($slugLocked): ?>
                <p class="text-secondary small">Built-in role: the slug stays fixed for compatibility. You can edit display name, description, access level, restriction, and behaviour notes.</p>
            <?php endif; ?>

            <form method="post" action="<?= esc($action ?? '') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <?php if ($slugLocked): ?>
                        <input type="hidden" name="slug" value="<?= $slugVal ?>">
                        <input type="text" class="form-control" id="slug" value="<?= $slugVal ?>" disabled readonly>
                    <?php else: ?>
                        <input
                            type="text"
                            class="form-control"
                            id="slug"
                            name="slug"
                            maxlength="32"
                            autocomplete="off"
                            value="<?= $slugVal ?>"
                            required
                            pattern="[a-z][a-z0-9_-]{0,31}"
                            title="Lowercase letter first; letters, digits, hyphen, underscore; max 32 characters."
                        >
                        <div class="form-text">Used in code and permissions (e.g. <code>administrator</code>). Cannot duplicate another role.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Display name</label>
                    <input type="text" class="form-control" id="name" name="name" maxlength="64" value="<?= $nameVal ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description <span class="text-secondary fw-normal small">(optional)</span></label>
                    <textarea class="form-control" id="description" name="description" rows="4" maxlength="8192" placeholder="What this role is for"><?= esc($descVal) ?></textarea>
                    <div class="form-text">Plain text, up to 8192 characters.</div>
                </div>

                <div class="mb-3">
                    <label for="access_level" class="form-label">Access level</label>
                    <input
                        type="number"
                        class="form-control"
                        id="access_level"
                        name="access_level"
                        min="0"
                        max="65535"
                        step="1"
                        value="<?= esc((string) $accessLevelVal) ?>"
                        required
                    >
                    <div class="form-text">
                        Numeric tier for permission checks in code (higher = broader access).                         Typical defaults:
                        guest <strong>0</strong>,
                        user <strong><?= esc((string) \App\Models\RolesModel::ACCESS_LEVEL_USER_DEFAULT) ?></strong>,
                        author <strong><?= esc((string) \App\Models\RolesModel::ACCESS_LEVEL_AUTHOR_DEFAULT) ?></strong>,
                        moderator <strong><?= esc((string) \App\Models\RolesModel::ACCESS_LEVEL_MODERATOR_DEFAULT) ?></strong>,
                        administrator <strong><?= esc((string) \App\Models\RolesModel::ACCESS_LEVEL_ADMINISTRATOR_DEFAULT) ?></strong>,
                        owner <strong><?= esc((string) \App\Models\RolesModel::ACCESS_LEVEL_OWNER_DEFAULT) ?></strong>.
                    </div>
                </div>

                <div class="mb-3 border rounded-3 p-3 bg-light">
                    <fieldset>
                        <legend class="form-label fs-6 fw-semibold mb-2">What members in this role may do</legend>
                        <p class="text-secondary small mb-3">
                            Options are grouped by <strong>role type</strong> (guest, member user, author/moderator, administrator/owner). Use them to document intent; actual enforcement still depends on role slug, access level, and application code.
                        </p>
                        <?php
                        $_capBlocks = $capabilityByRoleType;
                        $_capLast   = count($_capBlocks) - 1;
                        ?>
                        <?php foreach ($_capBlocks as $_capIdx => $block): ?>
                            <div class="mb-4 <?= $_capIdx < $_capLast ? 'pb-3 border-bottom' : '' ?>">
                                <div class="d-flex flex-column flex-sm-row flex-wrap gap-1 gap-sm-3 align-items-start align-items-sm-baseline mb-2">
                                    <h3 class="h6 mb-0 text-dark"><?= esc($block['title']) ?></h3>
                                    <?php if (($block['typical_slugs'] ?? '') !== ''): ?>
                                        <span class="small text-secondary">
                                            Typical slug(s): <code><?= esc($block['typical_slugs']) ?></code>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if (($block['hint'] ?? '') !== ''): ?>
                                    <p class="small text-secondary mb-3"><?= esc($block['hint']) ?></p>
                                <?php endif; ?>
                                <div class="row row-cols-1 ms-0">
                                    <?php foreach ($block['items'] as $capKey => $capLabel): ?>
                                        <?php $cid = 'cap_' . preg_replace('/[^a-z0-9_-]/i', '_', $capKey); ?>
                                        <div class="col mb-2">
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    name="restriction_capability_keys[]"
                                                    value="<?= esc($capKey, 'attr') ?>"
                                                    id="<?= esc($cid, 'attr') ?>"
                                                    <?= in_array($capKey, $capKeysSelected, true) ? 'checked' : '' ?>
                                                >
                                                <label class="form-check-label small" for="<?= esc($cid, 'attr') ?>">
                                                    <?= esc($capLabel) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>

                    <div class="mb-0">
                        <label for="restriction_notes" class="form-label">Additional restriction notes <span class="text-secondary fw-normal small">(optional)</span></label>
                        <textarea
                            class="form-control"
                            id="restriction_notes"
                            name="restriction_notes"
                            rows="3"
                            maxlength="8192"
                            placeholder="Limits, ceilings, or exceptions not covered above"
                        ><?= esc($restrictionNotesVal) ?></textarea>
                        <div class="form-text">Merged with the selections above when you save. Legacy roles without boxes show prior text here until you add capability selections.</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="behaviour" class="form-label">Behaviour <span class="text-secondary fw-normal small">(optional)</span></label>
                    <textarea class="form-control" id="behaviour" name="behaviour" rows="3" maxlength="8192" placeholder="Expected behaviour and responsibilities"><?= esc($behaviourVal) ?></textarea>
                    <div class="form-text">How this role should act or what it may access (plain text).</div>
                </div>

                <button class="btn btn-primary" type="submit">Save</button>
                <a href="<?= esc($listUrl) ?>" class="btn btn-outline-secondary">Back to roles</a>
            </form>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
