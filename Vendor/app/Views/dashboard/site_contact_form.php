<?php

$r      = is_array($row ?? null) ? $row : [];
$errors = $errors ?? [];
$list   = $listUrl ?? site_url('DashBoard/Site_Contacts');

$remoteUrl = trim((string) ($r['remote_image'] ?? ''));
$remotePreviewOk = $remoteUrl !== '' && preg_match('#\Ahttps?://#i', $remoteUrl) === 1;

?>
<?= view('dashboard/partials/chrome_start') ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3"><?= ($mode ?? '') === 'edit' ? 'Edit site contact' : 'Create site contact' ?></h1>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $msg): ?>
                            <li><?= esc((string) $msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= esc($action) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="site_contact_name">Name</label>
                    <input id="site_contact_name" name="name" class="form-control" value="<?= esc((string) ($r['name'] ?? '')) ?>" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="site_contact_email">Email</label>
                    <input id="site_contact_email" name="email" type="email" class="form-control" value="<?= esc((string) ($r['email'] ?? '')) ?>" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="site_contact_message">Message</label>
                    <textarea id="site_contact_message" name="message" class="form-control" rows="4" maxlength="65535"><?= esc((string) ($r['message'] ?? '')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="site_contact_remote_image">Remote image</label>
                    <input
                        id="site_contact_remote_image"
                        name="remote_image"
                        type="text"
                        inputmode="url"
                        class="form-control"
                        value="<?= esc($remoteUrl) ?>"
                        maxlength="2048"
                        placeholder="https://…"
                        autocomplete="off"
                    >
                    <div class="form-text">Optional image URL for this contact on the public site. Must start with <code>http://</code> or <code>https://</code>.</div>
                    <?php if ($remotePreviewOk): ?>
                        <div class="mt-3 d-flex align-items-start gap-3 flex-wrap">
                            <img
                                src="<?= esc($remoteUrl, 'attr') ?>"
                                alt=""
                                class="rounded-circle border shadow-sm flex-shrink-0"
                                style="width: 120px; height: 120px; object-fit: cover;"
                                loading="lazy"
                                referrerpolicy="no-referrer"
                                width="120"
                                height="120"
                                onerror="this.classList.add('d-none'); var fb=this.nextElementSibling; if(fb) fb.classList.remove('d-none');"
                            >
                            <div class="d-none small text-secondary" style="max-width: 28rem;">
                                <p class="mb-1">Thumbnail blocked by the image host; URL is still saved.</p>
                                <code class="text-break d-inline-block"><?= esc($remoteUrl) ?></code>
                            </div>
                        </div>
                    <?php elseif ($remoteUrl !== ''): ?>
                        <p class="small text-warning mt-2 mb-0">URL must start with http:// or https:// to preview.</p>
                    <?php endif; ?>
                </div>
                <button class="btn btn-primary" type="submit">Save</button>
                <a href="<?= esc($list) ?>" class="btn btn-outline-secondary">Back</a>
            </form>
        </div>
    </div>
<?= view('dashboard/partials/chrome_end') ?>
