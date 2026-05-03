<?php

declare(strict_types=1);

namespace Config;

/**
 * Logical database table names (no `ci_` prefix).
 *
 * Physical names are {@see \Config\Database::$default} `DBPrefix` + logical name (default prefix `ci_`
 * yields `ci_products`, `ci_migration`, etc.). Query Builder, Forge, tableExists(), and fieldExists()
 * apply the prefix automatically when you pass these logical names.
 *
 * Raw SQL (`CREATE TABLE`, `ALTER TABLE`, `SHOW INDEX`, …) must use
 * {@see \CodeIgniter\Database\BaseConnection::prefixTable()}.
 */
final class CiTables
{
    /** Migration history (Forge creates {@see Migrations::$table} with prefix → `ci_migration`). */
    public const MIGRATION_LOG = 'migration';

    public const PRODUCTS = 'products';

    public const SEO_SETTINGS = 'seo_settings';

    public const USERS = 'users';

    /** Former `user_deactivation_tokens`. */
    public const USER_DEACTIVATION = 'user_deactivation';

    public const USER_PASSWORD_RESETS = 'user_password_resets';

    /** Former `roles`. */
    public const USER_ROLES = 'user_roles';

    public const WEB_PROMOTING = 'web_promoting';

    public const WEB_SETTINGS = 'web_settings';

    public const SITE_CONTACTS = 'site_contacts';

    public const CHECKOUTS = 'checkouts';
}
