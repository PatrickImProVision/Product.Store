<?php

namespace App\Controllers;

use App\Libraries\MemberCapabilityGate;
use App\Libraries\RoleRestrictionCapabilities;
use App\Libraries\RolesSchema;
use App\Models\RolesModel;
use App\Models\UserModel;
use CodeIgniter\CodeIgniter;

class DashBoard extends BaseController
{
    /**
     * Site chrome (nav + footer), no hero image. Pass `pageTitle` in `$data` for the browser tab.
     *
     * @param array<string, mixed> $data
     */
    private function renderDashboard(string $view, array $data): string
    {
        return view($view, array_merge($this->getSiteLayoutData(), $data));
    }

    private function safeTableCount(string $table): ?int
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists($table)) {
                return null;
            }

            return (int) $db->table($table)->countAllResults();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function safeWebPromotingActiveCount(): ?int
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('web_promoting')) {
                return null;
            }
            if (! $db->fieldExists('is_active', 'web_promoting')) {
                return null;
            }

            return (int) $db->table('web_promoting')
                ->groupStart()
                ->where('is_active', 1)
                ->orWhere('is_active', null)
                ->groupEnd()
                ->countAllResults();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function safeCheckoutPendingCount(): ?int
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('checkouts')) {
                return null;
            }

            return (int) $db->table('checkouts')->where('status', 'pending')->countAllResults();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function safeHasRows(string $table): ?bool
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists($table)) {
                return null;
            }

            return $db->table($table)->countAllResults() > 0;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function databaseConnected(): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array{usage: list<array{label: string, value: string, href: string|null}>, other: list<array{label: string, value: string}>}
     */
    private function dashboardOverviewStats(): array
    {
        $countProducts = $this->safeTableCount('products');
        $countUsers    = $this->safeTableCount('users');
        $countRoles    = $this->safeTableCount('roles');
        $countContacts = $this->safeTableCount('site_contacts');

        $promoTotal  = $this->safeTableCount('web_promoting');
        $promoActive = $this->safeWebPromotingActiveCount();
        $promoValue  = '—';
        if ($promoTotal !== null) {
            $promoValue = $promoActive !== null
                ? sprintf('%d (%d active)', $promoTotal, $promoActive)
                : (string) $promoTotal;
        }

        $checkoutsTotal = $this->safeTableCount('checkouts');
        $checkoutsPen   = $this->safeCheckoutPendingCount();
        $checkoutValue  = '—';
        if ($checkoutsTotal !== null) {
            $checkoutValue = $checkoutsPen !== null
                ? sprintf('%d records (%d pending)', $checkoutsTotal, $checkoutsPen)
                : (string) $checkoutsTotal;
        }

        $seoSaved = $this->safeHasRows('seo_settings');
        $seoValue = match ($seoSaved) {
            true    => 'Configured',
            false   => 'Not saved yet',
            default => '—',
        };

        $webSaved = $this->safeHasRows('web_settings');
        $webValue = match ($webSaved) {
            true    => 'Configured',
            false   => 'Not saved yet',
            default => '—',
        };

        $logsDir = defined('WRITEPATH') ? WRITEPATH . 'logs' : '';
        $logsOk  = ($logsDir !== '' && is_dir($logsDir) && is_writable($logsDir));

        $fmt = static fn (?int $n): string => $n === null ? '—' : (string) $n;

        $usage = [
            [
                'label' => 'Catalog products',
                'value' => $fmt($countProducts),
                'href'  => site_url('Store/Index'),
            ],
            [
                'label' => 'Member accounts',
                'value' => $fmt($countUsers),
                'href'  => site_url('DashBoard/Member/User/Profiles'),
            ],
            [
                'label' => 'Roles',
                'value' => $fmt($countRoles),
                'href'  => site_url('DashBoard/Member/Admin/Roles'),
            ],
            [
                'label' => 'Site contacts',
                'value' => $fmt($countContacts),
                'href'  => site_url('DashBoard/Site_Contacts'),
            ],
            [
                'label' => 'Web promotions',
                'value' => $promoValue,
                'href'  => site_url('DashBoard/Web_Promoting'),
            ],
            [
                'label' => 'Checkouts',
                'value' => $checkoutValue,
                'href'  => site_url('Store/CheckOut/Index'),
            ],
            [
                'label' => 'SEO settings',
                'value' => $seoValue,
                'href'  => site_url('DashBoard/SEO_Settings'),
            ],
            [
                'label' => 'Web settings',
                'value' => $webValue,
                'href'  => site_url('DashBoard/Web_Settings'),
            ],
        ];

        $dbLabel = $this->databaseConnected() ? 'Connected' : 'Unavailable';

        $other = [
            ['label' => 'PHP', 'value' => PHP_VERSION],
            ['label' => 'CodeIgniter', 'value' => CodeIgniter::CI_VERSION],
            ['label' => 'Environment', 'value' => ENVIRONMENT],
            ['label' => 'Time zone', 'value' => date_default_timezone_get()],
            ['label' => 'Database', 'value' => $dbLabel],
            ['label' => 'Logs folder writable', 'value' => $logsOk ? 'Yes' : 'No'],
        ];

        return ['usage' => $usage, 'other' => $other];
    }

    private function ensureWebPromotingTable(): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->query(
                'CREATE TABLE IF NOT EXISTS web_promoting (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL DEFAULT "",
                    description TEXT NULL,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );
            $this->upgradeWebPromotingSchema();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function upgradeWebPromotingSchema(): void
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('web_promoting')) {
                return;
            }

            if (! $db->fieldExists('sort_order', 'web_promoting')) {
                $db->query('ALTER TABLE web_promoting ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER description');
            }

            if (! $db->fieldExists('is_active', 'web_promoting')) {
                $db->query('ALTER TABLE web_promoting ADD COLUMN is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER sort_order');
            }
        } catch (\Throwable $e) {
            // Best-effort upgrades only.
        }
    }

    private function ensureSiteContactTable(): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->query(
                'CREATE TABLE IF NOT EXISTS site_contacts (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    message TEXT NULL,
                    remote_image VARCHAR(2048) NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );
            $this->upgradeSiteContactSchema();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function upgradeSiteContactSchema(): void
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('site_contacts')) {
                return;
            }

            if (! $db->fieldExists('remote_image', 'site_contacts')) {
                $db->query('ALTER TABLE site_contacts ADD COLUMN remote_image VARCHAR(2048) NULL AFTER message');
            }
        } catch (\Throwable $e) {
            // Best-effort upgrades only.
        }
    }

    public function index()
    {
        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/dashboard_index', [
            'pageTitle' => 'Dashboard',
            'notice'        => session()->getFlashdata('message'),
            'pageTitle'     => 'Dashboard',
            'pageMessage'   => 'Usage across the storefront and content tools, plus runtime details.',
            'overview'      => $this->dashboardOverviewStats(),
        ]);
    }

    public function Site_Contacts()
    {
        if (! $this->ensureSiteContactTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $rows = $db->table('site_contacts')->orderBy('id', 'DESC')->get()->getResultArray();
        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/site_contacts_index', [
            'pageTitle' => 'Site contacts',
            'rows'          => $rows,
            'message'       => session()->getFlashdata('message'),
        ]);
    }

    public function Site_Contact_Create()
    {
        if (! $this->ensureSiteContactTable()) {
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $layout = $this->getSiteLayoutData();
        $listUrl = site_url('DashBoard/Site_Contacts');

        $renderForm = function (array $extra = []) use ($layout, $listUrl): string {
            return $this->renderDashboard('dashboard/site_contact_form', array_merge([
                'pageTitle' => 'Create site contact',
                'mode'          => 'create',
                'row'           => null,
                'action'        => site_url('DashBoard/Site_Contact/Create'),
                'listUrl'       => $listUrl,
            ], $extra));
        };

        if ($this->request->is('post')) {
            $name    = trim((string) $this->request->getPost('name'));
            $email   = trim((string) $this->request->getPost('email'));
            $message = trim((string) $this->request->getPost('message'));
            [$imgOk, $remoteImage, $imgErr] = $this->sanitizeRemoteProfileImageUrl((string) $this->request->getPost('remote_image'));

            $errors = [];
            if ($name === '') {
                $errors['name'] = 'Name is required.';
            }
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email is required.';
            }
            if (! $imgOk) {
                $errors['remote_image'] = $imgErr ?? 'Invalid remote image URL.';
            }

            $rowState = [
                'name'          => $name,
                'email'         => $email,
                'message'       => $message,
                'remote_image'  => $imgOk ? ($remoteImage ?? '') : trim((string) $this->request->getPost('remote_image')),
            ];

            if ($errors !== []) {
                return $renderForm([
                    'errors' => $errors,
                    'row'    => $rowState,
                ]);
            }

            $db = \Config\Database::connect();
            $db->table('site_contacts')->insert([
                'name'         => $name,
                'email'        => $email,
                'message'      => $message !== '' ? $message : null,
                'remote_image' => $remoteImage,
            ]);

            return redirect()->to($listUrl)->with('message', 'Contact created successfully.');
        }

        return $renderForm([
            'errors' => [],
            'row'    => null,
        ]);
    }

    public function Site_Contact_Edit(int $id)
    {
        if (! $this->ensureSiteContactTable()) {
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $row = $db->table('site_contacts')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Contact not found.');
        }

        $layout  = $this->getSiteLayoutData();
        $listUrl = site_url('DashBoard/Site_Contacts');

        $renderForm = function (array $extra = []) use ($layout, $listUrl, $row, $id): string {
            return $this->renderDashboard('dashboard/site_contact_form', array_merge([
                'pageTitle' => 'Edit site contact',
                'mode'          => 'edit',
                'row'           => $row,
                'action'        => site_url('DashBoard/Site_Contact/Edit/' . $id),
                'listUrl'       => $listUrl,
            ], $extra));
        };

        if ($this->request->is('post')) {
            $name    = trim((string) $this->request->getPost('name'));
            $email   = trim((string) $this->request->getPost('email'));
            $message = trim((string) $this->request->getPost('message'));
            [$imgOk, $remoteImage, $imgErr] = $this->sanitizeRemoteProfileImageUrl((string) $this->request->getPost('remote_image'));

            $errors = [];
            if ($name === '') {
                $errors['name'] = 'Name is required.';
            }
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email is required.';
            }
            if (! $imgOk) {
                $errors['remote_image'] = $imgErr ?? 'Invalid remote image URL.';
            }

            $merged = array_merge($row, [
                'name'         => $name,
                'email'        => $email,
                'message'      => $message,
                'remote_image' => $imgOk ? ($remoteImage ?? '') : trim((string) $this->request->getPost('remote_image')),
            ]);

            if ($errors !== []) {
                return $renderForm([
                    'errors' => $errors,
                    'row'    => $merged,
                ]);
            }

            $db->table('site_contacts')->where('id', $id)->update([
                'name'         => $name,
                'email'        => $email,
                'message'      => $message !== '' ? $message : null,
                'remote_image' => $remoteImage,
            ]);

            return redirect()->to($listUrl)->with('message', 'Contact updated successfully.');
        }

        return $renderForm(['errors' => []]);
    }

    public function Site_Contact_Delete(int $id)
    {
        if (! $this->ensureSiteContactTable()) {
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $db->table('site_contacts')->where('id', $id)->delete();
        return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Contact deleted successfully.');
    }

    public function Web_Promoting()
    {
        if (! $this->ensureWebPromotingTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $rows = $db->table('web_promoting')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/web_promoting_index', [
            'pageTitle' => 'Web promoting',
            'rows'          => $rows,
            'message'       => session()->getFlashdata('message'),
        ]);
    }

    public function Web_Promoting_Create()
    {
        if (! $this->ensureWebPromotingTable()) {
            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        if ($this->request->is('post')) {
            $db = \Config\Database::connect();

            $sortOrder = (int) $this->request->getPost('sort_order');
            $isActive  = (string) $this->request->getPost('is_active') === '1' ? 1 : 0;

            $db->table('web_promoting')->insert([
                'title'       => trim((string) $this->request->getPost('title')),
                'description' => trim((string) $this->request->getPost('description')),
                'sort_order'  => $sortOrder,
                'is_active'   => $isActive,
            ]);

            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Promotion created successfully.');
        }

        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/web_promoting_form', [
            'pageTitle' => 'Create promotion',
            'mode'          => 'create',
            'row'           => null,
            'action'        => site_url('DashBoard/Web_Promoting/Create'),
            'message'       => session()->getFlashdata('message'),
        ]);
    }

    public function Web_Promoting_Edit(int $id)
    {
        if (! $this->ensureWebPromotingTable()) {
            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $row = $db->table('web_promoting')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Promotion not found.');
        }

        if ($this->request->is('post')) {
            $sortOrder = (int) $this->request->getPost('sort_order');
            $isActive  = (string) $this->request->getPost('is_active') === '1' ? 1 : 0;

            $db->table('web_promoting')->where('id', $id)->update([
                'title'       => trim((string) $this->request->getPost('title')),
                'description' => trim((string) $this->request->getPost('description')),
                'sort_order'  => $sortOrder,
                'is_active'   => $isActive,
            ]);

            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Promotion updated successfully.');
        }

        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/web_promoting_form', [
            'pageTitle' => 'Edit promotion',
            'mode'          => 'edit',
            'row'           => $row,
            'action'        => site_url('DashBoard/Web_Promoting/Edit/' . $id),
            'message'       => session()->getFlashdata('message'),
        ]);
    }

    public function Web_Promoting_Delete(int $id)
    {
        if (! $this->ensureWebPromotingTable()) {
            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $db->table('web_promoting')->where('id', $id)->delete();

        return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Promotion deleted successfully.');
    }

    private function ensureRolesReady(): bool
    {
        try {
            RolesSchema::ensure();

            return \Config\Database::connect()->tableExists('roles');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Lowercase identifier: letter first; letters, digits, hyphen, underscore; max 32. */
    private function normalizeRoleSlug(string $raw): string
    {
        return strtolower(trim($raw));
    }

    private function roleSlugValid(string $slug): bool
    {
        return $slug !== '' && preg_match('/^[a-z][a-z0-9_-]{0,31}$/', $slug) === 1;
    }

    private function normalizeRoleDescription(string $raw): string
    {
        return trim($raw);
    }

    private function roleLongTextTooLong(string $text): bool
    {
        return mb_strlen($text) > 8192;
    }

    /** SMALLINT UNSIGNED gate: higher tier = broader access in application checks. */
    private function roleAccessLevelValid(int $level): bool
    {
        return $level >= 0 && $level <= 65535;
    }

    /**
     * Restriction field = optional capability checkboxes + free-form notes (serialized).
     *
     * @return array{capKeys: list<string>, notes: string, composed: string}
     */
    private function restrictionPayloadFromRequest(): array
    {
        $capKeys = RoleRestrictionCapabilities::sanitizeKeys((array) $this->request->getPost('restriction_capability_keys'));
        $notes   = $this->normalizeRoleDescription((string) $this->request->getPost('restriction_notes'));
        $composed = RoleRestrictionCapabilities::compose($capKeys, $notes);

        return [
            'capKeys'  => $capKeys,
            'notes'    => $notes,
            'composed' => $composed,
        ];
    }

    /**
     * List roles (`roles` table).
     */
    public function Member_Admin_Roles()
    {
        if (! $this->ensureRolesReady()) {
            return redirect()->to(site_url('DashBoard/Index'))->with('message', 'Roles are unavailable (database error).');
        }

        $rolesModel = new RolesModel();
        $rows       = $rolesModel->orderBy('access_level', 'DESC')->orderBy('id', 'ASC')->findAll();
        $layout     = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/member_admin_roles_index', [
            'pageTitle' => 'Roles',
            'rows'            => $rows,
            'message'         => session()->getFlashdata('message'),
            'protectedSlugs'  => RolesModel::getProtectedRoleSlugs(),
        ]);
    }

    /**
     * Create role (slug, display name, description, access_level, restriction, behaviour).
     */
    public function Member_Admin_Role_Create()
    {
        if (! $this->ensureRolesReady()) {
            return redirect()->to(site_url('DashBoard/Member/Admin/Roles'))->with('message', 'Roles are unavailable (database error).');
        }

        $rolesModel = new RolesModel();
        $layout     = $this->getSiteLayoutData();
        $listUrl    = site_url('DashBoard/Member/Admin/Roles');

        $renderForm = function (array $extra = []) use ($layout, $listUrl): string {
            return $this->renderDashboard('dashboard/member_admin_role_form', array_merge([
                'pageTitle' => 'Create role',
                'mode'          => 'create',
                'action'        => site_url('DashBoard/Member/Admin/Role/Create'),
                'slugLocked'    => false,
                'listUrl'       => $listUrl,
            ], $extra));
        };

        if ($this->request->is('post')) {
            $slug          = $this->normalizeRoleSlug((string) $this->request->getPost('slug'));
            $name          = trim((string) $this->request->getPost('name'));
            $description   = $this->normalizeRoleDescription((string) $this->request->getPost('description'));
            $behaviour     = $this->normalizeRoleDescription((string) $this->request->getPost('behaviour'));
            $accessLevel   = (int) $this->request->getPost('access_level');
            $restrictionRx = $this->restrictionPayloadFromRequest();
            $restriction   = $restrictionRx['composed'];

            $errors = [];
            if (! $this->roleSlugValid($slug)) {
                $errors['slug'] = 'Slug must start with a letter; use lowercase letters, digits, hyphen or underscore only (max 32 characters).';
            }
            if ($name === '' || mb_strlen($name) > 64) {
                $errors['name'] = 'Display name is required (max 64 characters).';
            }
            if (! $this->roleAccessLevelValid($accessLevel)) {
                $errors['access_level'] = 'Access level must be between 0 and 65535.';
            }
            if ($this->roleLongTextTooLong($description)) {
                $errors['description'] = 'Description must be at most 8192 characters.';
            }
            if ($this->roleLongTextTooLong($restriction)) {
                $errors['restriction'] = 'Restriction / capability outline must be at most 8192 characters.';
            }
            if ($this->roleLongTextTooLong($behaviour)) {
                $errors['behaviour'] = 'Behaviour notes must be at most 8192 characters.';
            }

            $rowState = [
                'slug'          => $slug,
                'name'          => $name,
                'description'   => $description,
                'restriction'   => $restriction,
                'behaviour'     => $behaviour,
                'access_level'  => $accessLevel,
            ];

            if ($errors !== []) {
                return $renderForm([
                    'errors'                    => $errors,
                    'row'                       => $rowState,
                    'restrictionCapabilityKeys' => $restrictionRx['capKeys'],
                    'restrictionNotes'          => $restrictionRx['notes'],
                ]);
            }

            if ($rolesModel->where('slug', $slug)->first() !== null) {
                return $renderForm([
                    'errors'                    => ['slug' => 'Another role already uses this slug.'],
                    'row'                       => $rowState,
                    'restrictionCapabilityKeys' => $restrictionRx['capKeys'],
                    'restrictionNotes'          => $restrictionRx['notes'],
                ]);
            }

            $descForDb = $description !== '' ? $description : null;
            $restForDb = $restriction !== '' ? $restriction : null;
            $behForDb  = $behaviour !== '' ? $behaviour : null;

            if ($rolesModel->insert([
                'slug'          => $slug,
                'name'          => $name,
                'description'   => $descForDb,
                'access_level'  => $accessLevel,
                'restriction'   => $restForDb,
                'behaviour'     => $behForDb,
            ], true) === false) {
                return $renderForm([
                    'errors'                    => ['database' => 'Could not create the role.'],
                    'row'                       => $rowState,
                    'restrictionCapabilityKeys' => $restrictionRx['capKeys'],
                    'restrictionNotes'          => $restrictionRx['notes'],
                ]);
            }

            MemberCapabilityGate::clearCache();

            return redirect()->to($listUrl)->with('message', 'Role created.');
        }

        return $renderForm([
            'errors' => [],
            'row'    => null,
        ]);
    }

    /**
     * Edit role. Protected catalog slugs: slug fixed; other fields editable.
     */
    public function Member_Admin_Role_Edit(int $id)
    {
        if (! $this->ensureRolesReady()) {
            return redirect()->to(site_url('DashBoard/Member/Admin/Roles'))->with('message', 'Roles are unavailable (database error).');
        }

        $rolesModel = new RolesModel();
        $row        = $rolesModel->find($id);

        if ($row === null) {
            return redirect()->to(site_url('DashBoard/Member/Admin/Roles'))->with('message', 'Role not found.');
        }

        $slugCurrent = (string) ($row['slug'] ?? '');
        $slugLocked  = RolesModel::isProtectedRoleSlug($slugCurrent);

        $layout  = $this->getSiteLayoutData();
        $listUrl = site_url('DashBoard/Member/Admin/Roles');

        $renderForm = function (array $extra = []) use ($layout, $listUrl, $row, $slugLocked, $id): string {
            return $this->renderDashboard('dashboard/member_admin_role_form', array_merge([
                'pageTitle' => 'Edit role',
                'mode'          => 'edit',
                'action'        => site_url('DashBoard/Member/Admin/Role/Edit/' . $id),
                'slugLocked'    => $slugLocked,
                'listUrl'       => $listUrl,
                'row'           => $row,
            ], $extra));
        };

        if ($this->request->is('post')) {
            $name          = trim((string) $this->request->getPost('name'));
            $description   = $this->normalizeRoleDescription((string) $this->request->getPost('description'));
            $behaviour     = $this->normalizeRoleDescription((string) $this->request->getPost('behaviour'));
            $accessLevel   = (int) $this->request->getPost('access_level');
            $restrictionRx = $this->restrictionPayloadFromRequest();
            $restriction   = $restrictionRx['composed'];

            if ($slugLocked) {
                $errors = [];
                if ($name === '' || mb_strlen($name) > 64) {
                    $errors['name'] = 'Display name is required (max 64 characters).';
                }
                if (! $this->roleAccessLevelValid($accessLevel)) {
                    $errors['access_level'] = 'Access level must be between 0 and 65535.';
                }
                if ($this->roleLongTextTooLong($description)) {
                    $errors['description'] = 'Description must be at most 8192 characters.';
                }
                if ($this->roleLongTextTooLong($restriction)) {
                    $errors['restriction'] = 'Restriction / capability outline must be at most 8192 characters.';
                }
                if ($this->roleLongTextTooLong($behaviour)) {
                    $errors['behaviour'] = 'Behaviour notes must be at most 8192 characters.';
                }
                if ($errors !== []) {
                    return $renderForm([
                        'errors'                    => $errors,
                        'row'                       => array_merge($row, [
                            'name'         => $name,
                            'description'  => $description,
                            'restriction'  => $restriction,
                            'behaviour'    => $behaviour,
                            'access_level' => $accessLevel,
                        ]),
                        'restrictionCapabilityKeys' => $restrictionRx['capKeys'],
                        'restrictionNotes'          => $restrictionRx['notes'],
                    ]);
                }

                $descForDb = $description !== '' ? $description : null;
                $restForDb = $restriction !== '' ? $restriction : null;
                $behForDb  = $behaviour !== '' ? $behaviour : null;

                if ($rolesModel->update($id, [
                    'name'          => $name,
                    'description'   => $descForDb,
                    'access_level'  => $accessLevel,
                    'restriction'   => $restForDb,
                    'behaviour'     => $behForDb,
                ]) === false) {
                    return $renderForm([
                        'errors'                    => ['database' => 'Could not save changes.'],
                        'row'                       => array_merge($row, [
                            'name'         => $name,
                            'description'  => $description,
                            'restriction'  => $restriction,
                            'behaviour'    => $behaviour,
                            'access_level' => $accessLevel,
                        ]),
                        'restrictionCapabilityKeys' => $restrictionRx['capKeys'],
                        'restrictionNotes'          => $restrictionRx['notes'],
                    ]);
                }

                MemberCapabilityGate::clearCache();

                return redirect()->to($listUrl)->with('message', 'Role updated.');
            }

            $slug = $this->normalizeRoleSlug((string) $this->request->getPost('slug'));

            $errors = [];
            if (! $this->roleSlugValid($slug)) {
                $errors['slug'] = 'Slug must start with a letter; use lowercase letters, digits, hyphen or underscore only (max 32 characters).';
            }
            if ($name === '' || mb_strlen($name) > 64) {
                $errors['name'] = 'Display name is required (max 64 characters).';
            }
            if (! $this->roleAccessLevelValid($accessLevel)) {
                $errors['access_level'] = 'Access level must be between 0 and 65535.';
            }
            if ($this->roleLongTextTooLong($description)) {
                $errors['description'] = 'Description must be at most 8192 characters.';
            }
            if ($this->roleLongTextTooLong($restriction)) {
                $errors['restriction'] = 'Restriction / capability outline must be at most 8192 characters.';
            }
            if ($this->roleLongTextTooLong($behaviour)) {
                $errors['behaviour'] = 'Behaviour notes must be at most 8192 characters.';
            }

            $merged = array_merge($row, [
                'slug'         => $slug,
                'name'         => $name,
                'description'  => $description,
                'restriction'  => $restriction,
                'behaviour'    => $behaviour,
                'access_level' => $accessLevel,
            ]);

            if ($errors !== []) {
                return $renderForm([
                    'errors'                    => $errors,
                    'row'                       => $merged,
                    'restrictionCapabilityKeys' => $restrictionRx['capKeys'],
                    'restrictionNotes'          => $restrictionRx['notes'],
                ]);
            }

            $dup = $rolesModel->where('slug', $slug)->where('id !=', $id)->first();
            if ($dup !== null) {
                return $renderForm([
                    'errors'                    => ['slug' => 'Another role already uses this slug.'],
                    'row'                       => $merged,
                    'restrictionCapabilityKeys' => $restrictionRx['capKeys'],
                    'restrictionNotes'          => $restrictionRx['notes'],
                ]);
            }

            $descForDb = $description !== '' ? $description : null;
            $restForDb = $restriction !== '' ? $restriction : null;
            $behForDb  = $behaviour !== '' ? $behaviour : null;

            if ($rolesModel->update($id, [
                'slug'          => $slug,
                'name'          => $name,
                'description'   => $descForDb,
                'access_level'  => $accessLevel,
                'restriction'   => $restForDb,
                'behaviour'     => $behForDb,
            ]) === false) {
                return $renderForm([
                    'errors'                    => ['database' => 'Could not save changes.'],
                    'row'                       => $merged,
                    'restrictionCapabilityKeys' => $restrictionRx['capKeys'],
                    'restrictionNotes'          => $restrictionRx['notes'],
                ]);
            }

            MemberCapabilityGate::clearCache();

            return redirect()->to($listUrl)->with('message', 'Role updated.');
        }

        return $renderForm(['errors' => []]);
    }

    /**
     * Remove custom role only (protected catalog slugs cannot be deleted; no members assigned).
     */
    public function Member_Admin_Role_Delete(int $id)
    {
        if (! $this->ensureRolesReady()) {
            return redirect()->to(site_url('DashBoard/Member/Admin/Roles'))->with('message', 'Roles are unavailable (database error).');
        }

        $rolesModel = new RolesModel();
        $row        = $rolesModel->find($id);

        if ($row === null) {
            return redirect()->to(site_url('DashBoard/Member/Admin/Roles'))->with('message', 'Role not found.');
        }

        $slug = (string) ($row['slug'] ?? '');
        if (RolesModel::isProtectedRoleSlug($slug)) {
            return redirect()->to(site_url('DashBoard/Member/Admin/Roles'))->with('message', 'Default catalog roles cannot be deleted.');
        }

        $assigned = (int) (new UserModel())->where('role_id', $id)->countAllResults();
        if ($assigned > 0) {
            return redirect()->to(site_url('DashBoard/Member/Admin/Roles'))->with(
                'message',
                'Cannot delete this role while ' . $assigned . ' member account(s) still use it.'
            );
        }

        $rolesModel->delete($id);

        MemberCapabilityGate::clearCache();

        return redirect()->to(site_url('DashBoard/Member/Admin/Roles'))->with('message', 'Role deleted.');
    }

    /** @return array<int, array<string, mixed>> */
    private function rolesForMemberProfileForms(): array
    {
        RolesSchema::ensure();

        return (new RolesModel())->orderBy('access_level', 'DESC')->orderBy('id', 'ASC')->findAll();
    }

    private function memberProfilesListUrl(): string
    {
        return site_url('DashBoard/Member/User/Profiles');
    }

    public function Member_User_Profiles()
    {
        if (! $this->ensureUsersTableExists()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database unavailable.');
        }

        if (! $this->ensureRolesReady()) {
            return redirect()->to(site_url('DashBoard/Index'))->with('message', 'Roles are unavailable (database error).');
        }

        $userModel   = new UserModel();
        $users       = $userModel->orderBy('id', 'DESC')->findAll();
        $rolesModel  = new RolesModel();

        foreach ($users as &$u) {
            $rid               = (int) ($u['role_id'] ?? 1);
            $u['role_slug']    = $rolesModel->slugForRoleId($rid);
            $u['role_display'] = $rolesModel->nameForRoleId($rid);
        }
        unset($u);

        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/member_user_profiles_index', [
            'pageTitle' => 'Member profiles',
            'rows'            => $users,
            'message'         => session()->getFlashdata('message'),
        ]);
    }

    public function Member_User_Profile_Create()
    {
        if (! $this->ensureUsersTableExists()) {
            return redirect()->to($this->memberProfilesListUrl())->with('message', 'Database unavailable.');
        }

        if (! $this->ensureRolesReady()) {
            return redirect()->to($this->memberProfilesListUrl())->with('message', 'Roles are unavailable (database error).');
        }

        $layout   = $this->getSiteLayoutData();
        $listUrl  = $this->memberProfilesListUrl();
        $roles    = $this->rolesForMemberProfileForms();
        $userModel = new UserModel();

        $renderForm = function (array $extra = []) use ($layout, $listUrl, $roles): string {
            return $this->renderDashboard('dashboard/member_user_profile_form', array_merge([
                'pageTitle' => 'Create member profile',
                'mode'           => 'create',
                'action'         => site_url('DashBoard/Member/User/Profile/Create'),
                'listUrl'        => $listUrl,
                'roles'          => $roles,
                'row'            => null,
            ], $extra));
        };

        if ($this->request->is('post')) {
            $rules = [
                'email'            => 'required|valid_email|max_length[255]',
                'display_name'     => 'permit_empty|max_length[120]',
                'password'         => 'required|min_length[8]|max_length[255]',
                'password_confirm' => 'required|matches[password]',
                'role_id'          => 'required|integer',
            ];

            if (! $this->validate($rules)) {
                return $renderForm([
                    'errors' => $this->validator->getErrors(),
                    'row'    => [
                        'email'          => strtolower(trim((string) $this->request->getPost('email'))),
                        'display_name'   => trim((string) $this->request->getPost('display_name')),
                        'remote_image'   => trim((string) $this->request->getPost('remote_image')),
                        'role_id'        => (int) $this->request->getPost('role_id'),
                        'active'         => $this->request->getPost('active') === '1' ? 1 : 0,
                    ],
                ]);
            }

            $email       = strtolower(trim((string) $this->request->getPost('email')));
            $displayName = trim((string) $this->request->getPost('display_name'));
            $password    = (string) $this->request->getPost('password');
            $roleId      = (int) $this->request->getPost('role_id');
            $active      = $this->request->getPost('active') === '1' ? 1 : 0;

            [$imgOk, $remoteImage, $imgErr] = $this->sanitizeRemoteProfileImageUrl((string) $this->request->getPost('remote_image'));
            if (! $imgOk) {
                return $renderForm([
                    'errors' => ['remote_image' => $imgErr ?? 'Invalid profile image URL.'],
                    'row'    => [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => trim((string) $this->request->getPost('remote_image')),
                        'role_id'      => $roleId,
                        'active'       => $active,
                    ],
                ]);
            }

            if ((new RolesModel())->find($roleId) === null) {
                return $renderForm([
                    'errors' => ['role_id' => 'Selected role does not exist.'],
                    'row'    => [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => $remoteImage ?? '',
                        'role_id'      => $roleId,
                        'active'       => $active,
                    ],
                ]);
            }

            if ($userModel->where('email', $email)->first() !== null) {
                return $renderForm([
                    'errors' => ['email' => 'An account with this email already exists.'],
                    'row'    => [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => $remoteImage ?? '',
                        'role_id'      => $roleId,
                        'active'       => $active,
                    ],
                ]);
            }

            $inserted = $userModel->insert([
                'email'          => $email,
                'password_hash'  => password_hash($password, PASSWORD_DEFAULT),
                'display_name'   => $displayName,
                'remote_image'   => $remoteImage,
                'role_id'        => $roleId,
                'active'         => $active,
            ], true);

            if ($inserted === false) {
                return $renderForm([
                    'errors' => ['database' => 'Could not create the member account.'],
                    'row'    => [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => $remoteImage ?? '',
                        'role_id'      => $roleId,
                        'active'       => $active,
                    ],
                ]);
            }

            return redirect()->to($listUrl)->with('message', 'Member profile created.');
        }

        return $renderForm(['errors' => [], 'row' => null]);
    }

    public function Member_User_Profile_Edit(int $id)
    {
        if (! $this->ensureUsersTableExists()) {
            return redirect()->to($this->memberProfilesListUrl())->with('message', 'Database unavailable.');
        }

        if (! $this->ensureRolesReady()) {
            return redirect()->to($this->memberProfilesListUrl())->with('message', 'Roles are unavailable (database error).');
        }

        $userModel = new UserModel();
        $row       = $userModel->find($id);

        if ($row === null) {
            return redirect()->to($this->memberProfilesListUrl())->with('message', 'Member not found.');
        }

        $layout  = $this->getSiteLayoutData();
        $listUrl = $this->memberProfilesListUrl();
        $roles   = $this->rolesForMemberProfileForms();

        $renderForm = function (array $extra = []) use ($layout, $listUrl, $roles, $row, $id): string {
            return $this->renderDashboard('dashboard/member_user_profile_form', array_merge([
                'pageTitle' => 'Edit member profile',
                'mode'           => 'edit',
                'action'         => site_url('DashBoard/Member/User/Profile/Edit/' . $id),
                'listUrl'        => $listUrl,
                'roles'          => $roles,
                'row'            => $row,
            ], $extra));
        };

        if ($this->request->is('post')) {
            $rules = [
                'email'        => 'required|valid_email|max_length[255]',
                'display_name' => 'permit_empty|max_length[120]',
                'role_id'      => 'required|integer',
            ];

            if (! $this->validate($rules)) {
                return $renderForm([
                    'errors' => $this->validator->getErrors(),
                    'row'    => array_merge($row, [
                        'email'          => trim((string) $this->request->getPost('email')),
                        'display_name'   => trim((string) $this->request->getPost('display_name')),
                        'remote_image'   => trim((string) $this->request->getPost('remote_image')),
                        'role_id'        => (int) $this->request->getPost('role_id'),
                        'active'         => $this->request->getPost('active') === '1' ? 1 : 0,
                    ]),
                ]);
            }

            $email       = strtolower(trim((string) $this->request->getPost('email')));
            $displayName = trim((string) $this->request->getPost('display_name'));
            $roleId      = (int) $this->request->getPost('role_id');
            $active      = $this->request->getPost('active') === '1' ? 1 : 0;
            $newPass     = (string) $this->request->getPost('password');
            $newConfirm  = (string) $this->request->getPost('password_confirm');

            [$imgOk, $remoteImage, $imgErr] = $this->sanitizeRemoteProfileImageUrl((string) $this->request->getPost('remote_image'));
            if (! $imgOk) {
                return $renderForm([
                    'errors' => ['remote_image' => $imgErr ?? 'Invalid profile image URL.'],
                    'row'    => array_merge($row, [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => trim((string) $this->request->getPost('remote_image')),
                        'role_id'      => $roleId,
                        'active'       => $active,
                    ]),
                ]);
            }

            if ((new RolesModel())->find($roleId) === null) {
                return $renderForm([
                    'errors' => ['role_id' => 'Selected role does not exist.'],
                    'row'    => array_merge($row, [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => $remoteImage ?? '',
                        'role_id'      => $roleId,
                        'active'       => $active,
                    ]),
                ]);
            }

            $passErrors = [];
            if ($newPass !== '' || $newConfirm !== '') {
                if (mb_strlen($newPass) < 8) {
                    $passErrors['password'] = 'New password must be at least 8 characters.';
                }
                if ($newPass !== $newConfirm) {
                    $passErrors['password_confirm'] = 'Does not match new password.';
                }
            }

            if ($passErrors !== []) {
                return $renderForm([
                    'errors' => $passErrors,
                    'row'    => array_merge($row, [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => $remoteImage ?? '',
                        'role_id'      => $roleId,
                        'active'       => $active,
                    ]),
                ]);
            }

            $duplicate = $userModel->where('email', $email)->where('id !=', $id)->first();
            if ($duplicate !== null) {
                return $renderForm([
                    'errors' => ['email' => 'Another account already uses this email.'],
                    'row'    => array_merge($row, [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => $remoteImage ?? '',
                        'role_id'      => $roleId,
                        'active'       => $active,
                    ]),
                ]);
            }

            $data = [
                'email'        => $email,
                'display_name' => $displayName,
                'remote_image' => $remoteImage,
                'role_id'      => $roleId,
                'active'       => $active,
            ];

            if ($newPass !== '') {
                $data['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
            }

            if (! $userModel->update($id, $data)) {
                return $renderForm([
                    'errors' => ['database' => 'Could not save changes.'],
                    'row'    => array_merge($row, [
                        'email'        => $email,
                        'display_name' => $displayName,
                        'remote_image' => $remoteImage ?? '',
                        'role_id'      => $roleId,
                        'active'       => $active,
                    ]),
                ]);
            }

            $sessionUser = session()->get('member_user');
            if (is_array($sessionUser) && (int) ($sessionUser['id'] ?? 0) === $id) {
                $fresh = $userModel->find($id);
                if ($fresh !== null) {
                    session()->set('member_user', $this->buildMemberSessionPayload($fresh));
                }
            }

            return redirect()->to($listUrl)->with('message', 'Member profile updated.');
        }

        return $renderForm(['errors' => []]);
    }

    public function Member_User_Profile_Delete(int $id)
    {
        if (! $this->ensureUsersTableExists()) {
            return redirect()->to($this->memberProfilesListUrl())->with('message', 'Database unavailable.');
        }

        $sessionUser = session()->get('member_user');
        $sessionId   = is_array($sessionUser) ? (int) ($sessionUser['id'] ?? 0) : 0;

        if ($sessionId !== 0 && $id === $sessionId) {
            return redirect()->to($this->memberProfilesListUrl())->with('message', 'You cannot delete the account you are signed in with.');
        }

        $userModel = new UserModel();
        $row       = $userModel->find($id);

        if ($row === null) {
            return redirect()->to($this->memberProfilesListUrl())->with('message', 'Member not found.');
        }

        $userModel->delete($id);

        return redirect()->to($this->memberProfilesListUrl())->with('message', 'Member profile deleted.');
    }
}
