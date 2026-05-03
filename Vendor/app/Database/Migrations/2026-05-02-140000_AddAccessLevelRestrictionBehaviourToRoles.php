<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccessLevelRestrictionBehaviourToRoles extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('roles')) {
            return;
        }

        if (! $this->db->fieldExists('access_level', 'roles')) {
            $this->db->query(
                'ALTER TABLE roles ADD COLUMN access_level SMALLINT UNSIGNED NOT NULL DEFAULT 10 AFTER description'
            );
            $this->db->query(
                "UPDATE roles SET access_level = 100 WHERE slug = 'administrator'"
            );
        }

        if (! $this->db->fieldExists('restriction', 'roles')) {
            $this->db->query(
                'ALTER TABLE roles ADD COLUMN restriction TEXT NULL AFTER access_level'
            );
        }

        if (! $this->db->fieldExists('behaviour', 'roles')) {
            $this->db->query(
                'ALTER TABLE roles ADD COLUMN behaviour TEXT NULL AFTER restriction'
            );
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('roles')) {
            return;
        }

        if ($this->db->fieldExists('behaviour', 'roles')) {
            $this->forge->dropColumn('roles', 'behaviour');
        }

        if ($this->db->fieldExists('restriction', 'roles')) {
            $this->forge->dropColumn('roles', 'restriction');
        }

        if ($this->db->fieldExists('access_level', 'roles')) {
            $this->forge->dropColumn('roles', 'access_level');
        }
    }
}
