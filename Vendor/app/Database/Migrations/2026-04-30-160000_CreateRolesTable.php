<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates `roles` and seeds User / Administrator.
 * Run: php spark migrate
 */
class CreateRolesTable extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('roles')) {
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
            $this->forge->createTable('roles');
        }

        $this->db->query(
            'INSERT IGNORE INTO roles (id, slug, name) VALUES
                (1, \'user\', \'User\'),
                (2, \'administrator\', \'Administrator\')'
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('roles', true);
    }
}
