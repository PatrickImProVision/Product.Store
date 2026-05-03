<?php

namespace App\Controllers;

use App\Libraries\RolesSchema;
use App\Models\RolesModel;
use App\Models\SeoSettingModel;
use App\Models\WebSettingModel;
use Config\CiTables;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        RolesSchema::ensure();

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /** Bootstrap utility classes for `<body>` on public storefront pages (home + catalog). */
    protected const STOREFRONT_BODY_CLASS = 'min-vh-100 bg-primary-subtle';

    /**
     * Shared SEO + site title/description for layouts matching the home page chrome.
     *
     * @return array{metaTitle: string, metaDescription: string, metaKeywords: string, webTitle: string, webDescription: string}
     */
    protected function getSiteLayoutData(): array
    {
        $seo = null;
        $web = null;

        try {
            $db = \Config\Database::connect();

            if ($db->tableExists(CiTables::SEO_SETTINGS)) {
                $seoModel = new SeoSettingModel();
                $seo      = $seoModel->first();
            }

            if ($db->tableExists(CiTables::WEB_SETTINGS)) {
                $webModel = new WebSettingModel();
                $web      = $webModel->first();
            }
        } catch (\Throwable $e) {
            $seo = null;
            $web = null;
        }

        $siteName = trim((string) ($web['title'] ?? '')) !== '' ? $web['title'] : 'Product Store';

        return [
            'metaTitle'       => $seo['meta_title'] ?? $siteName,
            'metaDescription' => $seo['meta_description'] ?? ($siteName . ' powered by CodeIgniter'),
            'metaKeywords'    => $seo['meta_keywords'] ?? '',
            'webTitle'        => $siteName,
            'webDescription'  => $web['description'] ?? 'This project now uses Bootstrap styling instead of the default CodeIgniter welcome style.',
        ];
    }

    /**
     * Ensure `ci_users` table exists (shared by Member and dashboard user management).
     */
    protected function ensureUsersTableExists(): bool
    {
        try {
            RolesSchema::ensure();

            $db        = \Config\Database::connect();
            $usersTable = $db->prefixTable(CiTables::USERS);

            $db->query(
                'CREATE TABLE IF NOT EXISTS `' . $usersTable . '` (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    display_name VARCHAR(120) NOT NULL DEFAULT \'\',
                    remote_image VARCHAR(2048) NULL,
                    role_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY users_email_unique (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );

            try {
                $db->query('ALTER TABLE `' . $usersTable . '` ADD COLUMN remote_image VARCHAR(2048) NULL');
            } catch (\Throwable $e) {
                // Column already present.
            }

            try {
                $db->query('ALTER TABLE `' . $usersTable . '` ADD COLUMN role_id TINYINT UNSIGNED NOT NULL DEFAULT 1');
            } catch (\Throwable $e) {
                // Column already present.
            }

            try {
                $db->query(
                    'ALTER TABLE `' . $usersTable . '` ADD COLUMN active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1'
                );
            } catch (\Throwable $e) {
                // Column already present.
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array{0: bool, 1: string|null, 2: string|null} ok, sanitized URL or null, error message
     */
    protected function sanitizeRemoteProfileImageUrl(string $raw): array
    {
        $s = trim($raw);
        if ($s === '') {
            return [true, null, null];
        }

        if (mb_strlen($s) > 2048) {
            return [false, null, 'Profile image URL is too long (max 2048 characters).'];
        }

        if (! preg_match('#\Ahttps?://#i', $s)) {
            return [false, null, 'Profile image URL must start with http:// or https://.'];
        }

        return [true, $s, null];
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function buildMemberSessionPayload(array $row): array
    {
        $roleId = (int) ($row['role_id'] ?? 1);
        $roles  = new RolesModel();

        return [
            'id'           => (int) ($row['id'] ?? 0),
            'email'        => (string) ($row['email'] ?? ''),
            'display_name' => (string) ($row['display_name'] ?? ''),
            'remote_image' => trim((string) ($row['remote_image'] ?? '')),
            'role_id'      => $roleId,
            'role'         => $roles->slugForRoleId($roleId),
            'role_name'    => $roles->nameForRoleId($roleId),
        ];
    }
}
