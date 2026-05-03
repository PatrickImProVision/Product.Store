<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\CiTables;

/**
 * Adds Owner, Moderator, Author, and Guest role rows for databases seeded before the six-role catalog.
 */
class InsertExtendedDefaultRoles extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists(CiTables::USER_ROLES)) {
            return;
        }

        $this->db->query(
            'INSERT IGNORE INTO `' . $this->db->prefixTable(CiTables::USER_ROLES) . '` (id, slug, name) VALUES
                (3, \'owner\', \'Owner\'),
                (4, \'moderator\', \'Moderator\'),
                (5, \'author\', \'Author\'),
                (6, \'guest\', \'Guest\')'
        );
    }

    public function down(): void
    {
        if (! $this->db->tableExists(CiTables::USER_ROLES)) {
            return;
        }

        $this->db->table(CiTables::USER_ROLES)->whereIn('slug', ['owner', 'moderator', 'author', 'guest'])->delete();
    }
}
