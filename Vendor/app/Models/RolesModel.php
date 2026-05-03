<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\CiTables;

class RolesModel extends Model
{
    public const SLUG_GUEST          = 'guest';
    public const SLUG_USER           = 'user';
    public const SLUG_AUTHOR         = 'author';
    public const SLUG_MODERATOR      = 'moderator';
    public const SLUG_ADMINISTRATOR = 'administrator';
    public const SLUG_OWNER          = 'owner';

    public const ACCESS_LEVEL_GUEST_DEFAULT = 0;

    /** Default access tier for standard members (see `access_level` column). */
    public const ACCESS_LEVEL_USER_DEFAULT = 10;

    public const ACCESS_LEVEL_AUTHOR_DEFAULT = 25;

    public const ACCESS_LEVEL_MODERATOR_DEFAULT = 45;

    /** Default access tier for administrators. */
    public const ACCESS_LEVEL_ADMINISTRATOR_DEFAULT = 100;

    /** Default access tier for installation Owner (above Administrator). */
    public const ACCESS_LEVEL_OWNER_DEFAULT = 1000;

    protected $table         = CiTables::USER_ROLES;
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['slug', 'name', 'description', 'access_level', 'restriction', 'behaviour'];

    /** @return list<string> */
    public static function getProtectedRoleSlugs(): array
    {
        return [
            self::SLUG_GUEST,
            self::SLUG_USER,
            self::SLUG_AUTHOR,
            self::SLUG_MODERATOR,
            self::SLUG_ADMINISTRATOR,
            self::SLUG_OWNER,
        ];
    }

    public static function isProtectedRoleSlug(string $slug): bool
    {
        return in_array($slug, self::getProtectedRoleSlugs(), true);
    }

    /** Dashboard / elevated operator login (Owner + Administrator). */
    public static function slugMayUseDashboard(string $slug): bool
    {
        return $slug === self::SLUG_OWNER || $slug === self::SLUG_ADMINISTRATOR;
    }

    /** Full catalog moderation or ownership over shared resources (same tier as dashboard operators today). */
    public static function slugMayElevatedManageContent(string $slug): bool
    {
        return self::slugMayUseDashboard($slug);
    }

    public function slugForRoleId(int $roleId): string
    {
        $row = $this->find($roleId);

        return $row !== null ? (string) $row['slug'] : self::SLUG_USER;
    }

    public function nameForRoleId(int $roleId): string
    {
        $row = $this->find($roleId);

        return $row !== null ? (string) $row['name'] : 'User';
    }

    public function idForSlug(string $slug): ?int
    {
        $row = $this->where('slug', $slug)->first();

        return $row !== null ? (int) $row['id'] : null;
    }

    /**
     * Numeric access tier (higher typically means broader capability). Used to gate features in application code.
     */
    public function accessLevelForRoleId(int $roleId): int
    {
        $row = $this->find($roleId);

        if ($row === null) {
            return 0;
        }

        if (! array_key_exists('access_level', $row)) {
            return self::ACCESS_LEVEL_USER_DEFAULT;
        }

        return max(0, (int) $row['access_level']);
    }
}
