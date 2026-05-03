<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\RolesModel;

/**
 * Interprets roles.restriction ROLE_CAPS_JSON allowlists for signed-in members.
 * Owner/Administrator slugs bypass capability checks (operational access unchanged).
 */
final class MemberCapabilityGate
{
    /** @var array<int, string> role_id => restriction text */
    private static array $restrictionCache = [];

    public static function clearCache(): void
    {
        self::$restrictionCache = [];
    }

    public static function restrictionTextForRoleId(int $roleId): string
    {
        if (isset(self::$restrictionCache[$roleId])) {
            return self::$restrictionCache[$roleId];
        }

        $row = (new RolesModel())->find($roleId);
        $text = $row !== null ? trim((string) ($row['restriction'] ?? '')) : '';
        self::$restrictionCache[$roleId] = $text;

        return $text;
    }

    /** Raw restriction column for the signed-in member's role (empty string if unknown). */
    public static function restrictionForMember(?array $member): string
    {
        if (! is_array($member) || empty($member['id'])) {
            return '';
        }

        $roleId = (int) ($member['role_id'] ?? 0);
        if ($roleId < 1) {
            return '';
        }

        return self::restrictionTextForRoleId($roleId);
    }

    /** Elevated operators ignore capability allowlists. */
    public static function bypasses(?array $member): bool
    {
        if (! is_array($member) || empty($member['id'])) {
            return false;
        }

        return RolesModel::slugMayElevatedManageContent((string) ($member['role'] ?? ''));
    }

    public static function enforcementActive(?array $member): bool
    {
        $t = self::restrictionForMember($member);

        return $t !== '' && str_starts_with($t, RoleRestrictionCapabilities::PREFIX);
    }

    /**
     * @return list<string>|null null = legacy restriction text only — do not apply capability allowlist
     */
    public static function allowedKeys(?array $member): ?array
    {
        if (! self::enforcementActive($member)) {
            return null;
        }

        return RoleRestrictionCapabilities::parse(self::restrictionForMember($member))['keys'];
    }

    public static function allows(?array $member, string $capKey): bool
    {
        if (! is_array($member) || empty($member['id'])) {
            return false;
        }

        if (self::bypasses($member)) {
            return true;
        }

        $allowed = self::allowedKeys($member);
        if ($allowed === null) {
            return true;
        }

        return in_array($capKey, $allowed, true);
    }
}
