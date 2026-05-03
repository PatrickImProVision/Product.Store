<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\CiTables;

class AddAccessLevelRestrictionBehaviourToRoles extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists(CiTables::USER_ROLES)) {
            return;
        }

        if (! $this->db->fieldExists('access_level', CiTables::USER_ROLES)) {
            $this->db->query(
                'ALTER TABLE `' . $this->db->prefixTable(CiTables::USER_ROLES) . '` ADD COLUMN access_level SMALLINT UNSIGNED NOT NULL DEFAULT 10 AFTER description'
            );
            $this->db->query(
                'UPDATE `' . $this->db->prefixTable(CiTables::USER_ROLES) . "` SET access_level = 100 WHERE slug = 'administrator'"
            );
        }

        if (! $this->db->fieldExists('restriction', CiTables::USER_ROLES)) {
            $this->db->query(
                'ALTER TABLE `' . $this->db->prefixTable(CiTables::USER_ROLES) . '` ADD COLUMN restriction TEXT NULL AFTER access_level'
            );
        }

        if (! $this->db->fieldExists('behaviour', CiTables::USER_ROLES)) {
            $this->db->query(
                'ALTER TABLE `' . $this->db->prefixTable(CiTables::USER_ROLES) . '` ADD COLUMN behaviour TEXT NULL AFTER restriction'
            );
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists(CiTables::USER_ROLES)) {
            return;
        }

        if ($this->db->fieldExists('behaviour', CiTables::USER_ROLES)) {
            $this->forge->dropColumn(CiTables::USER_ROLES, 'behaviour');
        }

        if ($this->db->fieldExists('restriction', CiTables::USER_ROLES)) {
            $this->forge->dropColumn(CiTables::USER_ROLES, 'restriction');
        }

        if ($this->db->fieldExists('access_level', CiTables::USER_ROLES)) {
            $this->forge->dropColumn(CiTables::USER_ROLES, 'access_level');
        }
    }
}
