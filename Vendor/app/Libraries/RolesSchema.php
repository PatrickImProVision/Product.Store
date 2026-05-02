<?php

declare(strict_types=1);

namespace App\Libraries;

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
        ]);

        $forge->addKey('id', true);
        $forge->addUniqueKey(['slug']);

        $ok = $forge->createTable('roles');

        if ($ok === false) {
            throw new \RuntimeException('Forge createTable("roles") returned false.');
        }
    }

    private static function seedRoles(BaseConnection $db): void
    {
        $db->query(
            'INSERT IGNORE INTO roles (id, slug, name) VALUES
                (1, \'user\', \'User\'),
                (2, \'administrator\', \'Administrator\')'
        );
    }
}
