<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\CiTables;

/**
 * Creates `ci_user_roles` and seeds the six-role catalog.
 * Run: php spark migrate
 */
class CreateRolesTable extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists(CiTables::USER_ROLES)) {
            $this->forge->addField([
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

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['slug']);
            $this->forge->createTable(CiTables::USER_ROLES);
        }

        $this->db->query(
            'INSERT IGNORE INTO `' . $this->db->prefixTable(CiTables::USER_ROLES) . '` (id, slug, name) VALUES
                (1, \'user\', \'User\'),
                (2, \'administrator\', \'Administrator\'),
                (3, \'owner\', \'Owner\'),
                (4, \'moderator\', \'Moderator\'),
                (5, \'author\', \'Author\'),
                (6, \'guest\', \'Guest\')'
        );
    }

    public function down(): void
    {
        $this->forge->dropTable(CiTables::USER_ROLES, true);
    }
}
