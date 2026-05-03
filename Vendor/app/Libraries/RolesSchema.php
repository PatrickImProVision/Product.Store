<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\RolesModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Runtime bootstrap for `roles` (mirrors migration for hosts where migrate was not run).
 */
class RolesSchema
{
    public static function ensure(): void
    {
        try {
            $db = \Config\Database::connect();

            if (! $db->tableExists('roles')) {
                self::createTableWithForge($db);
            }

            self::seedRoles($db);
            self::ensureDescriptionColumn($db);
            self::ensureAccessRestrictionBehaviourColumns($db);
            self::syncDefaultRoleDefinitions($db);

            if (! $db->tableExists('roles')) {
                log_message('critical', 'RolesSchema: roles table still missing after bootstrap.');
            }
        } catch (\Throwable $e) {
            log_message('critical', 'RolesSchema::ensure failed: {msg}', ['msg' => $e->getMessage()]);
            log_message('debug', (string) $e);
        }
    }

    private static function createTableWithForge(BaseConnection $db): void
    {
        $forge = \Config\Database::forge();

        $forge->addField([
            'id' => [
                'type'           => 'TINYINT',
                'constraint'     => 3,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'access_level' => [
                'type'       => 'SMALLINT',
                'unsigned'   => true,
                'null'       => false,
                'default'    => 10,
            ],
            'restriction' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'behaviour' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);

        $forge->addKey('id', true);
        $forge->addUniqueKey(['slug']);

        $ok = $forge->createTable('roles');

        if ($ok === false) {
            throw new \RuntimeException('Forge createTable("roles") returned false.');
        }
    }

    /**
     * Core role rows (deterministic IDs). Extended metadata applied in syncDefaultRoleDefinitions().
     */
    private static function seedRoles(BaseConnection $db): void
    {
        $db->query(
            'INSERT IGNORE INTO roles (id, slug, name) VALUES
                (1, \'user\', \'User\'),
                (2, \'administrator\', \'Administrator\'),
                (3, \'owner\', \'Owner\'),
                (4, \'moderator\', \'Moderator\'),
                (5, \'author\', \'Author\'),
                (6, \'guest\', \'Guest\')'
        );
    }

    private static function ensureDescriptionColumn(BaseConnection $db): void
    {
        try {
            if (! $db->tableExists('roles')) {
                return;
            }

            if ($db->fieldExists('description', 'roles')) {
                return;
            }

            $db->query('ALTER TABLE roles ADD COLUMN description TEXT NULL AFTER name');
        } catch (\Throwable $e) {
            log_message('debug', 'RolesSchema::ensureDescriptionColumn: {msg}', ['msg' => $e->getMessage()]);
        }
    }

    private static function ensureAccessRestrictionBehaviourColumns(BaseConnection $db): void
    {
        try {
            if (! $db->tableExists('roles')) {
                return;
            }

            if (! $db->fieldExists('access_level', 'roles')) {
                $db->query(
                    'ALTER TABLE roles ADD COLUMN access_level SMALLINT UNSIGNED NOT NULL DEFAULT 10 AFTER description'
                );
            }

            if (! $db->fieldExists('restriction', 'roles')) {
                $db->query('ALTER TABLE roles ADD COLUMN restriction TEXT NULL AFTER access_level');
            }

            if (! $db->fieldExists('behaviour', 'roles')) {
                $db->query('ALTER TABLE roles ADD COLUMN behaviour TEXT NULL AFTER restriction');
            }
        } catch (\Throwable $e) {
            log_message('debug', 'RolesSchema::ensureAccessRestrictionBehaviourColumns: {msg}', ['msg' => $e->getMessage()]);
        }
    }

    /**
     * Inserts missing catalog roles and fills default copy when description is still empty (preserves admin edits).
     *
     * @return array<string, array<string, mixed>>
     */
    private static function defaultRoleBlueprint(): array
    {
        return [
            RolesModel::SLUG_GUEST => [
                'id'            => 6,
                'name'          => 'Guest',
                'access_level'  => RolesModel::ACCESS_LEVEL_GUEST_DEFAULT,
                'description'   => 'Unauthenticated public visitor with minimal access to public routes and content.',
                'restriction'   => 'Read-only access to explicitly public routes and material; no authenticated or privileged actions.',
                'behaviour'     => 'Browse public content and listings until signing in.',
            ],
            RolesModel::SLUG_USER => [
                'id'            => 1,
                'name'          => 'User',
                'access_level'  => RolesModel::ACCESS_LEVEL_USER_DEFAULT,
                'description'   => 'Standard authenticated account with permitted application usage features and self-profile management.',
                'restriction'   => 'No administrative configuration, moderation queues, or others\' private content unless explicitly granted.',
                'behaviour'     => 'Uses permitted product features and maintains personal profile data.',
            ],
            RolesModel::SLUG_AUTHOR => [
                'id'            => 5,
                'name'          => 'Author',
                'access_level'  => RolesModel::ACCESS_LEVEL_AUTHOR_DEFAULT,
                'description'   => 'Creates and edits owned content or resources and submits items for review or publication.',
                'restriction'   => 'Limited to owned or assigned content; cannot change core system configuration.',
                'behaviour'     => 'Works within workflow permissions for drafting, submitting, and revising content.',
            ],
            RolesModel::SLUG_MODERATOR => [
                'id'            => 4,
                'name'          => 'Moderator',
                'access_level'  => RolesModel::ACCESS_LEVEL_MODERATOR_DEFAULT,
                'description'   => 'Oversees community or operational moderation tasks such as reports, flags, and user-generated content actions.',
                'restriction'   => 'No access to core system configuration, installation state, security policies, or module lifecycle outside moderation scope.',
                'behaviour'     => 'Uses moderation tools under policy for reports, flags, and content actions.',
            ],
            RolesModel::SLUG_ADMINISTRATOR => [
                'id'            => 2,
                'name'          => 'Administrator',
                'access_level'  => RolesModel::ACCESS_LEVEL_ADMINISTRATOR_DEFAULT,
                'description'   => 'Manages day-to-day system operations including users, roles, content, and approved module settings.',
                'restriction'   => 'Cannot override Owner-only protections or installation-level controls reserved for Owner.',
                'behaviour'     => 'Operates users, roles, catalog content, and approved module settings within bounds set by Owner.',
            ],
            RolesModel::SLUG_OWNER => [
                'id'            => 3,
                'name'          => 'Owner',
                'access_level'  => RolesModel::ACCESS_LEVEL_OWNER_DEFAULT,
                'description'   => 'Highest-authority account tied to installation; controls installation state, security policies, module lifecycle, and other administrators.',
                'restriction'   => 'Subject only to immutable safeguards outside this application; cannot be overridden by lower tiers.',
                'behaviour'     => 'Defines Owner-only protections and delegates routine operations to Administrators.',
            ],
        ];
    }

    private static function syncDefaultRoleDefinitions(BaseConnection $db): void
    {
        try {
            if (! $db->tableExists('roles')) {
                return;
            }

            if (! $db->fieldExists('description', 'roles') || ! $db->fieldExists('access_level', 'roles')) {
                return;
            }

            foreach (self::defaultRoleBlueprint() as $slug => $def) {
                $row = $db->table('roles')->where('slug', $slug)->get()->getRowArray();

                $payload = [
                    'name'          => $def['name'],
                    'description'   => $def['description'],
                    'access_level'  => (int) $def['access_level'],
                    'restriction'   => $def['restriction'],
                    'behaviour'     => $def['behaviour'],
                ];

                if ($row === null) {
                    $db->table('roles')->insert(array_merge(
                        ['id' => (int) $def['id'], 'slug' => $slug],
                        $payload
                    ));

                    continue;
                }

                $descEmpty = trim((string) ($row['description'] ?? '')) === '';
                if ($descEmpty) {
                    $db->table('roles')->where('slug', $slug)->update($payload);
                }
            }
        } catch (\Throwable $e) {
            log_message('debug', 'RolesSchema::syncDefaultRoleDefinitions: {msg}', ['msg' => $e->getMessage()]);
        }
    }
}
