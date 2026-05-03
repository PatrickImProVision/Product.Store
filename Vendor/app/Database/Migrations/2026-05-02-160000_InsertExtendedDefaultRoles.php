<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds Owner, Moderator, Author, and Guest role rows for databases seeded before the six-role catalog.
 */
class InsertExtendedDefaultRoles extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('roles')) {
            return;
        }

        $this->db->query(
            'INSERT IGNORE INTO roles (id, slug, name) VALUES
                (3, \'owner\', \'Owner\'),
                (4, \'moderator\', \'Moderator\'),
                (5, \'author\', \'Author\'),
                (6, \'guest\', \'Guest\')'
        );
    }

    public function down(): void
    {
        if (! $this->db->tableExists('roles')) {
            return;
        }

        $this->db->table('roles')->whereIn('slug', ['owner', 'moderator', 'author', 'guest'])->delete();
    }
}
