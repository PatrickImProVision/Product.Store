<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Optional structured capability outline stored inside roles.restriction (JSON prefix + prose).
 * Capabilities are grouped by role archetype (guest, user, author/moderator, admin/owner).
 */
final class RoleRestrictionCapabilities
{
    public const PREFIX = 'ROLE_CAPS_JSON:';

    /** @return array<string, array{title: string, hint: string, typical_slugs: string}> */
    private static function segmentDefinitions(): array
    {
        return [
            'guest' => [
                'title'         => 'Guest roles',
                'hint'          => 'Public visitors without signing in.',
                'typical_slugs' => 'guest',
            ],
            'member_user' => [
                'title'         => 'User (member) roles',
                'hint'          => 'Authenticated storefront accounts.',
                'typical_slugs' => 'user',
            ],
            'author_moderator' => [
                'title'         => 'Author & moderator roles',
                'hint'          => 'Elevated members who manage catalog content or moderation duties.',
                'typical_slugs' => 'author, moderator',
            ],
            'administrator_owner' => [
                'title'         => 'Administrator & owner roles',
                'hint'          => 'Operators with dashboard access and broad configuration responsibilities.',
                'typical_slugs' => 'administrator, owner',
            ],
        ];
    }

    /** @return list<string> */
    private static function segmentOrder(): array
    {
        return ['guest', 'member_user', 'author_moderator', 'administrator_owner'];
    }

    /**
     * @return array<string, array{segment: string, label: string}>
     */
    public static function catalog(): array
    {
        return [
            'cap_store_view' => [
                'segment' => 'guest',
                'label'   => 'View storefront: browse products and search (public)',
            ],
            'cap_member_register_login' => [
                'segment' => 'member_user',
                'label'   => 'Register, login, and logout',
            ],
            'cap_member_profile' => [
                'segment' => 'member_user',
                'label'   => 'View and edit profile and related account flows (password reset, deactivate, etc.)',
            ],
            'cap_store_basket_checkout' => [
                'segment' => 'member_user',
                'label'   => 'Use basket and checkout while signed in',
            ],
            'cap_store_products_cud' => [
                'segment' => 'author_moderator',
                'label'   => 'Create, edit, and delete products (catalog / content management)',
            ],
            'cap_dashboard' => [
                'segment' => 'administrator_owner',
                'label'   => 'Dashboard routes (roles, member profiles, contacts, SEO, web settings, promotions, etc.) — enforced in code by Owner / Administrator today',
            ],
        ];
    }

    /**
     * Capabilities grouped by role type for create/edit UI.
     *
     * @return list<array{segment: string, title: string, hint: string, typical_slugs: string, items: array<string, string>}>
     */
    public static function catalogByRoleType(): array
    {
        $definitions = self::segmentDefinitions();
        $bySegment   = [];

        foreach (self::catalog() as $key => $meta) {
            $seg = $meta['segment'];
            if (! isset($bySegment[$seg])) {
                $bySegment[$seg] = [];
            }
            $bySegment[$seg][$key] = $meta['label'];
        }

        $out = [];
        foreach (self::segmentOrder() as $seg) {
            if (! isset($bySegment[$seg]) || $bySegment[$seg] === []) {
                continue;
            }

            $def = $definitions[$seg] ?? [
                'title'         => $seg,
                'hint'          => '',
                'typical_slugs' => '',
            ];

            $out[] = [
                'segment'        => $seg,
                'title'          => $def['title'],
                'hint'           => $def['hint'],
                'typical_slugs'  => $def['typical_slugs'],
                'items'          => $bySegment[$seg],
            ];
        }

        return $out;
    }

    /**
     * @deprecated Use catalogByRoleType()
     *
     * @return array<string, array<string, string>>
     */
    public static function catalogGrouped(): array
    {
        $flat = [];
        foreach (self::catalogByRoleType() as $block) {
            $flat[$block['title']] = $block['items'];
        }

        return $flat;
    }

    /**
     * @param list<string>|mixed $posted
     *
     * @return list<string>
     */
    public static function sanitizeKeys($posted): array
    {
        if (! is_array($posted)) {
            return [];
        }

        $catalog = self::catalog();
        $out     = [];

        foreach ($posted as $k) {
            $k = is_string($k) ? trim($k) : '';
            if ($k !== '' && isset($catalog[$k]) && ! in_array($k, $out, true)) {
                $out[] = $k;
            }
        }

        return $out;
    }

    /**
     * @return array{keys: list<string>, prose: string}
     */
    public static function parse(?string $stored): array
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return ['keys' => [], 'prose' => ''];
        }

        $quoted = preg_quote(self::PREFIX, '/');
        if (preg_match('/\A' . $quoted . '(\{.*\})\R?\R?(.*)\z/s', $stored, $m) !== 1) {
            return ['keys' => [], 'prose' => $stored];
        }

        try {
            /** @var mixed $json */
            $json = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return ['keys' => [], 'prose' => $stored];
        }

        $keys = [];
        if (is_array($json) && isset($json['keys']) && is_array($json['keys'])) {
            $catalog = self::catalog();
            foreach ($json['keys'] as $k) {
                if (is_string($k) && isset($catalog[$k])) {
                    $keys[] = $k;
                }
            }
        }

        return ['keys' => $keys, 'prose' => trim((string) ($m[2] ?? ''))];
    }

    /**
     * @param list<string> $keys
     */
    public static function compose(array $keys, string $prose): string
    {
        $keys  = self::sanitizeKeys($keys);
        $prose = trim($prose);

        if ($keys === [] && $prose === '') {
            return '';
        }

        if ($keys === []) {
            return $prose;
        }

        $payload = json_encode(['v' => 1, 'keys' => $keys], JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return $prose;
        }

        $block = self::PREFIX . $payload;

        return $prose === '' ? $block : $block . "\n\n" . $prose;
    }

    /** @param list<string> $keys */
    public static function summarizeKeys(array $keys): string
    {
        $keys = self::sanitizeKeys($keys);
        if ($keys === []) {
            return '';
        }

        $catalog     = self::catalog();
        $definitions = self::segmentDefinitions();

        $bySegment = [];
        foreach ($keys as $k) {
            $seg = $catalog[$k]['segment'] ?? '_other';
            if (! isset($bySegment[$seg])) {
                $bySegment[$seg] = [];
            }
            $bySegment[$seg][] = $k;
        }

        $lines   = [];
        $ordered = array_merge(self::segmentOrder(), array_diff(array_keys($bySegment), self::segmentOrder()));

        foreach ($ordered as $seg) {
            if (! isset($bySegment[$seg]) || $bySegment[$seg] === []) {
                continue;
            }

            $title = $definitions[$seg]['title'] ?? ($seg === '_other' ? 'Other' : $seg);
            $lines[] = '[' . $title . ']';

            foreach ($bySegment[$seg] as $k) {
                $lines[] = '• ' . ($catalog[$k]['label'] ?? $k);
            }

            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Readable summary for table/snippets (bullets + trimmed prose).
     */
    public static function formatForDisplay(?string $stored, int $maxProse = 160): string
    {
        $parsed = self::parse($stored ?? '');
        $parts  = [];
        $sum    = self::summarizeKeys($parsed['keys']);
        if ($sum !== '') {
            $parts[] = $sum;
        }

        $prose = trim($parsed['prose']);
        if ($prose !== '') {
            if ($maxProse > 0 && mb_strlen($prose) > $maxProse) {
                $prose = mb_substr($prose, 0, $maxProse) . '…';
            }
            $parts[] = $prose;
        }

        return implode("\n\n", $parts);
    }
}
